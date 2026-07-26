<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // A view is the smallest additive solution: public claims remain untouched,
    // while academy claims are automatically included in the same global count.
    public function up(): void
    {
        DB::statement("CREATE VIEW user_daily_claim_counters AS SELECT user_id, created_at AS claimed_at, 'public' AS tier FROM donate_recipients UNION ALL SELECT claimer_id AS user_id, claimed_at, 'academy' AS tier FROM academy_donate_claims");
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS user_daily_claim_counters');
    }
};
