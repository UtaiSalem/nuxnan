<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * ระบบอุทธรณ์เกรด
     */
    public function up(): void
    {
        Schema::create('grade_appeals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_member_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();

            // Appeal details
            $table->enum('appeal_type', [
                'score_error',          // คะแนนผิดพลาด
                'attendance_error',     // การเข้าเรียนผิดพลาด
                'grading_criteria',     // เกณฑ์การให้เกรด
                'special_circumstance', // กรณีพิเศษ
                'other',
            ]);

            $table->text('reason')->comment('เหตุผลในการอุทธรณ์');
            $table->json('evidence')->nullable()->comment('หลักฐานประกอบ');

            // Original vs Requested
            $table->string('original_grade', 2);
            $table->decimal('original_score', 8, 2)->nullable();
            $table->string('requested_grade', 2)->nullable()
                ->comment('เกรดที่ขอ (ถ้ามี)');

            // Processing
            $table->enum('status', ['pending', 'reviewing', 'approved', 'rejected', 'withdrawn'])
                ->default('pending');
            $table->foreignId('reviewed_by')->nullable()
                ->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();

            // Result
            $table->string('new_grade', 2)->nullable();
            $table->decimal('new_score', 8, 2)->nullable();
            $table->text('reviewer_notes')->nullable();
            $table->text('rejection_reason')->nullable();

            // Deadline tracking
            $table->timestamp('deadline_at')->nullable()
                ->comment('กำหนดยื่นอุทธรณ์');
            $table->boolean('is_late_appeal')->default(false);

            $table->timestamps();

            $table->index(['course_id', 'status'], 'ga_course_status_idx');
            $table->index(['student_id', 'status'], 'ga_student_status_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grade_appeals');
    }
};
