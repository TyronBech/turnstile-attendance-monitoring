<?php

use App\Jobs\SendAttendanceSmsJob;
use App\Models\AttendanceLog;
use App\Models\Turnstile;
use App\Models\User;
use App\Services\SemaphoreSmsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use function Pest\Laravel\mock;

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
    $this->travelTo(Carbon::create(2026, 5, 24, 8, 6, 0, config('app.timezone')));

    $user = User::factory()->create([
        'first_name' => 'Tyron',
        'middle_name' => 'Panti',
        'last_name' => 'Bechayda',
        'guardian_contact_number' => '09171234567',
        'status' => true,
    ]);
    $turnstile = Turnstile::factory()->create(['name' => 'Main Gate']);
    $log = AttendanceLog::factory()->create([
        'user_id' => $user->id,
        'turnstile_id' => $turnstile->id,
        'action' => 'IN',
        'scanned_at' => Carbon::create(2026, 5, 24, 8, 5, 12, config('app.timezone')),
        'sms_status' => 'PENDING',
    ]);

    $sms = mock(SemaphoreSmsService::class);
    $sms->shouldReceive('send')
        ->once()
        ->with(
            '09171234567',
            '[SNCS]: Your child, Tyron P. Bechayda, has arrived at school at 8:05 AM. Thank you!',
        )
        ->andReturnTrue();

    $job = new SendAttendanceSmsJob($log->id);
    $job->handle($sms);

    expect($log->fresh()->sms_status)->toBe('SENT');
});

it('adds the apology when the sms is delayed', function (): void {
    $this->travelTo(Carbon::create(2026, 5, 24, 8, 20, 0, config('app.timezone')));

    $user = User::factory()->create([
        'first_name' => 'Tyron',
        'middle_name' => 'Panti',
        'last_name' => 'Bechayda',
        'guardian_contact_number' => '09171234567',
        'status' => true,
    ]);
    $turnstile = Turnstile::factory()->create(['name' => 'Main Gate']);
    $log = AttendanceLog::factory()->create([
        'user_id' => $user->id,
        'turnstile_id' => $turnstile->id,
        'action' => 'OUT',
        'scanned_at' => Carbon::create(2026, 5, 24, 8, 5, 12, config('app.timezone')),
        'sms_status' => 'PENDING',
    ]);

    $sms = mock(SemaphoreSmsService::class);
    $sms->shouldReceive('send')
        ->once()
        ->with(
            '09171234567',
            '[SNCS]: Your child, Tyron P. Bechayda, has left school at 8:05 AM. Thank You! Apologies if this message was delayed.',
        )
        ->andReturnTrue();

    $job = new SendAttendanceSmsJob($log->id);
    $job->handle($sms);
});

it('throws when semaphore rejects so the queue can retry', function (): void {
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

    $sms = mock(SemaphoreSmsService::class);
    $sms->shouldReceive('send')->once()->andReturnFalse();

    $job = new SendAttendanceSmsJob($log->id);

    expect(fn () => $job->handle($sms))
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
