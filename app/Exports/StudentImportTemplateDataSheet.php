<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StudentImportTemplateDataSheet implements FromArray, ShouldAutoSize, WithStyles, WithTitle
{
    public function title(): string
    {
        return 'Student Data';
    }

    /**
     * @return array<int, array<int, string>>
     */
    public function array(): array
    {
        return [
            ['rfid', 'first_name', 'middle_name', 'last_name', 'email', 'id_number', 'level', 'section', 'guardian_name', 'guardian_contact_number'],
            ['1234567890', 'Juan', 'Dela Cruz', 'Santos', 'juan.santos@school.edu', 'STU-2026-001', 'Grade 10', 'Section A', 'Maria Santos', '09171234567'],
            ['0987654321', 'Ana', '', 'Reyes', 'ana.reyes@school.edu', 'STU-2026-002', 'Grade 11', 'Section B', 'Pedro Reyes', '09181234567'],
        ];
    }

    public function styles(Worksheet $sheet): void
    {
        $sheet->getStyle('A1:J1')->getFont()->setBold(true);
        $sheet->getStyle('A1:J1')->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FF4472C4');
        $sheet->getStyle('A1:J1')->getFont()->getColor()->setARGB('FFFFFFFF');

        // Sample rows in light gray
        $sheet->getStyle('A2:J3')->getFont()->setItalic(true);
        $sheet->getStyle('A2:J3')->getFont()->getColor()->setARGB('FF808080');
    }
}
