<?php

namespace Database\Factories;

use App\Models\Turnstile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Turnstile>
 */
class TurnstileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Turnstile '.fake()->unique()->numberBetween(1, 100),
            'location' => fake()->randomElement(['Main Gate', 'Back Gate', 'Library Entrance', 'Gym Entrance']),
            'ip_address' => fake()->localIpv4(),
            'status' => true,
        ];
    }

    /**
     * Indicate the turnstile is inactive/disabled.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => false,
        ]);
    }
}
