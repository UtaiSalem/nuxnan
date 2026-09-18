<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ClassScheduleExport implements FromArray, ShouldAutoSize, WithHeadings, WithStyles, WithTitle
{
    public function __construct(protected array $rows, protected string $sheetTitle = 'ตารางเรียน') {}

    public function array(): array
    {
        return array_map(function ($row) {
            return [
                $row['classroom'],
                $row['grade_level'],
                $row['day_name'],
                $row['period_label'],
                $row['start_time'],
                $row['end_time'],
                $row['course_code'],
                $row['title'],
                $row['teacher_name'],
                $row['room'],
                $row['entry_type_label'],
            ];
        }, $this->rows);
    }

    public function headings(): array
    {
        return [
            'ห้องเรียน',
            'ระดับชั้น',
            'วัน',
            'คาบ',
            'เวลาเริ่ม',
            'เวลาสิ้นสุด',
            'รหัสวิชา',
            'ชื่อคาบ/วิชา',
            'ครูผู้สอน',
            'สถานที่',
            'ประเภท',
        ];
    }

    public function title(): string
    {
        $title = str_replace(['\\', '/', '?', '*', ':', '[', ']'], '', $this->sheetTitle);

        return mb_substr($title, 0, 31);
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->getStyle('A1:K1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4F46E5'],
            ],
        ]);

        return [];
    }
}
