<?php

namespace App\Services\Export;

use App\Services\Import\QuestionImportService;
use Illuminate\Database\Eloquent\Model;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class QuestionExportService
{
    public function build(Model $questionable, string $title): string
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('คำถาม');

        $headers = QuestionImportService::HEADERS;
        $headers[] = 'หมายเหตุ';

        $sheet->fromArray($headers, null, 'A1');
        $sheet->getStyle('1:1')->getFont()->setBold(true);
        $sheet->freezePane('A2');

        $questions = $questionable->questions()
            ->with(['options', 'images'])
            ->orderBy('id')
            ->get();

        $rowNum = 2;
        foreach ($questions as $index => $q) {
            $options = $q->options->sortBy('id')->values();

            $sheet->setCellValue('A'.$rowNum, $index + 1);
            $sheet->setCellValueExplicit('B'.$rowNum, $q->text, DataType::TYPE_STRING);

            for ($i = 0; $i < 6; $i++) {
                $col = chr(ord('C') + $i);
                if (isset($options[$i])) {
                    $sheet->setCellValueExplicit($col.$rowNum, $options[$i]->text, DataType::TYPE_STRING);
                } else {
                    $sheet->setCellValue($col.$rowNum, '');
                }
            }

            $correctIdx = null;

            $correctOption = $options->firstWhere('is_correct', true);
            if ($correctOption) {
                $idx = $options->search(function ($opt) use ($correctOption) {
                    return $opt->id === $correctOption->id;
                });
                if ($idx !== false) {
                    $correctIdx = $idx + 1;
                }
            }

            if ($correctIdx === null && $q->correct_option_id) {
                $idx = $options->search(function ($opt) use ($q) {
                    return $opt->id === $q->correct_option_id;
                });
                if ($idx !== false) {
                    $correctIdx = $idx + 1;
                }
            }

            if ($correctIdx !== null && $correctIdx > 6) {
                $correctIdx = null;
            }

            if ($correctIdx !== null) {
                $sheet->setCellValue('I'.$rowNum, $correctIdx);
            } else {
                $sheet->setCellValue('I'.$rowNum, '');
            }

            $sheet->setCellValue('J'.$rowNum, (int) $q->points);
            $sheet->setCellValueExplicit('K'.$rowNum, $q->explanation ?? '', DataType::TYPE_STRING);
            $sheet->setCellValue('L'.$rowNum, (int) ($q->pp_fine ?? 0));

            $notes = [];
            if ($options->count() > 6) {
                $notes[] = 'มีตัวเลือกเกิน 6 ช่อง ตัดเหลือ 6 ช่องแรก';
            }
            $imgCount = $q->images->count();
            if ($imgCount > 0) {
                $notes[] = "มีรูปภาพ {$imgCount} รูป (ไม่รวมในไฟล์)";
            }

            if (count($notes) > 0) {
                $sheet->setCellValueExplicit('M'.$rowNum, implode(' · ', $notes), DataType::TYPE_STRING);
            } else {
                $sheet->setCellValue('M'.$rowNum, '');
            }

            $rowNum++;
        }

        foreach (range('A', 'M') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('วิธีใช้');
        $instructions = [
            ['ข้อสอบของ: '.$title],
            ['ไฟล์นี้คือข้อสอบที่มีอยู่ในระบบ ณ เวลาที่ดาวน์โหลด'],
            ['หัวคอลัมน์ตรงกับแบบฟอร์มอัปโหลด แก้ไขแล้วอัปโหลดกลับเข้าระบบได้'],
            ['⚠ ระบบอัปโหลดเป็นแบบ "เพิ่มต่อท้าย" เท่านั้น ถ้าอัปโหลดไฟล์นี้กลับทั้งไฟล์ ข้อสอบจะซ้ำทั้งชุด'],
            ['ถ้าต้องการแก้ข้อสอบเดิม ให้แก้จากหน้าแก้ไขข้อสอบในระบบ ไม่ใช่อัปโหลดไฟล์นี้กลับ'],
            ['คอลัมน์ "หมายเหตุ" เป็นข้อมูลประกอบเท่านั้น ระบบจะไม่อ่านคอลัมน์นี้ตอนอัปโหลด'],
            ['ไฟล์นี้ไม่มีรูปภาพประกอบข้อสอบ'],
        ];
        $sheet2->fromArray($instructions, null, 'A1');
        $sheet2->getColumnDimension('A')->setAutoSize(true);

        $spreadsheet->setActiveSheetIndex(0);

        $tmp = tempnam(sys_get_temp_dir(), 'qexp');
        $path = $tmp.'.xlsx';
        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save($path);
        @unlink($tmp);

        return $path;
    }
}
