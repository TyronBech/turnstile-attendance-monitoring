<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class FacultyStaffImportTemplateDataSheet implements FromArray, ShouldAutoSize, WithStyles, WithTitle
{
    public function title(): string
    {
        return 'Faculty Staff Data';
    }

    /**
     * @return array<int, array<int, string>>
     */
    public function array(): array
    {
        return [
            ['rfid', 'first_name', 'middle_name', 'last_name', 'email', 'employee_id', 'employee_role'],
            ['RF-FAC-001', 'Maria', 'Lopez', 'Garcia', 'maria.garcia@school.edu', 'EMP-2026-001', 'Teacher'],
            ['RF-FAC-002', 'Jose', '', 'Mendoza', 'jose.mendoza@school.edu', 'EMP-2026-002', 'Staff'],
        ];
    }

    public function styles(Worksheet $sheet): void
    {
        $sheet->getStyle('A1:G1')->getFont()->setBold(true);
        $sheet->getStyle('A1:G1')->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FF4472C4');
        $sheet->getStyle('A1:G1')->getFont()->getColor()->setARGB('FFFFFFFF');

        // Sample rows in light gray
        $sheet->getStyle('A2:G3')->getFont()->setItalic(true);
        $sheet->getStyle('A2:G3')->getFont()->getColor()->setARGB('FF808080');
    }
}
