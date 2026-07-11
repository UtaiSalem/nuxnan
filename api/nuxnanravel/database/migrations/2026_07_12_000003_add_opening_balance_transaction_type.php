<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Adds the 'opening_balance' transaction type used to record a one-time
 * baseline ledger entry per user (see `php artisan wallet:baseline`), so that
 * every wallet becomes fully backed by the ledger and reconciliation starts
 * from a clean, verifiable point.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE wallet_transactions MODIFY COLUMN transaction_type ENUM('deposit','withdraw','transfer','conversion','admin_adjust','reward','purchase','course_income','refund','opening_balance') NOT NULL");
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'mysql') {
            // Will fail if any opening_balance rows still exist — remove/rename them first.
            DB::statement("ALTER TABLE wallet_transactions MODIFY COLUMN transaction_type ENUM('deposit','withdraw','transfer','conversion','admin_adjust','reward','purchase','course_income','refund') NOT NULL");
        }
    }
};
