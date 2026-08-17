<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('sports_matches')) {
            return;
        }

        if (! Schema::hasColumn('sports_disciplines', 'format')) {
            Schema::table('sports_disciplines', function (Blueprint $table) {
                $table->enum('format', ['none', 'knockout', 'round_robin', 'heats'])->default('none')->after('type');
            });
        }

        Schema::create('sports_matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('edition_id')->constrained('sports_editions')->cascadeOnDelete();
            $table->foreignId('academy_id')->constrained()->cascadeOnDelete();
            $table->foreignId('discipline_id')->constrained('sports_disciplines')->cascadeOnDelete();
            $table->foreignId('activity_session_id')->nullable()->constrained('activity_sessions')->nullOnDelete();
            $table->string('round_label', 60)->nullable();
            $table->unsignedSmallInteger('round_order')->default(0);
            $table->unsignedSmallInteger('match_number')->default(1);
            $table->dateTime('scheduled_at')->nullable();
            $table->string('location', 150)->nullable();
            $table->enum('status', ['scheduled', 'in_progress', 'finished', 'cancelled'])->default('scheduled');
            $table->foreignId('winner_house_group_id')->nullable()->constrained('academy_groups')->nullOnDelete();
            $table->unsignedBigInteger('next_match_id')->nullable();
            $table->unsignedTinyInteger('next_match_slot')->nullable();
            $table->string('note', 255)->nullable();
            $table->timestamps();

            $table->index(['edition_id', 'discipline_id', 'round_order'], 'sm_ed_disc_round_idx');
        });

        Schema::table('sports_matches', function (Blueprint $table) {
            $table->foreign('next_match_id', 'sm_next_match_fk')
                ->references('id')->on('sports_matches')->nullOnDelete();
        });

        Schema::create('sports_match_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('match_id')->constrained('sports_matches')->cascadeOnDelete();
            $table->foreignId('house_group_id')->constrained('academy_groups')->cascadeOnDelete();
            $table->unsignedTinyInteger('slot')->default(1);
            $table->decimal('score', 8, 2)->nullable();
            $table->unsignedInteger('time_ms')->nullable();
            $table->unsignedSmallInteger('placing')->nullable();
            $table->enum('status', ['ok', 'dq', 'dns', 'dnf'])->default('ok');
            $table->timestamps();

            $table->unique(['match_id', 'house_group_id'], 'smp_match_house_unique');
            $table->index(['match_id', 'slot'], 'smp_match_slot_idx');
        });

        Schema::create('sports_discipline_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('edition_id')->constrained('sports_editions')->cascadeOnDelete();
            $table->foreignId('discipline_id')->constrained('sports_disciplines')->cascadeOnDelete();
            $table->foreignId('house_group_id')->constrained('academy_groups')->cascadeOnDelete();
            $table->unsignedSmallInteger('placing');
            $table->enum('source', ['suggested', 'manual'])->default('suggested');
            $table->foreignId('score_entry_id')->nullable()->constrained('sports_score_entries')->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable();
            $table->foreignId('confirmed_by_user_id')->nullable()->constrained('users');
            $table->timestamps();

            $table->unique(['discipline_id', 'house_group_id'], 'sdr_disc_house_unique');
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('sports_matches')) {
            Schema::table('sports_matches', function (Blueprint $table) {
                $table->dropForeign('sm_next_match_fk');
            });
        }

        Schema::dropIfExists('sports_discipline_results');
        Schema::dropIfExists('sports_match_participants');
        Schema::dropIfExists('sports_matches');

        if (Schema::hasColumn('sports_disciplines', 'format')) {
            Schema::table('sports_disciplines', function (Blueprint $table) {
                $table->dropColumn('format');
            });
        }
    }
};
