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
        Schema::create('student_guardians', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('student_id')->index('student_guardians_student_id_foreign');
            $table->string('student_code', 20)->nullable()->index()->comment('รหัสนักเรียน');
            $table->enum('guardian_type', ['father', 'mother', 'guardian', 'other'])->index();
            $table->string('citizen_id', 13)->nullable()->comment('เลขประจำตัวประชาชน');
            $table->string('title_prefix', 20)->nullable()->comment('คำนำหน้าชื่อ');
            $table->string('first_name', 100)->comment('ชื่อ');
            $table->string('last_name', 100)->comment('นามสกุล');
            $table->string('occupation', 100)->nullable()->comment('อาชีพ');
            $table->string('workplace', 200)->nullable()->comment('สถานที่ทำงาน');
            $table->decimal('monthly_income', 10)->nullable()->comment('รายได้ต่อเดือน');
            $table->string('relationship', 50)->nullable()->comment('ความสัมพันธ์');
            $table->enum('status', ['alive', 'deceased', 'unknown'])->default('alive');
            $table->string('nationality', 50)->default('ไทย');
            $table->boolean('is_primary_contact')->default(false)->index();
            $table->boolean('is_emergency_contact')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_guardians');
    }
};
