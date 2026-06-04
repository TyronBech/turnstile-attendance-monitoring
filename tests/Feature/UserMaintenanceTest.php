<?php

use App\Models\EmployeeDetail;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

/* ------------------------------------------------------------------
 * Authorization
 * ----------------------------------------------------------------*/

test('guests are redirected to the login page', function () {
    $response = $this->get(route('user-maintenance.users.index'));
    $response->assertRedirect(route('login'));
});

test('authenticated users without view_users permission get 403', function () {
    $user = User::factory()->withoutStudentProfile()->create();
    $this->actingAs($user);

    $response = $this->get(route('user-maintenance.users.index'));
    $response->assertStatus(403);
});

/* ------------------------------------------------------------------
 * Index — page loads
 * ----------------------------------------------------------------*/

test('index page renders for authorized users with students tab by default', function () {
    $admin = User::factory()->withoutStudentProfile()->create();
    $admin->givePermissionTo('view_users');
    $this->actingAs($admin);

    $student = User::factory()->create();

    $response = $this->get(route('user-maintenance.users.index'));
    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('user-maintenance/users')
            ->has('users')
            ->where('tab', 'students')
            ->where('search', '')
        );
});

test('index page shows employees tab when requested', function () {
    $admin = User::factory()->withoutStudentProfile()->create();
    $admin->givePermissionTo('view_users');
    $this->actingAs($admin);

    $employee = User::factory()->withoutStudentProfile()->create();
    EmployeeDetail::factory()->create(['user_id' => $employee->id]);

    $response = $this->get(route('user-maintenance.users.index', ['tab' => 'employees']));
    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('user-maintenance/users')
            ->where('tab', 'employees')
        );
});

/* ------------------------------------------------------------------
 * Index — search
 * ----------------------------------------------------------------*/

test('search filters student results by name', function () {
    $admin = User::factory()->withoutStudentProfile()->create();
    $admin->givePermissionTo('view_users');
    $this->actingAs($admin);

    $matchingStudent = User::factory()->create(['first_name' => 'UniqueTestName']);

    $otherStudent = User::factory()->create(['first_name' => 'OtherPerson']);

    $response = $this->get(route('user-maintenance.users.index', ['search' => 'UniqueTestName']));
    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('users.data', 1)
        );
});

/* ------------------------------------------------------------------
 * Store — create user
 * ----------------------------------------------------------------*/

test('authorized users can create a student', function () {
    $admin = User::factory()->withoutStudentProfile()->create();
    $admin->givePermissionTo(['view_users', 'create_user']);
    $this->actingAs($admin);

    $response = $this->post(route('user-maintenance.users.store'), [
        'first_name' => 'Test',
        'middle_name' => 'M',
        'last_name' => 'Student',
        'rfid' => 'RFID-TEST-001',
        'email' => 'test.student@school.edu',
        'status' => true,
        'user_type' => 'student',
        'id_number' => 'STU-0001',
        'level' => 'Grade 10',
        'section' => 'Section A',
        'guardian_name' => 'Parent Name',
        'guardian_contact_number' => '09171234567',
    ]);

    $response->assertRedirect();

    $this->assertDatabaseHas('usr_users', [
        'first_name' => 'Test',
        'last_name' => 'Student',
        'rfid' => 'RFID-TEST-001',
        'email' => 'test.student@school.edu',
    ]);

    $this->assertDatabaseHas('usr_student_details', [
        'id_number' => 'STU-0001',
        'level' => 'Grade 10',
        'section' => 'Section A',
    ]);
});

test('authorized users can create an employee', function () {
    $admin = User::factory()->withoutStudentProfile()->create();
    $admin->givePermissionTo(['view_users', 'create_user']);
    $this->actingAs($admin);

    $response = $this->post(route('user-maintenance.users.store'), [
        'first_name' => 'Test',
        'middle_name' => '',
        'last_name' => 'Employee',
        'rfid' => 'RFID-EMP-001',
        'email' => 'test.emp@school.edu',
        'status' => true,
        'user_type' => 'employee',
        'employee_id' => 'EMP-0001',
        'employee_role' => 'Teacher',
    ]);

    $response->assertRedirect();

    $this->assertDatabaseHas('usr_users', [
        'first_name' => 'Test',
        'last_name' => 'Employee',
    ]);

    $this->assertDatabaseHas('usr_employee_details', [
        'employee_id' => 'EMP-0001',
        'employee_role' => 'Teacher',
    ]);
});

test('users without create_user permission cannot store', function () {
    $admin = User::factory()->withoutStudentProfile()->create();
    $admin->givePermissionTo('view_users');
    $this->actingAs($admin);

    $response = $this->post(route('user-maintenance.users.store'), [
        'first_name' => 'Blocked',
        'last_name' => 'User',
        'rfid' => 'RFID-BLOCK',
        'email' => 'blocked@school.edu',
        'status' => true,
        'user_type' => 'student',
        'id_number' => 'BLK-001',
        'level' => 'Grade 1',
        'section' => 'A',
        'guardian_name' => 'Guardian',
        'guardian_contact_number' => '0000',
    ]);

    $response->assertStatus(403);
});

/* ------------------------------------------------------------------
 * Update — edit user
 * ----------------------------------------------------------------*/

test('authorized users can update a student', function () {
    $admin = User::factory()->withoutStudentProfile()->create();
    $admin->givePermissionTo(['view_users', 'edit_user']);
    $this->actingAs($admin);

    $student = User::factory()->create([
        'first_name' => 'OldFirst',
        'student_id' => 'OLD-001',
    ]);

    $response = $this->put(route('user-maintenance.users.update', $student), [
        'first_name' => 'NewFirst',
        'middle_name' => '',
        'last_name' => $student->last_name,
        'rfid' => $student->rfid,
        'email' => $student->email,
        'status' => true,
        'user_type' => 'student',
        'id_number' => 'NEW-001',
        'level' => 'Grade 11',
        'section' => 'B',
        'guardian_name' => 'New Guardian',
        'guardian_contact_number' => '09999999999',
    ]);

    $response->assertRedirect();

    $this->assertDatabaseHas('usr_users', [
        'id' => $student->id,
        'first_name' => 'NewFirst',
    ]);

    $this->assertDatabaseHas('usr_student_details', [
        'user_id' => $student->id,
        'id_number' => 'NEW-001',
    ]);
});

/* ------------------------------------------------------------------
 * Destroy — delete user
 * ----------------------------------------------------------------*/

test('authorized users can delete a user', function () {
    $admin = User::factory()->withoutStudentProfile()->create();
    $admin->givePermissionTo(['view_users', 'delete_user']);
    $this->actingAs($admin);

    $student = User::factory()->create();

    $response = $this->delete(route('user-maintenance.users.destroy', $student));
    $response->assertRedirect();

    $this->assertSoftDeleted('usr_users', ['id' => $student->id]);
});

test('users without delete_user permission cannot destroy', function () {
    $admin = User::factory()->withoutStudentProfile()->create();
    $admin->givePermissionTo('view_users');
    $this->actingAs($admin);

    $student = User::factory()->create();

    $response = $this->delete(route('user-maintenance.users.destroy', $student));
    $response->assertStatus(403);
});

/* ------------------------------------------------------------------
 * Show — view user details (JSON)
 * ----------------------------------------------------------------*/

test('show returns user details as JSON', function () {
    $admin = User::factory()->withoutStudentProfile()->create();
    $admin->givePermissionTo('view_users');
    $this->actingAs($admin);

    $student = User::factory()->create(['student_id' => 'SHOW-001']);

    $response = $this->getJson(route('user-maintenance.users.show', $student));
    $response->assertOk()
        ->assertJsonPath('data.id', $student->id)
        ->assertJsonPath('data.student_detail.id_number', 'SHOW-001');
});
