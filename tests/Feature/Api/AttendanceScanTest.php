<?php

use App\Models\AttendanceLog;
use App\Models\Turnstile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->turnstile = Turnstile::factory()->create([
        'name' => 'Test Turnstile',
        'location' => 'Test Gate',
        'ip_address' => '192.168.1.50',
        'status' => true,
    ]);

    $this->student = User::factory()->create([
        'rfid' => 'TESTRF01',
        'status' => true,
    ]);

    $this->token = $this->turnstile->createToken('test-device', ['attendance:scan'])->plainTextToken;
});

it('rejects requests without Accept: application/json header', function (): void {
    $response = $this->postJson('/api/v1/attendance/scan', ['rfid' => 'TESTRF01'], [
        'Authorization' => "Bearer {$this->token}",
        'Accept' => 'text/html',
    ]);

    $response->assertStatus(406);
    $response->assertJsonPath('errors.0.code', 'INVALID_ACCEPT_HEADER');
});

it('rejects unauthenticated requests', function (): void {
    $response = $this->postJson('/api/v1/attendance/scan', ['rfid' => 'TESTRF01']);

    $response->assertStatus(401);
});

it('rejects requests with an invalid token', function (): void {
    $response = $this->postJson('/api/v1/attendance/scan', ['rfid' => 'TESTRF01'], [
        'Authorization' => 'Bearer invalid-token-here',
    ]);

    $response->assertStatus(401);
});

it('validates that rfid field is required', function (): void {
    $response = $this->withToken($this->token)
        ->postJson('/api/v1/attendance/scan', []);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors('rfid');
});

it('returns 404 for an unregistered RFID', function (): void {
    $response = $this->withToken($this->token)
        ->postJson('/api/v1/attendance/scan', ['rfid' => 'UNKNOWN_RFID']);

    $response->assertStatus(404);
    $response->assertJsonPath('errors.0.code', 'RFID_NOT_FOUND');
});

it('records a time-in for the first scan of the day', function (): void {
    $response = $this->withToken($this->token)
        ->postJson('/api/v1/attendance/scan', ['rfid' => 'TESTRF01']);

    $response->assertStatus(201);
    $response->assertJsonPath('data.action', 'IN');
    $response->assertJsonPath('data.student_id', $this->student->student_id);

    $this->assertDatabaseHas('attendance_logs', [
        'user_id' => $this->student->id,
        'turnstile_id' => $this->turnstile->id,
        'action' => 'IN',
    ]);
});

it('records a time-out for the second scan of the day', function (): void {
    // First scan — time IN
    $this->withToken($this->token)
        ->postJson('/api/v1/attendance/scan', ['rfid' => 'TESTRF01']);

    // Second scan — time OUT
    $response = $this->withToken($this->token)
        ->postJson('/api/v1/attendance/scan', ['rfid' => 'TESTRF01']);

    $response->assertStatus(201);
    $response->assertJsonPath('data.action', 'OUT');

    expect(AttendanceLog::where('user_id', $this->student->id)->count())->toBe(2);
});

it('alternates between IN and OUT on consecutive scans', function (): void {
    // Scan 1 → IN
    $r1 = $this->withToken($this->token)
        ->postJson('/api/v1/attendance/scan', ['rfid' => 'TESTRF01']);
    expect($r1->json('data.action'))->toBe('IN');

    // Scan 2 → OUT
    $r2 = $this->withToken($this->token)
        ->postJson('/api/v1/attendance/scan', ['rfid' => 'TESTRF01']);
    expect($r2->json('data.action'))->toBe('OUT');

    // Scan 3 → IN again
    $r3 = $this->withToken($this->token)
        ->postJson('/api/v1/attendance/scan', ['rfid' => 'TESTRF01']);
    expect($r3->json('data.action'))->toBe('IN');
});

it('rejects scan from an inactive turnstile', function (): void {
    $this->turnstile->update(['status' => false]);

    $response = $this->withToken($this->token)
        ->postJson('/api/v1/attendance/scan', ['rfid' => 'TESTRF01']);

    $response->assertStatus(403);
    $response->assertJsonPath('errors.0.code', 'TURNSTILE_INACTIVE');
});

it('rejects scan for an inactive student', function (): void {
    $this->student->update(['status' => false]);

    $response = $this->withToken($this->token)
        ->postJson('/api/v1/attendance/scan', ['rfid' => 'TESTRF01']);

    $response->assertStatus(403);
    $response->assertJsonPath('errors.0.code', 'STUDENT_INACTIVE');
});

it('allows turnstile ping with valid token', function (): void {
    $response = $this->withToken($this->token)
        ->getJson('/api/v1/turnstile/ping');

    $response->assertOk();
    $response->assertJsonPath('data.name', 'Test Turnstile');
    $response->assertJsonPath('data.status', 'connected');
});
