<?php

namespace App\Http\Controllers;

use App\Models\AttendanceLog;
use App\Models\Turnstile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    private const RECENT_ACTIVITY_LIMIT = 8;

    public function show(): Response
    {
        return Inertia::render('dashboard', [
            'summary' => $this->buildSummary(),
            'generatedAt' => now()->timezone(config('app.timezone'))->format('g:i A'),
            'todayActivity' => Inertia::defer(
                fn (): array => $this->buildTodayActivity(),
                'dashboard-panels',
            ),
            'recentActivity' => Inertia::defer(
                fn (): array => $this->buildRecentActivity(),
                'dashboard-panels',
            ),
            'turnstileStatus' => Inertia::defer(
                fn (): array => $this->buildTurnstileStatus(),
                'dashboard-panels',
            ),
        ]);
    }

    /**
     * @return array{
     *     totalIn: int,
     *     totalOut: int,
     *     uniqueStudents: int,
     *     smsAttention: int,
     *     activeTurnstiles: int,
     *     inactiveTurnstiles: int,
     *     lastScanLabel: string
     * }
     */
    private function buildSummary(): array
    {
        [$todayStart, $todayEnd] = $this->todayRange();

        /** @var object{total_in:int|string|null,total_out:int|string|null,unique_students:int|string|null,sms_attention:int|string|null} $totals */
        $totals = AttendanceLog::query()
            ->whereBetween('scanned_at', [$todayStart, $todayEnd])
            ->selectRaw("SUM(CASE WHEN action = 'IN' THEN 1 ELSE 0 END) as total_in")
            ->selectRaw("SUM(CASE WHEN action = 'OUT' THEN 1 ELSE 0 END) as total_out")
            ->selectRaw('COUNT(DISTINCT user_id) as unique_students')
            ->selectRaw("SUM(CASE WHEN sms_status IN ('PENDING', 'FAILED') THEN 1 ELSE 0 END) as sms_attention")
            ->first();

        $latestScan = AttendanceLog::query()
            ->latest('scanned_at')
            ->first(['scanned_at']);

        return [
            'totalIn' => (int) ($totals->total_in ?? 0),
            'totalOut' => (int) ($totals->total_out ?? 0),
            'uniqueStudents' => (int) ($totals->unique_students ?? 0),
            'smsAttention' => (int) ($totals->sms_attention ?? 0),
            'activeTurnstiles' => Turnstile::query()->where('status', true)->count(),
            'inactiveTurnstiles' => Turnstile::query()->where('status', false)->count(),
            'lastScanLabel' => $latestScan?->scanned_at instanceof Carbon
                ? $latestScan->scanned_at->timezone(config('app.timezone'))->format('g:i A')
                : 'No scans yet',
        ];
    }

    /**
     * @return array{
     *     totalScans: int,
     *     peakHourLabel: string|null,
     *     latestScanLabel: string|null,
     *     hasData: bool,
     *     points: array<int, array{hour:int,label:string,shortLabel:string,scanCount:int}>
     * }
     */
    private function buildTodayActivity(): array
    {
        [$todayStart, $todayEnd] = $this->todayRange();
        $driver = DB::connection()->getDriverName();

        $hourExpression = $driver === 'sqlite'
            ? "CAST(strftime('%H', scanned_at) AS INTEGER)"
            : 'HOUR(scanned_at)';

        /** @var Collection<int, object{hour_bucket:int|string, scan_count:int|string}> $rows */
        $rows = AttendanceLog::query()
            ->whereBetween('scanned_at', [$todayStart, $todayEnd])
            ->selectRaw("{$hourExpression} as hour_bucket")
            ->selectRaw('COUNT(*) as scan_count')
            ->groupBy('hour_bucket')
            ->orderBy('hour_bucket')
            ->get();

        $countsByHour = $rows
            ->mapWithKeys(fn (object $row): array => [(int) $row->hour_bucket => (int) $row->scan_count]);

        $points = collect(range(6, 19))
            ->map(fn (int $hour): array => [
                'hour' => $hour,
                'label' => Carbon::createFromTime($hour)->format('g A'),
                'shortLabel' => Carbon::createFromTime($hour)->format('g'),
                'scanCount' => (int) ($countsByHour[$hour] ?? 0),
            ])
            ->values();

        $totalScans = $points->sum('scanCount');
        $peakPoint = $points
            ->filter(fn (array $point): bool => $point['scanCount'] > 0)
            ->sortByDesc('scanCount')
            ->sortBy('hour')
            ->first();

        $latestScanAt = AttendanceLog::query()
            ->whereBetween('scanned_at', [$todayStart, $todayEnd])
            ->latest('scanned_at')
            ->value('scanned_at');

        return [
            'totalScans' => $totalScans,
            'peakHourLabel' => $peakPoint['label'] ?? null,
            'latestScanLabel' => filled($latestScanAt)
                ? Carbon::parse($latestScanAt)->timezone(config('app.timezone'))->format('g:i A')
                : null,
            'hasData' => $totalScans > 0,
            'points' => $points->all(),
        ];
    }

    /**
     * @return array<int, array{
     *     id:int,
     *     studentName:string,
     *     roleLabel:string,
     *     gradeSection:string|null,
     *     actionLabel:string,
     *     turnstileName:string,
     *     scannedAtLabel:string
     * }>
     */
    private function buildRecentActivity(): array
    {
        return AttendanceLog::query()
            ->with(['user.studentDetail', 'user.employeeDetail', 'turnstile'])
            ->latest('scanned_at')
            ->take(self::RECENT_ACTIVITY_LIMIT)
            ->get()
            ->map(function (AttendanceLog $log): array {
                $user = $log->user;
                $studentDetail = $user?->studentDetail;
                $employeeDetail = $user?->employeeDetail;

                return [
                    'id' => $log->id,
                    'studentName' => $user?->name ?? 'Unknown User',
                    'roleLabel' => $studentDetail !== null
                        ? 'Student'
                        : ($employeeDetail !== null ? 'Employee' : 'Staff'),
                    'gradeSection' => $studentDetail !== null
                        ? trim(implode(' | ', array_filter([
                            $studentDetail->level,
                            $studentDetail->section,
                        ])))
                        : null,
                    'actionLabel' => $log->action === 'OUT' ? 'Time Out' : 'Time In',
                    'turnstileName' => $log->turnstile?->name ?? 'Unknown Turnstile',
                    'scannedAtLabel' => $log->scanned_at->timezone(config('app.timezone'))->format('g:i A'),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{
     *     id:int,
     *     name:string,
     *     location:string,
     *     statusLabel:string,
     *     statusTone:'default'|'secondary'|'destructive',
     *     todayScans:int,
     *     lastScanLabel:string
     * }>
     */
    private function buildTurnstileStatus(): array
    {
        [$todayStart, $todayEnd] = $this->todayRange();

        return Turnstile::query()
            ->withCount([
                'attendanceLogs as today_scan_count' => fn ($query) => $query
                    ->whereBetween('scanned_at', [$todayStart, $todayEnd]),
            ])
            ->withMax('attendanceLogs as last_scanned_at', 'scanned_at')
            ->orderByDesc('status')
            ->orderBy('name')
            ->get()
            ->map(function (Turnstile $turnstile): array {
                $lastScannedAt = $turnstile->last_scanned_at
                    ? Carbon::parse($turnstile->last_scanned_at)
                    : null;

                return [
                    'id' => $turnstile->id,
                    'name' => $turnstile->name,
                    'location' => $turnstile->location,
                    'statusLabel' => $turnstile->status ? 'Active' : 'Inactive',
                    'statusTone' => $turnstile->status ? 'default' : 'destructive',
                    'todayScans' => (int) $turnstile->today_scan_count,
                    'lastScanLabel' => $lastScannedAt instanceof Carbon
                        ? $lastScannedAt->timezone(config('app.timezone'))->format('g:i A')
                        : 'No scans yet',
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function todayRange(): array
    {
        $today = now();

        return [$today->copy()->startOfDay(), $today->copy()->endOfDay()];
    }
}
