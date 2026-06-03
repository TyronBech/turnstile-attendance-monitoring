<?php

use App\Models\AttendanceLog;
use App\Models\Turnstile;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\StudentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;

uses(RefreshDatabase::class);

test('database seeder creates one student log, one employee log, and a live monitoring account', function (): void {
    Log::spy();

    $this->seed(DatabaseSeeder::class);

    $turnstile = Turnstile::query()->first();

    expect($turnstile)->not->toBeNull();
    expect(AttendanceLog::query()->count())->toBe(2);

    expect(
        User::query()
            ->where('email', 'test@example.com')
            ->value('profile_image')
    )->toBeNull();

    $jhonCarl = User::query()
        ->where('email', 'jhoncarl.ormita@university.edu')
        ->firstOrFail();

    expect($jhonCarl->only(['first_name', 'middle_name', 'last_name', 'profile_image']))->toBe([
        'first_name' => 'Jhon Carl',
        'middle_name' => 'Tortosa',
        'last_name' => 'Ormita',
        'profile_image' => 'profile-images/ormita.png',
    ]);

    expect($jhonCarl->studentDetail)->toBeNull();
    expect($jhonCarl->employeeDetail)->not->toBeNull();
    expect($jhonCarl->employeeDetail?->employee_role)->toBe('Teacher');

    $tyron = User::query()
        ->where('email', 'tyron.bechayda@university.edu')
        ->firstOrFail();

    expect($tyron->only(['first_name', 'middle_name', 'last_name', 'profile_image']))->toBe([
        'first_name' => 'Tyron',
        'middle_name' => 'Panti',
        'last_name' => 'Bechayda',
        'profile_image' => 'profile-images/tyron.jpg',
    ]);

    expect($tyron->studentDetail)->not->toBeNull();

    $liveMonitoringUser = User::query()
        ->where('email', 'live.monitoring@university.edu')
        ->firstOrFail();

    expect($liveMonitoringUser->hasRole('live_monitoring'))->toBeTrue();

    $seededLogs = AttendanceLog::query()
        ->orderBy('scanned_at')
        ->get();

    expect($seededLogs->pluck('user_id')->all())->toBe([$tyron->id, $jhonCarl->id]);
    expect($seededLogs->pluck('turnstile_id')->unique()->all())->toBe([$turnstile->id]);
    expect($seededLogs->pluck('action')->all())->toBe(['IN', 'IN']);
});

test('student seeder updates an existing user matched by rfid', function (): void {
    $existingUser = User::factory()->create([
        'email' => 'legacy.tyron@example.com',
        'rfid' => 'E5F6A7B8',
        'first_name' => 'Legacy',
        'middle_name' => null,
        'last_name' => 'Record',
    ]);

    $this->seed(StudentSeeder::class);

    expect(User::query()->where('rfid', 'E5F6A7B8')->count())->toBe(1);

    $existingUser->refresh();

    expect($existingUser->only(['email', 'first_name', 'middle_name', 'last_name', 'profile_image']))->toBe([
        'email' => 'tyron.bechayda@university.edu',
        'first_name' => 'Tyron',
        'middle_name' => 'Panti',
        'last_name' => 'Bechayda',
        'profile_image' => 'profile-images/tyron.jpg',
    ]);

    expect($existingUser->studentDetail)->not->toBeNull();
    expect($existingUser->studentDetail?->only(['id_number', 'level', 'section']))->toBe([
        'id_number' => '2026-00002',
        'level' => 'Grade 12',
        'section' => 'ABM 1',
    ]);
});
