<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LearningResultsExport implements FromArray, WithHeadings, WithTitle, ShouldAutoSize, WithStyles
{
    protected $data;
    protected $courseName;

    public function __construct(array $data, string $courseName)
    {
        $this->data = $data;
        $this->courseName = $courseName;
    }

    public function array(): array
    {
        $rows = [];
        foreach ($this->data as $item) {
            $scores = $item['scores'];
            $rows[] = [
                $item['member']['member_code'] ?? '-',
                $item['member']['user']['name'] ?? '-',
                $item['attendance_rate'] . '%',
                $scores['course_assignments'] ?? 0,
                $scores['course_quizzes'] ?? 0,
                $scores['bonus_points'] ?? 0,
                $scores['total_score'],
                $scores['percentage'] . '%',
                $scores['grade_name'],
            ];
        }
        return $rows;
    }

    public function headings(): array
    {
        // Get max scores from first row for header labels
        $first = $this->data[0]['scores'] ?? [];
        $maxCA = $first['max_course_assignments'] ?? 0;
        $maxCQ = $first['max_course_quizzes'] ?? 0;
        $maxTotal = $maxCA + $maxCQ;

        return [
            'รหัสนักศึกษา',
            'ชื่อ-นามสกุล',
            'การเข้าเรียน (%)',
            'งาน (' . $maxCA . ')',
            'แบบทดสอบ (' . $maxCQ . ')',
            'คะแนนพิเศษ',
            'คะแนนรวม (' . $maxTotal . ')',
            'ร้อยละ',
            'เกรด',
        ];
    }

    public function title(): string
    {
        return 'ผลการเรียน';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
