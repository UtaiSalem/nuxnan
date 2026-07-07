<?php

namespace App\Services\Import;

use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\IOFactory;

class StudentRosterXlsxParser
{
    protected static $columnMap = [
        'เลขประจำตัวประชาชน' => 'citizen_id',         // col 1
        'เลขประจำตัวนักเรียน' => 'student_code',       // col 2
        'ชั้นเรียน' => 'classroom_label',    // col 3
        'คำนำหน้าชื่อ' => 'title_prefix_th',    // col 4
        'ชื่อ' => 'first_name_th',      // col 5
        'นามสกุล' => 'last_name_th',       // col 6 (normalized to last_name_th)
        'ชื่อกลาง' => 'middle_name_th',     // col 7
        'คำนำหน้าชื่อ.1' => 'title_prefix_en',    // col 8
        'ชื่อภาษาอังกฤษ' => 'first_name_en',      // col 9
        'นามสกุลภาษาอังกฤษ' => 'last_name_en',       // col 10
        'ชื่อกลางภาษาอังกฤษ' => 'middle_name_en',     // col 11
        'ว.ด.ป. เกิด' => 'birth_date_raw',     // col 12
        'เพศ' => 'gender_raw',         // col 13
        'สัญชาติ' => 'nationality',        // col 14
        'ศาสนา' => 'religion',           // col 15
        'สถานะนักเรียน' => 'student_status_raw', // col 16
        'วันที่บันทึก' => 'record_date_raw',    // col 17
        'ประเภทความพิการ' => 'disability_type',    // col 18
        'รหัสประจำบ้าน' => 'house_code',         // col 19
        'บ้านเลขที่' => 'house_number',       // col 20
        'หมู่ที่' => 'village_number',     // col 21
        'ซอย' => 'alley',              // col 22
        'ถนน' => 'road',               // col 23
        'ตำบล/แขวง' => 'subdistrict',        // col 24
        'อำเภอ/เขต' => 'district',           // col 25
        'จังหวัด' => 'province',           // col 26
        'รหัสไปรษณีย์' => 'postal_code',        // col 27
        'เบอร์โทรศัพท์' => 'phone',              // col 28
        'วันที่เข้าเรียน' => 'enrollment_date_raw', // col 29
        'เลขประจำตัวประชาชน (บิดา)' => 'father_citizen_id',  // col 30
        'คำนำหน้าชื่อ (บิดา)' => 'father_title',       // col 31
        'ชื่อ (บิดา)' => 'father_first_name',  // col 32
        'นามสกุล (บิดา)' => 'father_last_name',   // col 33
        'สถานภาพของบิดา' => 'father_status',      // col 34
        'สัญชาติ.1' => 'father_nationality', // col 35
        'เลขประจำตัวประชาชน (มารดา)' => 'mother_citizen_id',  // col 36
        'คำนำหน้าชื่อ (มารดา)' => 'mother_title',       // col 37
        'ชื่อ (มารดา)' => 'mother_first_name',  // col 38
        'นามสกุล (มารดา)' => 'mother_last_name',   // col 39
        'สถานภาพของมารดา' => 'mother_status',      // col 40
        'สัญชาติ.2' => 'mother_nationality', // col 41
        'เลขประจำตัวประชาชน (ผู้ปกครอง)' => 'guardian_citizen_id',  // col 42
        'คำนำหน้าชื่อ.2' => 'guardian_title',       // col 43
        'ชื่อ - นามสกุล' => 'guardian_full_name',   // col 44
        'อาชีพของผู้ปกครอง' => 'guardian_occupation',  // col 45
        'เบอร์โทรศัพท์.1' => 'guardian_phone',       // col 46
        'ความสัมพันธ์' => 'guardian_relationship', // col 47
        'ความสูง (ซม.)' => 'height_cm',          // col 48
        'น้ำหนัก (กก.)' => 'weight_kg',          // col 49
        'ชื่อโรงเรียนเดิม' => 'previous_school',    // col 50
        'จังหวัดโรงเรียนเดิม' => 'previous_school_province', // col 51
        'ชั้นเรียน.1' => 'previous_grade',     // col 52
    ];

    public function parse(string $filePath, ?string $sheetName = null): Collection
    {
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $sheetName ? $spreadsheet->getSheetByName($sheetName) : $spreadsheet->getActiveSheet();

        if (! $sheet) {
            throw new \InvalidArgumentException('Sheet not found.');
        }

        $rows = $sheet->toArray(null, true, true, true);
        if (empty($rows)) {
            return collect();
        }

        // The first row contains headers.
        $headerRowKey = key($rows);
        $rawHeaders = $rows[$headerRowKey];
        unset($rows[$headerRowKey]);

        // Clean, normalize, and deduplicate headers (append .1, .2 for duplicates)
        $headers = [];
        $headerCounts = [];
        foreach ($rawHeaders as $colIndex => $header) {
            $header = trim((string) $header);
            $header = preg_replace('/\s+/', ' ', $header);
            if ($header === '') {
                $headers[$colIndex] = $header;

                continue;
            }
            if (! isset($headerCounts[$header])) {
                $headerCounts[$header] = 0;
                $headers[$colIndex] = $header;
            } else {
                $headerCounts[$header]++;
                $headers[$colIndex] = $header.'.'.$headerCounts[$header];
            }
        }

        $parsedRows = collect();

        foreach ($rows as $rowIndex => $rowData) {
            // Check if row is empty
            $nonEmptyValues = array_filter($rowData, function ($val) {
                return $val !== null && trim((string) $val) !== '';
            });
            if (empty($nonEmptyValues)) {
                continue;
            }

            // Map row data using the normalized headers
            $rowMap = [];
            foreach ($rowData as $colIndex => $cellValue) {
                $headerName = $headers[$colIndex] ?? '';
                if ($headerName !== '') {
                    $rowMap[$headerName] = $cellValue;
                }
            }

            // Clean the keys to match self::$columnMap keys precisely
            $mappedData = [];
            foreach (self::$columnMap as $thaiHeader => $key) {
                $foundVal = null;
                foreach ($rowMap as $headerName => $cellValue) {
                    if (self::normalizeHeaderName($headerName) === self::normalizeHeaderName($thaiHeader)) {
                        $foundVal = $cellValue;
                        break;
                    }
                }

                if ($foundVal === '-' || trim((string) $foundVal) === '') {
                    $foundVal = null;
                }
                $mappedData[$key] = $foundVal;
            }

            // Check classroom label
            $classroomLabel = trim((string) ($mappedData['classroom_label'] ?? ''));

            // SKIP kindergarten and primary levels (อ. or ป.)
            if (preg_match('/^[อป]\.?/ui', $classroomLabel)) {
                continue;
            }

            // Normalize grade_level and section
            $gradeLevel = null;
            $section = null;
            if (preg_match('/^([^\/]+)\/(\d+)$/ui', $classroomLabel, $m)) {
                $gradeLevel = trim($m[1]);
                $section = trim($m[2]);
            }

            // Build normalized struct
            $studentCode = trim((string) ($mappedData['student_code'] ?? ''));
            $citizenId = trim((string) ($mappedData['citizen_id'] ?? ''));

            // Clean citizen_id of dashes or spaces
            $citizenId = preg_replace('/\D/', '', $citizenId) ?: null;

            // Normalize gender (ชาย -> 1, หญิง -> 0)
            $genderRaw = trim((string) ($mappedData['gender_raw'] ?? ''));
            $gender = null;
            if ($genderRaw === 'ชาย') {
                $gender = 1;
            } elseif ($genderRaw === 'หญิง') {
                $gender = 0;
            }

            // Normalize guardian name (split full name)
            $guardianFullName = trim((string) ($mappedData['guardian_full_name'] ?? ''));
            $guardianFirstName = null;
            $guardianLastName = null;
            if ($guardianFullName !== '') {
                $parts = preg_split('/\s+/u', $guardianFullName, 2);
                $guardianFirstName = $parts[0] ?? null;
                $guardianLastName = $parts[1] ?? null;
            }

            // Father & Mother Status normalizer
            $fatherStatus = self::normalizeStatus($mappedData['father_status'] ?? null);
            $motherStatus = self::normalizeStatus($mappedData['mother_status'] ?? null);

            $normalized = [
                'row_number' => $rowIndex,
                'identity' => [
                    'student_id' => $studentCode,
                    'citizen_id' => $citizenId,
                ],
                'personal' => [
                    'title_prefix_th' => $mappedData['title_prefix_th'],
                    'first_name_th' => $mappedData['first_name_th'],
                    'last_name_th' => $mappedData['last_name_th'],
                    'middle_name_th' => $mappedData['middle_name_th'],
                    'title_prefix_en' => $mappedData['title_prefix_en'],
                    'first_name_en' => $mappedData['first_name_en'],
                    'last_name_en' => $mappedData['last_name_en'],
                    'middle_name_en' => $mappedData['middle_name_en'],
                    'date_of_birth' => ThaiDateParser::parse($mappedData['birth_date_raw']),
                    'gender' => $gender,
                    'nationality' => $mappedData['nationality'] ?? 'ไทย',
                    'religion' => $mappedData['religion'],
                ],
                'address' => [
                    'house_code' => $mappedData['house_code'],
                    'house_number' => $mappedData['house_number'],
                    'village_number' => $mappedData['village_number'],
                    'alley' => $mappedData['alley'],
                    'road' => $mappedData['road'],
                    'subdistrict' => $mappedData['subdistrict'],
                    'district' => $mappedData['district'],
                    'province' => $mappedData['province'],
                    'postal_code' => $mappedData['postal_code'],
                ],
                'admission' => [
                    'grade_level' => $gradeLevel,
                    'section' => $section,
                    'enrollment_date' => ThaiDateParser::parse($mappedData['enrollment_date_raw']) ?? now()->toDateString(),
                ],
                'health' => [
                    'height_cm' => $mappedData['height_cm'],
                    'weight_kg' => $mappedData['weight_kg'],
                    'disability_type' => $mappedData['disability_type'],
                ],
                'academic_history' => [
                    'previous_school' => $mappedData['previous_school'],
                    'previous_school_province' => $mappedData['previous_school_province'],
                    'previous_grade' => $mappedData['previous_grade'],
                ],
                'guardians' => [
                    'father' => [
                        'citizen_id' => preg_replace('/\D/', '', (string) ($mappedData['father_citizen_id'] ?? '')) ?: null,
                        'title' => $mappedData['father_title'],
                        'first_name' => $mappedData['father_first_name'],
                        'last_name' => $mappedData['father_last_name'],
                        'status' => $fatherStatus,
                        'nationality' => $mappedData['father_nationality'] ?? 'ไทย',
                    ],
                    'mother' => [
                        'citizen_id' => preg_replace('/\D/', '', (string) ($mappedData['mother_citizen_id'] ?? '')) ?: null,
                        'title' => $mappedData['mother_title'],
                        'first_name' => $mappedData['mother_first_name'],
                        'last_name' => $mappedData['mother_last_name'],
                        'status' => $motherStatus,
                        'nationality' => $mappedData['mother_nationality'] ?? 'ไทย',
                    ],
                    'primary' => [
                        'citizen_id' => preg_replace('/\D/', '', (string) ($mappedData['guardian_citizen_id'] ?? '')) ?: null,
                        'title' => $mappedData['guardian_title'],
                        'first_name' => $guardianFirstName,
                        'last_name' => $guardianLastName,
                        'relationship' => $mappedData['guardian_relationship'],
                        'phone' => $mappedData['guardian_phone'],
                        'occupation' => $mappedData['guardian_occupation'],
                    ],
                ],
            ];

            $parsedRows->push($normalized);
        }

        return $parsedRows;
    }

    private static function normalizeHeaderName(string $name): string
    {
        $name = trim($name);
        $name = preg_replace('/\s+/', '', $name);

        return $name;
    }

    private static function normalizeStatus(?string $status): string
    {
        $status = trim((string) $status);
        if ($status === 'เสียชีวิต' || $status === 'deceased' || $status === 'ตาย') {
            return 'deceased';
        }

        return 'alive';
    }
}
