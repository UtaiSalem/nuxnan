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
        Schema::create('typing_stages', function (Blueprint $table) {
            $table->id();
            $table->string('language', 8)->default('th');
            $table->unsignedTinyInteger('level')->default(1);
            $table->unsignedTinyInteger('stage_number')->default(1);
            $table->string('title');
            $table->string('description')->nullable();
            $table->string('stage_type', 32)->default('drill'); // drill, words, phrases, review, exam
            $table->json('target_keys');
            $table->json('exercise_pool');
            $table->decimal('min_accuracy', 5, 2)->default(90.00);
            $table->unsignedSmallInteger('min_wpm')->default(0);
            $table->unsignedSmallInteger('min_duration_sec')->default(0);
            $table->unsignedTinyInteger('pass_count_needed')->default(1);
            $table->unsignedTinyInteger('pass_window_size')->default(1);
            $table->unsignedSmallInteger('xp_reward')->default(20);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['language', 'level', 'stage_number'], 'idx_lang_level_stage');
        });

        Schema::create('typing_user_stages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('stage_id')->constrained('typing_stages')->cascadeOnDelete();
            $table->boolean('is_unlocked')->default(false);
            $table->boolean('is_passed')->default(false);
            $table->unsignedTinyInteger('stars')->default(0);
            $table->decimal('best_accuracy', 5, 2)->default(0.00);
            $table->unsignedSmallInteger('best_wpm')->default(0);
            $table->unsignedSmallInteger('best_cpm')->default(0);
            $table->unsignedInteger('attempts_count')->default(0);
            $table->json('recent_attempts')->nullable();
            $table->timestamp('passed_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'stage_id'], 'idx_user_stage');
            $table->index(['user_id', 'is_unlocked'], 'idx_user_unlocked');
        });

        if (Schema::hasTable('typing_sessions') && ! Schema::hasColumn('typing_sessions', 'stage_id')) {
            Schema::table('typing_sessions', function (Blueprint $table) {
                $table->foreignId('stage_id')->nullable()->after('challenge_id')->constrained('typing_stages')->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('typing_sessions') && Schema::hasColumn('typing_sessions', 'stage_id')) {
            Schema::table('typing_sessions', function (Blueprint $table) {
                $table->dropForeign(['stage_id']);
                $table->dropColumn('stage_id');
            });
        }

        Schema::dropIfExists('typing_user_stages');
        Schema::dropIfExists('typing_stages');
    }
};
