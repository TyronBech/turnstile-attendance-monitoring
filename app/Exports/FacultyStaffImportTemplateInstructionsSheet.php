<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class FacultyStaffImportTemplateInstructionsSheet implements FromArray, ShouldAutoSize, WithStyles, WithTitle
{
    public function title(): string
    {
        return 'Instructions';
    }

    /**
     * @return array<int, array<int, string>>
     */
    public function array(): array
    {
        return [
            ['FACULTY & STAFF IMPORT TEMPLATE — INSTRUCTIONS'],
            [''],
            ['HOW TO USE THIS TEMPLATE:'],
            ['1. Go to the "Faculty Staff Data" sheet (see tab at the bottom).'],
            ['2. Fill in the faculty/staff information starting from Row 2 (Row 1 is the header — DO NOT modify it).'],
            ['3. Save the file as .xlsx format.'],
            ['4. Upload the file through the Import Faculties & Staffs page.'],
            [''],
            ['COLUMN DESCRIPTIONS:'],
            ['Column', 'Required', 'Description', 'Example'],
            ['rfid', 'Yes', 'The unique RFID tag number assigned to the faculty/staff ID card.', 'RF-FAC-001'],
            ['first_name', 'Yes', 'Faculty/Staff first name.', 'Maria'],
            ['middle_name', 'No', 'Faculty/Staff middle name (leave blank if none).', 'Lopez'],
            ['last_name', 'Yes', 'Faculty/Staff last name / surname.', 'Garcia'],
            ['email', 'Yes', 'A valid and unique email address.', 'maria.garcia@school.edu'],
            ['employee_id', 'Yes', 'The unique employee ID number. Used to identify existing records for updates.', 'EMP-2026-001'],
            ['employee_role', 'No', 'The role or position (e.g., Teacher, Staff, Admin, Guidance Counselor).', 'Teacher'],
            [''],
            ['IMPORTANT NOTES:'],
            ['• Do NOT modify the header row in the "Faculty Staff Data" sheet.'],
            ['• Each faculty/staff member must have a unique RFID and email address.'],
            ['• The "employee_id" is used as the primary identifier. If an existing employee_id is found, the record will be UPDATED.'],
            ['• If the employee_id is new, a new faculty/staff record will be CREATED with a default password.'],
            ['• Maximum recommended rows per file: 5,000 records.'],
            ['• Supported file formats: .xlsx, .xls, .csv'],
            ['• Contact your system administrator if you encounter any issues.'],
        ];
    }

    public function styles(Worksheet $sheet): void
    {
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A3')->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('A9')->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('A10:D10')->getFont()->setBold(true);
        $sheet->getStyle('A10:D10')->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFD9E1F2');
        $sheet->getStyle('A19')->getFont()->setBold(true)->setSize(12);

        $sheet->getColumnDimension('A')->setWidth(30);
        $sheet->getColumnDimension('B')->setWidth(12);
        $sheet->getColumnDimension('C')->setWidth(70);
        $sheet->getColumnDimension('D')->setWidth(25);
    }
}
