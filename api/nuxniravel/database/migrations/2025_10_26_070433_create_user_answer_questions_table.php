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
        Schema::create('user_answer_questions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id')->index('user_answer_questions_user_id_foreign');
            $table->bigInteger('course_id');
            $table->bigInteger('quiz_id');
            $table->unsignedBigInteger('question_id')->index('user_answer_questions_question_id_foreign');
            $table->unsignedBigInteger('answer_id');
            $table->unsignedBigInteger('correct_option_id');
            $table->unsignedBigInteger('points');
            $table->integer('edit_count')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_answer_questions');
    }
};
