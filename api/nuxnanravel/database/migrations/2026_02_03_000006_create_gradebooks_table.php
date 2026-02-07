<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * ตาราง gradebooks - สมุดบันทึกคะแนน
     */
    public function up(): void
    {
        // ตารางหมวดหมู่การประเมิน (เช่น งานมอบหมาย, Quiz, สอบกลางภาค, สอบปลายภาค)
        Schema::create('assessment_categories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('academy_id');
            $table->string('name', 50);
            $table->string('name_en', 50)->nullable();
            $table->decimal('default_weight', 5, 2)->default(0)->comment('น้ำหนักคะแนนเริ่มต้น %');
            $table->string('icon', 50)->nullable();
            $table->string('color', 20)->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('academy_id')->references('id')->on('academies')->onDelete('cascade');
        });

        // ตารางการประเมินผลในแต่ละรายวิชา
        Schema::create('gradebook_assessments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('course_id');
            $table->unsignedBigInteger('semester_id')->nullable();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->string('name', 100)->comment('ชื่อการประเมิน');
            $table->text('description')->nullable();
            $table->decimal('max_score', 8, 2)->default(100);
            $table->decimal('weight', 5, 2)->default(0)->comment('น้ำหนักคะแนน %');
            $table->date('due_date')->nullable();
            $table->boolean('is_published')->default(false)->comment('เปิดให้นักเรียนดูคะแนน');
            $table->boolean('is_included_in_grade')->default(true)->comment('รวมในการคำนวณเกรด');
            $table->unsignedBigInteger('assignment_id')->nullable()->comment('เชื่อมกับ Assignment');
            $table->unsignedBigInteger('quiz_id')->nullable()->comment('เชื่อมกับ Quiz');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('course_id')->references('id')->on('courses')->onDelete('cascade');
            $table->foreign('semester_id')->references('id')->on('semesters')->onDelete('set null');
            $table->foreign('category_id')->references('id')->on('assessment_categories')->onDelete('set null');
            $table->foreign('assignment_id')->references('id')->on('assignments')->onDelete('set null');
            $table->foreign('quiz_id')->references('id')->on('course_quizzes')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->index(['course_id', 'semester_id']);
        });

        // ตารางคะแนนนักเรียน
        Schema::create('gradebook_scores', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('assessment_id');
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('user_id')->nullable()->comment('User ID ของนักเรียน');
            $table->decimal('score', 8, 2)->nullable();
            $table->text('feedback')->nullable();
            $table->enum('status', ['pending', 'graded', 'excused', 'missing'])->default('pending');
            $table->boolean('is_late')->default(false);
            $table->date('submitted_at')->nullable();
            $table->unsignedBigInteger('graded_by')->nullable();
            $table->timestamp('graded_at')->nullable();
            $table->integer('points_awarded')->default(0)->comment('Gamification points');
            $table->timestamps();

            $table->foreign('assessment_id')->references('id')->on('gradebook_assessments')->onDelete('cascade');
            $table->foreign('student_id')->references('id')->on('students')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('graded_by')->references('id')->on('users')->onDelete('set null');
            $table->unique(['assessment_id', 'student_id']);
            $table->index(['student_id', 'status']);
        });

        // ตารางสรุปเกรดรายวิชา
        Schema::create('course_grades', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('course_id');
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('semester_id')->nullable();
            $table->decimal('total_score', 8, 2)->nullable()->comment('คะแนนรวม');
            $table->decimal('percentage', 5, 2)->nullable()->comment('เปอร์เซ็นต์');
            $table->string('letter_grade', 5)->nullable()->comment('เกรดตัวอักษร');
            $table->decimal('grade_points', 3, 2)->nullable()->comment('ค่าระดับ');
            $table->enum('status', ['in_progress', 'completed', 'withdrawn', 'incomplete'])->default('in_progress');
            $table->text('remarks')->nullable();
            $table->boolean('is_published')->default(false);
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->foreign('course_id')->references('id')->on('courses')->onDelete('cascade');
            $table->foreign('student_id')->references('id')->on('students')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('semester_id')->references('id')->on('semesters')->onDelete('set null');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
            $table->unique(['course_id', 'student_id', 'semester_id']);
            $table->index(['student_id', 'semester_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_grades');
        Schema::dropIfExists('gradebook_scores');
        Schema::dropIfExists('gradebook_assessments');
        Schema::dropIfExists('assessment_categories');
    }
};
