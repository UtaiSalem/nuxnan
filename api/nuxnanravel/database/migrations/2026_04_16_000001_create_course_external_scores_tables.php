<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // หัวข้อการบันทึกคะแนนจากภายนอก (เช่น "สอบกลางภาค", "กิจกรรมทัศนศึกษา")
        Schema::create('course_external_scores', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('course_id')->index();
            $table->unsignedBigInteger('created_by')->index(); // ผู้สร้าง (ครู/แอดมิน)
            $table->string('title');                           // ชื่อหัวข้อ
            $table->text('description')->nullable();           // คำอธิบาย
            $table->string('category')->default('other');      // exam, activity, project, other
            $table->decimal('max_score', 8, 2)->default(0);   // คะแนนเต็ม
            $table->unsignedBigInteger('group_id')->nullable()->index(); // กลุ่มที่เกี่ยวข้อง (null = ทุกกลุ่ม)
            $table->date('scored_at')->nullable();             // วันที่จัดกิจกรรม/สอบ
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('course_id')->references('id')->on('courses')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('group_id')->references('id')->on('course_groups')->onDelete('set null');
        });

        // คะแนนรายบุคคลของนักเรียนแต่ละคน
        Schema::create('course_external_score_entries', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('external_score_id')->index();
            $table->unsignedBigInteger('course_member_id')->index();
            $table->decimal('score', 8, 2)->default(0);       // คะแนนที่ได้
            $table->text('note')->nullable();                  // หมายเหตุรายบุคคล
            $table->unsignedBigInteger('scored_by')->index();  // ผู้ให้คะแนน
            $table->timestamps();

            $table->foreign('external_score_id')->references('id')->on('course_external_scores')->onDelete('cascade');
            $table->foreign('course_member_id')->references('id')->on('course_members')->onDelete('cascade');
            $table->foreign('scored_by')->references('id')->on('users')->onDelete('cascade');

            $table->unique(['external_score_id', 'course_member_id'], 'ext_score_member_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_external_score_entries');
        Schema::dropIfExists('course_external_scores');
    }
};
