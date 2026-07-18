<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        $column = DB::selectOne(
            "SELECT EXTRA AS extra
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'user_usage_events'
               AND COLUMN_NAME = 'id'"
        );

        if (! $column || str_contains(strtolower($column->extra), 'auto_increment')) {
            return;
        }

        $primaryKey = DB::selectOne(
            "SELECT COLUMN_NAME
             FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'user_usage_events'
               AND CONSTRAINT_NAME = 'PRIMARY'
             LIMIT 1"
        );

        if (! $primaryKey) {
            DB::statement('ALTER TABLE user_usage_events ADD PRIMARY KEY (id)');
        }

        DB::statement('ALTER TABLE user_usage_events MODIFY id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT');
    }

    public function down(): void
    {
        // Keep the repair forward-only; removing AUTO_INCREMENT breaks inserts.
    }
};
