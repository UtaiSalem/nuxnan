<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TeacherWorkloadExport implements FromArray, ShouldAutoSize, WithHeadings, WithStyles, WithTitle
{
    public function __construct(protected array $rows, protected string $sheetTitle = 'ภาระงานสอน') {}

    public function array(): array
    {
        return array_map(function ($row, $index) {
            return [
                $index + 1,
                $row['name'],
                $row['periods_per_week'],
                $row['hours_per_week'],
                $row['minutes_per_week'],
                $row['course_count'],
                $row['classroom_count'],
                $row['teaching_days'],
                $row['max_periods_per_day'],
            ];
        }, $this->rows, array_keys($this->rows));
    }

    public function headings(): array
    {
        return [
            'ลำดับ',
            'ชื่อครู',
            'คาบ/สัปดาห์',
            'ชั่วโมง/สัปดาห์',
            'นาที/สัปดาห์',
            'จำนวนวิชา',
            'จำนวนห้อง',
            'จำนวนวันที่สอน',
            'คาบมากสุดใน 1 วัน',
        ];
    }

    public function title(): string
    {
        $title = str_replace(['\\', '/', '?', '*', ':', '[', ']'], '', $this->sheetTitle);

        return mb_substr($title, 0, 31);
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
