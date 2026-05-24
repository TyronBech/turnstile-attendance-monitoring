<?php

namespace App\Http\Controllers;

use App\Models\AttendanceLog;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class AttendanceDisplayController extends Controller
{
    private const PANEL_LIMIT = 4;

    public function show(): Response
    {
        return Inertia::render('attendance-display', [
            'panels' => $this->buildPanels(),
            'tickerItems' => [
                'Welcome to Sto. Nino Catholic School, Inc.',
                'Please tap your RFID card to log attendance.',
                'Have a great day and proceed safely through the gate.',
                'Thank you for keeping attendance records accurate.',
            ],
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildPanels(): array
    {
        $panels = AttendanceLog::query()
            ->with(['user.studentDetail', 'user.employeeDetail'])
            ->latest('scanned_at')
            ->take(self::PANEL_LIMIT)
            ->get()
            ->values()
            ->map(function (AttendanceLog $log, int $index): array {
                $user = $log->user;
                $studentDetail = $user?->studentDetail;
                $employeeDetail = $user?->employeeDetail;
                $isStudent = $studentDetail !== null;
                $isEmployee = $employeeDetail !== null;
                $name = $user?->name ?? 'Unknown User';
                $profileImage = $this->resolveProfileImage($user?->profile_image, $name);

                return [
                    'id' => $log->id,
                    'slot' => $index + 1,
                    'state' => $this->isRecentlyTapped($log->scanned_at) ? 'active' : 'idle',
                    'isRecent' => $this->isRecentlyTapped($log->scanned_at),
                    'name' => $name,
                    'role' => $isStudent ? 'Student' : ($isEmployee ? 'Employee' : 'Staff'),
                    'gradeSection' => $isStudent
                        ? trim(implode(' | ', array_filter([
                            $studentDetail->level,
                            $studentDetail->section,
                        ])))
                        : null,
                    'profileImage' => $profileImage,
                    'action' => $log->action,
                    'actionLabel' => $log->action === 'OUT' ? 'Time Out' : 'Time In',
                    'timeLabel' => $log->scanned_at->format('g:i:s A'),
                ];
            })
            ->all();

        while (count($panels) < self::PANEL_LIMIT) {
            $panels[] = [
                'id' => 'waiting-'.(count($panels) + 1),
                'slot' => count($panels) + 1,
                'state' => 'waiting',
                'isRecent' => false,
                'name' => 'Awaiting',
                'role' => 'Tap RFID card',
                'gradeSection' => null,
                'profileImage' => null,
                'action' => null,
                'actionLabel' => 'Waiting',
                'timeLabel' => '--:--:--',
            ];
        }

        return $panels;
    }

    private function isRecentlyTapped(?CarbonInterface $scannedAt): bool
    {
        if ($scannedAt === null) {
            return false;
        }

        return $scannedAt->greaterThanOrEqualTo(now()->subMinutes(2));
    }

    private function resolveProfileImage(?string $profileImage, string $name): string
    {
        if (filled($profileImage)) {
            if (
                str_starts_with($profileImage, 'http://')
                || str_starts_with($profileImage, 'https://')
                || str_starts_with($profileImage, 'data:image/')
                || str_starts_with($profileImage, '/storage/')
            ) {
                return $profileImage;
            }

            return Storage::disk('public')->url($profileImage);
        }

        return Storage::disk('public')->url('profile-images/default.svg');
    }
}
