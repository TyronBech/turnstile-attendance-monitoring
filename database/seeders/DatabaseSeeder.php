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
        User::factory()->withoutStudentProfile()->create([
            'rfid' => '0000000000',
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@example.com',
        ]);

        // Seed sample students and turnstile device with Sanctum token
        $this->call([
            TestUserSeeder::class,
            StudentSeeder::class,
            TurnstileSeeder::class,
        ]);
    }
}
