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
        Schema::create('user_stats_recalculation_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('run_id', 36);           // UUID ต่อการรัน 1 ครั้ง
            $table->string('field');                // 'pp', 'earned', 'spent', 'xp', 'level', 'current_xp', etc.
            $table->decimal('value_before', 15, 2)->nullable();
            $table->decimal('value_after', 15, 2)->nullable();
            $table->string('triggered_by')->default('command'); // 'command', 'admin', 'api'
            $table->unsignedBigInteger('triggered_by_user')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index('run_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_stats_recalculation_logs');
    }
};
