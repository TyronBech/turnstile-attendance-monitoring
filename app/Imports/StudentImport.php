<?php

namespace App\Imports;

use App\Models\StudentDetail;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Validators\Failure;

class StudentImport implements SkipsEmptyRows, SkipsOnFailure, ToCollection, WithChunkReading, WithHeadingRow, WithMultipleSheets, WithValidation
{
    use Importable;

    private int $processedCount = 0;

    private int $failedCount = 0;

    /** @var array<int, array<string, mixed>> */
    private array $errors = [];

    /**
     * Process a collection chunk of rows.
     *
     * @param  Collection<int, Collection<string, mixed>>  $rows
     */
    public function collection(Collection $rows): void
    {
        DB::transaction(function () use ($rows): void {
            foreach ($rows as $index => $row) {
                try {
                    $this->processRow($row, $index);
                    $this->processedCount++;
                } catch (\Throwable $e) {
                    $this->failedCount++;
                    $this->errors[] = [
                        'row' => $index + 2,
                        'id_number' => $row->get('id_number', ''),
                        'message' => $e->getMessage(),
                    ];

                    Log::warning('Student import row failed.', [
                        'row' => $index + 2,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        });
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'rfid' => ['required', 'max:255'],
            'first_name' => ['required', 'max:255'],
            'middle_name' => ['nullable', 'max:255'],
            'last_name' => ['required', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'id_number' => ['required', 'max:20'],
            'level' => ['required', 'max:15'],
            'section' => ['required', 'max:100'],
            'guardian_name' => ['required', 'max:255'],
            'guardian_contact_number' => ['required', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function customValidationMessages(): array
    {
        return [
            'rfid.required' => 'RFID is required.',
            'first_name.required' => 'First name is required.',
            'last_name.required' => 'Last name is required.',
            'email.required' => 'Email is required.',
            'email.email' => 'Email must be a valid email address.',
            'id_number.required' => 'Student ID number is required.',
            'level.required' => 'Level/Grade is required.',
            'section.required' => 'Section is required.',
            'guardian_name.required' => 'Guardian name is required.',
            'guardian_contact_number.required' => 'Guardian contact number is required.',
        ];
    }

    /**
     * Define when a row is considered empty.
     *
     * @param  array<string, mixed>  $row
     */
    public function isEmptyWhen(array $row): bool
    {
        $nonEmptyValues = array_filter($row, function ($value) {
            return $value !== null && trim((string) $value) !== '';
        });

        return empty($nonEmptyValues);
    }

    public function chunkSize(): int
    {
        return 500;
    }

    /**
     * Define the sheets to be processed during import.
     *
     * @return array<int, self>
     */
    public function sheets(): array
    {
        return [
            0 => $this,
        ];
    }

    public function getProcessedCount(): int
    {
        return $this->processedCount;
    }

    public function getFailedCount(): int
    {
        return $this->failedCount;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Process a single row: upsert User + StudentDetail by id_number.
     *
     * @param  Collection<string, mixed>  $row
     */
    private function processRow(Collection $row, int $index): void
    {
        $idNumber = (string) $row->get('id_number');

        $existingDetail = StudentDetail::query()
            ->where('id_number', $idNumber)
            ->first();

        if ($existingDetail !== null) {
            $user = $existingDetail->user;

            $user->update([
                'rfid' => (string) $row->get('rfid'),
                'first_name' => (string) $row->get('first_name'),
                'middle_name' => $row->get('middle_name') !== null && $row->get('middle_name') !== '' ? (string) $row->get('middle_name') : null,
                'last_name' => (string) $row->get('last_name'),
                'email' => (string) $row->get('email'),
            ]);

            $existingDetail->update([
                'level' => (string) $row->get('level'),
                'section' => (string) $row->get('section'),
                'guardian_name' => (string) $row->get('guardian_name'),
                'guardian_contact_number' => (string) $row->get('guardian_contact_number'),
                'active_id_number' => $idNumber,
            ]);

            return;
        }

        /** @var User $user */
        $user = User::query()->create([
            'rfid' => (string) $row->get('rfid'),
            'first_name' => (string) $row->get('first_name'),
            'middle_name' => $row->get('middle_name') !== null && $row->get('middle_name') !== '' ? (string) $row->get('middle_name') : null,
            'last_name' => (string) $row->get('last_name'),
            'email' => (string) $row->get('email'),
            'password' => Hash::make('password'),
            'status' => true,
        ]);

        $user->studentDetail()->create([
            'id_number' => $idNumber,
            'level' => (string) $row->get('level'),
            'section' => (string) $row->get('section'),
            'guardian_name' => (string) $row->get('guardian_name'),
            'guardian_contact_number' => (string) $row->get('guardian_contact_number'),
            'active_id_number' => $idNumber,
        ]);
    }

    /**
     * Handle row validation failure.
     */
    public function onFailure(Failure ...$failures): void
    {
        foreach ($failures as $failure) {
            $rowNumber = $failure->row();

            // Check if we already have an error for this row
            $existingIndex = null;
            foreach ($this->errors as $idx => $err) {
                if ($err['row'] === $rowNumber) {
                    $existingIndex = $idx;
                    break;
                }
            }

            $errorMessage = implode(' ', $failure->errors());

            if ($existingIndex !== null) {
                // Append the error message to the existing one
                $this->errors[$existingIndex]['message'] .= ' '.$errorMessage;
            } else {
                $this->failedCount++;
                $this->errors[] = [
                    'row' => $rowNumber,
                    'id_number' => (string) ($failure->values()['id_number'] ?? ''),
                    'message' => $errorMessage,
                ];
            }
        }
    }
}
