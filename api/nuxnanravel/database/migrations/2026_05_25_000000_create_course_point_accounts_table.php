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
        Schema::create('course_point_accounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('course_id')->unique();
            $table->unsignedBigInteger('balance')->default(0);         // แต้มคงเหลือ (integer, ไม่มีทศนิยม)
            $table->unsignedBigInteger('total_earned')->default(0);    // รวมรับจาก lesson unlock ทั้งหมด
            $table->unsignedBigInteger('total_withdrawn')->default(0); // ถอนออกโดย owner
            $table->unsignedBigInteger('total_distributed')->default(0); // แจกนักเรียนผ่าน campaign
            $table->decimal('commission_rate', 5, 4)->default(0.0000); // เช่น 0.1000 = 10%
            $table->unsignedBigInteger('minimum_withdrawal')->default(24000); // 24,000 แต้ม = 20 บาท
            $table->timestamps();

            $table->foreign('course_id')->references('id')->on('courses')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_point_accounts');
    }
};
