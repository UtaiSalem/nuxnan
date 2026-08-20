<?php

namespace App\Services\Import;

use App\Models\Course;
use App\Models\CourseExternalScore;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ExternalScoreImportService
{
    public function buildTemplate(Course $course, CourseExternalScore $externalScore, ?int $groupId = null): string
    {
        $spreadsheet = new Spreadsheet;

        // ชีต 1: คะแนน
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('คะแนน');

        $maxScore = (float) $externalScore->max_score; // ตัด .00 ออกถ้าเป็นจำนวนเต็ม
        $maxScoreStr = (string) $maxScore;

        $sheet->setCellValue('A1', 'course_member_id');
        $sheet->setCellValue('B1', 'เลขที่');
        $sheet->setCellValue('C1', 'ชื่อ-สกุล');
        $sheet->setCellValue('D1', 'กลุ่มเรียน');
        $sheet->setCellValue('E1', "คะแนน (เต็ม {$maxScoreStr})");
        $sheet->setCellValue('F1', 'หมายเหตุ');

        $sheet->getStyle('1:1')->getFont()->setBold(true);
        $sheet->freezePane('A2');

        $membersQuery = $course->courseMembers()
            ->where('role', '!=', 4)
            ->with('user:id,name,profile_photo_path', 'group:id,name');

        if ($groupId) {
            $membersQuery->where('group_id', $groupId);
        }

        $members = $membersQuery->orderBy('order_number')->get();
        $existing = $externalScore->entries()->get()->keyBy('course_member_id');

        $row = 2;
        foreach ($members as $member) {
            $sheet->setCellValue("A{$row}", $member->id);
            $sheet->setCellValue("B{$row}", $member->order_number);
            $sheet->setCellValue("C{$row}", $member->member_name ?? $member->user?->name ?? 'Unknown');
            $sheet->setCellValue("D{$row}", $member->group?->name ?? '');

            if ($existing->has($member->id)) {
                $entry = $existing->get($member->id);
                $sheet->setCellValue("E{$row}", (float) $entry->score);
                $sheet->setCellValue("F{$row}", $entry->note);
            }

            $row++;
        }

        foreach (['A', 'B', 'C', 'D', 'E', 'F'] as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        $sheet->getColumnDimension('A')->setVisible(false);

        // ชีต 2: _meta
        $meta = $spreadsheet->createSheet();
        $meta->setTitle('_meta');
        $meta->setSheetState(Worksheet::SHEETSTATE_HIDDEN);

        $meta->setCellValue('A1', 'external_score_id');
        $meta->setCellValue('B1', $externalScore->id);
        $meta->setCellValue('A2', 'course_id');
        $meta->setCellValue('B2', $course->id);
        $meta->setCellValue('A3', 'group_id');
        $meta->setCellValue('B3', $groupId ?? '');

        // ชีต 3: วิธีใช้
        $guide = $spreadsheet->createSheet();
        $guide->setTitle('วิธีใช้');

        $guideText = [
            'กรอกคะแนนในชีต "คะแนน" คอลัมน์ E เท่านั้น',
            'ห้ามลบแถว ห้ามสลับแถว ห้ามแก้ชื่อหัวคอลัมน์ในแถวแรก ห้ามลบชีต _meta',
            'เว้นช่องคะแนนว่างไว้ = ไม่เปลี่ยนแปลงคะแนนเดิมของคนนั้น',
            'พิมพ์เครื่องหมาย - ในช่องคะแนน = ล้างคะแนนของคนนั้นทิ้ง',
            "คะแนนต้องอยู่ระหว่าง 0 ถึง {$maxScoreStr}",
            "ไฟล์นี้ใช้ได้กับหัวข้อ \"{$externalScore->title}\" เท่านั้น อัปโหลดข้ามหัวข้อไม่ได้",
        ];

        foreach ($guideText as $index => $text) {
            $guide->setCellValue('A'.($index + 1), $text);
        }
        $guide->getColumnDimension('A')->setAutoSize(true);

        $spreadsheet->setActiveSheetIndex(0);

        $tmp = tempnam(sys_get_temp_dir(), 'ext_score_');
        $path = $tmp.'.xlsx';
        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save($path);
        @unlink($tmp);

        return $path;
    }

    public function parse(UploadedFile $file): array
    {
        $spreadsheet = IOFactory::load($file->getRealPath());

        $metaSheet = $spreadsheet->getSheetByName('_meta');
        if (! $metaSheet) {
            throw new \RuntimeException('ไฟล์นี้ไม่ใช่แบบฟอร์มที่ดาวน์โหลดจากระบบ กรุณาดาวน์โหลดแบบฟอร์มใหม่');
        }

        $meta = [
            'external_score_id' => $metaSheet->getCell('B1')->getValue(),
            'course_id' => $metaSheet->getCell('B2')->getValue(),
            'group_id' => $metaSheet->getCell('B3')->getValue(),
        ];

        if ($meta['external_score_id'] !== null) {
            $meta['external_score_id'] = (int) $meta['external_score_id'];
        }
        if ($meta['course_id'] !== null) {
            $meta['course_id'] = (int) $meta['course_id'];
        }
        if ($meta['group_id'] === '' || $meta['group_id'] === null) {
            $meta['group_id'] = null;
        } else {
            $meta['group_id'] = (int) $meta['group_id'];
        }

        $sheet = $spreadsheet->getSheetByName('คะแนน');
        if (! $sheet) {
            $sheet = $spreadsheet->getSheet(0);
        }

        $data = $sheet->toArray(null, true, true, true);

        $rows = [];
        $rowNumber = 1;
        foreach ($data as $rowIndex => $row) {
            if ($rowIndex === 1) {
                $rowNumber++;

                continue; // ข้าม header
            }
            if (empty($row['A'])) {
                $rowNumber++;

                continue; // ข้ามแถวที่คอลัมน์ A ว่าง
            }

            $rows[] = [
                'row_number' => $rowNumber,
                'course_member_id' => $row['A'] !== null ? (int) $row['A'] : null,
                'order_number' => $row['B'] ?? null,
                'name' => (string) ($row['C'] ?? ''),
                'score_raw' => (string) ($row['E'] ?? ''),
                'note' => $row['F'] === null ? null : (string) $row['F'],
            ];
            $rowNumber++;
        }

        return [
            'meta' => $meta,
            'rows' => $rows,
        ];
    }

    public function validateRows(Course $course, CourseExternalScore $externalScore, ?int $groupId, array $rows): array
    {
        $validMembers = $course->courseMembers()->where('role', '!=', 4)->with('group:id,name')->get()->keyBy('id');
        $existing = $externalScore->entries()->get()->keyBy('course_member_id');

        $result = [];

        foreach ($rows as $row) {
            $memberId = $row['course_member_id'];
            $scoreRaw = trim($row['score_raw']);
            $note = $row['note'];

            $item = [
                'row_number' => $row['row_number'],
                'course_member_id' => $memberId,
                'order_number' => $row['order_number'],
                'name' => $row['name'],
                'group_name' => null,
                'current_score' => null,
                'new_score' => null,
                'note' => $note,
                'action' => 'skip',
                'errors' => [],
                'warnings' => [],
            ];

            $member = $validMembers->get($memberId);
            $hasExisting = $existing->has($memberId);
            $entry = $hasExisting ? $existing->get($memberId) : null;

            if ($member) {
                $item['group_name'] = $member->group?->name;
            }
            if ($entry) {
                $item['current_score'] = (float) $entry->score;
            }

            if (! is_numeric($memberId) || ! $member) {
                $item['errors'][] = 'ไม่พบนักเรียนคนนี้ในรายวิชา';
            } else {
                if ($groupId !== null && $member->group_id !== $groupId) {
                    $item['errors'][] = 'นักเรียนคนนี้ไม่ได้อยู่ในกลุ่มเรียนที่เลือก';
                }
            }

            if ($scoreRaw === '') {
                $item['action'] = 'skip';
            } elseif (in_array($scoreRaw, ['-', '–', '—'])) {
                if (! $hasExisting) {
                    $item['action'] = 'skip';
                } else {
                    $item['action'] = 'clear';
                }
            } elseif (! is_numeric($scoreRaw)) {
                $item['errors'][] = 'คะแนนต้องเป็นตัวเลข';
            } else {
                $numericScore = (float) $scoreRaw;
                if ($numericScore < 0) {
                    $item['errors'][] = 'คะแนนต้องไม่ติดลบ';
                } elseif ($numericScore > (float) $externalScore->max_score) {
                    $item['errors'][] = 'คะแนนเกินคะแนนเต็ม ('.(float) $externalScore->max_score.')';
                } else {
                    $item['action'] = 'set';
                    $item['new_score'] = $numericScore;

                    if ($hasExisting && $item['new_score'] === $item['current_score']) {
                        $item['warnings'][] = 'คะแนนเท่าเดิม';
                    }
                }
            }

            if ($note !== null && mb_strlen($note) > 500) {
                $item['errors'][] = 'หมายเหตุยาวเกิน 500 ตัวอักษร';
            }

            if (count($item['errors']) > 0) {
                $item['action'] = 'skip';
            }

            $result[] = $item;
        }

        return $result;
    }
}
