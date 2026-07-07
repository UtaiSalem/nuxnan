<?php

namespace App\Services\Import;

use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class ThaiDateParser
{
    public static function parse(mixed $value): ?string
    {
        if (empty($value)) {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            $carbon = Carbon::instance($value);
            if ($carbon->year > 2400) {
                $carbon = $carbon->copy()->subYears(543);
            }

            return $carbon->toDateString();
        }

        // If it's a numeric Excel date serial number
        if (is_numeric($value)) {
            try {
                $dt = Date::excelToDateTimeObject($value);
                $carbon = Carbon::instance($dt);
                // If Excel date was entered as BE year directly (e.g. 2550-04-25)
                if ($carbon->year > 2400) {
                    $carbon = $carbon->copy()->subYears(543);
                }

                return $carbon->toDateString();
            } catch (\Exception $e) {
                // Fallback to string parsing
            }
        }

        $value = trim((string) $value);
        if ($value === '' || $value === '-') {
            return null;
        }

        // Clean value: replace dashes, dots, spaces with slash
        $cleaned = preg_replace('/[\s\.\-]+/', '/', $value);

        // Match d/m/y or d/m/yyyy
        if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{2,4})$/', $cleaned, $matches)) {
            $day = (int) $matches[1];
            $month = (int) $matches[2];
            $year = (int) $matches[3];

            // Normalize 2-digit year (e.g., 50 -> 2550, or 09 -> 2509)
            if ($year < 100) {
                $year += 2500;
            }

            // Convert Buddhist Era to Gregorian
            if ($year > 2400) {
                $year -= 543;
            }

            try {
                return Carbon::createFromDate($year, $month, $day)->toDateString();
            } catch (\Exception $e) {
                return null;
            }
        }

        // Handle Thai Month names (e.g., "08 เม.ย. 57", "07 พ.ค. 2569")
        $cleanedText = preg_replace('/\s+/', ' ', $value);
        if (preg_match('/^(\d{1,2})\s+(\S+)\s+(\d{2,4})$/u', $cleanedText, $matches)) {
            $day = (int) $matches[1];
            $monthStr = $matches[2];
            $year = (int) $matches[3];

            $thaiMonths = [
                'ม.ค.' => 1, 'ก.พ.' => 2, 'มี.ค.' => 3, 'เม.ย.' => 4,
                'พ.ค.' => 5, 'มิ.ย.' => 6, 'ก.ค.' => 7, 'ส.ค.' => 8,
                'ก.ย.' => 9, 'ต.ค.' => 10, 'พ.ย.' => 11, 'ธ.ค.' => 12,
                'มกราคม' => 1, 'กุมภาพันธ์' => 2, 'มีนาคม' => 3, 'เมษายน' => 4,
                'พฤษภาคม' => 5, 'มิถุนายน' => 6, 'กรกฎาคม' => 7, 'สิงหาคม' => 8,
                'กันยายน' => 9, 'ตุลาคม' => 10, 'พฤศจิกายน' => 11, 'ธันวาคม' => 12,
            ];

            if (isset($thaiMonths[$monthStr])) {
                $month = $thaiMonths[$monthStr];

                if ($year < 100) {
                    $year += 2500;
                }

                if ($year > 2400) {
                    $year -= 543;
                }

                try {
                    return Carbon::createFromDate($year, $month, $day)->toDateString();
                } catch (\Exception $e) {
                    return null;
                }
            }
        }

        // Standard fallback parse
        try {
            $carbon = Carbon::parse($value);
            if ($carbon->year > 2400) {
                $carbon = $carbon->copy()->subYears(543);
            }

            return $carbon->toDateString();
        } catch (\Exception $e) {
            return null;
        }
    }
}
