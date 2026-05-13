<?php

use App\Jobs\SendAttendanceSmsJob;
use App\Models\AttendanceLog;
use App\Models\Turnstile;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

beforeEach(function (): void {
    config([
        'services.semaphore.api_key' => 'test-api-key',
        'services.semaphore.api_url' => 'https://api.semaphore.test/messages',
        'services.semaphore.sender_name' => 'SNCS',
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

it('dispatches guardian sms job when semaphore is enabled', function (): void {
    config(['services.semaphore.enabled' => true]);
    Queue::fake();

    $this->withToken($this->token)
        ->postJson('/api/v1/attendance/scan', ['rfid' => 'TESTRF01'])
        ->assertCreated();

    $this->app->terminate();

    Queue::assertPushed(SendAttendanceSmsJob::class, function (SendAttendanceSmsJob $job): bool {
        return $job->attendanceLogId === AttendanceLog::query()->latest('id')->value('id');
    });
});

it('does not dispatch sms job when semaphore is disabled', function (): void {
    config(['services.semaphore.enabled' => false]);
    Queue::fake();

    $this->withToken($this->token)
        ->postJson('/api/v1/attendance/scan', ['rfid' => 'TESTRF01'])
        ->assertCreated();

    $this->app->terminate();

    Queue::assertNothingPushed();
});

it('does not dispatch sms job without guardian contact number', function (): void {
    config(['services.semaphore.enabled' => true]);
    Queue::fake();

    $this->student->update(['guardian_contact_number' => '']);

    $this->withToken($this->token)
        ->postJson('/api/v1/attendance/scan', ['rfid' => 'TESTRF01'])
        ->assertCreated();

    $this->app->terminate();

    Queue::assertNothingPushed();
});
