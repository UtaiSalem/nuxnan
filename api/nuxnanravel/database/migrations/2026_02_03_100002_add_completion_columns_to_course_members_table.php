<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * เพิ่มคอลัมน์สำหรับระบบสิทธิ์สอบและการจบวิชา
     */
    public function up(): void
    {
        Schema::table('course_members', function (Blueprint $table) {
            // Exam Eligibility
            $table->boolean('exam_eligible')->default(true)
                ->comment('มีสิทธิ์สอบหรือไม่');
            $table->enum('eligibility_status', ['eligible', 'at_risk', 'ineligible', 'unlocked'])
                ->default('eligible')
                ->comment('สถานะสิทธิ์สอบ');
            $table->decimal('absence_percent', 5, 2)->default(0)
                ->comment('เปอร์เซ็นต์ขาดเรียน');
            $table->timestamp('eligibility_unlocked_at')->nullable();
            $table->string('eligibility_unlock_method')->nullable()
                ->comment('วิธีที่ใช้ปลดล็อค: points, reading, admin');

            // Draft Grade (ก่อนประกาศอย่างเป็นทางการ)
            $table->decimal('draft_total_score', 8, 2)->nullable();
            $table->string('draft_grade', 2)->nullable();
            $table->decimal('draft_grade_point', 3, 2)->nullable();

            // Final Grade (หลังประกาศ)
            $table->decimal('final_total_score', 8, 2)->nullable();
            $table->string('final_grade', 2)->nullable();
            $table->decimal('final_grade_point', 3, 2)->nullable();

            // Completion Status
            $table->enum('completion_status', [
                'in_progress',      // กำลังเรียน
                'pending_exam',     // รอสอบ
                'pending_grade',    // รอเกรด
                'pending_acceptance', // รอยืนยันเกรด
                'completed',        // จบแล้ว
                'failed',           // ไม่ผ่าน
                'remediation',      // กำลังแก้ตัว
                'withdrawn',         // ถอนวิชา
            ])->default('in_progress');

            $table->timestamp('grade_accepted_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            // Remediation tracking
            $table->integer('remediation_attempts')->default(0);
            $table->string('original_grade', 2)->nullable()
                ->comment('เกรดเดิมก่อนแก้ตัว');

            // Index for performance
            $table->index(['exam_eligible', 'eligibility_status'], 'cm_eligibility_idx');
            $table->index('completion_status', 'cm_completion_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('course_members', function (Blueprint $table) {
            $table->dropIndex('cm_eligibility_idx');
            $table->dropIndex('cm_completion_idx');
            $table->dropColumn([
                'exam_eligible',
                'eligibility_status',
                'absence_percent',
                'eligibility_unlocked_at',
                'eligibility_unlock_method',
                'draft_total_score',
                'draft_grade',
                'draft_grade_point',
                'final_total_score',
                'final_grade',
                'final_grade_point',
                'completion_status',
                'grade_accepted_at',
                'completed_at',
                'remediation_attempts',
                'original_grade',
            ]);
        });
    }
};
