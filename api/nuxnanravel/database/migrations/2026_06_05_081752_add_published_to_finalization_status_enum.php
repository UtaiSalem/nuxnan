<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add 'published' to finalization_status enum
        // Using DB::statement for compatibility with existing data
        DB::statement("ALTER TABLE courses MODIFY COLUMN finalization_status ENUM('active', 'grading', 'published', 'finalized', 'archived') NOT NULL DEFAULT 'active'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE courses MODIFY COLUMN finalization_status ENUM('active', 'grading', 'finalized', 'archived') NOT NULL DEFAULT 'active'");
    }
};
