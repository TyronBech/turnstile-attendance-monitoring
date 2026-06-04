<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class StudentImportTemplate implements WithMultipleSheets
{
    /**
     * @return array<int, StudentImportTemplateInstructionsSheet|StudentImportTemplateDataSheet>
     */
    public function sheets(): array
    {
        return [
            new StudentImportTemplateDataSheet,
            new StudentImportTemplateInstructionsSheet,
        ];
    }
}
