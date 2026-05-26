<?php

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\AttendanceLog;
use App\Models\Turnstile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('guests are redirected to login before viewing attendance display', function (): void {
    $this->get(route('attendance-display'))->assertRedirect(route('login'));
});

test('attendance display page shows recent tap panels to authenticated users', function (): void {
    $this->travelTo(now()->setTime(8, 15, 0));

    $student = User::factory()->create([
        'first_name' => 'William',
        'middle_name' => 'Mendoza',
        'last_name' => 'Quitiquit',
        'profile_image' => 'profile-images/william.png',
    ]);

    $student->studentDetail()->update([
        'level' => 'Grade 12',
        'section' => 'Mercy',
    ]);

    $turnstile = Turnstile::factory()->create();

    AttendanceLog::factory()->timeIn()->create([
        'user_id' => $student->id,
        'turnstile_id' => $turnstile->id,
        'scanned_at' => now()->subSeconds(3),
    ]);

    $this->actingAs($student);
    $profileImageUrl = Storage::disk('public')->url('profile-images/william.png');

    $this->get(route('attendance-display'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('attendance-display')
            ->has('panels', 4)
            ->where('panels.0.name', 'William M. Quitiquit')
            ->where('panels.0.profileImage', $profileImageUrl)
            ->where('panels.0.state', 'active')
            ->where('panels.0.role', 'Student')
            ->where('panels.0.gradeSection', 'Grade 12 | Mercy')
            ->where('panels.0.timeLabel', '8:14:57 AM')
            ->where('panels.1.state', 'waiting')
            ->where('tickerItems.3', 'Thank you for keeping attendance records accurate.')
        );
});

test('attendance display returns to awaiting panels after five seconds', function (): void {
    $this->travelTo(now()->setTime(9, 0, 0));

    expect(Schema::hasColumn('usr_users', 'profile_image'))->toBeTrue();

    $employee = User::factory()->withoutStudentProfile()->create([
        'profile_image' => 'profile-images/maria.png',
    ]);

    $employee->employeeDetail()->create([
        'employee_id' => 'EMP-2026-0002',
        'employee_role' => 'Registrar',
        'active_employee_id' => 'EMP-2026-0002',
    ]);

    $turnstile = Turnstile::factory()->create();

    AttendanceLog::factory()->timeOut()->create([
        'user_id' => $employee->id,
        'turnstile_id' => $turnstile->id,
        'scanned_at' => now()->subSeconds(6),
    ]);

    $this->actingAs($employee);

    $this->get(route('attendance-display'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('panels', 4)
            ->where('panels.0.state', 'waiting')
            ->where('panels.1.state', 'waiting')
        );

    $this->get('/attendance-display/feed')->assertNotFound();
});

test('attendance display supports partial reloads for panels', function (): void {
    $this->travelTo(now()->setTime(9, 30, 0));

    $student = User::factory()->create([
        'first_name' => 'Elena',
        'middle_name' => 'Santos',
        'last_name' => 'Rivera',
        'profile_image' => null,
    ]);

    $student->studentDetail()->update([
        'level' => 'Grade 11',
        'section' => 'Hope',
    ]);

    $turnstile = Turnstile::factory()->create();

    AttendanceLog::factory()->timeIn()->create([
        'user_id' => $student->id,
        'turnstile_id' => $turnstile->id,
        'scanned_at' => now()->subSeconds(2),
    ]);

    $this->actingAs($student);
    $version = app(HandleInertiaRequests::class)->version(Request::create(route('attendance-display')));

    $this->withHeaders([
        'X-Inertia' => 'true',
        'X-Inertia-Version' => $version,
        'X-Inertia-Partial-Component' => 'attendance-display',
        'X-Inertia-Partial-Data' => 'panels',
    ])->get(route('attendance-display'))
        ->assertOk()
        ->assertJsonPath('component', 'attendance-display')
        ->assertJsonPath('props.panels.0.name', 'Elena S. Rivera')
        ->assertJsonPath('props.panels.0.gradeSection', 'Grade 11 | Hope')
        ->assertJsonPath('props.panels.0.profileImage', null)
        ->assertJsonPath('props.panels.0.timeLabel', '9:29:58 AM')
        ->assertJsonMissingPath('props.tickerItems');
});
