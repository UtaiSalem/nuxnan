<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * บันทึกประวัติการดำเนินการจบวิชา
     */
    public function up(): void
    {
        Schema::create('course_finalization_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('performed_by')->constrained('users')->cascadeOnDelete();

            $table->enum('action', [
                'start_grading',        // เริ่มช่วงให้เกรด
                'publish_draft_grades', // ประกาศเกรดเบื้องต้น
                'finalize_grades',      // ยืนยันเกรดสุดท้าย
                'open_remediation',     // เปิดช่วงแก้ตัว
                'close_remediation',    // ปิดช่วงแก้ตัว
                'reopen_grading',       // เปิดให้แก้เกรดใหม่
                'archive_course',       // เก็บวิชาเข้า Archive
            ]);

            $table->json('snapshot')->nullable()
                ->comment('Snapshot ของข้อมูลสำคัญ ณ ขณะนั้น');
            $table->text('notes')->nullable();

            // Statistics at the time of action
            $table->integer('total_students')->default(0);
            $table->integer('passed_count')->default(0);
            $table->integer('failed_count')->default(0);
            $table->decimal('average_score', 5, 2)->nullable();

            $table->timestamps();

            $table->index(['course_id', 'action'], 'cfl_course_action_idx');
            $table->index('created_at', 'cfl_created_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_finalization_logs');
    }
};
