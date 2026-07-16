<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $connection = config('telescope.storage.database.connection') ?: config('database.default');

        // information_schema / AUTO_INCREMENT are MySQL-only; skip on sqlite (tests) etc.
        if (DB::connection($connection)->getDriverName() !== 'mysql') {
            return;
        }

        $schema = DB::connection($connection)->getDatabaseName();

        $column = DB::connection($connection)->selectOne(
            'SELECT EXTRA FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            [$schema, 'telescope_entries', 'sequence']
        );

        if ($column && ! str_contains(strtolower((string) $column->EXTRA), 'auto_increment')) {
            DB::connection($connection)->statement(
                'ALTER TABLE `telescope_entries` MODIFY `sequence` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT'
            );
        }
    }

    public function down(): void
    {
        // Keep the column auto-incrementing; removing it would break Telescope writes.
    }
};
