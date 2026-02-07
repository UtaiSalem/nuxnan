<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * การลงทะเบียนแก้ตัวของนักเรียน
     */
    public function up(): void
    {
        Schema::create('course_remediation_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('remediation_session_id')
                ->constrained('course_remediation_sessions')->cascadeOnDelete();
            $table->foreignId('course_member_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            
            // Original performance
            $table->string('original_grade', 2);
            $table->decimal('original_score', 8, 2)->nullable();
            
            // Enrollment status
            $table->enum('status', [
                'enrolled',     // ลงทะเบียนแล้ว
                'confirmed',    // ยืนยันเข้าร่วม
                'attended',     // เข้าร่วมแล้ว
                'submitted',    // ส่งงานแล้ว
                'graded',       // ให้คะแนนแล้ว
                'passed',       // ผ่าน
                'failed',       // ไม่ผ่าน
                'cancelled',    // ยกเลิก
                'no_show'       // ไม่มา
            ])->default('enrolled');
            
            $table->timestamp('enrolled_at');
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('attended_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            
            // Remediation result
            $table->decimal('remediation_score', 8, 2)->nullable();
            $table->string('remediation_grade', 2)->nullable();
            $table->text('grader_notes')->nullable();
            $table->foreignId('graded_by')->nullable()
                ->constrained('users')->nullOnDelete();
            $table->timestamp('graded_at')->nullable();
            
            // Submission (for assignment/project types)
            $table->json('submission')->nullable()
                ->comment('ไฟล์หรือลิงก์งานที่ส่ง');
            $table->text('student_notes')->nullable();
            
            // Attempt tracking
            $table->integer('attempt_number')->default(1)
                ->comment('ครั้งที่แก้ตัว');
            
            $table->timestamps();
            
            $table->unique(['remediation_session_id', 'course_member_id'], 'cre_session_member_unique');
            $table->index(['student_id', 'status'], 'cre_student_status_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_remediation_enrollments');
    }
};
