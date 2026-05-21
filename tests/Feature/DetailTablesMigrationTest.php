<?php

use Illuminate\Support\Facades\Schema;

it('creates the student and employee detail tables with student-only guardian columns', function (): void {
    expect(Schema::hasTable('usr_users'))->toBeTrue();
    expect(Schema::hasTable('users'))->toBeFalse();
    expect(Schema::hasTable('usr_student_details'))->toBeTrue();
    expect(Schema::hasTable('usr_employee_details'))->toBeTrue();
    expect(Schema::hasColumn('usr_users', 'student_id'))->toBeFalse();
    expect(Schema::hasColumn('usr_users', 'guardian_name'))->toBeFalse();
    expect(Schema::hasColumn('usr_users', 'guardian_contact_number'))->toBeFalse();

    expect(Schema::hasColumns('usr_student_details', [
        'user_id',
        'id_number',
        'level',
        'section',
        'guardian_name',
        'guardian_contact_number',
        'active_id_number',
        'created_at',
        'updated_at',
        'deleted_at',
    ]))->toBeTrue();

    expect(Schema::hasColumns('usr_employee_details', [
        'user_id',
        'employee_id',
        'employee_role',
        'active_employee_id',
        'created_at',
        'updated_at',
        'deleted_at',
    ]))->toBeTrue();

    expect(Schema::hasColumn('usr_employee_details', 'guardian_name'))->toBeFalse();
    expect(Schema::hasColumn('usr_employee_details', 'guardian_contact_number'))->toBeFalse();
});
