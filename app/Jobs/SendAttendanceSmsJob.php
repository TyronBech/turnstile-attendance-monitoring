<?php

namespace App\Jobs;

use App\Models\AttendanceLog;
use App\Services\SemaphoreSmsService;
use Carbon\CarbonInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

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

    public function handle(SemaphoreSmsService $sms): void
    {
        $log = AttendanceLog::query()
            ->with(['user.studentDetail', 'turnstile'])
            ->find($this->attendanceLogId);

        if ($log === null) {
            return;
        }

        if ($log->sms_status !== 'PENDING') {
            return;
        }

        $user = $log->user;
        $guardianNumber = $user->studentDetail?->guardian_contact_number ?? '';

        if ($guardianNumber === '') {
            return;
        }

        $studentName = trim($user->name);

        if ($studentName === '') {
            $studentName = 'Student';
        }

        $timeLabel = $log->scanned_at instanceof CarbonInterface
            ? $log->scanned_at->timezone(config('app.timezone'))->format('g:i A')
            : now()->format('g:i A');

        $schoolName = (string) config('services.semaphore.sender_name', 'School');
        $message = $log->action === 'IN'
            ? "[{$schoolName}]: Your child, {$studentName}, has arrived at school at {$timeLabel}. Thank you!"
            : "[{$schoolName}]: Your child, {$studentName}, has left school at {$timeLabel}. Thank You!";

        if ($this->shouldIncludeDelayNotice($log->scanned_at)) {
            $message .= ' Apologies if this message was delayed.';
        }

        $ok = $sms->send($guardianNumber, $message);

        if (! $ok) {
            throw new \RuntimeException('Semaphore SMS send failed');
        }

        $log->forceFill(['sms_status' => 'SENT'])->save();
    }

    public function failed(?\Throwable $e): void
    {
        AttendanceLog::query()
            ->where('id', $this->attendanceLogId)
            ->where('sms_status', 'PENDING')
            ->update(['sms_status' => 'FAILED']);
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
