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
        Schema::create('typing_user_daily_challenges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('challenge_id')->constrained('typing_daily_challenges')->onDelete('cascade');
            $table->foreignId('session_id')->nullable()->constrained('typing_sessions')->onDelete('set null');
            $table->boolean('completed')->default(false);
            $table->unsignedInteger('score')->default(0);
            $table->unsignedSmallInteger('wpm')->default(0);
            $table->decimal('accuracy', 5, 2)->default(0.00);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'challenge_id'], 'unique_user_challenge');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('typing_user_daily_challenges');
    }
};
