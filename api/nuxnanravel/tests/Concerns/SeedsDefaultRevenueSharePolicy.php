<?php

namespace Tests\Concerns;

use App\Models\RevenueSharePolicy;

/**
 * The default platform revenue-share policy (60/25/10/5) is inserted as *data* by
 * migration 2026_07_18_220000_seed_default_revenue_share_policy. On the sqlite
 * profile `RefreshDatabase` runs that migration so the row is present, but
 * `test:db:rebuild` copies the dev schema **without data**, so `nuxnan_testing`
 * has no default policy and `RevenueSharePolicyResolver` throws
 * `No active revenue share policy found.` for every reward calculation.
 *
 * Tests that rely on the resolver falling back to the platform default seed it
 * explicitly. The values must match the seed migration (and the resolver tests'
 * expectations). `firstOrCreate` keyed on scope/version makes it a no-op on the
 * sqlite profile where the migration already created it.
 */
trait SeedsDefaultRevenueSharePolicy
{
    protected function seedDefaultRevenueSharePolicy(): RevenueSharePolicy
    {
        return RevenueSharePolicy::firstOrCreate(
            ['scope_type' => RevenueSharePolicy::SCOPE_PLATFORM, 'scope_id' => null, 'version' => 1],
            [
                'student_pct' => 60,
                'course_pct' => 25,
                'academy_pct' => 10,
                'platform_pct' => 5,
                'effective_from' => now()->subMinute(),
            ]
        );
    }
}
