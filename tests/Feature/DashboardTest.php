<?php

use App\Jobs\SendAttendanceSmsJob;
use App\Models\AttendanceLog;
use App\Models\Turnstile;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users without view_dashboard permission cannot visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertStatus(403);
});

test('authenticated users can visit the dashboard and receive expected props', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('view_dashboard');
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('dashboard')
            ->has('stats')
            ->has('stats.currentlyTimedIn')
            ->has('stats.totalScansToday')
            ->has('stats.pendingSmsCount')
            ->has('stats.failedSmsCount')
            ->has('stats.sentSmsCount')
            ->has('stats.activeTurnstiles')
            ->has('stats.totalTurnstiles')
            // Deferred / lazy props should not be present in the initial standard JSON response
            // unless requested, but AssertableInertia handles them nicely.
        );
});

test('authenticated users can trigger sms retry', function () {
    Queue::fake();

    $user = User::factory()->create();
    $user->givePermissionTo('view_dashboard');
    $turnstile = Turnstile::factory()->create();

    $log = AttendanceLog::factory()->timeIn()->create([
        'user_id' => $user->id,
        'turnstile_id' => $turnstile->id,
        'sms_status' => 'FAILED',
        'scanned_at' => now(),
    ]);

    $this->actingAs($user);

    $response = $this->post(route('dashboard.retry-sms', ['attendanceLog' => $log->id]));

    $response->assertRedirect();
    $this->assertDatabaseHas('attendance_logs', [
        'id' => $log->id,
        'sms_status' => 'PENDING',
    ]);

    Queue::assertPushed(SendAttendanceSmsJob::class, function ($job) use ($log) {
        return $job->attendanceLogId === $log->id;
    });
});
