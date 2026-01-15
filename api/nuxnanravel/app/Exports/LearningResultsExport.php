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
            $rows[] = [
                $item['member']['member_code'] ?? '-',
                $item['member']['user']['name'] ?? '-',
                $item['member']['group']['name'] ?? 'ไม่มีกลุ่ม',
                $item['attendance_rate'] . '%',
                $item['lessons_progress'] . '%',
                $item['assignments_progress'] . '%',
                $item['quizzes_progress'] . '%',
                $item['scores']['total_score'],
                $item['scores']['grade_name'],
            ];
        }
        return $rows;
    }

    public function headings(): array
    {
        return [
            'รหัสนักศึกษา',
            'ชื่อ-นามสกุล',
            'กลุ่ม',
            'การเข้าเรียน (%)',
            'บทเรียน (%)',
            'งาน (%)',
            'แบบทดสอบ (%)',
            'คะแนนรวม',
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
