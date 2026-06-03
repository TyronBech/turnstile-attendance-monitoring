<?php

use App\Http\Controllers\Api\AttendanceController;
use App\Http\Middleware\EnsureJsonRequest;
use App\Http\Middleware\EnsureTurnstileIsActive;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Turnstile Device Endpoints
| --------------------------
| These routes are protected by Sanctum token authentication.
| Each ESP32 turnstile device authenticates using a Bearer token
| issued via the TurnstileSeeder (or a future admin panel).
|
| Postman Testing:
| 1. Set Header: Accept → application/json
| 2. Set Header: Content-Type → application/json
| 3. Set Authorization: Bearer Token → <token from seeder output>
| 4. POST /api/v1/attendance/scan with body: { "rfid": "<student_rfid>" }
|
*/

Route::prefix('v1')->middleware([EnsureJsonRequest::class])->group(function (): void {

    // Authenticated turnstile device routes
    Route::middleware(['auth:sanctum', EnsureTurnstileIsActive::class])->group(function (): void {

        // RFID scan endpoint — records student time-in/time-out
        Route::post('/attendance/scan', [AttendanceController::class, 'scan'])
            ->name('api.v1.attendance.scan');

        // Batch sync offline logs
        Route::post('/attendance/sync', [AttendanceController::class, 'sync'])
            ->name('api.v1.attendance.sync');

        // Fetch students for offline validation
        Route::get('/turnstile/students', [AttendanceController::class, 'students'])
            ->name('api.v1.turnstile.students');

        // Health check — verifies the turnstile's token is valid
        Route::get('/turnstile/ping', function (Request $request): JsonResponse {
            $turnstile = $request->user();

            return response()->json([
                'data' => [
                    'turnstile_id' => $turnstile->id,
                    'name' => $turnstile->name,
                    'location' => $turnstile->location,
                    'status' => 'connected',
                ],
                'meta' => ['timestamp' => now()->toIso8601String()],
                'errors' => [],
            ]);
        })->name('api.v1.turnstile.ping');
    });
});
