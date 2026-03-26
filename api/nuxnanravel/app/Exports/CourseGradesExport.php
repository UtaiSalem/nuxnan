<?php

namespace App\Exports;

use App\Models\Course;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class CourseGradesExport implements FromArray, WithHeadings, WithTitle, ShouldAutoSize, WithStyles
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
                $row['email'],
                $row['group'],
                $row['achieved_score'],
                $row['total_score'],
                $row['percentage'],
                $row['final_grade'] . ' (' . $row['grade_name'] . ')',
                $row['bonus_points'],
                $this->getStatusLabel($row['completion_status']),
            ];
        }, $this->data);
    }

    public function headings(): array
    {
        return [
            'รหัสนักศึกษา',
            'ชื่อ-นามสกุล',
            'อีเมล',
            'กลุ่ม',
            'คะแนนที่ได้',
            'คะแนนเต็ม',
            'เปอร์เซ็นต์',
            'เกรด',
            'คะแนนโบนัส',
            'สถานะ',
        ];
    }

    public function title(): string
    {
        return 'ผลการเรียน';
    }

    public function styles(Worksheet $sheet)
    {
        // Header row styling
        $sheet->getStyle('A1:J1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4F46E5'],
            ],
        ]);

        // Course info in header
        $sheet->setCellValue('A1', 'รายวิชา: ' . $this->course->name);
        $sheet->mergeCells('A1:J1');
        $sheet->getStyle('A1')->getFont()->setSize(14)->setBold(true);
        
        // Insert actual headers at row 2
        $sheet->insertNewRowBefore(2);
        $headers = $this->headings();
        foreach ($headers as $col => $header) {
            $sheet->setCellValueByColumnAndRow($col + 1, 2, $header);
        }
        $sheet->getStyle('A2:J2')->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E5E7EB'],
            ],
        ]);

        return [];
    }

    protected function getStatusLabel(string $status): string
    {
        return match($status) {
            'pending' => 'รอดำเนินการ',
            'pending_acceptance' => 'รอยืนยันเกรด',
            'accepted' => 'ยืนยันแล้ว',
            'completed' => 'จบวิชา',
            default => $status,
        };
    }
}
