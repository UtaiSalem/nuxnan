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
        Schema::create('lesson_answer_questions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('lesson_id');
            $table->unsignedBigInteger('question_id');
            $table->unsignedBigInteger('answer_id')->nullable(); // Selected option ID
            $table->unsignedBigInteger('points')->default(0);
            $table->boolean('is_correct')->default(false);
            $table->timestamps();

            // Foreign keys if needed, but often skipped for flexibility/speed in prototyping.
            // Let's keep it simple for now, but adding indexes is good practice.
            $table->index(['user_id', 'lesson_id']);
            $table->index(['user_id', 'question_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lesson_answer_questions');
    }
};
