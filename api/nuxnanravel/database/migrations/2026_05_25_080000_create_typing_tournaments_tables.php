<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('typing_tournaments', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('slug', 100)->unique();
            $table->text('description')->nullable();
            $table->enum('type', ['weekly', 'monthly', 'special'])->default('weekly');
            $table->enum('language', ['th', 'en', 'ar', 'any'])->default('en');
            $table->enum('difficulty', ['beginner', 'easy', 'normal', 'hard', 'expert', 'any'])->default('normal');
            $table->enum('game_mode', ['word_typing', 'time_attack', 'sentence_typing'])->default('time_attack');
            $table->unsignedSmallInteger('time_limit')->default(60);

            // Schedule
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->enum('status', ['upcoming', 'active', 'finished'])->default('upcoming');

            // Prizes (Top 3)
            $table->unsignedInteger('prize_1st_xp')->default(500);
            $table->unsignedInteger('prize_1st_pp')->default(100);
            $table->unsignedInteger('prize_2nd_xp')->default(300);
            $table->unsignedInteger('prize_2nd_pp')->default(50);
            $table->unsignedInteger('prize_3rd_xp')->default(150);
            $table->unsignedInteger('prize_3rd_pp')->default(25);
            $table->unsignedInteger('prize_all_xp')->default(20); // Participating prize

            // Stats (cache)
            $table->unsignedInteger('total_entries')->default(0);
            $table->string('banner_color', 20)->default('#3b82f6');

            $table->timestamps();

            $table->index(['status', 'starts_at', 'ends_at'], 'idx_status_dates');
            $table->index(['type', 'status'], 'idx_type_status');
        });

        Schema::create('typing_tournament_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_id')->constrained('typing_tournaments')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->unsignedBigInteger('best_session_id')->nullable();

            // Best performance
            $table->unsignedSmallInteger('best_wpm')->default(0);
            $table->decimal('best_accuracy', 5, 2)->default(0.00);
            $table->unsignedInteger('best_score')->default(0);

            // Tracking
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('last_played_at')->nullable();
            $table->unsignedInteger('rank')->nullable();

            // Rewards
            $table->boolean('prize_claimed')->default(false);
            $table->unsignedInteger('prize_xp')->default(0);
            $table->unsignedInteger('prize_pp')->default(0);

            $table->timestamps();

            $table->unique(['tournament_id', 'user_id'], 'uq_tournament_user');
            $table->index(['tournament_id', 'best_score'], 'idx_tournament_score');
            
            // Note: Foreign key for best_session_id is added later or managed in app to avoid circular dep if needed, 
            // but here it's fine since typing_sessions already exists.
            $table->foreign('best_session_id')->references('id')->on('typing_sessions')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('typing_tournament_entries');
        Schema::dropIfExists('typing_tournaments');
    }
};
