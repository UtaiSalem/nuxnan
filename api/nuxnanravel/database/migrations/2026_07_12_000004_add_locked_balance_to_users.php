<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Materializes the amount of wallet money held against in-flight withdrawals
 * into users.locked_balance. `wallet` remains the spendable balance; the total
 * a user owns is wallet + locked_balance. Backfilled from the current active
 * withdrawals so it matches the previously-derived value from day one.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->decimal('locked_balance', 15, 2)->default(0)->after('wallet');
        });

        // Backfill from active (held) withdrawals. Safe on an empty DB too.
        DB::statement("
            UPDATE users SET locked_balance = COALESCE((
                SELECT SUM(amount) FROM wallet_transactions
                WHERE wallet_transactions.user_id = users.id
                  AND transaction_type = 'withdraw'
                  AND status IN ('pending', 'under_review', 'approved', 'processing')
            ), 0)
        ");
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('locked_balance');
        });
    }
};
