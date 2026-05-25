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
        Schema::create('typing_race_rooms', function (Blueprint $table) {
            $table->id();
            $table->string('room_code', 6)->unique();
            $table->foreignId('host_user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('academy_id')->nullable()->constrained('academies')->onDelete('set null');
            $table->enum('status', ['waiting', 'countdown', 'racing', 'finished'])->default('waiting');
            $table->enum('game_mode', ['word_typing', 'time_attack', 'sentence_typing'])->default('word_typing');
            $table->enum('language', ['th', 'en', 'ar'])->default('en');
            $table->enum('difficulty', ['beginner', 'easy', 'normal', 'hard', 'expert'])->default('normal');
            $table->smallInteger('time_limit')->unsigned()->default(60);
            $table->json('content_ids')->nullable();
            $table->tinyInteger('max_players')->unsigned()->default(10);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('academy_id');
        });

        Schema::create('typing_race_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained('typing_race_rooms')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('session_id')->nullable()->constrained('typing_sessions')->onDelete('set null');
            $table->string('player_name', 100)->nullable();
            $table->enum('status', ['waiting', 'racing', 'finished', 'left'])->default('waiting');
            $table->tinyInteger('progress')->unsigned()->default(0); // 0-100%
            $table->smallInteger('wpm')->unsigned()->default(0);
            $table->decimal('accuracy', 5, 2)->default(0);
            $table->integer('score')->unsigned()->default(0);
            $table->tinyInteger('rank')->unsigned()->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->unique(['room_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('typing_race_participants');
        Schema::dropIfExists('typing_race_rooms');
    }
};
