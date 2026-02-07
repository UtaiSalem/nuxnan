<?php

namespace App\Exports;

use App\Models\Course;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CourseAttendanceExport implements FromArray, WithHeadings, WithTitle, ShouldAutoSize, WithStyles
{
    protected array $data;
    protected Course $course;
    protected $attendances;

    public function __construct(array $data, Course $course, $attendances)
    {
        $this->data = $data;
        $this->course = $course;
        $this->attendances = $attendances;
    }

    public function array(): array
    {
        return array_map(function ($row) {
            return [
                $row['member_code'],
                $row['name'],
                $row['group'],
                $row['present'],
                $row['late'],
                $row['absent'],
                $row['leave'],
                $row['total_sessions'],
                $row['attendance_rate'] . '%',
            ];
        }, $this->data);
    }

    public function headings(): array
    {
        return [
            'รหัสนักศึกษา',
            'ชื่อ-นามสกุล',
            'กลุ่ม',
            'มา',
            'สาย',
            'ขาด',
            'ลา',
            'ครั้งทั้งหมด',
            'อัตราการเข้าเรียน',
        ];
    }

    public function title(): string
    {
        return 'การเข้าเรียน';
    }

    public function styles(Worksheet $sheet)
    {
        // Header styling
        $sheet->getStyle('A1:I1')->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E5E7EB'],
            ],
        ]);

        // Color code attendance rate
        $lastRow = count($this->data) + 1;
        for ($row = 2; $row <= $lastRow; $row++) {
            $rate = floatval(str_replace('%', '', $sheet->getCell("I{$row}")->getValue()));
            
            if ($rate >= 80) {
                $color = '10B981'; // Green
            } elseif ($rate >= 60) {
                $color = 'F59E0B'; // Yellow
            } else {
                $color = 'EF4444'; // Red
            }
            
            $sheet->getStyle("I{$row}")->applyFromArray([
                'font' => ['color' => ['rgb' => $color], 'bold' => true],
            ]);
        }

        return [];
    }
}
