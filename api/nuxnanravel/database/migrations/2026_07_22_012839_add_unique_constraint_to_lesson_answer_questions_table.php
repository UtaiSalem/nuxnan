<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Add unique constraint to prevent duplicate lesson answers for the same question.
     *
     * LessonAnswerQuestionController uses updateOrCreate([user_id, lesson_id, question_id], ...)
     * which is a check-then-insert race: concurrent requests (two tabs, network retry,
     * double submit) can both miss the SELECT and insert duplicate rows. Duplicates inflate
     * correct_count (premature "answered everything correctly" modal), let percentage exceed
     * 100, and cause CourseScoreService::recompute() to double-count points into the gradebook.
     *
     * Mirrors 2026_01_04_045237_add_unique_constraint_to_user_answer_questions_table.php
     * for the sibling user_answer_questions table.
     */
    public function up(): void
    {
        // First, remove any existing duplicates (keep the latest record)
        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement('
                DELETE a1 FROM lesson_answer_questions a1
                INNER JOIN lesson_answer_questions a2
                WHERE a1.id < a2.id
                AND a1.user_id = a2.user_id
                AND a1.lesson_id = a2.lesson_id
                AND a1.question_id = a2.question_id
            ');
        } elseif (DB::getDriverName() === 'sqlite') {
            DB::statement('
                DELETE FROM lesson_answer_questions
                WHERE id NOT IN (
                    SELECT MAX(id)
                    FROM lesson_answer_questions
                    GROUP BY user_id, lesson_id, question_id
                )
            ');
        }

        Schema::table('lesson_answer_questions', function (Blueprint $table) {
            $table->unique(['user_id', 'lesson_id', 'question_id'], 'unique_user_lesson_question_answer');
        });
    }

    /**
     * Reverse the migrations.
     *
     * Note: this only drops the unique index. Duplicate rows deleted by up() are NOT
     * restored - that deletion is irreversible.
     */
    public function down(): void
    {
        Schema::table('lesson_answer_questions', function (Blueprint $table) {
            $table->dropUnique('unique_user_lesson_question_answer');
        });
    }
};
