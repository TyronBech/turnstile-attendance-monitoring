<?php

namespace App\Http\Controllers;

use App\Models\AttendanceLog;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class AttendanceDisplayController extends Controller
{
    private const PANEL_LIMIT = 4;

    private const PANEL_DISPLAY_WINDOW_SECONDS = 12;

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
            ->where('scanned_at', '>=', now()->subSeconds(self::PANEL_DISPLAY_WINDOW_SECONDS))
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
                $profileImage = $this->resolveProfileImage($user?->profile_image);

                return [
                    'id' => $log->id,
                    'slot' => $index + 1,
                    'state' => 'active',
                    'isRecent' => true,
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
                    'timeLabel' => $log->scanned_at->timezone(config('app.timezone'))->format('g:i:s A'),
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

    private function resolveProfileImage(?string $profileImage): ?string
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

        return null;
    }
}
