<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Academy;
use App\Models\ClassroomStudent;
use App\Models\Student;
use App\Models\StudentCard;
use Illuminate\Support\Facades\DB;

class StudentCardAuditService
{
    /**
     * Generate an audit report for student cards and related records.
     */
    public function audit(Academy $academy, AcademicYear $year, array $levels = []): array
    {
        $report = [
            'section_a_m6' => $this->auditM6($academy, $year),
            'section_b_new_intake' => $this->auditNewIntake($academy, $year),
            'section_c_consistency' => $this->auditConsistency($academy, $year, $levels),
        ];

        return $report;
    }

    protected function auditM6(Academy $academy, AcademicYear $year): array
    {
        // 1. Active enrollment ของ ม.6 ในปีที่ระบุ
        $activeM6 = ClassroomStudent::select('classroom_students.*')
            ->join('classrooms', 'classroom_students.classroom_id', '=', 'classrooms.id')
            ->where('classrooms.academy_id', $academy->id)
            ->where('classroom_students.academic_year_id', $year->id)
            ->where('classrooms.grade_level', 'ม.6')
            ->where('classroom_students.status', 'active')
            ->count();

        // 2. ม.6 ที่ student_card ยัง active อยู่ (ดึงจากบัตรโดยตรง)
        $activeCardsM6 = StudentCard::where('academy_id', $academy->id)
            ->where('class_level', 6)
            ->where('student_status', 'active')
            ->get(['id', 'student_id', 'full_name_thai', 'level_and_room']);

        // 3. คนที่ classroom_students.status = 'graduated' แต่บัตรยัง active
        $unsyncedGraduates = StudentCard::select('student_cards.*')
            ->join('classroom_students', 'student_cards.student_id', '=', 'classroom_students.student_id')
            ->where('student_cards.academy_id', $academy->id)
            ->where('student_cards.student_status', 'active')
            ->where('classroom_students.status', 'graduated')
            ->where('classroom_students.academic_year_id', $year->id)
            ->groupBy('student_cards.id')
            ->get(['student_cards.id', 'student_cards.student_id', 'student_cards.full_name_thai']);

        // 4. ม.6 ที่มีบัตร active หลายใบ
        $duplicateCards = StudentCard::select('student_id', DB::raw('count(*) as total'))
            ->where('academy_id', $academy->id)
            ->where('class_level', 6)
            ->where('student_status', 'active')
            ->whereNotNull('student_id')
            ->groupBy('student_id')
            ->having('total', '>', 1)
            ->get();

        // 5. ม.6 ที่ไม่มี student_academic_info ปัจจุบัน
        $missingAcademicInfo = Student::select('students.id', 'students.student_id', 'students.first_name_th', 'students.last_name_th')
            ->join('classroom_students', 'students.id', '=', 'classroom_students.student_id')
            ->join('classrooms', 'classroom_students.classroom_id', '=', 'classrooms.id')
            ->leftJoin('student_academic_info', function ($join) {
                $join->on('students.id', '=', 'student_academic_info.student_id')
                    ->where('student_academic_info.is_current', 1);
            })
            ->where('students.academy_id', $academy->id)
            ->where('classrooms.grade_level', 'ม.6')
            ->where('classroom_students.academic_year_id', $year->id)
            ->where('classroom_students.status', 'active')
            ->whereNull('student_academic_info.id')
            ->get();

        return [
            'active_m6_enrollments' => $activeM6,
            'active_m6_cards' => $activeCardsM6,
            'unsynced_graduates' => $unsyncedGraduates,
            'duplicate_active_cards' => $duplicateCards,
            'missing_academic_info' => $missingAcademicInfo,
        ];
    }

    protected function auditNewIntake(Academy $academy, AcademicYear $year): array
    {
        // 1. คนที่อยู่ใน students (ม.1/ม.4) แต่ไม่มี active enrollment
        $pendingIntakes = Student::select('id', 'student_id', 'first_name_th', 'last_name_th', 'class_level')
            ->where('academy_id', $academy->id)
            ->where('status', 'active')
            ->whereIn('class_level', ['ม.1', 'ม.4'])
            ->whereNotExists(function ($query) use ($year) {
                $query->select(DB::raw(1))
                    ->from('classroom_students')
                    ->whereColumn('classroom_students.student_id', 'students.id')
                    ->where('classroom_students.academic_year_id', $year->id)
                    ->where('classroom_students.status', 'active');
            })
            ->get();

        // 2. คนที่มี enrollment แล้วแต่ไม่มี student_card
        $enrolledWithoutCard = ClassroomStudent::select('students.id', 'students.student_id', 'students.first_name_th', 'students.last_name_th')
            ->join('students', 'classroom_students.student_id', '=', 'students.id')
            ->join('classrooms', 'classroom_students.classroom_id', '=', 'classrooms.id')
            ->where('classrooms.academy_id', $academy->id)
            ->where('classroom_students.academic_year_id', $year->id)
            ->where('classroom_students.status', 'active')
            ->whereIn('classrooms.grade_level', ['ม.1', 'ม.4'])
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('student_cards')
                    ->whereColumn('student_cards.student_id', 'students.id')
                    ->where('student_cards.student_status', 'active');
            })
            ->get();

        // 3. คนที่มี student_card active แต่ student_id = NULL
        $orphanCards = StudentCard::where('academy_id', $academy->id)
            ->where('student_status', 'active')
            ->whereNull('student_id')
            ->get(['id', 'full_name_thai', 'level_and_room', 'student_number', 'national_id']);

        // 4. ขาดข้อมูลจำเป็น
        $missingData = Student::where('academy_id', $academy->id)
            ->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('first_name_th')
                    ->orWhereNull('student_id')
                    ->orWhereNull('date_of_birth')
                    ->orWhereNull('citizen_id');
            })
            ->get(['id', 'student_id', 'first_name_th', 'last_name_th', 'date_of_birth', 'citizen_id']);

        // 5. student_id หรือ citizen_id ซ้ำ
        $duplicateIds = Student::select('student_id', DB::raw('count(*) as total'))
            ->where('academy_id', $academy->id)
            ->where('status', 'active')
            ->whereNotNull('student_id')
            ->groupBy('student_id')
            ->having('total', '>', 1)
            ->get();

        $duplicateCitizens = Student::select('citizen_id', DB::raw('count(*) as total'))
            ->where('academy_id', $academy->id)
            ->where('status', 'active')
            ->whereNotNull('citizen_id')
            ->groupBy('citizen_id')
            ->having('total', '>', 1)
            ->get();

        return [
            'pending_intakes' => $pendingIntakes,
            'enrolled_without_card' => $enrolledWithoutCard,
            'orphan_cards' => $orphanCards,
            'missing_data' => $missingData,
            'duplicate_student_ids' => $duplicateIds,
            'duplicate_citizen_ids' => $duplicateCitizens,
        ];
    }

    protected function auditConsistency(Academy $academy, AcademicYear $year, array $levels = []): array
    {
        $query = Student::select(
            'students.id', 'students.student_id', 'students.status as student_status',
            'classroom_students.status as enrollment_status', 'classrooms.grade_level as classroom_level',
            'student_cards.class_level as card_level'
        )
            ->join('classroom_students', function ($join) use ($year) {
                $join->on('students.id', '=', 'classroom_students.student_id')
                    ->where('classroom_students.academic_year_id', $year->id)
                    ->where('classroom_students.status', 'active');
            })
            ->join('classrooms', 'classroom_students.classroom_id', '=', 'classrooms.id')
            ->leftJoin('student_cards', function ($join) {
                $join->on('students.id', '=', 'student_cards.student_id')
                    ->where('student_cards.student_status', 'active');
            })
            ->where('students.academy_id', $academy->id);

        if (! empty($levels)) {
            $query->whereIn('classrooms.grade_level', $levels);
        }

        $allStudents = $query->get();

        $inconsistentStatus = [];
        $staleSnapshots = [];
        $wrongYearEnrollments = [];

        foreach ($allStudents as $student) {
            // students ↔ classroom_students
            if ($student->student_status !== $student->enrollment_status) {
                $inconsistentStatus[] = $student;
            }

            // students ↔ student_cards (e.g. 'ม.1' vs 1)
            if ($student->card_level !== null) {
                $expectedCardLevel = (int) str_replace('ม.', '', $student->classroom_level);
                if ($student->card_level !== $expectedCardLevel) {
                    $staleSnapshots[] = $student;
                }
            }
        }

        // classroom_students ↔ classrooms (enrollment ที่ชี้ classroom ไม่อยู่ในปีปัจจุบัน)
        // Since we explicitly filter classroom_students.academic_year_id = $year->id, we need to check if classroom itself is in the same year
        $wrongYearEnrollments = ClassroomStudent::select('classroom_students.id', 'classroom_students.student_id', 'classrooms.academic_year_id as class_year_id', 'classroom_students.academic_year_id as enroll_year_id')
            ->join('classrooms', 'classroom_students.classroom_id', '=', 'classrooms.id')
            ->where('classrooms.academy_id', $academy->id)
            ->where('classroom_students.status', 'active')
            ->where('classroom_students.academic_year_id', $year->id)
            ->whereColumn('classrooms.academic_year_id', '!=', 'classroom_students.academic_year_id')
            ->get();

        return [
            'inconsistent_status' => $inconsistentStatus,
            'stale_card_snapshots' => $staleSnapshots,
            'wrong_year_enrollments' => $wrongYearEnrollments,
        ];
    }
}
