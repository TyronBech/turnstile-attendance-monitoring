<?php

use App\Models\AttendanceLog;
use App\Models\Turnstile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('guests are redirected to the login page', function (): void {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can view dashboard summary and deferred widgets', function (): void {
    $this->travelTo(now()->setTime(8, 15, 0));

    $user = User::factory()->create();
    $studentOne = User::factory()->create([
        'first_name' => 'Ariana',
        'middle_name' => 'Lopez',
        'last_name' => 'Santos',
    ]);
    $studentOne->studentDetail()->update([
        'level' => 'Grade 12',
        'section' => 'Mercy',
    ]);

    $studentTwo = User::factory()->create([
        'first_name' => 'Marco',
        'middle_name' => 'Reyes',
        'last_name' => 'Tan',
    ]);
    $studentTwo->studentDetail()->update([
        'level' => 'Grade 11',
        'section' => 'Hope',
    ]);

    $mainGate = Turnstile::factory()->create([
        'name' => 'Main Gate',
        'location' => 'North Entrance',
        'status' => true,
    ]);
    $backGate = Turnstile::factory()->inactive()->create([
        'name' => 'Back Gate',
        'location' => 'South Entrance',
    ]);

    AttendanceLog::factory()->timeIn()->create([
        'user_id' => $studentOne->id,
        'turnstile_id' => $mainGate->id,
        'scanned_at' => now()->setTime(7, 10),
        'sms_status' => 'SENT',
    ]);

    AttendanceLog::factory()->timeOut()->create([
        'user_id' => $studentOne->id,
        'turnstile_id' => $mainGate->id,
        'scanned_at' => now()->setTime(8, 5),
        'sms_status' => 'FAILED',
    ]);

    AttendanceLog::factory()->timeIn()->create([
        'user_id' => $studentTwo->id,
        'turnstile_id' => $backGate->id,
        'scanned_at' => now()->setTime(7, 45),
        'sms_status' => 'PENDING',
    ]);

    $this->actingAs($user);

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('dashboard')
            ->where('summary.totalIn', 2)
            ->where('summary.totalOut', 1)
            ->where('summary.uniqueStudents', 2)
            ->where('summary.smsAttention', 2)
            ->where('summary.activeTurnstiles', 1)
            ->where('summary.inactiveTurnstiles', 1)
            ->where('generatedAt', '8:15 AM')
            ->loadDeferredProps('dashboard-panels', fn (Assert $deferredPage) => $deferredPage
                ->where('todayActivity.totalScans', 3)
                ->where('todayActivity.peakHourLabel', '7 AM')
                ->where('todayActivity.latestScanLabel', '8:05 AM')
                ->where('todayActivity.hasData', true)
                ->has('todayActivity.points', 14)
                ->where('todayActivity.points.1.label', '7 AM')
                ->where('todayActivity.points.1.scanCount', 2)
                ->has('recentActivity', 3)
                ->where('recentActivity.0.studentName', 'Ariana L. Santos')
                ->where('recentActivity.0.gradeSection', 'Grade 12 | Mercy')
                ->where('recentActivity.0.actionLabel', 'Time Out')
                ->has('turnstileStatus', 2)
                ->where('turnstileStatus.0.name', 'Main Gate')
                ->where('turnstileStatus.0.statusLabel', 'Active')
                ->where('turnstileStatus.0.todayScans', 2)
                ->where('turnstileStatus.1.name', 'Back Gate')
                ->where('turnstileStatus.1.statusLabel', 'Inactive')
            )
        );
});
