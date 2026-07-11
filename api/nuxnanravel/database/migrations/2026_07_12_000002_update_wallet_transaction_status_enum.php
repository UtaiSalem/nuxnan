<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE wallet_transactions MODIFY COLUMN status ENUM('pending','under_review','approved','processing','completed','paid','rejected','failed','cancelled') DEFAULT 'completed'");
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE wallet_transactions MODIFY COLUMN status ENUM('pending','completed','failed','cancelled') DEFAULT 'completed'");
        }
    }
};
