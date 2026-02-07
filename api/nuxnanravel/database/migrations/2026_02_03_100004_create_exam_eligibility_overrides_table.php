<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * บันทึกการปลดล็อคสิทธิ์สอบ
     */
    public function up(): void
    {
        Schema::create('exam_eligibility_overrides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_member_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            
            $table->enum('unlock_method', ['points', 'reading', 'admin', 'appeal'])
                ->comment('วิธีที่ใช้ปลดล็อค');
            
            // For points unlock
            $table->integer('points_spent')->nullable();
            $table->foreignId('points_transaction_id')->nullable()
                ->comment('อ้างอิง transaction ของ Points');
            
            // For reading unlock
            $table->integer('reading_minutes_completed')->nullable();
            $table->json('reading_proof')->nullable()
                ->comment('หลักฐานการอ่าน: บทความ, วิดีโอ ฯลฯ');
            
            // For admin unlock
            $table->foreignId('approved_by')->nullable()
                ->constrained('users')->nullOnDelete();
            $table->text('admin_reason')->nullable();
            
            // Status
            $table->enum('status', ['pending', 'approved', 'rejected', 'expired'])
                ->default('pending');
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('expires_at')->nullable()
                ->comment('วันหมดอายุของการปลดล็อค');
            
            // Attendance snapshot
            $table->decimal('absence_percent_at_unlock', 5, 2)
                ->comment('เปอร์เซ็นต์ขาด ณ ตอนปลดล็อค');
            $table->integer('total_sessions_at_unlock');
            $table->integer('absent_sessions_at_unlock');
            
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['course_id', 'student_id'], 'eeo_course_student_idx');
            $table->index('status', 'eeo_status_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_eligibility_overrides');
    }
};
