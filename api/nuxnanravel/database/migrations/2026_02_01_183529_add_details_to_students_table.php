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
        Schema::table('students', function (Blueprint $table) {
            $table->string('blood_type', 5)->nullable()->after('gender')->comment('กรุ๊ปเลือด');
            $table->text('current_address')->nullable()->after('religion')->comment('ที่อยู่ปัจจุบัน');
            $table->text('registered_address')->nullable()->after('current_address')->comment('ที่อยู่ตามทะเบียนบ้าน');
            $table->string('prev_school')->nullable()->after('class_section')->comment('โรงเรียนเดิม');
            $table->decimal('prev_gpa', 3, 2)->nullable()->after('prev_school')->comment('เกรดเฉลี่ยเดิม');
            $table->enum('admission_type', ['exam', 'quota', 'talent', 'other'])->default('exam')->after('prev_gpa')->comment('ประเภทการรับเข้า');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            //
        });
    }
};
