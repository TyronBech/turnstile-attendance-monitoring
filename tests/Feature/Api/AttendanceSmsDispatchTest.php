<?php

use App\Jobs\SendAttendanceSmsJob;
use App\Models\AttendanceLog;
use App\Models\Turnstile;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;

beforeEach(function (): void {
    config([
        'services.unisms.api_key' => 'test-api-key',
        'services.unisms.api_url' => 'https://unismsapi.test/api/sms',
        'services.unisms.sender_id' => 'SNCS',
    ]);

    $this->turnstile = Turnstile::factory()->create([
        'name' => 'Library Demo Gate',
        'location' => 'Library',
        'status' => true,
    ]);

    $this->student = User::factory()->create([
        'rfid' => 'TESTRF01',
        'status' => true,
        'guardian_contact_number' => '09171234567',
    ]);

    $this->token = $this->turnstile->createToken('test-device', ['attendance:scan'])->plainTextToken;
});

it('dispatches guardian sms job when unisms is enabled', function (): void {
    config(['services.unisms.enabled' => true]);
    Log::spy();
    Queue::fake();

    $this->withToken($this->token)
        ->postJson('/api/v1/attendance/scan', ['rfid' => 'TESTRF01'])
        ->assertCreated();

    $this->app->terminate();

    Queue::assertPushed(SendAttendanceSmsJob::class, function (SendAttendanceSmsJob $job): bool {
        return $job->attendanceLogId === AttendanceLog::query()->latest('id')->value('id');
    });

    Log::shouldHaveReceived('info')
        ->once()
        ->with('Attendance SMS queued.', Mockery::on(function (array $context): bool {
            return $context['attendance_log_id'] === AttendanceLog::query()->latest('id')->value('id')
                && $context['user_id'] === $this->student->id
                && ! array_key_exists('reason', $context);
        }));
});

it('does not dispatch sms job when unisms is disabled', function (): void {
    config(['services.unisms.enabled' => false]);
    Log::spy();
    Queue::fake();

    $this->withToken($this->token)
        ->postJson('/api/v1/attendance/scan', ['rfid' => 'TESTRF01'])
        ->assertCreated();

    $this->app->terminate();

    Queue::assertNothingPushed();

    Log::shouldHaveReceived('info')
        ->once()
        ->with('Attendance SMS skipped before queue.', Mockery::on(function (array $context): bool {
            return $context['reason'] === 'unisms_disabled';
        }));
});

it('does not dispatch sms job without guardian contact number', function (): void {
    config(['services.unisms.enabled' => true]);
    Log::spy();
    Queue::fake();

    $this->student->update(['guardian_contact_number' => '']);

    $this->withToken($this->token)
        ->postJson('/api/v1/attendance/scan', ['rfid' => 'TESTRF01'])
        ->assertCreated();

    $this->app->terminate();

    Queue::assertNothingPushed();

    Log::shouldHaveReceived('info')
        ->once()
        ->with('Attendance SMS skipped before queue.', Mockery::on(function (array $context): bool {
            return $context['reason'] === 'missing_guardian_contact_number';
        }));
});
