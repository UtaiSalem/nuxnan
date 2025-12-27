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
        Schema::create('students', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('student_id', 20)->unique()->comment('รหัสนักเรียน');
            $table->string('citizen_id', 13)->nullable()->unique()->comment('เลขประจำตัวประชาชน');
            $table->string('title_prefix_th', 20)->nullable()->comment('คำนำหน้าชื่อ');
            $table->string('first_name_th', 100)->comment('ชื่อ');
            $table->string('last_name_th', 100)->comment('นามสกุล');
            $table->string('middle_name_th', 100)->nullable()->comment('ชื่อกลาง');
            $table->string('title_prefix_en', 20)->nullable()->comment('Title (EN)');
            $table->string('first_name_en', 100)->nullable()->comment('First Name (EN)');
            $table->string('last_name_en', 100)->nullable()->comment('Last Name (EN)');
            $table->string('middle_name_en', 100)->nullable()->comment('Middle Name (EN)');
            $table->string('nickname', 50)->nullable()->comment('ชื่อเล่น');
            $table->date('date_of_birth')->nullable()->comment('วันเดือนปีเกิด');
            $table->tinyInteger('gender')->nullable()->comment('เพศ: 0=หญิง, 1=ชาย');
            $table->string('nationality', 50)->default('ไทย')->comment('สัญชาติ');
            $table->string('religion', 50)->nullable()->comment('ศาสนา');
            $table->string('profile_image')->nullable()->comment('รูปโปรไฟล์นักเรียน');
            $table->enum('status', ['active', 'inactive', 'graduated', 'transferred'])->default('active')->index();
            $table->date('enrollment_date')->nullable()->comment('วันที่เข้าเรียน');
            $table->string('class_level', 10)->nullable()->comment('ระดับชั้น เช่น ม.1, ม.2');
            $table->string('class_section', 10)->nullable()->comment('ห้องเรียน เช่น 1, 2, 3');
            $table->timestamps();

            $table->index(['class_level', 'class_section']);
            $table->index(['first_name_th', 'last_name_th']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
