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

class CourseCertificatesExport implements FromArray, ShouldAutoSize, WithHeadings, WithStyles, WithTitle
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
                $row['certificate_number'],
                $row['verification_code'],
                $row['student_name'],
                $row['student_email'],
                $row['group'],
                $row['grade'],
                $row['issued_at'],
                $row['downloaded_at'] ?? '-',
                $this->getStatusLabel($row['status']),
            ];
        }, $this->data);
    }

    public function headings(): array
    {
        return [
            'เลขที่ใบประกาศ',
            'รหัสตรวจสอบ',
            'ชื่อ-นามสกุล',
            'อีเมล',
            'กลุ่ม',
            'เกรด',
            'วันที่ออก',
            'วันที่ดาวน์โหลด',
            'สถานะ',
        ];
    }

    public function title(): string
    {
        return 'ใบประกาศ';
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

    protected function getStatusLabel(string $status): string
    {
        return match ($status) {
            'issued' => 'ออกแล้ว',
            'downloaded' => 'ดาวน์โหลดแล้ว',
            'revoked' => 'ถูกเพิกถอน',
            default => $status,
        };
    }
}
