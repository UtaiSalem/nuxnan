<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CoursePointAccount;
use App\Models\RevenueSharePolicy;
use App\Services\RevenueSharePolicyResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RevenueSharePolicyResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_resolver_falls_back_to_platform_default(): void
    {
        $policy = app(RevenueSharePolicyResolver::class)->resolve(null, null, null);
        $this->assertSame(70.00, (float) $policy->student_pct);
    }

    public function test_course_policy_wins_over_platform(): void
    {
        RevenueSharePolicy::create(['scope_type' => 'course', 'scope_id' => 7, 'student_pct' => 60, 'course_pct' => 30, 'platform_pct' => 10, 'effective_from' => now()]);
        $policy = app(RevenueSharePolicyResolver::class)->resolve(null, 7, null);
        $this->assertSame(60.00, (float) $policy->student_pct);
    }

    public function test_campaign_policy_wins_over_course_and_platform(): void
    {
        RevenueSharePolicy::create(['scope_type' => 'course', 'scope_id' => 7, 'student_pct' => 60, 'course_pct' => 30, 'platform_pct' => 10, 'effective_from' => now()]);
        RevenueSharePolicy::create(['scope_type' => 'campaign', 'scope_id' => 9, 'student_pct' => 50, 'course_pct' => 25, 'platform_pct' => 25, 'effective_from' => now()]);
        $this->assertSame(50.00, (float) app(RevenueSharePolicyResolver::class)->resolve(9, 7, null)->student_pct);
    }

    public function test_expired_policy_is_ignored_and_falls_back(): void
    {
        RevenueSharePolicy::create(['scope_type' => 'course', 'scope_id' => 7, 'effective_from' => now()->subDays(2), 'effective_to' => now()->subDay(), 'student_pct' => 1, 'course_pct' => 1, 'platform_pct' => 98]);
        $this->assertSame(70.00, (float) app(RevenueSharePolicyResolver::class)->resolve(null, 7, null)->student_pct);
    }

    public function test_split_rounding_gives_remainder_to_platform(): void
    {
        $policy = RevenueSharePolicy::create(['scope_type' => 'platform', 'student_pct' => 70, 'course_pct' => 20, 'platform_pct' => 10, 'effective_from' => now(), 'version' => 1]);
        $resolver = app(RevenueSharePolicyResolver::class);
        $this->assertSame(['student' => 70, 'course' => 20, 'platform' => 10, 'policy_version' => 1, 'policy_id' => $policy->id], $resolver->split(100, $policy));
        $this->assertSame(11, $resolver->split(101, $policy)['platform']);
        $this->assertSame(1, $resolver->split(1, $policy)['platform']);
    }

    public function test_policy_that_does_not_sum_to_100_throws(): void
    {
        RevenueSharePolicy::create(['scope_type' => 'platform', 'student_pct' => 70, 'course_pct' => 20, 'platform_pct' => 9, 'effective_from' => now(), 'version' => 2]);
        $this->expectException(\DomainException::class);
        app(RevenueSharePolicyResolver::class)->resolve(null, null, null);
    }

    public function test_increment_platform_earned_bumps_version_and_column(): void
    {
        $course = Course::factory()->create();
        $account = CoursePointAccount::create(['course_id' => $course->id]);
        $version = $account->version;
        $account->incrementPlatformEarned(25);
        $account->refresh();
        $this->assertSame(25, (int) $account->platform_earned);
        $this->assertSame($version + 1, (int) $account->version);
    }
}
