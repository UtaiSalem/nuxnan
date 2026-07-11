<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Convert typing_sessions.game_mode from a fixed ENUM to a VARCHAR.
     *
     * The controller validation already whitelists the allowed modes
     * (including key_training and letter_runner), but the ENUM column was
     * never extended, so finishing those modes failed with a 500 "Data
     * truncated for column 'game_mode'". A string column keeps the value
     * space open so new modes only need a validation change, not a schema
     * migration.
     *
     * MySQL uses a raw MODIFY; other drivers (SQLite in tests) go through the
     * schema builder, since `MODIFY` is MySQL-specific syntax.
     */
    public function up(): void
    {
        if (! Schema::hasTable('typing_sessions')) {
            return;
        }

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE `typing_sessions` MODIFY `game_mode` VARCHAR(32) NOT NULL');

            return;
        }

        Schema::table('typing_sessions', function (Blueprint $table) {
            $table->string('game_mode', 32)->nullable(false)->change();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('typing_sessions')) {
            return;
        }

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `typing_sessions` MODIFY `game_mode` ENUM('word_typing','time_attack','sentence_typing','monster_battle','falling_words','classroom_race','daily_challenge') NOT NULL");

            return;
        }

        // Non-MySQL rollback simply keeps a plain string column; recreating the
        // original ENUM check constraint is unnecessary for test/dev use.
        Schema::table('typing_sessions', function (Blueprint $table) {
            $table->string('game_mode', 32)->nullable(false)->change();
        });
    }
};
