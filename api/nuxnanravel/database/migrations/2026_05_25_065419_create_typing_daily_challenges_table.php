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
        Schema::create('typing_daily_challenges', function (Blueprint $table) {
            $table->id();
            $table->date('challenge_date')->unique();
            $table->enum('game_mode', ['word_typing', 'time_attack', 'sentence_typing']);
            $table->enum('language', ['th', 'en', 'ar'])->default('en');
            $table->enum('difficulty', ['beginner', 'easy', 'normal', 'hard', 'expert']);
            $table->json('content_ids');
            $table->unsignedSmallInteger('target_wpm')->default(30);
            $table->decimal('target_acc', 5, 2)->default(90.00);
            $table->unsignedSmallInteger('xp_reward')->default(50);
            $table->unsignedSmallInteger('pp_reward')->default(10);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('typing_daily_challenges');
    }
};
