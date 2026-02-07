<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * ตาราง classrooms - ห้องเรียน/กลุ่มเรียน
     */
    public function up(): void
    {
        Schema::create('classrooms', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('academy_id');
            $table->unsignedBigInteger('academic_year_id')->nullable();
            $table->string('grade_level', 10)->comment('ระดับชั้น เช่น ม.1, ป.1');
            $table->string('section', 10)->comment('ห้อง เช่น 1, 2, 3');
            $table->string('name', 50)->nullable()->comment('ชื่อห้อง เช่น ม.1/1');
            $table->unsignedBigInteger('homeroom_teacher_id')->nullable()->comment('ครูประจำชั้น');
            $table->string('room_location', 100)->nullable()->comment('ตำแหน่งห้อง เช่น อาคาร 1 ชั้น 2');
            $table->integer('capacity')->nullable()->comment('จำนวนที่รับได้');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('academy_id')->references('id')->on('academies')->onDelete('cascade');
            $table->foreign('academic_year_id')->references('id')->on('academic_years')->onDelete('set null');
            $table->foreign('homeroom_teacher_id')->references('id')->on('users')->onDelete('set null');
            $table->unique(['academy_id', 'academic_year_id', 'grade_level', 'section'], 'classrooms_unique');
            $table->index(['academy_id', 'grade_level'], 'classrooms_grade_idx');
        });

        // ตารางนักเรียนในห้องเรียน
        Schema::create('classroom_students', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('classroom_id');
            $table->unsignedBigInteger('student_id');
            $table->integer('student_number')->nullable()->comment('เลขที่');
            $table->enum('status', ['active', 'transferred', 'graduated', 'dropped'])->default('active');
            $table->timestamps();

            $table->foreign('classroom_id')->references('id')->on('classrooms')->onDelete('cascade');
            $table->foreign('student_id')->references('id')->on('students')->onDelete('cascade');
            $table->unique(['classroom_id', 'student_id']);
            $table->index(['classroom_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('classroom_students');
        Schema::dropIfExists('classrooms');
    }
};
