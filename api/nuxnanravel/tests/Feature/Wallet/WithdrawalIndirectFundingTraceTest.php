<?php

namespace Tests\Feature\Wallet;

use App\Models\Course;
use App\Models\CourseMember;
use App\Models\CoursePointAccount;
use App\Models\CoursePointCampaign;
use App\Models\Donate;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Services\CourseClaimService;
use App\Services\CourseDonateService;
use App\Services\CoursePointAccountService;
use App\Services\PointsService;
use App\Services\WalletService;
use App\Services\WithdrawalFundSourceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 5 (D3) of .agents/plans/disable-p2p-value-transfers.md: course sales and
 * pool claims stay on, but the admin withdrawal review must show when one
 * person routed most of a withdrawal to the requester through them.
 */
class WithdrawalIndirectFundingTraceTest extends TestCase
{
    use RefreshDatabase;

    private function user(array $attributes = []): User
    {
        return User::factory()->create(array_merge(
            ['email_verified_at' => now(), 'pp' => 0, 'total_points_earned' => 0, 'wallet' => 0],
            $attributes,
        ));
    }

    private function withdraw(User $user, string $amount = '100'): WalletTransaction
    {
        return app(WalletService::class)->withdraw($user->fresh(), $amount, 'bank_transfer', [
            'bank_name' => 'kbank',
            'account_number' => '1234567890',
            'account_name' => 'Test User',
        ]);
    }

    private function trace(WalletTransaction $withdrawal): array
    {
        return app(WithdrawalFundSourceService::class)->trace($withdrawal);
    }

    private function sellCourse(User $seller, User $buyer, float $price): void
    {
        $course = Course::factory()->create(['user_id' => $seller->id, 'tuition_fees' => $price, 'price' => $price, 'discount' => 0]);
        app(WalletService::class)->purchaseCourse($buyer->fresh(), $course);
    }

    public function test_course_bought_by_one_person_funding_the_withdrawal_is_high_risk(): void
    {
        $seller = $this->user();
        $buyer = $this->user(['wallet' => 500]);
        $this->sellCourse($seller, $buyer, 100);

        $trace = $this->trace($this->withdraw($seller));

        $this->assertSame('high', $trace['risk']['level']);
        $this->assertContains('course_sale_income', $trace['risk']['flags']);
        $this->assertContains('single_indirect_funder', $trace['risk']['flags']);
        $this->assertSame(1, $trace['summary']['course_sales']['count']);
        $this->assertSame($buyer->id, $trace['summary']['top_indirect_funder']['user']['id']);
        $sale = collect($trace['inflows'])->firstWhere('type', 'course_sale');
        $this->assertSame($buyer->id, $sale['counterparty']['id']);
    }

    public function test_course_sales_spread_over_many_buyers_stay_low_risk(): void
    {
        $seller = $this->user();
        for ($i = 0; $i < 5; $i++) {
            $this->sellCourse($seller, $this->user(['wallet' => 100]), 20);
        }

        $trace = $this->trace($this->withdraw($seller));

        $this->assertSame('low', $trace['risk']['level']);
        $this->assertContains('course_sale_income', $trace['risk']['flags']);
        $this->assertNotContains('single_indirect_funder', $trace['risk']['flags']);
        $this->assertSame(5, $trace['summary']['course_sales']['count']);
    }

    public function test_campaign_claim_resolves_creator_and_flags_concentration(): void
    {
        $owner = $this->user();
        $member = $this->user();
        $course = Course::factory()->create(['user_id' => $owner->id]);
        CourseMember::create(['course_id' => $course->id, 'user_id' => $member->id, 'course_member_status' => 1]);
        $account = CoursePointAccount::create(['course_id' => $course->id, 'balance' => 120000]);
        $campaign = CoursePointCampaign::create([
            'course_point_account_id' => $account->id, 'course_id' => $course->id, 'title' => 'ก้อนเดียว',
            'points_per_claim' => 120000, 'max_claims' => null, 'status' => CoursePointCampaign::STATUS_ACTIVE,
            'campaign_type' => CoursePointCampaign::CAMPAIGN_TYPE_MANUAL, 'eligible_type' => 'all_enrolled', 'created_by' => $owner->id,
        ]);

        $this->assertTrue(app(CoursePointAccountService::class)->claimManualCampaign($campaign->id, $member)['success']);
        app(PointsService::class)->convertPointsToWallet($member->fresh(), 120000);

        $trace = $this->trace($this->withdraw($member));

        $this->assertSame('high', $trace['risk']['level']);
        $this->assertContains('pool_claim_rewards', $trace['risk']['flags']);
        $this->assertContains('single_indirect_funder', $trace['risk']['flags']);
        $this->assertSame(1, $trace['summary']['pool_claims']['count']);
        $claim = collect($trace['inflows'])->firstWhere('type', 'pool_claim');
        $this->assertSame($owner->id, $claim['counterparty']['id']);
    }

    public function test_course_donation_claim_resolves_the_donor(): void
    {
        User::factory()->create(['personal_code' => config('economy.platform_personal_code'), 'pp' => 0]);
        $owner = $this->user();
        $donor = $this->user(['pp' => 1000]);
        $member = $this->user(['wallet' => 500]);
        $course = Course::factory()->create(['user_id' => $owner->id, 'donation_enabled' => true]);
        CourseMember::create(['course_id' => $course->id, 'user_id' => $member->id, 'course_member_status' => 1]);
        $donation = app(CourseDonateService::class)->createPointDonation($donor, $course, 270, [], 'trace-donate-1');
        app(CourseClaimService::class)->claimSpecific($member, $course, $donation);

        $trace = $this->trace($this->withdraw($member));

        $claim = collect($trace['inflows'])->firstWhere('type', 'pool_claim');
        $this->assertSame($donor->id, $claim['counterparty']['id']);
        // 210 แต้ม ≈ 0.18 บาท จากยอดถอน 100 — ไม่ถึงเกณฑ์กระจุกตัว
        $this->assertNotContains('single_indirect_funder', $trace['risk']['flags']);
    }

    public function test_public_donation_claim_resolves_the_donor(): void
    {
        $donor = $this->user();
        $member = $this->user(['wallet' => 500]);
        $donate = Donate::factory()->create(['donor_id' => $donor->id]);
        app(PointsService::class)->earn($member, 220, 'donation_claim', $donate->id);

        $trace = $this->trace($this->withdraw($member));

        $claim = collect($trace['inflows'])->firstWhere('type', 'pool_claim');
        $this->assertSame($donor->id, $claim['counterparty']['id']);
    }

    public function test_own_campaign_or_own_course_value_is_not_counted_as_someone_else(): void
    {
        $owner = $this->user();
        $course = Course::factory()->create(['user_id' => $owner->id]);
        $account = CoursePointAccount::create(['course_id' => $course->id, 'balance' => 120000]);
        $campaign = CoursePointCampaign::create([
            'course_point_account_id' => $account->id, 'course_id' => $course->id, 'title' => 'ของตัวเอง',
            'points_per_claim' => 120000, 'max_claims' => null, 'status' => CoursePointCampaign::STATUS_ACTIVE,
            'campaign_type' => CoursePointCampaign::CAMPAIGN_TYPE_MANUAL, 'eligible_type' => 'all_enrolled', 'created_by' => $owner->id,
        ]);
        app(CoursePointAccountService::class)->claimManualCampaign($campaign->id, $owner);
        app(PointsService::class)->convertPointsToWallet($owner->fresh(), 120000);

        $trace = $this->trace($this->withdraw($owner));

        $this->assertNull($trace['summary']['top_indirect_funder']);
        $this->assertNotContains('single_indirect_funder', $trace['risk']['flags']);
    }
}
