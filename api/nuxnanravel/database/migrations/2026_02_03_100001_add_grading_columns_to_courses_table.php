<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * เพิ่มคอลัมน์สำหรับระบบจบวิชาและสิทธิ์สอบ
     */
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            // Attendance & Exam Eligibility Settings
            $table->decimal('max_absence_percent', 5, 2)->default(20.00)
                ->comment('เปอร์เซ็นต์ขาดสูงสุดที่ยังมีสิทธิ์สอบ (default 20%)');
            
            // Unlock Exam Eligibility Options
            $table->boolean('allow_unlock_by_points')->default(false)
                ->comment('อนุญาตปลดล็อคสิทธิ์สอบด้วย Points');
            $table->integer('unlock_points_cost')->nullable()
                ->comment('จำนวน Points ที่ต้องใช้ปลดล็อค');
            
            $table->boolean('allow_unlock_by_reading')->default(false)
                ->comment('อนุญาตปลดล็อคด้วยการอ่านเพิ่มเติม');
            $table->integer('unlock_reading_minutes')->nullable()
                ->comment('จำนวนนาทีที่ต้องอ่านเพิ่ม');
            
            // Course Finalization
            $table->enum('finalization_status', ['active', 'grading', 'finalized', 'archived'])
                ->default('active')
                ->comment('สถานะการจบวิชา');
            $table->timestamp('finalized_at')->nullable();
            $table->foreignId('finalized_by')->nullable()
                ->constrained('users')->nullOnDelete();
            
            // Remedial Settings
            $table->boolean('allow_remediation')->default(true)
                ->comment('อนุญาตให้แก้ตัวได้');
            $table->integer('max_remediation_attempts')->default(2)
                ->comment('จำนวนครั้งสูงสุดที่แก้ตัวได้');
            $table->string('remediation_max_grade', 2)->default('C')
                ->comment('เกรดสูงสุดที่ได้จากการแก้ตัว');
            
            // Grade Appeal Settings
            $table->boolean('allow_grade_appeal')->default(true);
            $table->integer('appeal_deadline_days')->default(7)
                ->comment('จำนวนวันที่อนุญาตให้อุทธรณ์หลังประกาศเกรด');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropForeign(['finalized_by']);
            $table->dropColumn([
                'max_absence_percent',
                'allow_unlock_by_points',
                'unlock_points_cost',
                'allow_unlock_by_reading',
                'unlock_reading_minutes',
                'finalization_status',
                'finalized_at',
                'finalized_by',
                'allow_remediation',
                'max_remediation_attempts',
                'remediation_max_grade',
                'allow_grade_appeal',
                'appeal_deadline_days',
            ]);
        });
    }
};
