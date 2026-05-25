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
        Schema::create('typing_user_achievements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('achievement_id')->constrained('typing_achievements')->onDelete('cascade');
            $table->timestamp('earned_at');
            $table->foreignId('session_id')->nullable()->constrained('typing_sessions')->onDelete('set null');
            $table->timestamps();

            $table->unique(['user_id', 'achievement_id'], 'unique_user_ach');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('typing_user_achievements');
    }
};
