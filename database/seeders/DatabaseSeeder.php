<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed the admin/test user
        $testUser = User::query()->updateOrCreate(
            ['email' => 'test@example.com'],
            [
                'rfid' => '0000000000',
                'first_name' => 'Test',
                'middle_name' => null,
                'last_name' => 'User',
                'profile_image' => null,
                'password' => 'password',
                'status' => true,
            ],
        );

        $testUser->studentDetail()->delete();
        $testUser->employeeDetail()->delete();

        // Seed roles, people, and turnstile device with Sanctum token.
        $this->call([
            RolesAndPermissionsSeeder::class,
            StudentSeeder::class,
            EmployeeSeeder::class,
            TurnstileSeeder::class,
            AttendanceLogSeeder::class,
        ]);
    }
}
