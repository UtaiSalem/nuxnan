<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class SavedReportArrayExport implements FromArray, WithHeadings
{
    public function __construct(
        protected array $headings,
        protected array $rows,
    ) {}

    public function headings(): array
    {
        return $this->headings;
    }

    public function array(): array
    {
        // เรียงค่าตามลำดับ headings ให้ตรงคอลัมน์ · ค่าที่ไม่ใช่ scalar แปลงเป็น json
        return array_map(function ($row) {
            $ordered = [];
            foreach ($this->headings as $key) {
                $value = $row[$key] ?? '';
                $ordered[] = (is_scalar($value) || $value === null)
                    ? $value
                    : json_encode($value, JSON_UNESCAPED_UNICODE);
            }

            return $ordered;
        }, $this->rows);
    }
}
