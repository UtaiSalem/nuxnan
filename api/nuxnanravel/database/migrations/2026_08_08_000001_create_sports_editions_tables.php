<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('sports_editions')) {
            return;
        }

        Schema::create('sports_editions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academy_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $table->foreignId('school_event_id')->nullable()->constrained('school_events')->nullOnDelete();
            $table->string('name', 150);
            $table->unsignedSmallInteger('sequence')->default(1);
            $table->enum('status', ['draft', 'active', 'closed'])->default('draft');
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->foreignId('created_by_user_id')->constrained('users');
            $table->timestamps();

            // At most one active edition per academy: both MySQL and SQLite treat NULLs
            // as distinct in a unique index, so only the 'active' rows can collide.
            //
            // ⚠️ VIRTUAL, not STORED, and CASE WHEN, not IF():
            //   * MySQL forbids ON DELETE CASCADE on the base column of a STORED generated
            //     column, and academy_id is a base column here — making this STORED fails
            //     the academy_id foreign key with errno 1215. The same restriction covers
            //     only ON UPDATE for a VIRTUAL column, and this table has no ON UPDATE.
            //   * SQLite, which the test suite runs on, has no IF() function.
            // Neither trap shows up in the test suite, so both have to be right by reading.
            $table->unsignedBigInteger('active_key')
                ->nullable()
                ->virtualAs("CASE WHEN status = 'active' THEN academy_id END");
            $table->unique('active_key', 'se_active_key_unique');

            $table->unique(['academy_id', 'academic_year_id', 'sequence'], 'se_academy_year_sequence_unique');
            $table->index(['academy_id', 'status'], 'se_academy_status_idx');
        });

        Schema::create('sports_edition_houses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('edition_id')->constrained('sports_editions')->cascadeOnDelete();
            $table->foreignId('house_group_id')->constrained('academy_groups')->cascadeOnDelete();
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->timestamps();
            $table->unique(['edition_id', 'house_group_id'], 'seh_edition_house_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sports_edition_houses');
        Schema::dropIfExists('sports_editions');
    }
};
