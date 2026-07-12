<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('adverts')->update([
            'campaign_type' => DB::raw("CASE WHEN duration = 0 AND COALESCE(exchange_points, 0) > 0 THEN 'support' ELSE 'advertisement' END"),
            'scope_type' => 'public',
            'budget_amount' => DB::raw('amounts'),
            'review_status' => DB::raw("CASE status WHEN 1 THEN 'approved' WHEN 2 THEN 'rejected' ELSE 'pending' END"),
            // Approved rows are already paid; only unapproved rows carrying a slip are still awaiting slip review.
            'payment_status' => DB::raw("CASE WHEN status = 1 THEN 'paid' WHEN slip IS NOT NULL AND slip <> '' THEN 'pending_slip' ELSE 'unpaid' END"),
        ]);

        // Legacy support settled its rewards at creation time and never used beneficiary revenue-split,
        // so mark already-approved rows as distributed to keep the new approve flow idempotent.
        DB::table('adverts')
            ->where('duration', 0)
            ->where('exchange_points', '>', 0)
            ->where('status', 1)
            ->update(['distributed_at' => now()]);
    }

    public function down(): void {}
};
