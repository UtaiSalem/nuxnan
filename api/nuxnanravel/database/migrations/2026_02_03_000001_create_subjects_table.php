<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * ตาราง subjects - รายวิชาของโรงเรียน
     */
    public function up(): void
    {
        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('academy_id');
            $table->string('subject_code', 20)->nullable();
            $table->string('name_th', 150);
            $table->string('name_en', 150)->nullable();
            $table->text('description')->nullable();
            $table->decimal('credits', 3, 1)->default(1.0)->comment('หน่วยกิต');
            $table->decimal('hours_per_week', 3, 1)->default(1.0)->comment('ชั่วโมง/สัปดาห์');
            $table->enum('subject_type', ['required', 'elective', 'activity'])->default('required');
            $table->string('subject_group', 50)->nullable()->comment('กลุ่มสาระ เช่น วิทยาศาสตร์, คณิตศาสตร์');
            $table->json('grade_levels')->nullable()->comment('ระดับชั้นที่เปิดสอน เช่น ["1","2","3"]');
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('academy_id')->references('id')->on('academies')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->unique(['academy_id', 'subject_code']);
            $table->index(['academy_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subjects');
    }
};
