<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class StudentSeeder extends Seeder
{
    /**
     * Seed 3 sample students with known RFID tags for Postman testing.
     *
     * These RFID values simulate what the ESP32's MFRC522 reader
     * would send — typically hex strings like "A1B2C3D4".
     */
    public function run(): void
    {
        $students = [
            [
                'student_id' => '2024-00001',
                'rfid' => 'A1B2C3D4',
                'first_name' => 'Juan',
                'middle_name' => 'Santos',
                'last_name' => 'Dela Cruz',
                'email' => 'juan.delacruz@university.edu',
                'level' => 'Grade 11',
                'section' => 'STEM A',
                'guardian_name' => 'Maria Dela Cruz',
                'guardian_contact_number' => '09171234567',
                'password' => 'password',
                'status' => true,
            ],
            [
                'student_id' => '2024-00002',
                'rfid' => 'E5F6A7B8',
                'first_name' => 'Maria',
                'middle_name' => 'Reyes',
                'last_name' => 'Garcia',
                'email' => 'maria.garcia@university.edu',
                'level' => 'Grade 12',
                'section' => 'ABM 1',
                'guardian_name' => 'Jose Garcia',
                'guardian_contact_number' => '09181234567',
                'password' => 'password',
                'status' => true,
            ],
            [
                'student_id' => '2024-00003',
                'rfid' => 'C9D0E1F2',
                'first_name' => 'Pedro',
                'middle_name' => 'Lopez',
                'last_name' => 'Bautista',
                'email' => 'pedro.bautista@university.edu',
                'level' => 'Grade 10',
                'section' => 'Section 2',
                'guardian_name' => 'Ana Bautista',
                'guardian_contact_number' => '09191234567',
                'password' => 'password',
                'status' => true,
            ],
        ];

        foreach ($students as $student) {
            $user = User::updateOrCreate(
                ['email' => $student['email']],
                [
                    'rfid' => $student['rfid'],
                    'first_name' => $student['first_name'],
                    'middle_name' => $student['middle_name'],
                    'last_name' => $student['last_name'],
                    'email' => $student['email'],
                    'password' => $student['password'],
                    'status' => $student['status'],
                ],
            );

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

        $this->command->info('');
        $this->command->info('╔══════════════════════════════════════════════════════════════╗');
        $this->command->info('║                   SAMPLE STUDENTS SEEDED                    ║');
        $this->command->info('╠══════════════════════════════════════════════════════════════╣');
        $this->command->info('║  Student ID    │ RFID       │ Name                          ║');
        $this->command->info('║  ─────────────────────────────────────────────────────────── ║');
        $this->command->info('║  2024-00001    │ A1B2C3D4   │ Juan Santos Dela Cruz          ║');
        $this->command->info('║  2024-00002    │ E5F6A7B8   │ Maria Reyes Garcia             ║');
        $this->command->info('║  2024-00003    │ C9D0E1F2   │ Pedro Lopez Bautista           ║');
        $this->command->info('╚══════════════════════════════════════════════════════════════╝');
        $this->command->info('');
    }
}
