<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LearningResultsExport implements FromArray, ShouldAutoSize, WithHeadings, WithStyles, WithTitle
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
            $memberName = $item['member']['member_name'] ?? $item['member']['user']['name'] ?? '-';
            $rows[] = [
                $item['member']['member_code'] ?? '-',
                $memberName,
                $item['attendance_rate'],
                $scores['lesson_assignments'] ?? 0,
                $scores['lesson_quizzes'] ?? 0,
                $scores['course_assignments'] ?? 0,
                $scores['course_quizzes'] ?? 0,
                $scores['bonus_points'] ?? 0,
                $scores['total_score'],
                (int) $scores['percentage'],
                $scores['grade_progress'].' ('.$scores['grade_name'].')',
            ];
        }

        return $rows;
    }

    public function headings(): array
    {
        // Get max scores from first row for header labels
        $first = $this->data[0]['scores'] ?? [];
        $maxLA = $first['max_lesson_assignments'] ?? 0;
        $maxLQ = $first['max_lesson_quizzes'] ?? 0;
        $maxCA = $first['max_course_assignments'] ?? 0;
        $maxCQ = $first['max_course_quizzes'] ?? 0;
        $maxTotal = $first['max_total'] ?? ($maxLA + $maxLQ + $maxCA + $maxCQ);

        return [
            'รหัสนักศึกษา',
            'ชื่อ-นามสกุล',
            'การเข้าเรียน (%)',
            'งานบทเรียน ('.$maxLA.')',
            'ทดสอบบทเรียน ('.$maxLQ.')',
            'งานรายวิชา ('.$maxCA.')',
            'ทดสอบรายวิชา ('.$maxCQ.')',
            'คะแนนพิเศษ',
            'คะแนนรวม ('.$maxTotal.')',
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
