<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update groups count from course_groups table
        DB::statement('
            UPDATE courses
            SET groups = (
                SELECT COUNT(*)
                FROM course_groups cg
                WHERE cg.course_id = courses.id
            )
        ');
        
        // Update quizzes count from course_quizzes table
        DB::statement('
            UPDATE courses
            SET quizzes = (
                SELECT COUNT(*)
                FROM course_quizzes cq
                WHERE cq.course_id = courses.id
            )
        ');
        
        // Update enrolled_students count from course_members table
        // Count only active students (status = 1 and role = 1 or 2)
        DB::statement('
            UPDATE courses
            SET enrolled_students = (
                SELECT COUNT(*)
                FROM course_members cm
                WHERE cm.course_id = courses.id
                AND cm.course_member_status = 1
                AND (cm.role = 1 OR cm.role = 2)
            )
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Set all counts to 0 (simple reversal)
        DB::statement('UPDATE courses SET groups = 0, quizzes = 0, enrolled_students = 0');
    }
};
