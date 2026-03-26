<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Compound indexes for progress page query optimization
        Schema::table('assignment_answers', function (Blueprint $table) {
            $table->index(['assignment_id', 'user_id', 'status'], 'aa_assign_user_status_idx');
        });

        Schema::table('course_quiz_results', function (Blueprint $table) {
            $table->index(['course_id', 'user_id'], 'cqr_course_user_idx');
        });

        Schema::table('user_answer_questions', function (Blueprint $table) {
            $table->index(['question_id', 'user_id'], 'uaq_question_user_idx');
        });

        Schema::table('lesson_progress', function (Blueprint $table) {
            $table->index(['lesson_id', 'user_id', 'status'], 'lp_lesson_user_status_idx');
        });

        Schema::table('attendance_details', function (Blueprint $table) {
            $table->index(['course_attendance_id', 'course_member_id', 'status'], 'ad_attendance_member_status_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assignment_answers', function (Blueprint $table) {
            $table->dropIndex('aa_assign_user_status_idx');
        });

        Schema::table('course_quiz_results', function (Blueprint $table) {
            $table->dropIndex('cqr_course_user_idx');
        });

        Schema::table('user_answer_questions', function (Blueprint $table) {
            $table->dropIndex('uaq_question_user_idx');
        });

        Schema::table('lesson_progress', function (Blueprint $table) {
            $table->dropIndex('lp_lesson_user_status_idx');
        });

        Schema::table('attendance_details', function (Blueprint $table) {
            $table->dropIndex('ad_attendance_member_status_idx');
        });
    }
};
