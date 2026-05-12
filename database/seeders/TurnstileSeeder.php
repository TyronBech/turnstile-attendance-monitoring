<?php

namespace Database\Seeders;

use App\Models\Turnstile;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class TurnstileSeeder extends Seeder
{
    /**
     * Seed the turnstile devices and generate Sanctum tokens.
     *
     * The token printed to the console is what you paste into Postman's
     * Authorization → Bearer Token field (or flash onto the ESP32).
     */
    public function run(): void
    {
        $turnstile = Turnstile::firstOrCreate(
            ['name' => 'Main Gate Turnstile'],
            [
                'location' => 'Main Entrance',
                'ip_address' => '192.168.1.100',
                'status' => true,
            ],
        );

        // Revoke any existing tokens so we get a fresh one each seed
        $turnstile->tokens()->delete();

        $token = $turnstile->createToken(
            name: 'esp32-main-gate',
            abilities: ['attendance:scan'],
        )->plainTextToken;

        $this->command->info('');
        $this->command->info('╔══════════════════════════════════════════════════════════════╗');
        $this->command->info('║              TURNSTILE SANCTUM TOKEN GENERATED              ║');
        $this->command->info('╠══════════════════════════════════════════════════════════════╣');
        $this->command->info("║  Device:   {$turnstile->name}");
        $this->command->info("║  Location: {$turnstile->location}");
        $this->command->info("║  Token:    {$token}");
        $this->command->info('╠══════════════════════════════════════════════════════════════╣');
        $this->command->info('║  Postman Setup:                                             ║');
        $this->command->info('║  1. Authorization → Bearer Token → paste the token above    ║');
        $this->command->info('║  2. Headers → Accept: application/json                      ║');
        $this->command->info('║  3. Headers → Content-Type: application/json                ║');
        $this->command->info('║  4. POST http://localhost:8000/api/v1/attendance/scan        ║');
        $this->command->info('║     Body: { "rfid": "<student_rfid>" }                      ║');
        $this->command->info('╚══════════════════════════════════════════════════════════════╝');
        $this->command->info('');

        Log::info('Turnstile token generated', [
            'turnstile_id' => $turnstile->id,
            'turnstile_name' => $turnstile->name,
        ]);
    }
}
