<?php

use App\Jobs\SendAttendanceSmsJob;
use App\Models\AttendanceLog;
use App\Models\Turnstile;
use App\Models\User;
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
    Queue::fake();

    $this->withToken($this->token)
        ->postJson('/api/v1/attendance/scan', ['rfid' => 'TESTRF01'])
        ->assertCreated();

    $this->app->terminate();

    Queue::assertPushed(SendAttendanceSmsJob::class, function (SendAttendanceSmsJob $job): bool {
        return $job->attendanceLogId === AttendanceLog::query()->latest('id')->value('id');
    });
});

it('does not dispatch sms job when unisms is disabled', function (): void {
    config(['services.unisms.enabled' => false]);
    Queue::fake();

    $this->withToken($this->token)
        ->postJson('/api/v1/attendance/scan', ['rfid' => 'TESTRF01'])
        ->assertCreated();

    $this->app->terminate();

    Queue::assertNothingPushed();
});

it('does not dispatch sms job without guardian contact number', function (): void {
    config(['services.unisms.enabled' => true]);
    Queue::fake();

    $this->student->update(['guardian_contact_number' => '']);

    $this->withToken($this->token)
        ->postJson('/api/v1/attendance/scan', ['rfid' => 'TESTRF01'])
        ->assertCreated();

    $this->app->terminate();

    Queue::assertNothingPushed();
});
