<?php

namespace App\Exports;

use App\Models\SchoolEvent;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ActivityAttendanceExport implements FromArray, ShouldAutoSize, WithHeadings, WithStyles, WithTitle
{
    public function __construct(protected array $data, protected SchoolEvent $event) {}

    public function array(): array
    {
        return array_map(fn ($row) => [
            $row['student_number'], $row['name'], $row['classroom_name'], $row['present'],
            $row['late'], $row['leave'], $row['activity_leave'], $row['absent'],
            $row['not_recorded'], $row['sessions_total'] ?? null, $row['attendance_rate'].'%',
        ], $this->data);
    }

    public function headings(): array
    {
        return ['เลขประจำตัว', 'ชื่อ-นามสกุล', 'ห้อง', 'มา', 'สาย', 'ลา', 'ลากิจกรรม', 'ขาด', 'ไม่ได้บันทึก', 'คาบทั้งหมด', 'อัตราการเข้าร่วม'];
    }

    public function title(): string
    {
        return 'การเข้าร่วมกิจกรรม';
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:K1')->applyFromArray([
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E5E7EB']],
        ]);

        for ($row = 2; $row <= count($this->data) + 1; $row++) {
            $rate = floatval(str_replace('%', '', $sheet->getCell("K{$row}")->getValue()));
            $color = $rate >= 80 ? '10B981' : ($rate >= 60 ? 'F59E0B' : 'EF4444');
            $sheet->getStyle("K{$row}")->applyFromArray([
                'font' => ['color' => ['rgb' => $color], 'bold' => true],
            ]);
        }

        return [];
    }
}
