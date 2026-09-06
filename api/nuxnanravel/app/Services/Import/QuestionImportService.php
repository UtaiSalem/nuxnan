<?php

namespace App\Services\Import;

use App\Models\Course;
use App\Models\CourseQuiz;
use App\Models\User;
use App\Services\CourseScoreService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class QuestionImportService
{
    /**
     * หัวคอลัมน์ของแบบฟอร์มข้อสอบ — ใช้ร่วมกันระหว่างแบบฟอร์มเปล่า (buildTemplate)
     * และไฟล์ที่ export ออกไป (QuestionExportService) ห้ามแก้ลำดับหรือข้อความ
     * เพราะ parse() แมตช์หัวคอลัมน์เหล่านี้ตอนอ่านไฟล์กลับเข้ามา
     */
    public const HEADERS = ['ข้อที่', 'คำถาม', 'ตัวเลือก1', 'ตัวเลือก2', 'ตัวเลือก3', 'ตัวเลือก4', 'ตัวเลือก5', 'ตัวเลือก6', 'เฉลย', 'คะแนน', 'คำอธิบายเฉลย', 'หักคะแนนเมื่อตอบผิด'];

    public function buildTemplate(): string
    {
        $spreadsheet = new Spreadsheet;
        $sheet1 = $spreadsheet->getActiveSheet();
        $sheet1->setTitle('คำถาม');

        $headers = self::HEADERS;
        $sheet1->fromArray($headers, null, 'A1');

        $examples = [
            [1, 'เมืองหลวงของประเทศไทยคือข้อใด', 'กรุงเทพมหานคร', 'เชียงใหม่', 'ภูเก็ต', 'ขอนแก่น', '', '', 1, 1, 'กรุงเทพมหานครเป็นเมืองหลวงตั้งแต่ พ.ศ. 2325', 0],
            [2, '2 + 3 เท่ากับเท่าใด', 4, 5, 6, 7, '', '', 2, 2, '', 0],
        ];
        $sheet1->fromArray($examples, null, 'A2');

        $sheet1->getStyle('1:1')->getFont()->setBold(true);
        $sheet1->freezePane('A2');
        foreach (range('A', 'L') as $col) {
            $sheet1->getColumnDimension($col)->setAutoSize(true);
        }

        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('วิธีใช้');
        $instructions = [
            ['กรอกข้อมูลในชีต "คำถาม" เท่านั้น ห้ามเปลี่ยนชื่อหัวคอลัมน์ในแถวแรก'],
            ['ลบแถวตัวอย่าง 2 แถวออกก่อนอัปโหลด'],
            ['คำถาม 1 แถว = 1 ข้อ อัปโหลดได้สูงสุด 200 ข้อต่อครั้ง'],
            ['ตัวเลือกใส่ได้สูงสุด 6 ช่อง ต้องกรอกอย่างน้อย 2 ช่อง ช่องที่ไม่ใช้เว้นว่างไว้'],
            ['ช่อง "เฉลย" ใส่ได้ข้อเดียวเท่านั้น ใส่เป็นตัวเลข 1-6 หรือ ก-ฉ หรือ A-F'],
            ['ช่อง "คะแนน" เว้นว่างได้ ระบบจะใช้ค่า 1'],
            ['ระบบจะ "เพิ่มต่อท้าย" ข้อสอบเดิมเสมอ ไม่ลบและไม่ทับของเดิม'],
            ['ยังไม่รองรับรูปภาพ ให้เพิ่มรูปทีหลังจากหน้าแก้ไขข้อสอบ'],
        ];
        $sheet2->fromArray($instructions, null, 'A1');
        $sheet2->getColumnDimension('A')->setAutoSize(true);

        $spreadsheet->setActiveSheetIndex(0);

        $tmp = tempnam(sys_get_temp_dir(), 'qtpl');
        $path = $tmp.'.xlsx';
        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save($path);
        @unlink($tmp);

        return $path;
    }

    public function parse(UploadedFile $file): array
    {
        $ext = strtolower($file->getClientOriginalExtension());
        if (in_array($ext, ['xlsx', 'xls'])) {
            $spreadsheet = IOFactory::load($file->getRealPath());
            $sheet = $spreadsheet->getActiveSheet();
            $data = $sheet->toArray(null, true, true, true);
        } else {
            $data = [];
            $i = 1;
            if (($handle = fopen($file->getRealPath(), 'r')) !== false) {
                while (($row = fgetcsv($handle)) !== false) {
                    $assoc = [];
                    $char = 'A';
                    foreach ($row as $val) {
                        $assoc[$char++] = $val;
                    }
                    $data[$i++] = $assoc;
                }
                fclose($handle);
            }
        }

        if (empty($data)) {
            throw new \RuntimeException('ไฟล์ว่างเปล่า');
        }

        if (isset($data[1]['A'])) {
            $data[1]['A'] = trim(ltrim((string) $data[1]['A'], "\xEF\xBB\xBF"));
        }

        $headerMap = [
            'ข้อที่' => 'no', 'no' => 'no', 'ลำดับ' => 'no',
            'คำถาม' => 'text', 'question' => 'text',
            'ตัวเลือก1' => 'option1', 'option1' => 'option1', 'choice1' => 'option1',
            'ตัวเลือก2' => 'option2', 'option2' => 'option2', 'choice2' => 'option2',
            'ตัวเลือก3' => 'option3', 'option3' => 'option3', 'choice3' => 'option3',
            'ตัวเลือก4' => 'option4', 'option4' => 'option4', 'choice4' => 'option4',
            'ตัวเลือก5' => 'option5', 'option5' => 'option5', 'choice5' => 'option5',
            'ตัวเลือก6' => 'option6', 'option6' => 'option6', 'choice6' => 'option6',
            'เฉลย' => 'correct', 'answer' => 'correct', 'correct' => 'correct',
            'คะแนน' => 'points', 'points' => 'points', 'score' => 'points',
            'คำอธิบายเฉลย' => 'explanation', 'explanation' => 'explanation',
            'หักคะแนนเมื่อตอบผิด' => 'pp_fine', 'pp_fine' => 'pp_fine',
        ];

        $colToField = [];
        foreach ($data[1] ?? [] as $colIndex => $val) {
            $val = trim((string) $val);
            if ($val === '') {
                continue;
            }
            $normalized = mb_strtolower(str_replace(' ', '', $val));
            if (isset($headerMap[$normalized])) {
                $colToField[$colIndex] = $headerMap[$normalized];
            }
        }

        $required = ['text' => 'คำถาม', 'option1' => 'ตัวเลือก1', 'option2' => 'ตัวเลือก2', 'correct' => 'เฉลย'];
        $missing = [];
        foreach ($required as $field => $label) {
            if (! in_array($field, $colToField)) {
                $missing[] = $label;
            }
        }
        if (! empty($missing)) {
            throw new \RuntimeException('ไฟล์ไม่มีหัวคอลัมน์ที่จำเป็น: '.implode(', ', $missing));
        }

        $resultRows = [];

        for ($i = 2; $i <= max(array_keys($data)); $i++) {
            if (! isset($data[$i])) {
                continue;
            }

            $row = $data[$i];
            $isEmpty = true;
            foreach ($row as $val) {
                if (trim((string) $val) !== '') {
                    $isEmpty = false;
                    break;
                }
            }
            if ($isEmpty) {
                continue;
            }

            $mapped = [];
            foreach ($colToField as $col => $field) {
                $mapped[$field] = trim((string) ($row[$col] ?? ''));
            }

            $resultRows[] = [
                'row_number' => $i,
                'text' => $mapped['text'] ?? '',
                'options' => [
                    $mapped['option1'] ?? '',
                    $mapped['option2'] ?? '',
                    $mapped['option3'] ?? '',
                    $mapped['option4'] ?? '',
                    $mapped['option5'] ?? '',
                    $mapped['option6'] ?? '',
                ],
                'correct_raw' => $mapped['correct'] ?? '',
                'points_raw' => $mapped['points'] ?? '',
                'explanation' => $mapped['explanation'] ?? '',
                'pp_fine_raw' => $mapped['pp_fine'] ?? '',
            ];
        }

        if (empty($resultRows)) {
            throw new \RuntimeException('ไม่พบข้อมูลคำถามในไฟล์');
        }

        return ['rows' => $resultRows];
    }

    public function validateRows(array $rows): array
    {
        $validated = [];
        $seenQuestions = [];

        foreach ($rows as $row) {
            $errors = [];
            $warnings = [];

            $text = $row['text'] ?? '';
            if ($text === '') {
                $errors[] = 'ไม่ได้กรอกคำถาม';
            } elseif (mb_strlen($text) > 5000) {
                $errors[] = 'คำถามยาวเกิน 5000 ตัวอักษร';
            }

            if ($text !== '' && isset($seenQuestions[$text])) {
                $warnings[] = 'คำถามนี้ซ้ำกับข้ออื่นในไฟล์';
            }
            if ($text !== '') {
                $seenQuestions[$text] = true;
            }

            $options = [];
            $originalIndices = [];
            foreach ($row['options'] ?? [] as $idx => $opt) {
                if ($opt !== '') {
                    $options[] = $opt;
                    $originalIndices[$idx] = count($options) - 1;
                }
            }

            if (count($options) < 2) {
                $errors[] = 'ต้องมีตัวเลือกอย่างน้อย 2 ข้อ';
            }

            $uniqueOptions = array_unique($options);
            if (count($uniqueOptions) < count($options)) {
                $warnings[] = 'มีตัวเลือกซ้ำกัน';
            }

            $correctRaw = trim((string) ($row['correct_raw'] ?? ''));
            $correctIdx = null;
            if ($correctRaw === '') {
                $errors[] = 'เฉลยไม่ถูกต้อง (ใส่ได้ 1-6 หรือ ก-ฉ)';
            } else {
                if (preg_match('/[, \/และ]/', $correctRaw) || mb_strlen($correctRaw) > 1) {
                    $errors[] = 'ระบุเฉลยได้ข้อเดียวเท่านั้น';
                } else {
                    $map = [
                        '1' => 0, '2' => 1, '3' => 2, '4' => 3, '5' => 4, '6' => 5,
                        'ก' => 0, 'ข' => 1, 'ค' => 2, 'ง' => 3, 'จ' => 4, 'ฉ' => 5,
                        'A' => 0, 'B' => 1, 'C' => 2, 'D' => 3, 'E' => 4, 'F' => 5,
                        'a' => 0, 'b' => 1, 'c' => 2, 'd' => 3, 'e' => 4, 'f' => 5,
                    ];
                    if (! isset($map[$correctRaw])) {
                        $errors[] = 'เฉลยไม่ถูกต้อง (ใส่ได้ 1-6 หรือ ก-ฉ)';
                    } else {
                        $originalIndex = $map[$correctRaw];
                        if (! isset($originalIndices[$originalIndex])) {
                            $errors[] = 'เฉลยชี้ไปตัวเลือกที่ไม่ได้กรอก';
                        } else {
                            $correctIdx = $originalIndices[$originalIndex];
                        }
                    }
                }
            }

            $pointsRaw = trim((string) ($row['points_raw'] ?? ''));
            $points = 1;
            if ($pointsRaw !== '') {
                if (! is_numeric($pointsRaw) || $pointsRaw < 0 || $pointsRaw > 100) {
                    $errors[] = 'คะแนนต้องเป็นตัวเลข 0-100';
                } elseif ((string) (int) $pointsRaw !== $pointsRaw) {
                    $errors[] = 'คะแนนต้องเป็นจำนวนเต็ม';
                } else {
                    $points = (int) $pointsRaw;
                }
            }

            $ppFineRaw = trim((string) ($row['pp_fine_raw'] ?? ''));
            $ppFine = 0;
            if ($ppFineRaw !== '') {
                if (! is_numeric($ppFineRaw) || $ppFineRaw < 0) {
                    $errors[] = 'คะแนนที่หักต้องเป็นตัวเลขไม่ติดลบ';
                } elseif ((string) (int) $ppFineRaw !== $ppFineRaw) {
                    $errors[] = 'คะแนนที่หักต้องเป็นจำนวนเต็ม';
                } else {
                    $ppFine = (int) $ppFineRaw;
                }
            }

            $validated[] = [
                'row_number' => $row['row_number'],
                'data' => [
                    'text' => $text,
                    'options' => $options,
                    'correct' => $correctIdx ?? 0,
                    'points' => $points,
                    'explanation' => (isset($row['explanation']) && $row['explanation'] !== '') ? $row['explanation'] : null,
                    'pp_fine' => $ppFine,
                ],
                'errors' => $errors,
                'warnings' => $warnings,
            ];
        }

        return $validated;
    }

    public function commit(Model $questionable, Course $course, array $rows, User $user): array
    {
        $importedIds = [];

        DB::transaction(function () use ($questionable, $course, $rows, $user, &$importedIds) {
            $start = (int) ($questionable->questions()->max('position') ?? 0);

            foreach ($rows as $i => $row) {
                $question = $questionable->questions()->create([
                    'user_id' => $user->id,
                    'course_id' => $course->id,
                    'text' => $row['text'],
                    'type' => 'multiple_choice',
                    'points' => $row['points'],
                    'pp_fine' => $row['pp_fine'],
                    'explanation' => $row['explanation'],
                    'position' => $start + $i + 1,
                ]);

                $correctId = null;
                foreach ($row['options'] as $j => $optText) {
                    $opt = $question->options()->create([
                        'text' => $optText,
                        'is_correct' => $j === $row['correct'],
                        'position' => $j + 1,
                        'status' => 'active',
                    ]);
                    if ($j === $row['correct']) {
                        $correctId = $opt->id;
                    }
                }

                $question->update(['correct_option_id' => $correctId]);
                $importedIds[] = $question->id;
            }

            if ($questionable instanceof CourseQuiz) {
                $questionable->update([
                    'total_score' => (int) $questionable->questions()->sum('points'),
                    'total_questions' => (int) $questionable->questions()->count(),
                ]);
            }
        });

        // The questions are already committed at this point. A failure here must not surface as a
        // failed import — the teacher would retry and, because import is append-only, duplicate
        // every question. A stale course total is recomputed by any later question edit.
        try {
            app(CourseScoreService::class)->syncCourseTotalScore($course);
        } catch (\Throwable $e) {
            Log::error('syncCourseTotalScore failed after question import', [
                'course_id' => $course->id,
                'questionable_type' => $questionable::class,
                'questionable_id' => $questionable->id,
                'imported' => count($rows),
                'exception' => $e->getMessage(),
            ]);
        }

        return ['imported' => count($rows), 'question_ids' => $importedIds];
    }
}
