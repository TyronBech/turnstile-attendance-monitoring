<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class UserMaintenanceService
{
    /**
     * Get a paginated, searchable list of users based on tab.
     */
    public function getPaginatedUsers(string $tab, string $search, int $perPage): LengthAwarePaginator
    {
        $query = User::query();

        if ($tab === 'employees') {
            $query->whereHas('employeeDetail')
                ->with('employeeDetail');
        } else {
            $tab = 'students';
            $query->whereHas('studentDetail')
                ->with('studentDetail');
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search, $tab): void {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('middle_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('rfid', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");

                if ($tab === 'students') {
                    $q->orWhereHas('studentDetail', function ($sq) use ($search): void {
                        $sq->where('id_number', 'like', "%{$search}%")
                            ->orWhere('level', 'like', "%{$search}%")
                            ->orWhere('section', 'like', "%{$search}%")
                            ->orWhere('guardian_name', 'like', "%{$search}%")
                            ->orWhere('guardian_contact_number', 'like', "%{$search}%");
                    });
                } else {
                    $q->orWhereHas('employeeDetail', function ($eq) use ($search): void {
                        $eq->where('employee_id', 'like', "%{$search}%")
                            ->orWhere('employee_role', 'like', "%{$search}%");
                    });
                }
            });
        }

        return $query->latest()->paginate($perPage);
    }

    /**
     * Create a user and their details in a transaction.
     */
    public function createUser(array $data): User
    {
        return DB::transaction(function () use ($data): User {
            /** @var User $user */
            $user = User::query()->create([
                'first_name' => $data['first_name'],
                'middle_name' => $data['middle_name'] ?? null,
                'last_name' => $data['last_name'],
                'rfid' => $data['rfid'],
                'email' => $data['email'],
                'password' => bcrypt(str()->random(32)),
                'status' => $data['status'],
            ]);

            if ($data['user_type'] === 'student') {
                $user->studentDetail()->create([
                    'id_number' => $data['id_number'],
                    'level' => $data['level'],
                    'section' => $data['section'],
                    'guardian_name' => $data['guardian_name'],
                    'guardian_contact_number' => $data['guardian_contact_number'],
                    'active_id_number' => $data['id_number'],
                ]);
            } else {
                $user->employeeDetail()->create([
                    'employee_id' => $data['employee_id'],
                    'employee_role' => $data['employee_role'] ?? null,
                    'active_employee_id' => $data['employee_id'],
                ]);
            }

            return $user;
        });
    }

    /**
     * Update an existing user and their details in a transaction.
     */
    public function updateUser(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data): User {
            $user->update([
                'first_name' => $data['first_name'],
                'middle_name' => $data['middle_name'] ?? null,
                'last_name' => $data['last_name'],
                'rfid' => $data['rfid'],
                'email' => $data['email'],
                'status' => $data['status'],
            ]);

            if ($data['user_type'] === 'student') {
                $user->studentDetail()->updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'id_number' => $data['id_number'],
                        'level' => $data['level'],
                        'section' => $data['section'],
                        'guardian_name' => $data['guardian_name'],
                        'guardian_contact_number' => $data['guardian_contact_number'],
                        'active_id_number' => $data['id_number'],
                    ],
                );
            } else {
                $user->employeeDetail()->updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'employee_id' => $data['employee_id'],
                        'employee_role' => $data['employee_role'] ?? null,
                        'active_employee_id' => $data['employee_id'],
                    ],
                );
            }

            return $user;
        });
    }

    /**
     * Delete a user.
     */
    public function deleteUser(User $user): bool
    {
        return (bool) $user->delete();
    }
}
