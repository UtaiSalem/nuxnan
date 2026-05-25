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
        Schema::create('typing_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('session_token', 64)->unique();
            $table->enum('game_mode', [
                'word_typing', 'time_attack', 'sentence_typing', 
                'monster_battle', 'falling_words', 'classroom_race', 
                'daily_challenge'
            ]);
            $table->enum('language', ['th', 'en', 'ar'])->default('en');
            $table->enum('difficulty', ['beginner', 'easy', 'normal', 'hard', 'expert']);

            // Stats
            $table->unsignedSmallInteger('wpm')->default(0);
            $table->unsignedSmallInteger('raw_wpm')->default(0);
            $table->decimal('accuracy', 5, 2)->default(0.00);
            $table->unsignedInteger('score')->default(0);
            $table->unsignedSmallInteger('max_combo')->default(0);
            $table->unsignedInteger('correct_chars')->default(0);
            $table->unsignedInteger('total_chars')->default(0);
            $table->unsignedSmallInteger('correct_words')->default(0);
            $table->unsignedSmallInteger('total_words')->default(0);
            $table->unsignedSmallInteger('mistakes')->default(0);
            $table->unsignedSmallInteger('time_elapsed')->default(0);
            $table->unsignedSmallInteger('time_limit')->nullable();

            // Bonuses
            $table->integer('speed_bonus')->default(0);
            $table->integer('combo_bonus')->default(0);
            $table->integer('accuracy_bonus')->default(0);
            $table->unsignedSmallInteger('xp_earned')->default(0);

            // Context
            $table->foreignId('classroom_id')->nullable()->constrained('academies')->onDelete('set null');
            $table->unsignedBigInteger('challenge_id')->nullable();
            $table->boolean('completed')->default(false);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'game_mode'], 'idx_user_mode');
            $table->index(['user_id', 'created_at'], 'idx_user_date');
            $table->index(['game_mode', 'score'], 'idx_mode_score'); // Note: index order usually ASC, for DESC we rely on query
            $table->index('classroom_id', 'idx_classroom');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('typing_sessions');
    }
};
