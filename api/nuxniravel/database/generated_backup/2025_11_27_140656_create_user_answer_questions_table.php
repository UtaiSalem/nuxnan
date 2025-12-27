<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('user_answer_questions', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id')->nullable();
            $table->bigInteger('course_id')->nullable();
            $table->bigInteger('quiz_id')->nullable();
            $table->bigInteger('question_id')->nullable();
            $table->bigInteger('answer_id')->nullable();
            $table->bigInteger('correct_option_id')->nullable();
            $table->bigInteger('points')->nullable();
            $table->integer('edit_count')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_answer_questions');
    }
};
