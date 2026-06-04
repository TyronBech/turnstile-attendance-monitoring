<?php

namespace Database\Factories;

use App\Models\StudentDetail;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudentDetail>
 */
class StudentDetailFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $idNumber = fake()->unique()->numerify('##########');

        return [
            'user_id' => User::factory(),
            'id_number' => $idNumber,
            'level' => fake()->randomElement(['Grade 7', 'Grade 8', 'Grade 9', 'Grade 10', 'Grade 11', 'Grade 12']),
            'section' => fake()->randomElement(['Section A', 'Section B', 'Section C', 'Section D']),
            'guardian_name' => fake()->name(),
            'guardian_contact_number' => fake()->phoneNumber(),
            'active_id_number' => $idNumber,
        ];
    }
}
