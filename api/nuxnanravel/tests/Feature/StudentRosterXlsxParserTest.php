<?php

namespace Tests\Feature;

use App\Services\Import\StudentRosterXlsxParser;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class StudentRosterXlsxParserTest extends TestCase
{
    public function test_it_parses_xlsx_roster_correctly_and_skips_kindergarten_primary(): void
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();

        // Setup headers matching the mapping
        $headers = [
            'เลขประจำตัวประชาชน',
            'เลขประจำตัวนักเรียน ', // Note the trailing space
            'ชั้นเรียน',
            'คำนำหน้าชื่อ',
            'ชื่อ',
            'นามสกุล',
            'เพศ',
            'ว.ด.ป. เกิด',
        ];

        foreach ($headers as $colIndex => $header) {
            $sheet->setCellValueByColumnAndRow($colIndex + 1, 1, $header);
        }

        // Row 2: Secondary grade (Should parse)
        $rowData2 = [
            '1234567890123',
            'S001',
            'ม.1/5',
            'เด็กชาย',
            'สมชาย',
            'ใจดี',
            'ชาย',
            '07 พ.ค. 2569',
        ];
        foreach ($rowData2 as $colIndex => $val) {
            $sheet->setCellValueByColumnAndRow($colIndex + 1, 2, $val);
        }

        // Row 3: Kindergarten grade (Should skip)
        $rowData3 = [
            '2234567890123',
            'K001',
            'อ.1/1',
            'เด็กหญิง',
            'สมหญิง',
            'รักดี',
            'หญิง',
            '15 พ.ค. 2569',
        ];
        foreach ($rowData3 as $colIndex => $val) {
            $sheet->setCellValueByColumnAndRow($colIndex + 1, 3, $val);
        }

        // Row 4: Primary grade (Should skip)
        $rowData4 = [
            '3234567890123',
            'P001',
            'ป.3/2',
            'เด็กชาย',
            'สมรักษ์',
            'ยินดี',
            'ชาย',
            '23 ก.ย. 51',
        ];
        foreach ($rowData4 as $colIndex => $val) {
            $sheet->setCellValueByColumnAndRow($colIndex + 1, 4, $val);
        }

        $tempFile = tempnam(sys_get_temp_dir(), 'test_roster_').'.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempFile);

        try {
            $parser = new StudentRosterXlsxParser;
            $results = $parser->parse($tempFile);

            // Assertions
            $this->assertCount(1, $results); // Only the secondary row
            $first = $results->first();

            $this->assertEquals('S001', $first['identity']['student_id']);
            $this->assertEquals('1234567890123', $first['identity']['citizen_id']);
            $this->assertEquals('ม.1', $first['admission']['grade_level']);
            $this->assertEquals('5', $first['admission']['section']);
            $this->assertEquals('สมชาย', $first['personal']['first_name_th']);
            $this->assertEquals('ใจดี', $first['personal']['last_name_th']);
            $this->assertEquals(1, $first['personal']['gender']);
            $this->assertEquals('2026-05-07', $first['personal']['date_of_birth']);
        } finally {
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        }
    }
}
