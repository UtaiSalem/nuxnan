<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * ตาราง transcripts - ผลการเรียนรายภาค/ปี
     */
    public function up(): void
    {
        // ผลการเรียนรายภาค
        Schema::create('semester_transcripts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('academy_id');
            $table->unsignedBigInteger('semester_id');
            $table->unsignedBigInteger('classroom_id')->nullable();
            $table->decimal('total_credits', 5, 2)->default(0)->comment('หน่วยกิตรวม');
            $table->decimal('earned_credits', 5, 2)->default(0)->comment('หน่วยกิตที่ได้');
            $table->decimal('gpa', 4, 2)->nullable()->comment('เกรดเฉลี่ยประจำภาค');
            $table->integer('class_rank')->nullable()->comment('อันดับในชั้น');
            $table->integer('total_students_in_class')->nullable();
            $table->integer('grade_rank')->nullable()->comment('อันดับในระดับชั้น');
            $table->integer('total_students_in_grade')->nullable();
            $table->text('remarks')->nullable();
            $table->enum('status', ['draft', 'published', 'approved'])->default('draft');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->foreign('student_id')->references('id')->on('students')->onDelete('cascade');
            $table->foreign('academy_id')->references('id')->on('academies')->onDelete('cascade');
            $table->foreign('semester_id')->references('id')->on('semesters')->onDelete('cascade');
            $table->foreign('classroom_id')->references('id')->on('classrooms')->onDelete('set null');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
            $table->unique(['student_id', 'semester_id']);
            $table->index(['academy_id', 'semester_id']);
        });

        // รายละเอียดผลการเรียนแต่ละวิชา
        Schema::create('semester_transcript_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('transcript_id');
            $table->unsignedBigInteger('course_id');
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('subject_code', 20)->nullable();
            $table->string('subject_name', 150);
            $table->decimal('credits', 3, 1)->default(0);
            $table->decimal('score', 5, 2)->nullable();
            $table->string('letter_grade', 5)->nullable();
            $table->decimal('grade_points', 3, 2)->nullable();
            $table->timestamps();

            $table->foreign('transcript_id')->references('id')->on('semester_transcripts')->onDelete('cascade');
            $table->foreign('course_id')->references('id')->on('courses')->onDelete('cascade');
            $table->foreign('subject_id')->references('id')->on('subjects')->onDelete('set null');
        });

        // ผลการเรียนรายปี (GPAX)
        Schema::create('annual_transcripts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('academy_id');
            $table->unsignedBigInteger('academic_year_id');
            $table->string('grade_level', 10)->comment('ระดับชั้น');
            $table->decimal('total_credits', 5, 2)->default(0);
            $table->decimal('earned_credits', 5, 2)->default(0);
            $table->decimal('gpax', 4, 2)->nullable()->comment('เกรดเฉลี่ยสะสม');
            $table->integer('class_rank')->nullable();
            $table->integer('grade_rank')->nullable();
            $table->enum('promotion_status', ['promoted', 'retained', 'conditional'])->nullable();
            $table->text('remarks')->nullable();
            $table->enum('status', ['draft', 'published', 'approved'])->default('draft');
            $table->timestamps();

            $table->foreign('student_id')->references('id')->on('students')->onDelete('cascade');
            $table->foreign('academy_id')->references('id')->on('academies')->onDelete('cascade');
            $table->foreign('academic_year_id')->references('id')->on('academic_years')->onDelete('cascade');
            $table->unique(['student_id', 'academic_year_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('annual_transcripts');
        Schema::dropIfExists('semester_transcript_items');
        Schema::dropIfExists('semester_transcripts');
    }
};
