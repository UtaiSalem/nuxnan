<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Audit trail สำหรับการแก้ไขเกรด
     */
    public function up(): void
    {
        Schema::create('grade_edit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_member_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('edited_by')->constrained('users')->cascadeOnDelete();
            
            // Change type
            $table->enum('change_type', [
                'score_update',         // อัพเดทคะแนน
                'grade_override',       // แก้ไขเกรดตรง
                'attendance_correction', // แก้ไขการเข้าเรียน
                'appeal_result',        // ผลจากการอุทธรณ์
                'remediation_result',   // ผลจากการแก้ตัว
                'admin_adjustment',     // Admin ปรับเอง
                'bulk_update',          // อัพเดทเป็นกลุ่ม
                'system_recalculation'  // ระบบคำนวณใหม่
            ]);
            
            // Before values
            $table->decimal('old_score', 8, 2)->nullable();
            $table->string('old_grade', 2)->nullable();
            $table->decimal('old_grade_point', 3, 2)->nullable();
            
            // After values
            $table->decimal('new_score', 8, 2)->nullable();
            $table->string('new_grade', 2)->nullable();
            $table->decimal('new_grade_point', 3, 2)->nullable();
            
            // Context
            $table->text('reason')->nullable();
            $table->json('metadata')->nullable()
                ->comment('ข้อมูลเพิ่มเติม เช่น assessment ที่แก้ไข');
            
            // Reference to related records
            $table->foreignId('appeal_id')->nullable()
                ->constrained('grade_appeals')->nullOnDelete();
            $table->foreignId('remediation_enrollment_id')->nullable()
                ->constrained('course_remediation_enrollments')->nullOnDelete();
            
            // IP and User Agent for security
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            
            $table->timestamp('created_at');
            
            $table->index(['course_member_id', 'created_at'], 'gel_member_created_idx');
            $table->index(['course_id', 'change_type'], 'gel_course_type_idx');
            $table->index('edited_by', 'gel_edited_by_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grade_edit_logs');
    }
};
