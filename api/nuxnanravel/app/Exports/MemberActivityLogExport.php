<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

/**
 * ส่งออกประวัติกิจกรรมสมาชิก (เมนู #22)
 *
 * รับ array ของแถวที่ map แล้วจาก controller — ไม่ query เอง เพื่อให้ filter
 * เดียวกับที่ผู้ใช้เห็นบนจอถูกส่งออกตรง ๆ
 */
class MemberActivityLogExport implements FromArray, ShouldAutoSize, WithHeadings
{
    public function __construct(private array $rows) {}

    public function headings(): array
    {
        return ['วันเวลา', 'ผู้ทำรายการ', 'กิจกรรม', 'รายละเอียด', 'เป้าหมาย'];
    }

    public function array(): array
    {
        return $this->rows;
    }
}
