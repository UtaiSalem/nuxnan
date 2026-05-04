<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Add unique constraint to prevent duplicate quiz answers for the same question
     */
    public function up(): void
    {
        // First, remove any existing duplicates (keep the earliest record)
        if (DB::getDriverName() === 'mysql') {
            DB::statement('
                DELETE a1 FROM user_answer_questions a1
                INNER JOIN user_answer_questions a2 
                WHERE a1.id > a2.id 
                AND a1.user_id = a2.user_id
                AND a1.quiz_id = a2.quiz_id
                AND a1.question_id = a2.question_id
            ');
        } elseif (DB::getDriverName() === 'sqlite') {
            DB::statement('
                DELETE FROM user_answer_questions 
                WHERE id NOT IN (
                    SELECT MIN(id) 
                    FROM user_answer_questions 
                    GROUP BY user_id, quiz_id, question_id
                )
            ');
        }

        Schema::table('user_answer_questions', function (Blueprint $table) {
            $table->unique(['user_id', 'quiz_id', 'question_id'], 'unique_user_quiz_question_answer');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_answer_questions', function (Blueprint $table) {
            $table->dropUnique('unique_user_quiz_question_answer');
        });
    }
};
