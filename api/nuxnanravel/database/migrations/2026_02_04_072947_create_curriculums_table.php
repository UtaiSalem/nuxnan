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
        // ตาราง curriculums - หลักสูตร
        Schema::create('curriculums', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academy_id')->constrained()->onDelete('cascade');
            $table->string('name'); // ชื่อหลักสูตร เช่น "หลักสูตรวิทยาศาสตร์-คณิตศาสตร์"
            $table->string('code')->nullable(); // รหัสหลักสูตร เช่น "SCI-MATH-2568"
            $table->text('description')->nullable();
            $table->string('level')->nullable(); // ระดับการศึกษา เช่น ประถม, มัธยม
            $table->string('academic_year')->nullable(); // ปีการศึกษา
            $table->integer('duration_years')->nullable()->default(1); // ระยะเวลาหลักสูตร (ปี)
            $table->integer('total_credits')->nullable()->default(0); // หน่วยกิตรวม
            $table->integer('required_credits')->nullable()->default(0); // หน่วยกิตบังคับ
            $table->integer('elective_credits')->nullable()->default(0); // หน่วยกิตเลือก
            $table->boolean('is_active')->default(true);
            $table->json('settings')->nullable(); // ตั้งค่าเพิ่มเติม
            $table->timestamps();
            
            $table->index(['academy_id', 'academic_year']);
            $table->index('is_active');
        });

        // ตาราง curriculum_courses - การผูก Course เข้ากับ Curriculum
        Schema::create('curriculum_courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('curriculum_id')->constrained()->onDelete('cascade');
            $table->foreignId('course_id')->constrained()->onDelete('cascade');
            $table->string('course_type')->default('required'); // required, elective, general
            $table->string('semester')->nullable(); // ภาคเรียนที่ควรเรียน เช่น "1", "2"
            $table->integer('year_level')->nullable(); // ชั้นปีที่ควรเรียน เช่น 1, 2, 3
            $table->integer('credits')->nullable(); // หน่วยกิตของรายวิชานี้
            $table->integer('sort_order')->default(0); // ลำดับการแสดง
            $table->boolean('is_required')->default(true); // บังคับเรียนหรือไม่
            $table->json('prerequisites')->nullable(); // รายวิชาที่ต้องผ่านก่อน (course_ids)
            $table->timestamps();
            
            $table->unique(['curriculum_id', 'course_id']);
            $table->index(['curriculum_id', 'course_type']);
            $table->index('year_level');
        });

        // ตาราง curriculum_students - นักเรียนที่ลงทะเบียนในหลักสูตร
        Schema::create('curriculum_students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('curriculum_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('academy_member_id')->nullable();
            $table->integer('current_year_level')->default(1); // ชั้นปีปัจจุบัน
            $table->string('status')->default('active'); // active, graduated, dropped, suspended
            $table->date('enrolled_at')->nullable();
            $table->date('expected_graduation')->nullable();
            $table->date('graduated_at')->nullable();
            $table->integer('completed_credits')->default(0);
            $table->timestamps();
            
            $table->unique(['curriculum_id', 'user_id']);
            $table->index('status');
            $table->foreign('academy_member_id')->references('id')->on('academy_members')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('curriculum_students');
        Schema::dropIfExists('curriculum_courses');
        Schema::dropIfExists('curriculums');
    }
};
