<?php

namespace Database\Seeders;

use App\Models\AttendanceLog;
use App\Models\Turnstile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class AttendanceLogSeeder extends Seeder
{
    /**
     * Seed mock attendance logs using one student and one employee.
     */
    public function run(): void
    {
        $turnstile = Turnstile::query()->first();

        if (! $turnstile) {
            return;
        }

        $student = User::query()
            ->whereHas('studentDetail')
            ->orderBy('id')
            ->first();

        $employee = User::query()
            ->whereHas('employeeDetail')
            ->orderBy('id')
            ->first();

        if ($student === null || $employee === null) {
            return;
        }

        $mockLogs = [
            [
                'user_id' => $student->id,
                'turnstile_id' => $turnstile->id,
                'action' => 'IN',
                'scanned_at' => Carbon::today()->setTime(7, 41, 0),
                'sms_status' => 'PENDING',
            ],
            [
                'user_id' => $employee->id,
                'turnstile_id' => $turnstile->id,
                'action' => 'IN',
                'scanned_at' => Carbon::today()->setTime(8, 5, 12),
                'sms_status' => 'PENDING',
            ],
        ];

        foreach ($mockLogs as $mockLog) {
            AttendanceLog::query()->updateOrCreate(
                [
                    'user_id' => $mockLog['user_id'],
                    'turnstile_id' => $mockLog['turnstile_id'],
                    'scanned_at' => $mockLog['scanned_at'],
                ],
                [
                    'action' => $mockLog['action'],
                    'sms_status' => $mockLog['sms_status'],
                ],
            );
        }
    }
}
