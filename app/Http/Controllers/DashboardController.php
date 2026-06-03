<?php

namespace App\Http\Controllers;

use App\Jobs\SendAttendanceSmsJob;
use App\Models\AttendanceLog;
use App\Models\Turnstile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Render the analytics dashboard with real-time data.
     */
    public function show(): Response
    {
        return Inertia::render('dashboard', [
            'stats' => $this->buildStats(),
            'hourlyBreakdown' => Inertia::defer(fn (): array => $this->buildHourlyBreakdown()),
            'recentActivity' => Inertia::defer(fn (): array => $this->buildRecentActivity()),
            'turnstiles' => Inertia::defer(fn (): array => $this->buildTurnstileStatuses()),
            'smsEntries' => Inertia::defer(fn (): array => $this->buildSmsEntries()),
        ]);
    }

    /**
     * Retry sending SMS for a specific attendance log.
     */
    public function retrySms(AttendanceLog $attendanceLog): RedirectResponse
    {
        $attendanceLog->update(['sms_status' => 'PENDING']);

        SendAttendanceSmsJob::dispatch($attendanceLog->id);

        return back();
    }

    /**
     * Build aggregate stats for KPI cards — fast single-query operations.
     *
     * @return array{currentlyTimedIn: int, totalScansToday: int, pendingSmsCount: int, failedSmsCount: int, sentSmsCount: int, activeTurnstiles: int, totalTurnstiles: int}
     */
    private function buildStats(): array
    {
        $today = Carbon::today();

        $smsCounts = AttendanceLog::query()
            ->whereDate('scanned_at', $today)
            ->select('sms_status', DB::raw('count(*) as total'))
            ->groupBy('sms_status')
            ->pluck('total', 'sms_status');

        $currentlyTimedIn = DB::query()
            ->fromSub(
                AttendanceLog::query()
                    ->whereDate('scanned_at', $today)
                    ->select('user_id', DB::raw('MAX(id) as latest_id'))
                    ->groupBy('user_id'),
                'latest_logs',
            )
            ->join('attendance_logs', 'attendance_logs.id', '=', 'latest_logs.latest_id')
            ->where('attendance_logs.action', 'IN')
            ->count();

        $turnstileCounts = Turnstile::query()
            ->select(
                DB::raw('count(*) as total'),
                DB::raw('sum(case when status = true then 1 else 0 end) as active'),
            )
            ->first();

        return [
            'currentlyTimedIn' => $currentlyTimedIn,
            'totalScansToday' => (int) $smsCounts->sum(),
            'pendingSmsCount' => (int) ($smsCounts['PENDING'] ?? 0),
            'failedSmsCount' => (int) ($smsCounts['FAILED'] ?? 0),
            'sentSmsCount' => (int) ($smsCounts['SENT'] ?? 0),
            'activeTurnstiles' => (int) ($turnstileCounts?->active ?? 0),
            'totalTurnstiles' => (int) ($turnstileCounts?->total ?? 0),
        ];
    }

    /**
     * Build hourly IN/OUT breakdown for today's attendance chart.
     *
     * @return array<int, array{hour: string, timeIn: int, timeOut: int}>
     */
    private function buildHourlyBreakdown(): array
    {
        $today = Carbon::today();

        $hourlyData = AttendanceLog::query()
            ->whereDate('scanned_at', $today)
            ->select(
                DB::raw('HOUR(scanned_at) as hour_num'),
                DB::raw("sum(case when action = 'IN' then 1 else 0 end) as time_in"),
                DB::raw("sum(case when action = 'OUT' then 1 else 0 end) as time_out"),
            )
            ->groupBy('hour_num')
            ->orderBy('hour_num')
            ->get()
            ->keyBy('hour_num');

        $breakdown = [];

        for ($hour = 5; $hour <= 20; $hour++) {
            $row = $hourlyData->get($hour);
            $breakdown[] = [
                'hour' => Carbon::createFromTime($hour)->format('g A'),
                'timeIn' => (int) ($row?->time_in ?? 0),
                'timeOut' => (int) ($row?->time_out ?? 0),
            ];
        }

        return $breakdown;
    }

    /**
     * Build the recent activity feed — last 20 scans.
     *
     * @return array<int, array{id: int, userName: string, action: string, scannedAt: string, turnstileName: string, profileImage: string|null}>
     */
    private function buildRecentActivity(): array
    {
        return AttendanceLog::query()
            ->with(['user', 'turnstile'])
            ->latest('scanned_at')
            ->take(20)
            ->get()
            ->map(function (AttendanceLog $log): array {
                $user = $log->user;

                return [
                    'id' => $log->id,
                    'userName' => $user?->name ?? 'Unknown',
                    'action' => $log->action,
                    'scannedAt' => $log->scanned_at->toIso8601String(),
                    'turnstileName' => $log->turnstile?->name ?? 'Unknown',
                    'profileImage' => $this->resolveProfileImage($user?->profile_image),
                ];
            })
            ->all();
    }

    /**
     * Build turnstile statuses using last scan as connectivity proxy.
     *
     * Considers a turnstile "online" if it recorded a scan within the last 5 minutes.
     *
     * @return array<int, array{id: int, name: string, location: string, ipAddress: string, isActive: bool, isOnline: bool, lastSeenAt: string|null}>
     */
    private function buildTurnstileStatuses(): array
    {
        return Turnstile::query()
            ->withMax('attendanceLogs', 'scanned_at')
            ->orderBy('name')
            ->get()
            ->map(function (Turnstile $turnstile): array {
                $lastScannedAt = $turnstile->attendance_logs_max_scanned_at
                    ? Carbon::parse($turnstile->attendance_logs_max_scanned_at)
                    : null;

                return [
                    'id' => $turnstile->id,
                    'name' => $turnstile->name,
                    'location' => $turnstile->location,
                    'ipAddress' => $turnstile->ip_address,
                    'isActive' => (bool) $turnstile->status,
                    'isOnline' => $lastScannedAt?->greaterThanOrEqualTo(now()->subMinutes(5)) ?? false,
                    'lastSeenAt' => $lastScannedAt?->toIso8601String(),
                ];
            })
            ->all();
    }

    /**
     * Build the SMS entries list showing PENDING, SENT, and FAILED statuses.
     *
     * @return array<int, array{id: int, userName: string, guardianContact: string, action: string, scannedAt: string, smsStatus: string}>
     */
    private function buildSmsEntries(): array
    {
        return AttendanceLog::query()
            ->with(['user.studentDetail'])
            ->whereDate('scanned_at', Carbon::today())
            ->latest('scanned_at')
            ->take(50)
            ->get()
            ->map(function (AttendanceLog $log): array {
                $user = $log->user;

                return [
                    'id' => $log->id,
                    'userName' => $user?->name ?? 'Unknown',
                    'guardianContact' => $user?->studentDetail?->guardian_contact_number ?? '—',
                    'action' => $log->action,
                    'scannedAt' => $log->scanned_at->toIso8601String(),
                    'smsStatus' => $log->sms_status,
                ];
            })
            ->all();
    }

    /**
     * Resolve profile image URL from storage path or external URL.
     */
    private function resolveProfileImage(?string $profileImage): ?string
    {
        if (! filled($profileImage)) {
            return null;
        }

        if (
            str_starts_with($profileImage, 'http://')
            || str_starts_with($profileImage, 'https://')
            || str_starts_with($profileImage, 'data:image/')
            || str_starts_with($profileImage, '/storage/')
        ) {
            return $profileImage;
        }

        /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
        $disk = Storage::disk('public');

        return $disk->url($profileImage);
    }
}
