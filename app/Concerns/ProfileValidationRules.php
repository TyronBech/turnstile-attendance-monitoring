<?php

namespace App\Concerns;

use App\Models\StudentDetail;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

trait ProfileValidationRules
{
    /**
     * Get the validation rules used to validate user profiles.
     *
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function profileRules(?int $userId = null): array
    {
        return [
            'student_id' => [
                'required',
                'string',
                'max:255',
                $userId === null
                    ? Rule::unique(StudentDetail::class, 'id_number')
                    : Rule::unique(StudentDetail::class, 'id_number')->ignore($userId, 'user_id'),
            ],
            'rfid' => ['required', 'string', 'max:255', $userId === null ? Rule::unique(User::class) : Rule::unique(User::class)->ignore($userId)],
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => $this->emailRules($userId),
            'guardian_name' => ['required', 'string', 'max:255'],
            'guardian_contact_number' => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * Get the validation rules used to validate user emails.
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function emailRules(?int $userId = null): array
    {
        return [
            'required',
            'string',
            'email',
            'max:255',
            $userId === null
                ? Rule::unique(User::class)
                : Rule::unique(User::class)->ignore($userId),
        ];
    }
}
