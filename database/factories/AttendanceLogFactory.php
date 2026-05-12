<?php

namespace Database\Factories;

use App\Models\AttendanceLog;
use App\Models\Turnstile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AttendanceLog>
 */
class AttendanceLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'turnstile_id' => Turnstile::factory(),
            'action' => fake()->randomElement(['IN', 'OUT']),
            'scanned_at' => fake()->dateTimeBetween('-1 month', 'now'),
            'sms_status' => fake()->randomElement(['PENDING', 'SENT', 'FAILED']),
        ];
    }

    /**
     * Set the action to time-in.
     */
    public function timeIn(): static
    {
        return $this->state(fn (array $attributes): array => [
            'action' => 'IN',
        ]);
    }

    /**
     * Set the action to time-out.
     */
    public function timeOut(): static
    {
        return $this->state(fn (array $attributes): array => [
            'action' => 'OUT',
        ]);
    }
}
