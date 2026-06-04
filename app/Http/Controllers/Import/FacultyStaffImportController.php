<?php

namespace App\Http\Controllers\Import;

use App\Exports\FacultyStaffImportTemplate;
use App\Http\Controllers\Controller;
use App\Jobs\ProcessFacultyStaffImportJob;
use App\Models\ImportLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class FacultyStaffImportController extends Controller
{
    /**
     * Display the faculty/staff import page with import history.
     */
    public function index(Request $request): Response
    {
        $imports = ImportLog::query()
            ->where('import_type', 'faculty_staff')
            ->latest()
            ->limit(20)
            ->get()
            ->map(fn (ImportLog $log) => [
                'id' => $log->id,
                'original_filename' => $log->original_filename,
                'total_rows' => $log->total_rows,
                'processed_rows' => $log->processed_rows,
                'failed_rows' => $log->failed_rows,
                'status' => $log->status,
                'error_summary' => $log->error_summary,
                'created_at' => $log->created_at?->toDateTimeString(),
            ]);

        return Inertia::render('imports/faculties-staffs', [
            'imports' => $imports,
        ]);
    }

    /**
     * Handle the uploaded Excel file and dispatch the import job.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'],
        ]);

        $file = $request->file('file');
        $originalName = $file->getClientOriginalName();

        // Count rows (subtract 1 for header)
        $spreadsheet = IOFactory::load($file->getRealPath());
        $worksheet = $spreadsheet->getActiveSheet();
        $totalRows = max(0, $worksheet->getHighestRow() - 1);

        $path = $file->store('imports/faculties-staffs', 'local');

        /** @var ImportLog $importLog */
        $importLog = ImportLog::query()->create([
            'user_id' => $request->user()->id,
            'import_type' => 'faculty_staff',
            'original_filename' => $originalName,
            'total_rows' => $totalRows,
            'status' => 'pending',
        ]);

        ProcessFacultyStaffImportJob::dispatch($importLog->id, $path);

        return response()->json([
            'import_log_id' => $importLog->id,
            'message' => 'Import has been queued for processing.',
        ]);
    }

    /**
     * Download the faculty/staff import Excel template.
     */
    public function downloadTemplate(): BinaryFileResponse
    {
        return Excel::download(new FacultyStaffImportTemplate, 'faculty-staff-import-template.xlsx');
    }

    /**
     * Get the progress of an import job.
     */
    public function progress(ImportLog $importLog): JsonResponse
    {
        if ($importLog->import_type !== 'faculty_staff') {
            abort(404);
        }

        $totalRows = max($importLog->total_rows, 1);
        $processedRows = $importLog->processed_rows + $importLog->failed_rows;
        $percentage = min(100, (int) round(($processedRows / $totalRows) * 100));

        $isFinished = in_array($importLog->status, ['completed', 'completed_with_errors', 'failed'], true);

        return response()->json([
            'id' => $importLog->id,
            'totalRows' => $importLog->total_rows,
            'processedRows' => $importLog->processed_rows,
            'failedRows' => $importLog->failed_rows,
            'progress' => $isFinished ? 100 : $percentage,
            'status' => $importLog->status,
            'finished' => $isFinished,
            'errors' => $importLog->error_summary ?? [],
        ]);
    }
}
