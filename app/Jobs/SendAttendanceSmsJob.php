<?php

namespace App\Jobs;

use App\Models\AttendanceLog;
use App\Services\SemaphoreSmsService;
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

        $turnstileName = $log->turnstile?->name ?? 'Gate';
        $timeLabel = $log->scanned_at instanceof Carbon
            ? $log->scanned_at->timezone(config('app.timezone'))->format('g:i A')
            : now()->format('g:i A');

        $actionLabel = $log->action === 'IN' ? 'Time In' : 'Time Out';
        $message = "[SNCS] {$studentName} — {$actionLabel} at {$turnstileName} ({$timeLabel}).";

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
}
