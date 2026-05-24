<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class TestUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $password = Hash::make('password');

        // 1. Create Super Admin Employee
        $superAdmin = User::updateOrCreate(
            ['email' => 'super_admin@gmail.com'],
            [
                'first_name' => 'Super',
                'last_name' => 'Admin',
                'password' => $password,
                'status' => true,
                'rfid' => '54636C06',
            ]
        );
        
        $superAdmin->syncRoles([Role::SuperAdmin]);

        // Insert employee details directly into DB if model doesn't exist
        DB::table('usr_employee_details')->updateOrInsert(
            ['user_id' => $superAdmin->id],
            [
                'employee_id' => 'EMP-001',
                'employee_role' => 'Super Admin',
                'active_employee_id' => 'EMP-001',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // 2. Create Students
        $studentRfids = [
            '1AFE8FE1',
            '86006C06',
            '2A763035',
            'FA8E2F35',
            'AAC51B35',
        ];

        foreach ($studentRfids as $index => $rfid) {
            $studentId = 'TEST-' . (1000 + $index);
            
            $student = User::updateOrCreate(
                ['rfid' => $rfid],
                [
                    'first_name' => 'Test',
                    'last_name' => 'Student ' . ($index + 1),
                    'email' => "teststudent{$index}@example.com",
                    'password' => $password,
                    'status' => true,
                    // Setting student_id here automatically triggers syncPendingStudentDetailAttributes
                    'student_id' => $studentId, 
                ]
            );

            // Update student level and section
            if ($student->studentDetail) {
                $student->studentDetail->update([
                    'level' => 'Grade 10',
                    'section' => 'Section A',
                ]);
            }
        }

        $this->command->info('Test students and super admin employee seeded successfully!');
    }
}
