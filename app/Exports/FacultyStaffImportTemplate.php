<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class FacultyStaffImportTemplate implements WithMultipleSheets
{
    /**
     * @return array<int, FacultyStaffImportTemplateInstructionsSheet|FacultyStaffImportTemplateDataSheet>
     */
    public function sheets(): array
    {
        return [
            new FacultyStaffImportTemplateDataSheet,
            new FacultyStaffImportTemplateInstructionsSheet,
        ];
    }
}
