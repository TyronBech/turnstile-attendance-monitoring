<?php

use App\Models\AttendanceLog;
use App\Models\Turnstile;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
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
