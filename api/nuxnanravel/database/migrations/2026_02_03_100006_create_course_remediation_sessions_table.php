<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * ช่วงเวลาแก้ตัว (Remediation Sessions)
     */
    public function up(): void
    {
        Schema::create('course_remediation_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();

            $table->string('title');
            $table->text('description')->nullable();

            // Session period
            $table->dateTime('start_at');
            $table->dateTime('end_at');
            $table->dateTime('registration_deadline')->nullable()
                ->comment('กำหนดลงทะเบียนแก้ตัว');

            // Remediation type
            $table->enum('type', [
                'exam_retake',      // สอบใหม่
                'assignment',       // ส่งงานเพิ่ม
                'project',          // ทำโปรเจค
                'attendance_makeup', // เข้าเรียนชดเชย
                'mixed',             // ผสมผสาน
            ])->default('exam_retake');

            // Eligibility criteria
            $table->json('eligible_grades')->nullable()
                ->comment('เกรดที่มีสิทธิ์แก้ตัว เช่น ["F", "D", "D+"]');
            $table->decimal('min_score_to_remediate', 5, 2)->nullable()
                ->comment('คะแนนขั้นต่ำที่มีสิทธิ์แก้ตัว');

            // Settings
            $table->string('max_grade_achievable', 2)->default('C')
                ->comment('เกรดสูงสุดที่ได้จากการแก้ตัว');
            $table->integer('max_participants')->nullable()
                ->comment('จำนวนผู้เข้าร่วมสูงสุด');
            $table->decimal('passing_score', 5, 2)->nullable()
                ->comment('คะแนนที่ต้องได้เพื่อผ่าน');

            // Status
            $table->enum('status', ['draft', 'open', 'in_progress', 'grading', 'completed', 'cancelled'])
                ->default('draft');

            // Location/Method
            $table->string('location')->nullable();
            $table->boolean('is_online')->default(false);
            $table->string('online_link')->nullable();

            $table->text('instructions')->nullable();
            $table->json('attachments')->nullable();

            $table->timestamps();

            $table->index(['course_id', 'status'], 'crs_course_status_idx');
            $table->index('start_at', 'crs_start_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_remediation_sessions');
    }
};
