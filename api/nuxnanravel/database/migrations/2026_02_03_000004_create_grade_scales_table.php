<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * ตาราง grade_scales - มาตราส่วนเกรด
     */
    public function up(): void
    {
        Schema::create('grade_scales', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('academy_id');
            $table->string('name', 50)->comment('ชื่อมาตราส่วน เช่น มาตรฐาน, พิเศษ');
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('academy_id')->references('id')->on('academies')->onDelete('cascade');
        });

        // ตารางรายละเอียดเกรด
        Schema::create('grade_scale_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('grade_scale_id');
            $table->string('grade', 5)->comment('เช่น 4, 3.5, 3, 2.5, 2, 1.5, 1, 0');
            $table->string('letter_grade', 5)->nullable()->comment('เช่น A, B+, B, C+, C, D+, D, F');
            $table->decimal('min_score', 5, 2)->comment('คะแนนขั้นต่ำ');
            $table->decimal('max_score', 5, 2)->comment('คะแนนสูงสุด');
            $table->decimal('grade_points', 3, 2)->comment('ค่าระดับ GPA');
            $table->string('description', 50)->nullable()->comment('คำอธิบาย เช่น ดีเยี่ยม, ดี, พอใช้');
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('grade_scale_id')->references('id')->on('grade_scales')->onDelete('cascade');
            $table->unique(['grade_scale_id', 'grade']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grade_scale_items');
        Schema::dropIfExists('grade_scales');
    }
};
