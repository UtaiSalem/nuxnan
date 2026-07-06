<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE courses MODIFY COLUMN finalization_status ENUM('active', 'grading', 'published', 'finalized', 'archived') NOT NULL DEFAULT 'active'");
        }
        // SQLite doesn't need changes as it handles strings/enums as text
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE courses MODIFY COLUMN finalization_status ENUM('active', 'grading', 'finalized', 'archived') NOT NULL DEFAULT 'active'");
        }
    }
};
