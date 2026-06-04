<?php

namespace App\Jobs;

use App\Imports\FacultyStaffImport;
use App\Models\ImportLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcessFacultyStaffImportJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 1;

    public int $timeout = 600;

    public function __construct(
        public int $importLogId,
        public string $filePath,
    ) {}

    public function handle(): void
    {
        $importLog = ImportLog::query()->find($this->importLogId);

        if ($importLog === null) {
            Log::warning('Faculty/Staff import job skipped: ImportLog not found.', [
                'import_log_id' => $this->importLogId,
            ]);

            return;
        }

        $importLog->update(['status' => 'processing']);

        Log::info('Faculty/Staff import job started.', [
            'import_log_id' => $this->importLogId,
            'file' => $this->filePath,
        ]);

        try {
            $import = new FacultyStaffImport;
            $import->import($this->filePath, 'local');

            $importLog->update([
                'processed_rows' => $import->getProcessedCount(),
                'failed_rows' => $import->getFailedCount(),
                'error_summary' => $import->getErrors() !== [] ? $import->getErrors() : null,
                'status' => $import->getFailedCount() > 0 ? 'completed_with_errors' : 'completed',
            ]);

            Log::info('Faculty/Staff import job completed.', [
                'import_log_id' => $this->importLogId,
                'processed' => $import->getProcessedCount(),
                'failed' => $import->getFailedCount(),
            ]);
        } catch (\Throwable $e) {
            $importLog->update([
                'status' => 'failed',
                'error_summary' => [['row' => 0, 'message' => $e->getMessage()]],
            ]);

            Log::error('Faculty/Staff import job failed.', [
                'import_log_id' => $this->importLogId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        } finally {
            Storage::disk('local')->delete($this->filePath);
        }
    }

    public function failed(?\Throwable $exception): void
    {
        $importLog = ImportLog::query()->find($this->importLogId);

        if ($importLog !== null) {
            $importLog->update([
                'status' => 'failed',
                'error_summary' => [['row' => 0, 'message' => $exception?->getMessage() ?? 'Unknown error']],
            ]);
        }

        Log::error('Faculty/Staff import job failed permanently.', [
            'import_log_id' => $this->importLogId,
            'error' => $exception?->getMessage(),
        ]);
    }
}
