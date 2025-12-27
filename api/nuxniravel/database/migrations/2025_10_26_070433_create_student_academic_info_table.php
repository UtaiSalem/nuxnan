<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('student_academic_info', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('student_id');
            $table->string('current_grade', 10)->nullable()->comment('ชั้นปีปัจจุบัน');
            $table->tinyInteger('education_level')->nullable()->index()->comment('ระดับการศึกษา: 0=อนุบาล, 1=ประถม, 2=มัธยม');
            $table->string('current_class', 10)->nullable()->comment('ห้องเรียนปัจจุบัน');
            $table->string('classroom_full', 20)->nullable()->comment('ชั้น/ห้อง เต็ม');
            $table->string('school_name', 200)->nullable()->comment('ชื่อสถาบันการศึกษา');
            $table->text('school_address')->nullable()->comment('ที่อยู่โรงเรียน');
            $table->string('school_province', 100)->nullable()->comment('จังหวัดของโรงเรียน');
            $table->string('previous_school_name', 200)->nullable()->comment('ชื่อโรงเรียนเดิม');
            $table->string('previous_school_province', 100)->nullable()->comment('จังหวัดโรงเรียนเดิม');
            $table->string('previous_grade_level', 20)->nullable()->comment('ชั้นเรียนเดิม');
            $table->string('disability_type', 100)->nullable()->comment('ประเภทความพิการ');
            $table->text('special_needs')->nullable()->comment('ความต้องการพิเศษ');
            $table->string('academic_year', 10)->nullable()->index()->comment('ปีการศึกษา');
            $table->tinyInteger('semester')->nullable()->comment('ภาคเรียน');
            $table->date('enrollment_date')->nullable()->comment('วันที่เข้าเรียน');
            $table->date('graduation_date')->nullable()->comment('วันที่จบการศึกษา');
            $table->enum('study_status', ['studying', 'graduated', 'transferred', 'dropped', 'suspended'])->default('studying')->index('idx_study_status')->comment('สถานะการศึกษา');
            $table->boolean('is_current')->default(false)->comment('เป็นประวัติปัจจุบันหรือไม่');
            $table->string('transfer_reason', 500)->nullable()->comment('เหตุผลการย้าย/ออก');
            $table->text('notes')->nullable()->comment('หมายเหตุเพิ่มเติม');
            $table->timestamps();

            $table->index(['student_id', 'is_current'], 'idx_student_current');
            $table->index(['student_id', 'academic_year'], 'idx_student_year');
            $table->index(['current_grade', 'current_class']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_academic_info');
    }
};
