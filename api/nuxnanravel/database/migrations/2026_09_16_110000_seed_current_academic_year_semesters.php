<?php

use App\Models\AcademicYear;
use App\Models\Academy;
use App\Models\Semester;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * ภาคเรียนสองภาคที่ migration นี้ "จะสร้าง" ให้ปีการศึกษาหนึ่ง ๆ
     * คิดจากกรอบวันของปีนั้นเอง (ห้าม hardcode ปี/โรงเรียน) — ภาคที่ 1 ยาว 5 เดือนตามรูปแบบเดิมของระบบ
     * up() ใช้สร้าง · down() ใช้เทียบว่าแถวไหนเป็นของ migration นี้จริง ๆ ก่อนลบ
     */
    private function expectedSemesters(AcademicYear $year): array
    {
        $start = $year->start_date->copy();
        $end = $year->end_date->copy();

        $mid = $start->copy()->addMonths(5)->subDay();
        if ($mid >= $end) {
            $mid = $start->copy()->addDays(intdiv($start->diffInDays($end), 2));
        }

        return [
            1 => ['name' => 'ภาคเรียนที่ 1', 'start_date' => $start, 'end_date' => $mid],
            2 => ['name' => 'ภาคเรียนที่ 2', 'start_date' => $mid->copy()->addDay(), 'end_date' => $end],
        ];
    }

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $currentAcademicYears = AcademicYear::where('is_current', true)->get();

        foreach ($currentAcademicYears as $year) {
            foreach ($this->expectedSemesters($year) as $number => $data) {
                Semester::firstOrCreate([
                    'academic_year_id' => $year->id,
                    'semester_number' => $number,
                ], [
                    'name' => $data['name'],
                    'start_date' => $data['start_date'],
                    'end_date' => $data['end_date'],
                    'is_current' => false,
                ]);
            }
        }

        // Normalize is_current per academy
        $academies = Academy::whereHas('academicYears', function ($q) {
            $q->where('is_current', true);
        })->get();

        foreach ($academies as $academy) {
            // Turn off all is_current for this academy
            Semester::whereHas('academicYear', function ($q) use ($academy) {
                $q->where('academy_id', $academy->id);
            })->update(['is_current' => false]);

            $currentYear = AcademicYear::where('academy_id', $academy->id)->where('is_current', true)->first();
            if ($currentYear) {
                $now = now();
                $targetSemester = Semester::where('academic_year_id', $currentYear->id)
                    ->where('start_date', '<=', $now)
                    ->where('end_date', '>=', $now)
                    ->first();

                if (! $targetSemester) {
                    $targetSemester = Semester::where('academic_year_id', $currentYear->id)
                        ->orderBy('semester_number')
                        ->first();
                }

                if ($targetSemester) {
                    $targetSemester->update(['is_current' => true]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // คืนค่า is_current เดิมแบบเป๊ะ ๆ ไม่ได้ เพราะ migration ไม่ได้บันทึกสถานะเดิมไว้

        $currentAcademicYears = AcademicYear::where('is_current', true)->get();

        foreach ($currentAcademicYears as $year) {
            // ลบได้เฉพาะแถวที่ "หน้าตาตรงกับที่ up() สร้าง" เท่านั้น — ภาคเรียนที่โรงเรียนสร้างเอง
            // (ชื่อหรือช่วงวันต่างออกไป) ต้องไม่ถูกลบตอน rollback
            $expected = $this->expectedSemesters($year);

            $semesters = Semester::where('academic_year_id', $year->id)->get();

            foreach ($semesters as $semester) {
                $match = $expected[$semester->semester_number] ?? null;

                if (! $match
                    || $semester->name !== $match['name']
                    || ! $semester->start_date?->isSameDay($match['start_date'])
                    || ! $semester->end_date?->isSameDay($match['end_date'])) {
                    continue;
                }

                $canDelete = true;

                if (Schema::hasTable('class_schedules') && DB::table('class_schedules')->where('semester_id', $semester->id)->exists()) {
                    $canDelete = false;
                }
                if (Schema::hasTable('courses') && DB::table('courses')->where('semester_id', $semester->id)->exists()) {
                    $canDelete = false;
                }
                if (Schema::hasTable('gradebook_assessments') && DB::table('gradebook_assessments')->where('semester_id', $semester->id)->exists()) {
                    $canDelete = false;
                }
                if (Schema::hasTable('course_grades') && DB::table('course_grades')->where('semester_id', $semester->id)->exists()) {
                    $canDelete = false;
                }
                if (Schema::hasTable('semester_transcripts') && DB::table('semester_transcripts')->where('semester_id', $semester->id)->exists()) {
                    $canDelete = false;
                }

                if ($canDelete) {
                    $semester->delete();
                }
            }

            // Restore is_current for the academy if none left
            $academyId = $year->academy_id;
            $hasCurrent = Semester::whereHas('academicYear', function ($q) use ($academyId) {
                $q->where('academy_id', $academyId);
            })->where('is_current', true)->exists();

            if (! $hasCurrent) {
                $latestSemester = Semester::whereHas('academicYear', function ($q) use ($academyId) {
                    $q->where('academy_id', $academyId);
                })->orderByDesc('start_date')->first();

                if ($latestSemester) {
                    $latestSemester->update(['is_current' => true]);
                }
            }
        }
    }
};
