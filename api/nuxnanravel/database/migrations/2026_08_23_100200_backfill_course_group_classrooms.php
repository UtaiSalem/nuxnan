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
        $groups = DB::table('course_groups')
            ->join('courses', 'course_groups.course_id', '=', 'courses.id')
            ->whereNotNull('courses.academy_id')
            ->select('course_groups.id as group_id', 'course_groups.name', 'courses.academy_id')
            ->get();

        $currentYears = DB::table('academic_years')
            ->where('is_current', 1)
            ->pluck('id', 'academy_id');

        $now = now();

        foreach ($groups as $group) {
            $name = trim($group->name);
            $lastSlashPos = strrpos($name, '/');

            if ($lastSlashPos === false) {
                continue;
            }

            $gradeLevel = trim(substr($name, 0, $lastSlashPos));
            $section = trim(substr($name, $lastSlashPos + 1));

            if ($gradeLevel === '' || $section === '') {
                continue;
            }

            if (! isset($currentYears[$group->academy_id])) {
                continue;
            }

            $currentYearId = $currentYears[$group->academy_id];

            $classrooms = DB::table('classrooms')
                ->where('academy_id', $group->academy_id)
                ->where('grade_level', $gradeLevel)
                ->where('section', $section)
                ->where('academic_year_id', $currentYearId)
                ->get();

            if ($classrooms->count() === 1) {
                $classroom = $classrooms->first();

                $exists = DB::table('course_group_classrooms')
                    ->where('course_group_id', $group->group_id)
                    ->where('classroom_id', $classroom->id)
                    ->exists();

                if (! $exists) {
                    DB::table('course_group_classrooms')->insert([
                        'course_group_id' => $group->group_id,
                        'classroom_id' => $classroom->id,
                        'academic_year_id' => $currentYearId,
                        'created_by_user_id' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('course_group_classrooms')
            ->whereNull('created_by_user_id')
            ->delete();
    }
};
