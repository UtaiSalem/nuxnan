<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('course_quiz_results', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id')->nullable();
            $table->bigInteger('course_id')->nullable();
            $table->bigInteger('quiz_id')->nullable();
            $table->integer('score')->nullable();
            $table->double('percentage')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->integer('duration')->nullable();
            $table->integer('attempted_questions')->nullable();
            $table->integer('correct_answers')->nullable();
            $table->integer('incorrect_answers')->nullable();
            $table->integer('skipped_questions')->nullable();
            $table->integer('edit_count')->nullable();
            $table->double('efficiency')->nullable();
            $table->integer('status')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('course_quiz_results');
    }
};
