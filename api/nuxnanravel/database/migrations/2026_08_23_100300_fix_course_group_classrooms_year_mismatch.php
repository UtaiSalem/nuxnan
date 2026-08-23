<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $links = DB::table('course_group_classrooms as cgc')
            ->join('course_groups as cg', 'cgc.course_group_id', '=', 'cg.id')
            ->join('courses as c', 'cg.course_id', '=', 'c.id')
            ->join('academic_years as ay', 'cgc.academic_year_id', '=', 'ay.id')
            ->select('cgc.id', 'c.academic_year as course_year', 'ay.name as ay_name')
            ->whereNull('cgc.created_by_user_id')
            ->get();

        $idsToDelete = [];
        foreach ($links as $link) {
            $courseYear = trim((string) $link->course_year);
            $ayName = trim((string) $link->ay_name);

            if (empty($courseYear) || $courseYear !== $ayName) {
                $idsToDelete[] = $link->id;
            }
        }

        if (! empty($idsToDelete)) {
            DB::table('course_group_classrooms')->whereIn('id', $idsToDelete)->delete();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $groups = DB::table('course_groups')->get();
        foreach ($groups as $group) {
            $pos = strrpos($group->name, '/');
            if ($pos === false) {
                continue;
            }

            $gradeLevel = trim(substr($group->name, 0, $pos));
            $section = trim(substr($group->name, $pos + 1));

            $course = DB::table('courses')->where('id', $group->course_id)->first();
            if (! $course) {
                continue;
            }

            $academyId = $course->academy_id;

            $year = DB::table('academic_years')
                ->where('academy_id', $academyId)
                ->where('is_current', 1)
                ->first();

            if (! $year) {
                continue;
            }

            $classrooms = DB::table('classrooms')
                ->where('academy_id', $academyId)
                ->where('academic_year_id', $year->id)
                ->where('grade_level', $gradeLevel)
                ->where('section', $section)
                ->get();

            if ($classrooms->count() === 1) {
                $classroom = $classrooms->first();

                $exists = DB::table('course_group_classrooms')
                    ->where('course_group_id', $group->id)
                    ->where('classroom_id', $classroom->id)
                    ->exists();

                if (! $exists) {
                    DB::table('course_group_classrooms')->insert([
                        'course_group_id' => $group->id,
                        'classroom_id' => $classroom->id,
                        'academic_year_id' => $year->id,
                        'created_by_user_id' => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }
};
