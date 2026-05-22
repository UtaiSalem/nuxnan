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
        Schema::create('gamification_rule_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('usage_event_id')->nullable();
            $table->string('rule_key', 128);
            $table->enum('result', ['awarded', 'skipped', 'capped', 'cooldown', 'duplicate']);
            $table->decimal('points_awarded', 10, 2)->default(0);
            $table->integer('xp_awarded')->default(0);
            $table->string('reason')->nullable();
            $table->timestamp('evaluated_at');
            $table->timestamps();

            $table->index(['user_id', 'rule_key', 'evaluated_at']);
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gamification_rule_logs');
    }
};
