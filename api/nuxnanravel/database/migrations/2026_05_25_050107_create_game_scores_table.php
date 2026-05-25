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
        Schema::create('game_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('player_name')->nullable();
            $table->string('game_type')->default('crossmath');
            $table->integer('score')->default(0);
            $table->integer('level')->default(1);
            $table->integer('time_spent')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->index(['game_type', 'score']);
            $table->index(['game_type', 'level']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_scores');
    }
};
