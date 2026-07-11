<?php

use Illuminate\Database\Migrations\Migration;
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
     */
    public function up(): void
    {
        if (! Schema::hasTable('typing_sessions')) {
            return;
        }

        DB::statement("ALTER TABLE `typing_sessions` MODIFY `game_mode` VARCHAR(32) NOT NULL");
    }

    public function down(): void
    {
        if (! Schema::hasTable('typing_sessions')) {
            return;
        }

        // Restore the original ENUM definition. Any rows using modes outside
        // this set would block the change; that is acceptable for a rollback.
        DB::statement("ALTER TABLE `typing_sessions` MODIFY `game_mode` ENUM('word_typing','time_attack','sentence_typing','monster_battle','falling_words','classroom_race','daily_challenge') NOT NULL");
    }
};
