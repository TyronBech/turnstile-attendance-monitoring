<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * Available local mock profile images.
     *
     * @var array<int, string>
     */
    private const PROFILE_IMAGES = [
        'profile-images/default.svg',
        'profile-images/juan-dela-cruz.svg',
        'profile-images/maria-garcia.svg',
        'profile-images/pedro-bautista.svg',
    ];

    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'student_id' => fake()->unique()->numerify('##########'),
            'rfid' => fake()->unique()->hexColor(), // Or some other way to get a unique string
            'first_name' => fake()->firstName(),
            'middle_name' => fake()->lastName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'profile_image' => fake()->randomElement(self::PROFILE_IMAGES),
            'guardian_name' => fake()->name(),
            'guardian_contact_number' => fake()->phoneNumber(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
            'status' => true,
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Indicate that the model has two-factor authentication configured.
     */
    public function withTwoFactor(): static
    {
        return $this->state(fn (array $attributes) => [
            'two_factor_secret' => encrypt('secret'),
            'two_factor_recovery_codes' => encrypt(json_encode(['recovery-code-1'])),
            'two_factor_confirmed_at' => now(),
        ]);
    }

    /**
     * No guardian phone — SNCS will not queue Semaphore SMS for this user.
     */
    public function withoutGuardianSms(): static
    {
        return $this->state(fn (array $attributes): array => [
            'guardian_contact_number' => '',
        ]);
    }

    /**
     * Do not create a student profile payload for this user.
     */
    public function withoutStudentProfile(): static
    {
        return $this->state(fn (array $attributes): array => [
            'student_id' => null,
            'guardian_name' => null,
            'guardian_contact_number' => null,
        ]);
    }
}
