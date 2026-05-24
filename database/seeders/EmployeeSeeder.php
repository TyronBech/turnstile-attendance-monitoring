<?php

namespace Database\Seeders;

use App\Enums\Role as RoleEnum;
use App\Models\User;
use Illuminate\Database\Seeder;

class EmployeeSeeder extends Seeder
{
    /**
     * Seed sample employees and the live monitoring account.
     */
    public function run(): void
    {
        $employee = User::query()->updateOrCreate(
            ['email' => 'jhoncarl.ormita@university.edu'],
            [
                'rfid' => 'A1B2C3D4',
                'first_name' => 'Jhon Carl',
                'middle_name' => 'Tortosa',
                'last_name' => 'Ormita',
                'email' => 'jhoncarl.ormita@university.edu',
                'profile_image' => 'profile-images/ormita.png',
                'password' => 'password',
                'status' => true,
            ],
        );

        $employee->studentDetail()->delete();
        $employee->employeeDetail()->updateOrCreate(
            ['user_id' => $employee->id],
            [
                'employee_id' => 'EMP-2026-0001',
                'employee_role' => 'Teacher',
                'active_employee_id' => 'EMP-2026-0001',
            ],
        );

        $liveMonitoringUser = User::query()->updateOrCreate(
            ['email' => 'live.monitoring@university.edu'],
            [
                'rfid' => 'LM20260001',
                'first_name' => 'Live',
                'middle_name' => 'Monitor',
                'last_name' => 'User',
                'email' => 'live.monitoring@university.edu',
                'profile_image' => null,
                'password' => 'password',
                'status' => true,
            ],
        );

        $liveMonitoringUser->studentDetail()->delete();
        $liveMonitoringUser->employeeDetail()->delete();
        $liveMonitoringUser->syncRoles([RoleEnum::Live_Monitoring->value]);
    }
}
