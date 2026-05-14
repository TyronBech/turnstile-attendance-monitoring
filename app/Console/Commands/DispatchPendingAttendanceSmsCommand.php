<?php

namespace App\Console\Commands;

use App\Jobs\SendAttendanceSmsJob;
use App\Models\AttendanceLog;
use Illuminate\Console\Command;

class DispatchPendingAttendanceSmsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'attendance:dispatch-pending-sms
                            {--chunk=100 : Number of attendance logs to load per chunk}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dispatch SendAttendanceSmsJob for logs still PENDING (recovery / batch catch-up)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (! config('services.semaphore.enabled') || ! filled((string) config('services.semaphore.api_key'))) {
            $this->warn('Semaphore is disabled or SEMAPHORE_API_KEY is empty. Nothing dispatched.');

            return self::SUCCESS;
        }

        $chunk = max(1, (int) $this->option('chunk'));
        $dispatched = 0;

        AttendanceLog::query()
            ->where('sms_status', 'PENDING')
            ->whereHas('user', function ($q): void {
                $q->whereNotNull('guardian_contact_number')
                    ->where('guardian_contact_number', '!=', '');
            })
            ->orderBy('id')
            ->chunkById($chunk, function ($logs) use (&$dispatched): void {
                foreach ($logs as $log) {
                    SendAttendanceSmsJob::dispatch($log->id);
                    $dispatched++;
                }
            });

        $this->info("Dispatched {$dispatched} SMS job(s).");

        return self::SUCCESS;
    }
}
