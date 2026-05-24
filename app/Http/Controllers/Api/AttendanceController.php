<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ScanRfidRequest;
use App\Models\Turnstile;
use App\Services\AttendanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\User;

class AttendanceController extends Controller
{
    /**
     * Handle an RFID scan from an ESP32 turnstile device.
     */
    public function scan(ScanRfidRequest $request, AttendanceService $service): JsonResponse
    {
        /** @var Turnstile $turnstile */
        $turnstile = $request->user();

        try {
            $log = $service->recordScan($turnstile, $request->validated('rfid'));
            $student = $log->user;

            return response()->json([
                'data' => [
                    'log_id' => $log->id,
                    'student_id' => $student->student_id,
                    'student_name' => $student->name,
                    'action' => $log->action,
                    'scanned_at' => $log->scanned_at->toIso8601String(),
                    'turnstile' => $turnstile->name,
                ],
                'meta' => ['timestamp' => now()->toIso8601String()],
                'errors' => [],
            ], 201);
        } catch (\Exception $e) {
            $code = $e->getMessage();
            $status = $e->getCode() ?: 400;

            return response()->json([
                'data' => null,
                'meta' => ['timestamp' => now()->toIso8601String()],
                'errors' => [[
                    'code' => $code,
                    'message' => $this->getErrorMessage($code),
                ]],
            ], $status);
        }
    }

    /**
     * Get human-readable error message from code.
     */
    private function getErrorMessage(string $code): string
    {
        return match ($code) {
            'USER_INACTIVE' => 'This account or device is currently inactive.',
            'RFID_NOT_FOUND' => 'No student found with this RFID tag.',
            default => 'An unexpected error occurred.',
        };
    }

    /**
     * Handle batch sync of offline RFID scans.
     */
    public function sync(Request $request, AttendanceService $service): JsonResponse
    {
        $request->validate([
            'offline_scans' => ['required', 'array'],
            'offline_scans.*' => ['string'],
        ]);

        /** @var Turnstile $turnstile */
        $turnstile = $request->user();

        $logs = $service->processBatchScans($turnstile, $request->input('offline_scans'));

        return response()->json([
            'data' => [
                'synced_count' => $logs->count(),
                'turnstile' => $turnstile->name,
            ],
            'meta' => ['timestamp' => now()->toIso8601String()],
            'errors' => [],
        ], 200);
    }

    /**
     * Get the list of registered students for offline ESP32 storage.
     */
    public function students(Request $request): JsonResponse
    {
        $students = User::whereNotNull('rfid')
            ->where('status', true)
            ->with('studentDetail')
            ->get()
            ->map(function (User $user) {
                return [
                    'rfid' => $user->rfid,
                    'name' => $user->name,
                    'level' => $user->studentDetail?->level ?? 'N/A',
                    'section' => $user->studentDetail?->section ?? 'N/A',
                ];
            });

        return response()->json($students);
    }
}
