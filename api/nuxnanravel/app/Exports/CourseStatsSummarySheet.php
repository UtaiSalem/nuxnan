<?php

namespace App\Exports;

use App\Models\Course;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CourseStatsSummarySheet implements FromArray, ShouldAutoSize, WithStyles, WithTitle
{
    protected Course $course;

    protected array $stats;

    public function __construct(Course $course, array $stats)
    {
        $this->course = $course;
        $this->stats = $stats;
    }

    public function array(): array
    {
        $rows = [
            ['รายงานสรุปรายวิชา'],
            [''],
            ['ข้อมูลรายวิชา'],
            ['ชื่อวิชา', $this->course->name],
            ['รหัสวิชา', $this->course->code ?? '-'],
            [''],
            ['สถิติผลการเรียน'],
            ['จำนวนนักเรียนทั้งหมด', $this->stats['total'] ?? 0],
            ['ผ่าน', $this->stats['passed'] ?? 0],
            ['ไม่ผ่าน', $this->stats['failed'] ?? 0],
            ['อัตราการผ่าน', ($this->stats['pass_rate'] ?? 0).'%'],
            ['คะแนนเฉลี่ย', $this->stats['average_score'] ?? 0],
            ['คะแนนสูงสุด', $this->stats['max_score'] ?? 0],
            ['คะแนนต่ำสุด', $this->stats['min_score'] ?? 0],
            ['GPA เฉลี่ย', $this->stats['average_gpa'] ?? 0],
            [''],
            ['การกระจายเกรด'],
        ];

        // Add grade distribution
        $distribution = $this->stats['distribution'] ?? [];
        foreach ($distribution as $grade => $count) {
            $rows[] = [$grade, $count];
        }

        $rows[] = [''];
        $rows[] = ['วันที่ออกรายงาน', now()->format('d/m/Y H:i')];

        return $rows;
    }

    public function title(): string
    {
        return 'สรุป';
    }

    public function styles(Worksheet $sheet)
    {
        // Title
        $sheet->getStyle('A1')->getFont()->setSize(16)->setBold(true);

        // Section headers
        $sheet->getStyle('A3')->getFont()->setBold(true);
        $sheet->getStyle('A7')->getFont()->setBold(true);
        $sheet->getStyle('A17')->getFont()->setBold(true);

        // Merge title
        $sheet->mergeCells('A1:B1');

        return [];
    }
}
