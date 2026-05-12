<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class ScanRfidRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * The turnstile device is already authenticated via Sanctum middleware,
     * so we allow all authenticated requests through.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'rfid' => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * Get custom error messages for validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'rfid.required' => 'RFID tag data is required.',
            'rfid.string' => 'RFID tag data must be a string.',
        ];
    }
}
