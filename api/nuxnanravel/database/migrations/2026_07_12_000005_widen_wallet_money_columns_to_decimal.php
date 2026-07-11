<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Money precision cleanup:
 *  - users.wallet was a floating-point `double` (the root cause of the cent
 *    drift we saw, e.g. 0.79999999999995). Convert it to DECIMAL(15,2) so the
 *    stored value is exact, matching the bcmath code and the ledger columns.
 *  - Widen wallet_transactions.fee / net_amount from DECIMAL(10,2) to (15,2)
 *    for headroom and consistency.
 *
 * amount / balance_before / balance_after are already DECIMAL(20,2) (exact and
 * wide enough), so they are left untouched.
 *
 * MySQL-only; SQLite (used in tests) is dynamically typed and the code relies
 * on bcmath + the model's decimal cast, not the column's declared precision.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement('ALTER TABLE users MODIFY COLUMN wallet DECIMAL(15,2) UNSIGNED NOT NULL DEFAULT 0.00');
        DB::statement('ALTER TABLE wallet_transactions MODIFY COLUMN fee DECIMAL(15,2) NOT NULL DEFAULT 0.00');
        DB::statement('ALTER TABLE wallet_transactions MODIFY COLUMN net_amount DECIMAL(15,2) NULL DEFAULT NULL');
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement('ALTER TABLE users MODIFY COLUMN wallet DOUBLE UNSIGNED NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE wallet_transactions MODIFY COLUMN fee DECIMAL(10,2) NOT NULL DEFAULT 0.00');
        DB::statement('ALTER TABLE wallet_transactions MODIFY COLUMN net_amount DECIMAL(10,2) NULL DEFAULT NULL');
    }
};
