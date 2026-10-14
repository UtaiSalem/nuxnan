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
            // poll_option_id → question_options (ไม่ใช่ poll_options ที่ไม่มีจริง): ตัวเลือกของโพลล์เก็บใน
            // question_options (Poll::options() morphMany) และ PollVoteController validate
            // exists:question_options,id แล้วเก็บ poll_option_id = question_option->id · เดิม ->constrained()
            // อนุมานตาราง poll_options → migrate จากศูนย์ตายที่ 1824 (G25)
            $table->foreignId('poll_option_id')->constrained('question_options')->onDelete('cascade');
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
