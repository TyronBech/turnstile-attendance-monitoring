<?php

use App\Models\EmployeeDetail;
use App\Models\ImportLog;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Http\UploadedFile;
use Inertia\Testing\AssertableInertia as Assert;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

/**
 * Helper to generate a valid Excel spreadsheet for faculty import.
 */
function createFacultyExcel(array $headers, array $rows, string $filename = 'faculties.xlsx'): UploadedFile
{
    $spreadsheet = new Spreadsheet;
    $sheet = $spreadsheet->getActiveSheet();

    // Write headers
    foreach ($headers as $colIndex => $header) {
        $sheet->setCellValue([$colIndex + 1, 1], $header);
    }

    // Write rows
    foreach ($rows as $rowIndex => $row) {
        foreach ($row as $colIndex => $value) {
            $sheet->setCellValue([$colIndex + 1, $rowIndex + 2], $value);
        }
    }

    $tempFile = tempnam(sys_get_temp_dir(), 'faculty_import_test_');
    $writer = new Xlsx($spreadsheet);
    $writer->save($tempFile);

    return new UploadedFile(
        $tempFile,
        $filename,
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        null,
        true // test mode
    );
}

test('guest cannot access faculty/staff import routes', function () {
    $this->get(route('imports.faculties-staffs.index'))->assertRedirect(route('login'));
    $this->post(route('imports.faculties-staffs.store'))->assertRedirect(route('login'));
    $this->get(route('imports.faculties-staffs.template'))->assertRedirect(route('login'));
});

test('user without import_users permission cannot access faculty import routes', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->get(route('imports.faculties-staffs.index'))->assertStatus(403);
    $this->post(route('imports.faculties-staffs.store'))->assertStatus(403);
    $this->get(route('imports.faculties-staffs.template'))->assertStatus(403);
});

test('authorized user can visit faculty import index', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('import_users');
    $this->actingAs($user);

    // Create a mock import log
    ImportLog::create([
        'user_id' => $user->id,
        'import_type' => 'faculty_staff',
        'original_filename' => 'old_faculty.xlsx',
        'status' => 'completed',
        'total_rows' => 5,
        'processed_rows' => 5,
        'failed_rows' => 0,
    ]);

    $this->get(route('imports.faculties-staffs.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('imports/faculties-staffs')
            ->has('imports')
            ->where('imports.0.original_filename', 'old_faculty.xlsx')
        );
});

test('authorized user can download faculty import template', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('import_users');
    $this->actingAs($user);

    $response = $this->get(route('imports.faculties-staffs.template'));
    $response->assertHeader('content-disposition', 'attachment; filename=faculty-staff-import-template.xlsx');
});

test('authorized user can import a valid faculty Excel sheet', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('import_users');
    $this->actingAs($user);

    $headers = [
        'rfid', 'first_name', 'middle_name', 'last_name', 'email', 'employee_id', 'employee_role',
    ];
    $rows = [
        ['rfid-999', 'Professor', 'X', 'Charles', 'charles@example.com', 'EMP-111', 'Faculty'],
        ['rfid-888', 'Wolverine', '', 'Logan', 'logan@example.com', 'EMP-222', 'Staff'],
    ];

    $file = createFacultyExcel($headers, $rows);

    $response = $this->post(route('imports.faculties-staffs.store'), [
        'file' => $file,
    ]);

    $response->assertOk()
        ->assertJsonStructure(['import_log_id', 'message']);

    $importLogId = $response->json('import_log_id');

    // Assert ImportLog status
    $this->assertDatabaseHas('import_logs', [
        'id' => $importLogId,
        'import_type' => 'faculty_staff',
        'status' => 'completed',
        'total_rows' => 2,
        'processed_rows' => 2,
        'failed_rows' => 0,
    ]);

    // Assert Users were created
    $this->assertDatabaseHas('usr_users', [
        'rfid' => 'rfid-999',
        'first_name' => 'Professor',
        'middle_name' => 'X',
        'last_name' => 'Charles',
        'email' => 'charles@example.com',
    ]);

    $this->assertDatabaseHas('usr_users', [
        'rfid' => 'rfid-888',
        'first_name' => 'Wolverine',
        'middle_name' => null,
        'last_name' => 'Logan',
        'email' => 'logan@example.com',
    ]);

    // Assert EmployeeDetails were created
    $this->assertDatabaseHas('usr_employee_details', [
        'employee_id' => 'EMP-111',
        'employee_role' => 'Faculty',
    ]);

    $this->assertDatabaseHas('usr_employee_details', [
        'employee_id' => 'EMP-222',
        'employee_role' => 'Staff',
    ]);
});

test('duplicate employee_id updates existing employee instead of creating a new one', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('import_users');
    $this->actingAs($user);

    // Pre-create an employee
    $existingUser = User::factory()->withoutStudentProfile()->create([
        'rfid' => 'rfid-emp-old',
        'first_name' => 'Old',
        'last_name' => 'Professor',
        'email' => 'old.professor@example.com',
    ]);
    EmployeeDetail::create([
        'user_id' => $existingUser->id,
        'employee_id' => 'EMP-9999',
        'employee_role' => 'Faculty',
        'active_employee_id' => 'EMP-9999',
    ]);

    $headers = [
        'rfid', 'first_name', 'middle_name', 'last_name', 'email', 'employee_id', 'employee_role',
    ];
    $rows = [
        ['rfid-emp-new', 'NewName', 'B', 'Professor', 'new.professor@example.com', 'EMP-9999', 'Admin'],
    ];

    $file = createFacultyExcel($headers, $rows);

    $response = $this->post(route('imports.faculties-staffs.store'), [
        'file' => $file,
    ]);

    $response->assertOk();

    $importLogId = $response->json('import_log_id');
    $importLog = ImportLog::findOrFail($importLogId);
    expect($importLog->status)->toBe('completed');
    expect($importLog->failed_rows)->toBe(0);

    // Verify existing employee was updated
    $this->assertDatabaseHas('usr_users', [
        'id' => $existingUser->id,
        'rfid' => 'rfid-emp-new',
        'first_name' => 'NewName',
        'email' => 'new.professor@example.com',
    ]);

    $this->assertDatabaseHas('usr_employee_details', [
        'user_id' => $existingUser->id,
        'employee_id' => 'EMP-9999',
        'employee_role' => 'Admin',
    ]);

    // Ensure we don't have multiple entries with that employee ID or RFID
    expect(User::where('rfid', 'rfid-emp-new')->count())->toBe(1);
    expect(EmployeeDetail::where('employee_id', 'EMP-9999')->count())->toBe(1);
});

test('validation errors are skipped and logged for faculty', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('import_users');
    $this->actingAs($user);

    $headers = [
        'rfid', 'first_name', 'middle_name', 'last_name', 'email', 'employee_id', 'employee_role',
    ];
    $rows = [
        // Valid row
        ['rfid-777', 'Valid', '', 'Emp', 'valid@example.com', 'EMP-777', 'Faculty'],
        // Invalid row: missing employee_id
        ['rfid-666', 'Invalid', '', 'Emp', 'invalid@example.com', '', 'Staff'],
    ];

    $file = createFacultyExcel($headers, $rows);

    $response = $this->post(route('imports.faculties-staffs.store'), [
        'file' => $file,
    ]);

    $response->assertOk();
    $importLogId = $response->json('import_log_id');

    // Should complete with errors
    $importLog = ImportLog::findOrFail($importLogId);
    expect($importLog->status)->toBe('completed_with_errors');
    expect($importLog->total_rows)->toBe(2);
    expect($importLog->processed_rows)->toBe(1);
    expect($importLog->failed_rows)->toBe(1);

    $errors = $importLog->error_summary;
    expect($errors)->toHaveCount(1);
    expect($errors[0]['row'])->toBe(3); // row 3 is the second row
    expect($errors[0]['message'])->toContain('Employee ID is required.');
});

test('progress endpoint returns correct progress response for faculty', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('import_users');
    $this->actingAs($user);

    $log = ImportLog::create([
        'user_id' => $user->id,
        'import_type' => 'faculty_staff',
        'original_filename' => 'progress_test.xlsx',
        'status' => 'processing',
        'total_rows' => 10,
        'processed_rows' => 4,
        'failed_rows' => 2,
        'error_summary' => [['row' => 2, 'message' => 'Error']],
    ]);

    $response = $this->get(route('imports.faculties-staffs.progress', ['importLog' => $log->id]));

    $response->assertOk()
        ->assertJson([
            'id' => $log->id,
            'totalRows' => 10,
            'processedRows' => 4,
            'failedRows' => 2,
            'progress' => 60,
            'status' => 'processing',
            'finished' => false,
            'errors' => [['row' => 2, 'message' => 'Error']],
        ]);
});
