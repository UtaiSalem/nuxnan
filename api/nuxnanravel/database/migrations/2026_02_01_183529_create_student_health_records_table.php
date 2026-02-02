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
        Schema::create('student_health_records', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id')->index();
            $table->decimal('weight', 5, 2)->nullable()->comment('น้ำหนัก (kg)');
            $table->decimal('height', 5, 2)->nullable()->comment('ส่วนสูง (cm)');
            $table->text('allergies')->nullable()->comment('ประวัติการแพ้');
            $table->text('underlying_disease')->nullable()->comment('โรคประจำตัว');
            $table->date('recorded_at')->nullable()->comment('วันที่บันทึกข้อมูล');
            $table->timestamps();

            $table->foreign('student_id')->references('id')->on('students')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_health_records');
    }
};
