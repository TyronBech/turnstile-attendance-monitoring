<?php

use App\Models\ImportLog;
use App\Models\StudentDetail;
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
 * Helper to generate a valid Excel spreadsheet for student import.
 */
function createStudentExcel(array $headers, array $rows, string $filename = 'students.xlsx'): UploadedFile
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

    $tempFile = tempnam(sys_get_temp_dir(), 'student_import_test_');
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

test('guest cannot access student import routes', function () {
    $this->get(route('imports.students.index'))->assertRedirect(route('login'));
    $this->post(route('imports.students.store'))->assertRedirect(route('login'));
    $this->get(route('imports.students.template'))->assertRedirect(route('login'));
});

test('user without import_users permission cannot access student import routes', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->get(route('imports.students.index'))->assertStatus(403);
    $this->post(route('imports.students.store'))->assertStatus(403);
    $this->get(route('imports.students.template'))->assertStatus(403);
});

test('authorized user can visit student import index', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('import_users');
    $this->actingAs($user);

    // Create a mock import log
    ImportLog::create([
        'user_id' => $user->id,
        'import_type' => 'student',
        'original_filename' => 'old_students.xlsx',
        'status' => 'completed',
        'total_rows' => 5,
        'processed_rows' => 5,
        'failed_rows' => 0,
    ]);

    $this->get(route('imports.students.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('imports/students')
            ->has('imports')
            ->where('imports.0.original_filename', 'old_students.xlsx')
        );
});

test('authorized user can download student import template', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('import_users');
    $this->actingAs($user);

    $response = $this->get(route('imports.students.template'));
    $response->assertHeader('content-disposition', 'attachment; filename=student-import-template.xlsx');
});

test('authorized user can import a valid student Excel sheet', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('import_users');
    $this->actingAs($user);

    $headers = [
        'rfid', 'first_name', 'middle_name', 'last_name', 'email', 'id_number', 'level', 'section', 'guardian_name', 'guardian_contact_number',
    ];
    $rows = [
        ['rfid-123', 'John', 'A', 'Doe', 'john.doe@example.com', 'STU-1234', 'Grade 10', 'Section Blue', 'Papa Doe', '0917-123-4567'],
        ['rfid-456', 'Jane', '', 'Smith', 'jane.smith@example.com', 'STU-5678', 'Grade 11', 'Section Green', 'Mama Smith', '0918-765-4321'],
    ];

    $file = createStudentExcel($headers, $rows);

    $response = $this->post(route('imports.students.store'), [
        'file' => $file,
    ]);

    $response->assertOk()
        ->assertJsonStructure(['import_log_id', 'message']);

    $importLogId = $response->json('import_log_id');

    // Assert ImportLog status
    $this->assertDatabaseHas('import_logs', [
        'id' => $importLogId,
        'import_type' => 'student',
        'status' => 'completed',
        'total_rows' => 2,
        'processed_rows' => 2,
        'failed_rows' => 0,
    ]);

    // Assert Users were created
    $this->assertDatabaseHas('usr_users', [
        'rfid' => 'rfid-123',
        'first_name' => 'John',
        'middle_name' => 'A',
        'last_name' => 'Doe',
        'email' => 'john.doe@example.com',
    ]);

    $this->assertDatabaseHas('usr_users', [
        'rfid' => 'rfid-456',
        'first_name' => 'Jane',
        'middle_name' => null,
        'last_name' => 'Smith',
        'email' => 'jane.smith@example.com',
    ]);

    // Assert StudentDetails were created
    $this->assertDatabaseHas('usr_student_details', [
        'id_number' => 'STU-1234',
        'level' => 'Grade 10',
        'section' => 'Section Blue',
        'guardian_name' => 'Papa Doe',
        'guardian_contact_number' => '0917-123-4567',
    ]);

    $this->assertDatabaseHas('usr_student_details', [
        'id_number' => 'STU-5678',
        'level' => 'Grade 11',
        'section' => 'Section Green',
        'guardian_name' => 'Mama Smith',
        'guardian_contact_number' => '0918-765-4321',
    ]);
});

test('duplicate id_number updates existing student instead of creating a new one', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('import_users');
    $this->actingAs($user);

    // Pre-create a student without the factory creating a profile
    $existingUser = User::factory()->withoutStudentProfile()->create([
        'rfid' => 'rfid-old',
        'first_name' => 'Original',
        'last_name' => 'Student',
        'email' => 'original@example.com',
    ]);
    StudentDetail::create([
        'user_id' => $existingUser->id,
        'id_number' => 'STU-9999',
        'level' => 'Grade 9',
        'section' => 'Section Yellow',
        'guardian_name' => 'Old Guardian',
        'guardian_contact_number' => '12345',
        'active_id_number' => 'STU-9999',
    ]);

    $headers = [
        'rfid', 'first_name', 'middle_name', 'last_name', 'email', 'id_number', 'level', 'section', 'guardian_name', 'guardian_contact_number',
    ];
    $rows = [
        ['rfid-new', 'UpdatedName', 'B', 'Student', 'updated@example.com', 'STU-9999', 'Grade 10', 'Section Blue', 'New Guardian', '54321'],
    ];

    $file = createStudentExcel($headers, $rows);

    $response = $this->post(route('imports.students.store'), [
        'file' => $file,
    ]);

    $response->assertOk();

    $importLogId = $response->json('import_log_id');
    $importLog = ImportLog::findOrFail($importLogId);
    expect($importLog->status)->toBe('completed');
    expect($importLog->failed_rows)->toBe(0);

    // Verify existing student was updated
    $this->assertDatabaseHas('usr_users', [
        'id' => $existingUser->id,
        'rfid' => 'rfid-new',
        'first_name' => 'UpdatedName',
        'email' => 'updated@example.com',
    ]);

    $this->assertDatabaseHas('usr_student_details', [
        'user_id' => $existingUser->id,
        'id_number' => 'STU-9999',
        'level' => 'Grade 10',
        'section' => 'Section Blue',
        'guardian_name' => 'New Guardian',
        'guardian_contact_number' => '54321',
    ]);

    // Ensure we don't have multiple entries with that student ID or RFID
    expect(User::where('rfid', 'rfid-new')->count())->toBe(1);
    expect(StudentDetail::where('id_number', 'STU-9999')->count())->toBe(1);
});

test('validation errors are skipped and logged', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('import_users');
    $this->actingAs($user);

    $headers = [
        'rfid', 'first_name', 'middle_name', 'last_name', 'email', 'id_number', 'level', 'section', 'guardian_name', 'guardian_contact_number',
    ];
    $rows = [
        // Valid row
        ['rfid-111', 'Valid', '', 'User', 'valid@example.com', 'STU-1111', 'Grade 10', 'Sec A', 'Guardian', '0912'],
        // Invalid row: missing rfid, first_name, email, level
        ['', '', '', 'User', 'invalid-email', 'STU-2222', '', 'Sec B', 'Guardian', '0912'],
    ];

    $file = createStudentExcel($headers, $rows);

    $response = $this->post(route('imports.students.store'), [
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
    expect($errors[0]['row'])->toBe(3); // row index is 1-based, plus 1 for header => row 3 is the second row
    expect($errors[0]['message'])->toContain('RFID is required.');
    expect($errors[0]['message'])->toContain('First name is required.');
    expect($errors[0]['message'])->toContain('Email must be a valid email address.');
    expect($errors[0]['message'])->toContain('Level/Grade is required.');
});

test('progress endpoint returns correct progress response', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('import_users');
    $this->actingAs($user);

    $log = ImportLog::create([
        'user_id' => $user->id,
        'import_type' => 'student',
        'original_filename' => 'progress_test.xlsx',
        'status' => 'processing',
        'total_rows' => 10,
        'processed_rows' => 4,
        'failed_rows' => 2,
        'error_summary' => [['row' => 2, 'message' => 'Error']],
    ]);

    $response = $this->get(route('imports.students.progress', ['importLog' => $log->id]));

    $response->assertOk()
        ->assertJson([
            'id' => $log->id,
            'totalRows' => 10,
            'processedRows' => 4,
            'failedRows' => 2,
            'progress' => 60, // (4 + 2) / 10 = 60%
            'status' => 'processing',
            'finished' => false,
            'errors' => [['row' => 2, 'message' => 'Error']],
        ]);
});
