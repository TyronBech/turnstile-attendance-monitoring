<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class StudentSeeder extends Seeder
{
    /**
     * Seed sample students for attendance display and RFID testing.
     */
    public function run(): void
    {
        $students = [
            [
                'student_id' => '2026-00002',
                'rfid' => 'E5F6A7B8',
                'first_name' => 'Tyron',
                'middle_name' => 'Panti',
                'last_name' => 'Bechayda',
                'email' => 'tyron.bechayda@university.edu',
                'profile_image' => 'profile-images/tyron.jpg',
                'level' => 'Grade 12',
                'section' => 'ABM 1',
                'guardian_name' => 'Ana Bechayda',
                'guardian_contact_number' => '09181234567',
                'password' => 'password',
                'status' => true,
            ],
            [
                'student_id' => '2026-00003',
                'rfid' => 'C9D0E1F2',
                'first_name' => 'Pedro',
                'middle_name' => 'Lopez',
                'last_name' => 'Bautista',
                'email' => 'pedro.bautista@university.edu',
                'profile_image' => null,
                'level' => 'Grade 10',
                'section' => 'Section 2',
                'guardian_name' => 'Ana Bautista',
                'guardian_contact_number' => '09191234567',
                'password' => 'password',
                'status' => true,
            ],
        ];

        foreach ($students as $student) {
            $user = User::query()
                ->where('email', $student['email'])
                ->orWhere('rfid', $student['rfid'])
                ->first() ?? new User;

            $user->fill([
                'rfid' => $student['rfid'],
                'first_name' => $student['first_name'],
                'middle_name' => $student['middle_name'],
                'last_name' => $student['last_name'],
                'email' => $student['email'],
                'profile_image' => $student['profile_image'],
                'password' => $student['password'],
                'status' => $student['status'],
            ]);
            $user->save();

            $user->employeeDetail()->delete();
            $user->studentDetail()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'id_number' => $student['student_id'],
                    'level' => $student['level'],
                    'section' => $student['section'],
                    'guardian_name' => $student['guardian_name'],
                    'guardian_contact_number' => $student['guardian_contact_number'],
                    'active_id_number' => $student['student_id'],
                ],
            );
        }
    }
}
