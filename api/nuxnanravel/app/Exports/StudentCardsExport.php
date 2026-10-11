<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * ส่งออก/เทมเพลตบัตรนักเรียน
 *
 * หัวคอลัมน์ต้องตรงกับ StudentCardsImport::HEADING_MAP เป๊ะ ๆ — ไฟล์ที่ export
 * ออกไปต้อง import กลับเข้ามาได้ทันที (รวมถึงไฟล์ template เปล่า)
 */
class StudentCardsExport implements FromArray, ShouldAutoSize, WithHeadings, WithStyles, WithTitle
{
    /**
     * ลำดับหัวคอลัมน์ (ภาษาไทย) — ใช้ร่วมกับ import
     */
    public const HEADINGS = [
        'รหัสนักเรียน',
        'คำนำหน้า',
        'ชื่อ (ไทย)',
        'นามสกุล (ไทย)',
        'ชื่อ (อังกฤษ)',
        'เลขบัตรประชาชน',
        'วันเกิด (YYYY-MM-DD)',
        'ระดับชั้น',
        'ห้อง',
    ];

    /**
     * @param  array<int, array<string, mixed>>  $rows  แถวข้อมูล (ว่าง = template)
     */
    public function __construct(protected array $rows = []) {}

    public function array(): array
    {
        return array_map(fn ($row) => [
            $row['student_number'] ?? '',
            $row['title_name'] ?? '',
            $row['first_name_thai'] ?? '',
            $row['last_name_thai'] ?? '',
            $row['first_name_english'] ?? '',
            $row['national_id'] ?? '',
            $row['birth_date'] ?? '',
            $row['class_level'] ?? '',
            $row['class_section'] ?? '',
        ], $this->rows);
    }

    public function headings(): array
    {
        return self::HEADINGS;
    }

    public function title(): string
    {
        return 'บัตรนักเรียน';
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->getStyle('A1:I1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4F46E5'],
            ],
        ]);

        return [];
    }
}
