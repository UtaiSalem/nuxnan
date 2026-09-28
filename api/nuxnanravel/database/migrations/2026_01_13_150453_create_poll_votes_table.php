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
        Schema::create('poll_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('poll_id')->constrained()->onDelete('cascade');
            // poll_option_id: ไม่มี FK เพราะ "ตาราง poll_options ไม่มีจริง" (ไม่มี migration/model —
            // ตัวเลือกของโพลล์เก็บใน question_options ผ่าน Poll::options() morphMany) · เดิม ->constrained()
            // อนุมานตาราง poll_options → migrate จากศูนย์ตายที่ 1824 (G25). เก็บ column + index ไว้
            // ให้เจ้าของตัดสินภายหลังว่าจะ repoint ไป question_options หรือถอด poll_option_id ทิ้ง
            $table->unsignedBigInteger('poll_option_id')->index();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->integer('points_earned')->default(0);
            $table->timestamps();

            $table->unique(['poll_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('poll_votes');
    }
};
