<?php

namespace App\Http\Requests\Maintenance;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $tab = $this->input('user_type', 'student');
        /** @var User $user */
        $user = $this->route('user');

        $baseRules = [
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'rfid' => ['required', 'string', 'max:255', Rule::unique('usr_users', 'rfid')->ignore($user->id)],
            'email' => ['required', 'email', 'max:255', Rule::unique('usr_users', 'email')->ignore($user->id)],
            'status' => ['required', 'boolean'],
            'user_type' => ['required', 'in:student,employee'],
        ];

        $detailRules = $tab === 'student'
            ? [
                'id_number' => ['required', 'string', 'max:20', Rule::unique('usr_student_details', 'id_number')->ignore($user->studentDetail?->id)],
                'level' => ['required', 'string', 'max:15'],
                'section' => ['required', 'string', 'max:100'],
                'guardian_name' => ['required', 'string', 'max:255'],
                'guardian_contact_number' => ['required', 'string', 'max:255'],
            ]
            : [
                'employee_id' => ['required', 'string', 'max:50', Rule::unique('usr_employee_details', 'employee_id')->ignore($user->employeeDetail?->id)],
                'employee_role' => ['nullable', 'string', 'max:45'],
            ];

        return [...$baseRules, ...$detailRules];
    }
}
