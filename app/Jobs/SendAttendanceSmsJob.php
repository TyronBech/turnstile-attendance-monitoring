<?php

namespace App\Jobs;

use App\Models\AttendanceLog;
use App\Services\UniSmsService;
use Carbon\CarbonInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendAttendanceSmsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    private const DELAY_NOTICE_THRESHOLD_MINUTES = 5;

    public int $tries = 3;

    public function __construct(public int $attendanceLogId) {}

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [30, 120, 300];
    }

    public function handle(UniSmsService $sms): void
    {
        Log::info('Attendance SMS job started.', [
            'attendance_log_id' => $this->attendanceLogId,
            'attempt' => $this->attempts(),
        ]);

        $log = AttendanceLog::query()
            ->with(['user.studentDetail', 'turnstile'])
            ->find($this->attendanceLogId);

        if ($log === null) {
            Log::warning('Attendance SMS job skipped because attendance log was not found.', [
                'attendance_log_id' => $this->attendanceLogId,
            ]);

            return;
        }

        if ($log->sms_status !== 'PENDING') {
            Log::info('Attendance SMS job skipped because attendance log is no longer pending.', [
                'attendance_log_id' => $log->id,
                'sms_status' => $log->sms_status,
            ]);

            return;
        }

        $user = $log->user;
        $guardianNumber = $user->studentDetail?->guardian_contact_number ?? '';

        if ($guardianNumber === '') {
            Log::warning('Attendance SMS job skipped because guardian contact number is missing.', [
                'attendance_log_id' => $log->id,
                'user_id' => $user->id,
            ]);

            return;
        }

        $studentName = trim($user->name);

        if ($studentName === '') {
            $studentName = 'Student';
        }

        $timeLabel = $log->scanned_at instanceof CarbonInterface
            ? $log->scanned_at->timezone(config('app.timezone'))->format('g:i A')
            : now()->format('g:i A');

        $schoolName = (string) config('services.unisms.sender_id', config('app.name', 'School'));
        $message = $log->action === 'IN'
            ? "[{$schoolName}]: Your child, {$studentName}, has arrived at school at {$timeLabel}. Thank you!"
            : "[{$schoolName}]: Your child, {$studentName}, has left school at {$timeLabel}. Thank You!";

        if ($this->shouldIncludeDelayNotice($log->scanned_at)) {
            $message .= ' Apologies if this message was delayed.';
        }

        $ok = $sms->send($guardianNumber, $message);

        if (! $ok) {
            Log::warning('Attendance SMS provider call returned false.', [
                'attendance_log_id' => $log->id,
                'user_id' => $user->id,
                'guardian_contact_number' => $guardianNumber,
                'attempt' => $this->attempts(),
            ]);

            throw new \RuntimeException('UniSMS send failed');
        }

        $log->forceFill(['sms_status' => 'SENT'])->save();

        Log::info('Attendance SMS marked as sent.', [
            'attendance_log_id' => $log->id,
            'user_id' => $user->id,
            'guardian_contact_number' => $guardianNumber,
            'action' => $log->action,
        ]);
    }

    public function failed(?\Throwable $e): void
    {
        AttendanceLog::query()
            ->where('id', $this->attendanceLogId)
            ->where('sms_status', 'PENDING')
            ->update(['sms_status' => 'FAILED']);

        Log::error('Attendance SMS job failed permanently.', [
            'attendance_log_id' => $this->attendanceLogId,
            'error' => $e?->getMessage(),
        ]);
    }

    private function shouldIncludeDelayNotice(?CarbonInterface $scannedAt): bool
    {
        if ($this->attempts() > 1) {
            return true;
        }

        if ($scannedAt === null) {
            return false;
        }

        return $scannedAt->lt(now()->subMinutes(self::DELAY_NOTICE_THRESHOLD_MINUTES));
    }
}
