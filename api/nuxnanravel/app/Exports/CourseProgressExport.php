<?php

namespace App\Exports;

use App\Models\Course;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CourseProgressExport implements FromArray, ShouldAutoSize, WithHeadings, WithStyles, WithTitle
{
    protected array $data;

    protected Course $course;

    public function __construct(array $data, Course $course)
    {
        $this->data = $data;
        $this->course = $course;
    }

    public function array(): array
    {
        return array_map(function ($row) {
            return [
                $row['member_code'],
                $row['name'],
                $row['group'],
                "{$row['lessons_completed']}/{$row['total_lessons']}",
                $row['lessons_progress'].'%',
                "{$row['assignments_completed']}/{$row['total_assignments']}",
                $row['assignments_progress'].'%',
                "{$row['quizzes_completed']}/{$row['total_quizzes']}",
                $row['quizzes_progress'].'%',
            ];
        }, $this->data);
    }

    public function headings(): array
    {
        return [
            'รหัสนักศึกษา',
            'ชื่อ-นามสกุล',
            'กลุ่ม',
            'บทเรียน',
            'บทเรียน (%)',
            'งาน',
            'งาน (%)',
            'แบบทดสอบ',
            'แบบทดสอบ (%)',
        ];
    }

    public function title(): string
    {
        return 'ความคืบหน้า';
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:I1')->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E5E7EB'],
            ],
        ]);

        return [];
    }
}
