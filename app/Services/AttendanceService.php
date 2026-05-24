<?php

namespace App\Services;

use App\Jobs\SendAttendanceSmsJob;
use App\Models\AttendanceLog;
use App\Models\Turnstile;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

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
                SendAttendanceSmsJob::dispatch($log->id)
                    ->afterCommit()
                    ->afterResponse();
            }

            return $log;
        });
    }

    private function shouldQueueGuardianSms(User $student): bool
    {
        if (! config('services.semaphore.enabled')) {
            return false;
        }

        if (! filled((string) config('services.semaphore.api_key'))) {
            return false;
        }

        return filled($student->studentDetail?->guardian_contact_number);
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
     * @param array<string> $rfids
     * @return \Illuminate\Support\Collection<int, AttendanceLog>
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
