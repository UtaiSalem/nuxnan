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
        Schema::create('user_activity_summaries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->date('summary_date');
            $table->enum('period_type', ['daily', 'weekly', 'monthly'])->default('daily');
            $table->json('event_counts')->nullable();
            $table->decimal('points_earned', 10, 2)->default(0);
            $table->integer('xp_earned')->default(0);
            $table->integer('active_minutes')->default(0);
            $table->integer('lessons_completed')->default(0);
            $table->integer('quizzes_completed')->default(0);
            $table->integer('streak_day')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'summary_date', 'period_type'], 'uk_user_date_period');
            $table->index(['user_id', 'summary_date']);
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_activity_summaries');
    }
};
