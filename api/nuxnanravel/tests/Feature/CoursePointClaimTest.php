<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseDonate;
use App\Models\CourseMember;
use App\Models\CoursePointAccount;
use App\Models\CoursePointCampaign;
use App\Models\CoursePointCampaignClaim;
use App\Models\CoursePointTransaction;
use App\Models\CourseQuiz;
use App\Models\User;
use App\Services\CoursePointAccountService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CoursePointClaimTest extends TestCase
{
    use RefreshDatabase;

    public function test_manual_claim_credits_student_and_debits_course_account(): void
    {
        [$service, $course, $account, $campaign, $student] = $this->fixture(100, null);
        $result = $service->claimManualCampaign($campaign->id, $student);

        $this->assertTrue($result['success']);
        $this->assertSame(50, $account->fresh()->balance);
        $this->assertSame(1, CoursePointCampaignClaim::where('campaign_id', $campaign->id)->count());
        $this->assertSame(1, CoursePointTransaction::where('related_campaign_id', $campaign->id)->count());
        $this->assertDatabaseHas('points_transactions', ['user_id' => $student->id, 'amount' => 50]);
    }

    public function test_duplicate_claim_is_rejected(): void
    {
        [$service, , , $campaign, $student] = $this->fixture(100, null);
        $service->claimManualCampaign($campaign->id, $student);
        $result = $service->claimManualCampaign($campaign->id, $student);

        $this->assertFalse($result['success']);
        $this->assertSame(1, CoursePointCampaignClaim::where('campaign_id', $campaign->id)->count());
    }

    public function test_view_returns_oldest_donation_with_remaining(): void
    {
        [, $course, , $campaign, $student] = $this->fixture(100, null);
        $donor = User::factory()->create(['name' => 'FIFO Donor']);
        $old = CourseDonate::create(['course_id' => $course->id, 'donor_id' => $donor->id, 'donation_type' => 'point', 'points_amount' => 20, 'remaining_points' => 7, 'status' => 'approved', 'created_at' => now()->subMinute()]);
        CourseDonate::create(['course_id' => $course->id, 'donor_id' => $donor->id, 'donation_type' => 'point', 'points_amount' => 30, 'remaining_points' => 30, 'status' => 'approved']);

        $this->actingAs($student, 'api')->postJson("/api/courses/{$course->id}/points/campaigns/{$campaign->id}/view")
            ->assertOk()->assertJsonPath('donation.id', $old->id)->assertJsonPath('donation.remaining_points', 7);
    }

    public function test_claim_records_viewed_donor_and_decrements_donation(): void
    {
        [$service, $course, , $campaign, $student] = $this->fixture(100, null);
        $donor = User::factory()->create();
        $donation = CourseDonate::create(['course_id' => $course->id, 'donor_id' => $donor->id, 'donation_type' => 'point', 'points_amount' => 100, 'remaining_points' => 60, 'status' => 'approved']);
        $result = $service->claimManualCampaign($campaign->id, $student, $donor->id, $donation->id);

        $this->assertTrue($result['success']);
        $this->assertDatabaseHas('course_point_campaign_claims', ['campaign_id' => $campaign->id, 'viewed_donor_id' => $donor->id, 'viewed_donation_id' => $donation->id]);
        $this->assertSame(10, $donation->fresh()->remaining_points);
    }

    public function test_non_enrolled_user_is_blocked(): void
    {
        [$service, , , $campaign, $student] = $this->fixture(100, 1, false);
        $result = $service->claimManualCampaign($campaign->id, $student);
        $this->assertFalse($result['success']);
    }

    public function test_fcfs_only_one_claim_succeeds_when_balance_covers_one_claim(): void
    {
        [$service, , , $campaign, $first] = $this->fixture(50, null);
        $second = User::factory()->create();
        CourseMember::create(['course_id' => $campaign->course_id, 'user_id' => $second->id]);
        $results = [$service->claimManualCampaign($campaign->id, $first), $service->claimManualCampaign($campaign->id, $second)];
        $this->assertCount(1, array_filter($results, fn ($result) => $result['success']));
    }

    public function test_paused_ended_and_expired_campaigns_are_blocked(): void
    {
        foreach ([CoursePointCampaign::STATUS_PAUSED, CoursePointCampaign::STATUS_ENDED] as $status) {
            [$service, , , $campaign, $student] = $this->fixture(100, 1);
            $campaign->update(['status' => $status]);
            $this->assertFalse($service->claimManualCampaign($campaign->id, $student)['success']);
        }
        [$service, , , $campaign, $student] = $this->fixture(100, 1);
        $campaign->update(['ends_at' => now()->subMinute()]);
        $this->assertFalse($service->claimManualCampaign($campaign->id, $student)['success']);
    }

    public function test_quiz_reward_is_credited_once_idempotently(): void
    {
        [$service, $course, $account, , $student] = $this->fixture(100, null);
        $quiz = CourseQuiz::factory()->create(['course_id' => $course->id]);
        $campaign = CoursePointCampaign::create([
            'course_point_account_id' => $account->id,
            'course_id' => $course->id,
            'quiz_id' => $quiz->id,
            'title' => 'Quiz reward',
            'points_per_claim' => 50,
            'max_claims' => null,
            'status' => CoursePointCampaign::STATUS_ACTIVE,
            'campaign_type' => CoursePointCampaign::CAMPAIGN_TYPE_QUIZ,
            'eligible_type' => 'all_enrolled',
            'created_by' => $course->user_id,
        ]);

        $first = $service->grantQuizCompletionReward($quiz, $student, 'quiz-reward:1');
        $second = $service->grantQuizCompletionReward($quiz, $student, 'quiz-reward:1');

        $this->assertTrue($first['rewarded']);
        $this->assertTrue($second['rewarded']);
        $this->assertSame(50, $account->fresh()->balance);
        $this->assertSame(1, CoursePointCampaignClaim::where('campaign_id', $campaign->id)->count());
        $this->assertSame(1, CoursePointTransaction::where('related_campaign_id', $campaign->id)->count());
    }

    private function fixture(int $balance, ?int $maxClaims, bool $enroll = true): array
    {
        $owner = User::factory()->create();
        $student = User::factory()->create();
        $student->update(['pp' => 0, 'total_points_earned' => 0]);
        $course = Course::factory()->create(['user_id' => $owner->id]);
        if ($enroll) {
            CourseMember::create(['course_id' => $course->id, 'user_id' => $student->id]);
        }
        $account = CoursePointAccount::create(['course_id' => $course->id, 'balance' => $balance]);
        $campaign = CoursePointCampaign::create(['course_point_account_id' => $account->id, 'course_id' => $course->id, 'title' => 'Test', 'points_per_claim' => 50, 'max_claims' => $maxClaims, 'status' => 'active', 'campaign_type' => 'manual_claim', 'eligible_type' => 'all_enrolled', 'created_by' => $owner->id]);

        return [app(CoursePointAccountService::class), $course, $account, $campaign, $student];
    }
}
