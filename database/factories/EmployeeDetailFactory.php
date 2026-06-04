<?php

namespace Database\Factories;

use App\Models\EmployeeDetail;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmployeeDetail>
 */
class EmployeeDetailFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $employeeId = fake()->unique()->numerify('EMP-####');

        return [
            'user_id' => User::factory(),
            'employee_id' => $employeeId,
            'employee_role' => fake()->randomElement(['Teacher', 'Staff', 'Administrator', 'Registrar', 'Librarian']),
            'active_employee_id' => $employeeId,
        ];
    }
}
