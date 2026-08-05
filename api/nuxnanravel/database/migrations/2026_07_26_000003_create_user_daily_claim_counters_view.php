<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // A view is the smallest additive solution: public claims remain untouched,
    // while academy claims are automatically included in the same global count.
    public function up(): void
    {
        // Drop first: imported production dumps ship this view already, and they ship
        // it with a DEFINER that does not exist locally, which makes every SELECT on
        // it fail with error 1449. Recreating it here rebinds it to the local user.
        // SQL SECURITY INVOKER keeps the view working after a dump is restored on a
        // host where the original definer does not exist. MySQL-only syntax.
        $security = DB::getDriverName() === 'mysql' ? 'SQL SECURITY INVOKER ' : '';

        DB::statement('DROP VIEW IF EXISTS user_daily_claim_counters');
        DB::statement("CREATE {$security}VIEW user_daily_claim_counters AS SELECT user_id, created_at AS claimed_at, 'public' AS tier FROM donate_recipients UNION ALL SELECT claimer_id AS user_id, claimed_at, 'academy' AS tier FROM academy_donate_claims");
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS user_daily_claim_counters');
    }
};
