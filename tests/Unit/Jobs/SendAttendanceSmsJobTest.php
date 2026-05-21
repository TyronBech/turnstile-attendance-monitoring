<?php

use App\Jobs\SendAttendanceSmsJob;
use App\Models\AttendanceLog;
use App\Models\Turnstile;
use App\Models\User;
use App\Services\SemaphoreSmsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    config([
        'logging.default' => 'null',
        'services.semaphore.api_key' => 'test-api-key',
        'services.semaphore.api_url' => 'https://api.semaphore.co/api/v1/messages',
        'services.semaphore.sender_name' => 'SNCS',
    ]);
});

it('sends sms and marks attendance log as sent when semaphore succeeds', function (): void {
    Http::fake([
        'https://api.semaphore.co/api/v1/messages' => Http::response([['status' => '1']], 200),
    ]);

    $user = User::factory()->create([
        'guardian_contact_number' => '09171234567',
        'status' => true,
    ]);
    $turnstile = Turnstile::factory()->create(['name' => 'Main Gate']);
    $log = AttendanceLog::factory()->create([
        'user_id' => $user->id,
        'turnstile_id' => $turnstile->id,
        'action' => 'IN',
        'sms_status' => 'PENDING',
    ]);

    $job = new SendAttendanceSmsJob($log->id);
    $job->handle(app(SemaphoreSmsService::class));

    expect($log->fresh()->sms_status)->toBe('SENT');
    Http::assertSentCount(1);
});

it('throws when semaphore rejects so the queue can retry', function (): void {
    Http::fake([
        'https://api.semaphore.co/api/v1/messages' => Http::response([['status' => '0', 'message' => 'bad']], 200),
    ]);

    $user = User::factory()->create([
        'guardian_contact_number' => '09171234567',
        'status' => true,
    ]);
    $turnstile = Turnstile::factory()->create();
    $log = AttendanceLog::factory()->create([
        'user_id' => $user->id,
        'turnstile_id' => $turnstile->id,
        'sms_status' => 'PENDING',
    ]);

    $job = new SendAttendanceSmsJob($log->id);

    expect(fn () => $job->handle(app(SemaphoreSmsService::class)))
        ->toThrow(RuntimeException::class, 'Semaphore SMS send failed');

    expect($log->fresh()->sms_status)->toBe('PENDING');
});

it('marks log as failed when failed callback runs', function (): void {
    $user = User::factory()->create([
        'guardian_contact_number' => '09171234567',
        'status' => true,
    ]);
    $turnstile = Turnstile::factory()->create();
    $log = AttendanceLog::factory()->create([
        'user_id' => $user->id,
        'turnstile_id' => $turnstile->id,
        'sms_status' => 'PENDING',
    ]);

    $job = new SendAttendanceSmsJob($log->id);
    $job->failed(new RuntimeException('exhausted'));

    expect($log->fresh()->sms_status)->toBe('FAILED');
});

it('does nothing when log is already sent', function (): void {
    Http::fake([
        'https://api.semaphore.co/api/v1/messages' => Http::response([['status' => '1']], 200),
    ]);

    $user = User::factory()->create(['guardian_contact_number' => '09171234567']);
    $turnstile = Turnstile::factory()->create();
    $log = AttendanceLog::factory()->create([
        'user_id' => $user->id,
        'turnstile_id' => $turnstile->id,
        'sms_status' => 'SENT',
    ]);

    $job = new SendAttendanceSmsJob($log->id);
    $job->handle(app(SemaphoreSmsService::class));

    Http::assertNothingSent();
});
