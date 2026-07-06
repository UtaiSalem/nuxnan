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
        Schema::create('quest_definitions', function (Blueprint $table) {
            $table->id();
            $table->string('quest_key', 64)->unique();
            $table->string('title');
            $table->string('title_th')->nullable();
            $table->text('description')->nullable();
            $table->enum('quest_type', ['daily', 'weekly', 'one_time'])->default('daily');
            $table->string('event_type', 64);
            $table->integer('target_count')->default(1);
            $table->decimal('points_reward', 10, 2)->default(0);
            $table->integer('xp_reward')->default(0);
            $table->string('icon')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('user_quest_progress', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('quest_id');
            $table->date('quest_date');
            $table->integer('progress')->default(0);
            $table->boolean('is_completed')->default(false);
            $table->timestamp('completed_at')->nullable();
            $table->boolean('reward_claimed')->default(false);
            $table->timestamps();

            $table->unique(['user_id', 'quest_id', 'quest_date'], 'uk_user_quest_date');
            $table->index(['user_id', 'quest_date']);

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('quest_id')->references('id')->on('quest_definitions')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_quest_progress');
        Schema::dropIfExists('quest_definitions');
    }
};
