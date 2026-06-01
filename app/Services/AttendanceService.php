<?php

namespace App\Services;

use App\Jobs\SendAttendanceSmsJob;
use App\Models\AttendanceLog;
use App\Models\Turnstile;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AttendanceService
{
    /**
     * Process an RFID scan and record the attendance log.
     *
     * @throws \Exception
     */
    public function recordScan(Turnstile $turnstile, string $rfid): AttendanceLog
    {
        return DB::transaction(function () use ($turnstile, $rfid) {
            $student = User::query()
                ->with('studentDetail')
                ->where('rfid', $rfid)
                ->whereHas('studentDetail')
                ->first();

            if (! $student) {
                throw new \Exception('RFID_NOT_FOUND', 404);
            }

            if (! $student->status) {
                throw new \Exception('USER_INACTIVE', 403);
            }

            $action = $this->determineNextAction($student->id);

            $log = AttendanceLog::create([
                'user_id' => $student->id,
                'turnstile_id' => $turnstile->id,
                'action' => $action,
                'scanned_at' => Carbon::now(),
                'sms_status' => 'PENDING',
            ]);

            if ($this->shouldQueueGuardianSms($student)) {
                Log::info('Attendance SMS queued.', [
                    'attendance_log_id' => $log->id,
                    'user_id' => $student->id,
                    'turnstile_id' => $turnstile->id,
                    'action' => $action,
                    'guardian_contact_number' => $student->studentDetail?->guardian_contact_number,
                ]);

                SendAttendanceSmsJob::dispatch($log->id)
                    ->afterCommit();
            } else {
                Log::info('Attendance SMS skipped before queue.', [
                    'attendance_log_id' => $log->id,
                    'user_id' => $student->id,
                    'turnstile_id' => $turnstile->id,
                    'action' => $action,
                    'reason' => $this->smsSkipReason($student),
                ]);
            }

            return $log;
        });
    }

    private function shouldQueueGuardianSms(User $student): bool
    {
        if (! config('services.unisms.enabled')) {
            return false;
        }

        if (! filled((string) config('services.unisms.api_key'))) {
            return false;
        }

        return filled($student->studentDetail?->guardian_contact_number);
    }

    private function smsSkipReason(User $student): string
    {
        if (! config('services.unisms.enabled')) {
            return 'unisms_disabled';
        }

        if (! filled((string) config('services.unisms.api_key'))) {
            return 'missing_api_key';
        }

        if (! filled($student->studentDetail?->guardian_contact_number)) {
            return 'missing_guardian_contact_number';
        }

        return 'not_skipped';
    }

    /**
     * Determine if the student's next scan should be IN or OUT.
     */
    public function determineNextAction(int $userId): string
    {
        $lastLog = AttendanceLog::where('user_id', $userId)
            ->whereDate('scanned_at', Carbon::today())
            ->latest('id')
            ->first();

        if (! $lastLog) {
            return 'IN';
        }

        return $lastLog->action === 'IN' ? 'OUT' : 'IN';
    }

    /**
     * Process an array of RFID scans, returning logs of successful scans.
     * Skips invalid RFIDs so the batch can still be completed.
     *
     * @param  array<string>  $rfids
     * @return Collection<int, AttendanceLog>
     */
    public function processBatchScans(Turnstile $turnstile, array $rfids)
    {
        $logs = collect();

        foreach ($rfids as $rfid) {
            try {
                $log = $this->recordScan($turnstile, $rfid);
                $logs->push($log);
            } catch (\Exception $e) {
                // Ignore failure for individual scans during batch so others can sync
                continue;
            }
        }

        return $logs;
    }
}
