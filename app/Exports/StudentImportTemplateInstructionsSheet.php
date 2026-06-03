<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StudentImportTemplateInstructionsSheet implements FromArray, ShouldAutoSize, WithStyles, WithTitle
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
            ['STUDENT IMPORT TEMPLATE — INSTRUCTIONS'],
            [''],
            ['HOW TO USE THIS TEMPLATE:'],
            ['1. Go to the "Student Data" sheet (see tab at the bottom).'],
            ['2. Fill in the student information starting from Row 2 (Row 1 is the header — DO NOT modify it).'],
            ['3. Save the file as .xlsx format.'],
            ['4. Upload the file through the Import Students page.'],
            [''],
            ['COLUMN DESCRIPTIONS:'],
            ['Column', 'Required', 'Description', 'Example'],
            ['rfid', 'Yes', 'The unique RFID tag number assigned to the student\'s ID card.', '1234567890'],
            ['first_name', 'Yes', 'Student\'s first name.', 'Juan'],
            ['middle_name', 'No', 'Student\'s middle name (leave blank if none).', 'Dela Cruz'],
            ['last_name', 'Yes', 'Student\'s last name / surname.', 'Santos'],
            ['email', 'Yes', 'A valid and unique email address for the student.', 'juan.santos@school.edu'],
            ['id_number', 'Yes', 'The student\'s unique school ID number. Used to identify existing records for updates.', 'STU-2026-001'],
            ['level', 'Yes', 'Grade level or year level (e.g., Grade 7, Grade 12, 1st Year).', 'Grade 10'],
            ['section', 'Yes', 'The section or class the student belongs to.', 'Section A'],
            ['guardian_name', 'Yes', 'Full name of the student\'s parent or guardian.', 'Maria Santos'],
            ['guardian_contact_number', 'Yes', 'Contact number of the guardian for SMS notifications.', '09171234567'],
            [''],
            ['IMPORTANT NOTES:'],
            ['• Do NOT modify the header row in the "Student Data" sheet.'],
            ['• Each student must have a unique RFID and email address.'],
            ['• The "id_number" is used as the primary identifier. If an existing id_number is found, the record will be UPDATED.'],
            ['• If the id_number is new, a new student record will be CREATED with a default password.'],
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
        $sheet->getStyle('A22')->getFont()->setBold(true)->setSize(12);

        $sheet->getColumnDimension('A')->setWidth(30);
        $sheet->getColumnDimension('B')->setWidth(12);
        $sheet->getColumnDimension('C')->setWidth(70);
        $sheet->getColumnDimension('D')->setWidth(25);
    }
}
