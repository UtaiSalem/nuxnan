<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('sports_disciplines')) {
            return;
        }

        Schema::create('sports_disciplines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('edition_id')->constrained('sports_editions')->cascadeOnDelete();
            $table->foreignId('academy_id')->constrained()->cascadeOnDelete();
            $table->string('name', 150);
            $table->enum('type', ['team', 'individual', 'judged']);
            $table->json('scoring_table')->nullable();
            $table->decimal('max_score', 8, 2)->nullable();
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->timestamps();

            $table->unique(['edition_id', 'name'], 'sd_edition_name_unique');
            $table->index(['edition_id', 'display_order'], 'sd_edition_order_idx');
        });

        Schema::create('sports_score_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('edition_id')->constrained('sports_editions')->cascadeOnDelete();
            $table->foreignId('academy_id')->constrained()->cascadeOnDelete();
            $table->foreignId('house_group_id')->constrained('academy_groups')->cascadeOnDelete();
            $table->foreignId('discipline_id')->nullable()->constrained('sports_disciplines')->nullOnDelete();
            $table->enum('source', ['placing', 'judged', 'manual']);
            $table->unsignedSmallInteger('placing')->nullable();
            // points can be negative for point deductions
            $table->decimal('points', 8, 2);
            $table->string('note', 255)->nullable();
            $table->string('ref_type', 60)->nullable();
            $table->unsignedBigInteger('ref_id')->nullable();
            $table->foreignId('awarded_by_user_id')->constrained('users');
            $table->timestamp('voided_at')->nullable();
            $table->foreignId('voided_by_user_id')->nullable()->constrained('users');
            $table->timestamps();

            $table->index(['edition_id', 'house_group_id'], 'sse_edition_house_idx');
            $table->index(['edition_id', 'discipline_id'], 'sse_edition_discipline_idx');
        });

        Schema::create('sports_house_standings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('edition_id')->constrained('sports_editions')->cascadeOnDelete();
            $table->foreignId('house_group_id')->constrained('academy_groups')->cascadeOnDelete();
            $table->decimal('total_points', 10, 2)->default(0);
            $table->unsignedInteger('gold_count')->default(0);
            $table->unsignedInteger('silver_count')->default(0);
            $table->unsignedInteger('bronze_count')->default(0);
            $table->unsignedSmallInteger('rank')->default(0);
            $table->timestamp('computed_at')->nullable();
            $table->timestamps();

            $table->unique(['edition_id', 'house_group_id'], 'shs_edition_house_unique');
            $table->index(['edition_id', 'rank'], 'shs_edition_rank_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sports_house_standings');
        Schema::dropIfExists('sports_score_entries');
        Schema::dropIfExists('sports_disciplines');
    }
};
