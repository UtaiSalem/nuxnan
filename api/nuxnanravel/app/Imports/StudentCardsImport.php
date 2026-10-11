<?php

namespace App\Imports;

use App\Exports\StudentCardsExport;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToArray;

/**
 * อ่านไฟล์นำเข้าบัตรนักเรียนเป็นแถวดิบ แล้วให้ controller จับคู่หัวคอลัมน์เอง
 *
 * ใช้การ "จับคู่ชื่อหัวคอลัมน์" (ไม่ยึดตำแหน่ง) เพื่อให้ไฟล์ที่ครูสลับคอลัมน์
 * หรือมีคอลัมน์เกินยัง import ได้ ตราบใดที่หัวคอลัมน์ตรงกับ template
 * หัวคอลัมน์มาตรฐานอยู่ที่ StudentCardsExport::HEADINGS (export/template ใช้ชุดเดียวกัน)
 */
class StudentCardsImport implements SkipsEmptyRows, ToArray
{
    /**
     * field key => หัวคอลัมน์ไทย (ลำดับตรงกับ StudentCardsExport::HEADINGS)
     */
    public const FIELD_HEADINGS = [
        'student_number' => 'รหัสนักเรียน',
        'title_name' => 'คำนำหน้า',
        'first_name_thai' => 'ชื่อ (ไทย)',
        'last_name_thai' => 'นามสกุล (ไทย)',
        'first_name_english' => 'ชื่อ (อังกฤษ)',
        'national_id' => 'เลขบัตรประชาชน',
        'birth_date' => 'วันเกิด (YYYY-MM-DD)',
        'class_level' => 'ระดับชั้น',
        'class_section' => 'ห้อง',
    ];

    /** @var array<int, array<int, mixed>> */
    public array $rows = [];

    public function array(array $array): void
    {
        $this->rows = $array;
    }

    /**
     * ทำความสะอาดหัวคอลัมน์เพื่อเทียบ (ตัดช่องว่าง/ตัวพิมพ์)
     */
    public static function normalizeHeader(?string $value): string
    {
        return mb_strtolower(trim((string) $value));
    }

    /**
     * จับคู่แถวหัวคอลัมน์ → [field => column index]
     *
     * @param  array<int, mixed>  $headerRow
     * @return array<string, int>
     */
    public static function headerIndexes(array $headerRow): array
    {
        $normalized = [];
        foreach ($headerRow as $index => $cell) {
            $normalized[self::normalizeHeader(is_string($cell) || is_numeric($cell) ? (string) $cell : '')] = $index;
        }

        $map = [];
        foreach (self::FIELD_HEADINGS as $field => $heading) {
            $key = self::normalizeHeader($heading);
            if (array_key_exists($key, $normalized)) {
                $map[$field] = $normalized[$key];
            }
        }

        return $map;
    }

    /**
     * ยืนยันว่าหัวคอลัมน์ที่จำเป็นครบ (รหัสนักเรียน + ชื่อ/นามสกุลไทย + ระดับชั้น + ห้อง)
     *
     * @param  array<string, int>  $headerIndexes
     * @return array<int, string>  รายชื่อ field ที่ขาด (ว่าง = ครบ)
     */
    public static function missingRequired(array $headerIndexes): array
    {
        $required = ['student_number', 'first_name_thai', 'last_name_thai', 'class_level', 'class_section'];

        return array_values(array_filter(
            $required,
            fn ($field) => ! array_key_exists($field, $headerIndexes)
        ));
    }

    /**
     * หัวคอลัมน์มาตรฐาน (ไว้ประกอบข้อความ error)
     *
     * @return array<int, string>
     */
    public static function expectedHeadings(): array
    {
        return StudentCardsExport::HEADINGS;
    }
}
