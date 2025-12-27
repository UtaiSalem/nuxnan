<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id')->nullable();
            $table->bigInteger('instructor_id')->nullable();
            $table->bigInteger('academy_id')->nullable();
            $table->string('name')->nullable();
            $table->string('slug')->nullable();
            $table->string('code')->nullable();
            $table->text('description')->nullable();
            $table->integer('duration')->nullable();
            $table->dateTime('start_date')->nullable();
            $table->dateTime('end_date')->nullable();
            $table->integer('tuition_fees')->nullable();
            $table->integer('price')->nullable();
            $table->integer('discount')->nullable();
            $table->bigInteger('points')->nullable();
            $table->boolean('credit_units')->nullable();
            $table->boolean('hours_per_week')->nullable();
            $table->string('category')->nullable();
            $table->integer('capacity')->nullable();
            $table->integer('enrolled_students')->nullable();
            $table->integer('lessons')->nullable();
            $table->integer('assignments')->nullable();
            $table->integer('quizzes')->nullable();
            $table->integer('groups')->nullable();
            $table->text('class_schedule')->nullable();
            $table->text('prerequisites')->nullable();
            $table->text('course_materials')->nullable();
            $table->boolean('status')->nullable();
            $table->boolean('saleable')->nullable();
            $table->string('accreditation')->nullable();
            $table->string('accreditation_body')->nullable();
            $table->string('level')->nullable();
            $table->double('rating')->nullable();
            $table->string('cover')->nullable();
            $table->text('syllabus')->nullable();
            $table->integer('total_score')->nullable();
            $table->boolean('certificate')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('courses');
    }
};
