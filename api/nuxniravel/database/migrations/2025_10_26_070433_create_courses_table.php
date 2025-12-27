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
        Schema::create('courses', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id')->index('courses_user_id_foreign');
            $table->unsignedBigInteger('instructor_id')->index('courses_instructor_id_foreign');
            $table->unsignedBigInteger('academy_id')->nullable();
            $table->string('name');
            $table->string('slug')->nullable();
            $table->string('code')->nullable();
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('duration')->nullable();
            $table->dateTime('start_date')->nullable();
            $table->dateTime('end_date')->nullable();
            $table->integer('tuition_fees')->nullable();
            $table->integer('price')->nullable();
            $table->integer('discount')->nullable()->default(0);
            $table->bigInteger('points')->nullable()->default(0);
            $table->tinyInteger('credit_units')->nullable()->default(0);
            $table->tinyInteger('hours_per_week')->nullable()->default(1);
            $table->string('category')->nullable();
            $table->unsignedInteger('capacity')->nullable();
            $table->unsignedInteger('enrolled_students')->default(0);
            $table->integer('lessons')->nullable()->default(0);
            $table->integer('assignments')->nullable()->default(0);
            $table->integer('quizzes')->nullable()->default(0);
            $table->integer('groups')->nullable()->default(0);
            $table->text('class_schedule')->nullable();
            $table->text('prerequisites')->nullable();
            $table->text('course_materials')->nullable();
            $table->tinyInteger('status')->default(1)->comment('[ 1 => \'Active\', 2 => \'Draft\', 3 => \'Completed\', 4 => \'Closed\', ]');
            $table->boolean('saleable')->nullable();
            $table->string('accreditation')->nullable();
            $table->string('accreditation_body')->nullable();
            $table->string('level')->nullable();
            $table->double('rating')->nullable();
            $table->string('cover')->nullable();
            $table->text('syllabus')->nullable();
            $table->integer('total_score')->nullable()->default(0);
            $table->boolean('certificate')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
