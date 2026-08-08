<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Academy;
use App\Models\ClassroomStudent;
use App\Models\StudentCard;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\GoneHttpException;

class StudentCardSyncService
{
    private function numericGradeLevel(?string $grade): int
    {
        $grade = trim((string) $grade);
        if (preg_match('/^[อปม]\.?\s*(\d+)/ui', $grade, $matches)) {
            return (int) $matches[1];
        }

        if (preg_match('/(\d+)/u', $grade, $matches)) {
            return (int) $matches[1];
        }

        throw new \InvalidArgumentException("Unsupported grade level: {$grade}");
    }

    /**
     * Preview sync operation without making changes
     */
    public function previewSync(Academy $academy, AcademicYear $year): array
    {
        $report = [
            'create' => [],
            'update' => [],
            'expire' => [],
            'unchanged' => [],
            'orphan' => [],
            'duplicate' => [],
        ];

        // 1. Get all active enrollments for this year
        $enrollments = ClassroomStudent::select(
            'classroom_students.*',
            'students.student_id as identifier', 'students.title_prefix_th', 'students.first_name_th', 'students.last_name_th', 'students.first_name_en', 'students.date_of_birth', 'students.citizen_id', 'students.profile_image',
            'classrooms.grade_level', 'classrooms.section'
        )
            ->join('classrooms', 'classroom_students.classroom_id', '=', 'classrooms.id')
            ->join('students', 'classroom_students.student_id', '=', 'students.id')
            ->where('classroom_students.academy_id', $academy->id)
            ->where('classrooms.academy_id', $academy->id)
            ->where('students.academy_id', $academy->id)
            ->where('classroom_students.academic_year_id', $year->id)
            ->where('classroom_students.status', 'active')
            ->where('classrooms.grade_level', 'like', 'ม.%')
            ->get();

        // 2. Get all active cards
        $cardRows = StudentCard::where('academy_id', $academy->id)
            ->where('student_status', 'active')
            ->get();
        foreach ($cardRows->whereNotNull('student_id')->groupBy('student_id') as $studentId => $group) {
            if ($group->count() > 1) {
                $report['duplicate'][] = [
                    'student_id' => (int) $studentId,
                    'card_ids' => $group->pluck('id')->values()->all(),
                ];
            }
        }
        $cards = $cardRows->keyBy('student_id');

        foreach ($enrollments as $enrollment) {
            $studentId = $enrollment->student_id;
            $classLevelNumeric = $this->numericGradeLevel($enrollment->grade_level);
            $classSectionNumeric = (int) $enrollment->section;

            if (! $cards->has($studentId)) {
                // Needs create
                $report['create'][] = [
                    'student_id' => $studentId,
                    'name' => "{$enrollment->title_prefix_th}{$enrollment->first_name_th} {$enrollment->last_name_th}",
                    'target_level' => $classLevelNumeric,
                    'target_section' => $classSectionNumeric,
                ];
            } else {
                $card = $cards->get($studentId);

                // Check if needs update
                if ((int) $card->class_level !== $classLevelNumeric ||
                    (int) $card->class_section !== $classSectionNumeric ||
                    (int) $card->academic_year_id !== (int) $year->id ||
                    (int) $card->order_no !== (int) $enrollment->student_number) {

                    $report['update'][] = [
                        'student_id' => $studentId,
                        'name' => $card->full_name_thai,
                        'from' => "{$card->class_level}/{$card->class_section}",
                        'to' => "{$classLevelNumeric}/{$classSectionNumeric}",
                    ];
                } else {
                    $report['unchanged'][] = $studentId;
                }

                $cards->forget($studentId);
            }
        }

        // Remaining active cards without active enrollment
        foreach ($cards as $studentId => $card) {
            if ($studentId === null || $studentId === '') {
                $report['orphan'][] = $card->id;
            } else {
                $report['expire'][] = [
                    'student_id' => $studentId,
                    'name' => $card->full_name_thai,
                    'current' => "{$card->class_level}/{$card->class_section}",
                ];
            }
        }

        return [
            'counts' => [
                'create' => count($report['create']),
                'update' => count($report['update']),
                'expire' => count($report['expire']),
                'unchanged' => count($report['unchanged']),
                'orphan' => count($report['orphan']),
                'duplicate' => count($report['duplicate']),
            ],
            'details' => $report,
        ];
    }

    /**
     * Commit the sync operation
     */
    public function commitSync(Academy $academy, AcademicYear $year, User $by): array
    {
        if ((bool) $academy->getSettings()?->card_request_flow_enabled) {
            throw new GoneHttpException('Legacy student card sync is disabled. Use student card requests instead.');
        }

        $result = [
            'created' => 0,
            'updated' => 0,
            'expired' => 0,
        ];

        DB::beginTransaction();

        try {
            $preview = $this->previewSync($academy, $year);
            $details = $preview['details'];
            if ($preview['counts']['duplicate'] > 0) {
                throw new \RuntimeException('Duplicate active student cards must be resolved before sync.');
            }

            $issueDate = Carbon::now();
            $expiryDate = Carbon::now()->addYears(3); // Basic policy
            $syncStudentIds = collect($details['create'])
                ->merge($details['update'])
                ->pluck('student_id')
                ->unique()
                ->values();
            $enrollments = ClassroomStudent::select(
                'classroom_students.student_number', 'classroom_students.student_id',
                'students.id as s_id', 'students.student_id as identifier',
                'students.title_prefix_th', 'students.first_name_th', 'students.last_name_th',
                'students.first_name_en', 'students.date_of_birth', 'students.citizen_id',
                'students.profile_image', 'classrooms.grade_level', 'classrooms.section'
            )
                ->join('students', 'classroom_students.student_id', '=', 'students.id')
                ->join('classrooms', 'classroom_students.classroom_id', '=', 'classrooms.id')
                ->where('classroom_students.academy_id', $academy->id)
                ->where('classrooms.academy_id', $academy->id)
                ->where('classroom_students.academic_year_id', $year->id)
                ->where('classroom_students.status', 'active')
                ->whereIn('students.id', $syncStudentIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('student_id');

            // 1. Create cards
            foreach ($details['create'] as $item) {
                $enrollment = $enrollments->get($item['student_id']);

                if ($enrollment) {
                    $classLevelNumeric = $this->numericGradeLevel($enrollment->grade_level);
                    $classSectionNumeric = (int) $enrollment->section;
                    $title = $enrollment->title_prefix_th ?? '';
                    $fullName = trim("{$title} {$enrollment->first_name_th} {$enrollment->last_name_th}");

                    $birthDateString = null;
                    if ($enrollment->date_of_birth) {
                        $birthDateString = Carbon::parse($enrollment->date_of_birth)->format('d/m/Y');
                    }

                    StudentCard::create([
                        'student_id' => $enrollment->s_id,
                        'academy_id' => $academy->id,
                        'academic_year_id' => $year->id,
                        'student_number' => $enrollment->identifier,
                        'full_name_thai' => $fullName,
                        'title_name' => $title,
                        'first_name_thai' => $enrollment->first_name_th,
                        'last_name_thai' => $enrollment->last_name_th,
                        'first_name_english' => $enrollment->first_name_en,
                        'national_id' => $enrollment->citizen_id,
                        'birth_date' => $enrollment->date_of_birth,
                        'birth_date_string' => $birthDateString,
                        'class_level' => $classLevelNumeric,
                        'class_section' => $classSectionNumeric,
                        'level_and_room' => "{$classLevelNumeric}/{$classSectionNumeric}",
                        'card_issue_date' => $issueDate,
                        'card_expiry_date' => $expiryDate,
                        'student_status' => 'active',
                        'profile_image' => $enrollment->profile_image,
                        'order_no' => $enrollment->student_number,
                    ]);
                    $result['created']++;
                }
            }

            // 2. Update cards
            foreach ($details['update'] as $item) {
                $enrollment = $enrollments->get($item['student_id']);

                if ($enrollment) {
                    $classLevelNumeric = $this->numericGradeLevel($enrollment->grade_level);
                    $classSectionNumeric = (int) $enrollment->section;
                    StudentCard::where('student_id', $item['student_id'])
                        ->where('academy_id', $academy->id)
                        ->where('student_status', 'active')
                        ->update([
                            'class_level' => $classLevelNumeric,
                            'class_section' => $classSectionNumeric,
                            'academic_year_id' => $year->id,
                            'level_and_room' => "{$classLevelNumeric}/{$classSectionNumeric}",
                            'order_no' => $enrollment->student_number,
                        ]);
                    $result['updated']++;
                }
            }

            // 3. Expire cards
            $expireIds = array_column($details['expire'], 'student_id');
            if (count($expireIds) > 0) {
                // ห้าม expire บัตรของคนที่ยังมีห้องเรียน active อยู่จริง
                //
                // $details['expire'] มาจาก previewSync() ซึ่งผูกกับ $year ที่ส่งเข้ามา ถ้ามีใคร
                // เรียก sync ด้วยปีที่ไม่ใช่ปีปัจจุบัน นักเรียนที่กำลังเรียนอยู่จะเข้าลิสต์นี้ทั้งโรงเรียน
                // เงื่อนไขนี้จึงเช็คกับ classroom_students ตรง ๆ ตอนจะเขียนจริง ไม่เชื่อลิสต์ที่รับมา
                $result['expired'] = StudentCard::whereIn('student_id', $expireIds)
                    ->where('academy_id', $academy->id)
                    ->where('student_status', 'active')
                    ->whereNotExists(fn ($query) => $query->selectRaw('1')
                        ->from('classroom_students as cs')
                        ->join('classrooms as c', 'c.id', '=', 'cs.classroom_id')
                        ->join('academic_years as ay', 'ay.id', '=', 'c.academic_year_id')
                        ->whereColumn('cs.student_id', 'student_cards.student_id')
                        ->whereColumn('c.academy_id', 'student_cards.academy_id')
                        ->where('cs.status', 'active')
                        ->where('c.status', 'active')
                        ->where('ay.is_current', 1))
                    ->update(['student_status' => 'expired']);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        return $result;
    }
}
