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
        Schema::create('course_quizzes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id')->index('course_quizzes_user_id_foreign');
            $table->unsignedBigInteger('course_id')->index('course_quizzes_course_id_foreign');
            $table->string('title');
            $table->text('description')->nullable();
            $table->timestamp('start_date')->nullable();
            $table->timestamp('end_date')->nullable();
            $table->integer('total_score')->nullable()->default(0);
            $table->integer('total_questions')->default(0);
            $table->integer('passing_score')->nullable();
            $table->boolean('shuffle_questions')->nullable()->default(false);
            $table->unsignedInteger('time_limit')->nullable();
            $table->boolean('is_active')->nullable()->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_quizzes');
    }
};
