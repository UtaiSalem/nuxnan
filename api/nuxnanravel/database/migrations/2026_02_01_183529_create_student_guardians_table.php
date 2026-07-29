<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // No-op permanently: the real schema is created by the 2025 migration.
    }

    public function down(): void
    {
        // No-op permanently so this duplicate can never alter legacy data.
    }
};
