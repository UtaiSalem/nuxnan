<?php

namespace Tests\Feature\Courses;

use App\Models\Course;
use App\Models\CourseMember;
use App\Models\CoursePointAccount;
use App\Models\CoursePointCampaign;
use App\Models\CourseQuiz;
use App\Models\User;
use App\Services\CourseClaimService;
use App\Services\CourseDonateService;
use App\Services\CoursePointAccountService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Tests\TestCase;

/**
 * Phase 5 (D3) of .agents/plans/disable-p2p-value-transfers.md: points can only
 * leave a course's point pool to the owner or an approved member
 * (course_members.course_member_status = 1). A pending join request is not enough.
 */
class CourseClaimApprovedMemberGateTest extends TestCase
{
    use RefreshDatabase;

    private function makeCourse(): array
    {
        $owner = User::factory()->create(['email_verified_at' => now(), 'pp' => 0, 'total_points_earned' => 0]);
        $course = Course::factory()->create(['user_id' => $owner->id, 'donation_enabled' => true]);

        return [$owner, $course];
    }

    private function join(Course $course, int $approval): User
    {
        $user = User::factory()->create(['email_verified_at' => now(), 'pp' => 0, 'total_points_earned' => 0]);
        CourseMember::create(['course_id' => $course->id, 'user_id' => $user->id, 'course_member_status' => $approval]);

        return $user;
    }

    private function manualCampaign(Course $course, int $balance = 500, string $type = CoursePointCampaign::CAMPAIGN_TYPE_MANUAL, array $extra = []): CoursePointCampaign
    {
        $account = CoursePointAccount::firstOrCreate(['course_id' => $course->id], ['balance' => $balance]);

        return CoursePointCampaign::create(array_merge([
            'course_point_account_id' => $account->id,
            'course_id' => $course->id,
            'title' => 'Test',
            'points_per_claim' => 50,
            'max_claims' => null,
            'status' => CoursePointCampaign::STATUS_ACTIVE,
            'campaign_type' => $type,
            'eligible_type' => 'all_enrolled',
            'created_by' => $course->user_id,
        ], $extra));
    }

    public function test_pending_member_cannot_claim_from_donation_pool(): void
    {
        [, $course] = $this->makeCourse();
        User::factory()->create(['personal_code' => config('economy.platform_personal_code'), 'pp' => 0]);
        $donor = User::factory()->create(['pp' => 1000]);
        $donation = app(CourseDonateService::class)->createPointDonation($donor, $course, 270, [], 'gate-donate-1');
        $pending = $this->join($course, 0);

        try {
            app(CourseClaimService::class)->claimSpecific($pending, $course, $donation);
            $this->fail('pending member should not be able to claim');
        } catch (DomainException $e) {
            $this->assertSame('not_a_course_member', $e->getMessage());
        }

        $this->assertEquals(0, (float) $pending->fresh()->pp);
        $this->assertEquals(270, (int) $donation->fresh()->remaining_points);
    }

    public function test_approved_member_can_claim_from_donation_pool(): void
    {
        [, $course] = $this->makeCourse();
        User::factory()->create(['personal_code' => config('economy.platform_personal_code'), 'pp' => 0]);
        $donor = User::factory()->create(['pp' => 1000]);
        $donation = app(CourseDonateService::class)->createPointDonation($donor, $course, 270, [], 'gate-donate-2');
        $approved = $this->join($course, 1);

        app(CourseClaimService::class)->claimSpecific($approved, $course, $donation);

        $this->assertEquals(210, (float) $approved->fresh()->pp);
    }

    public function test_pending_member_cannot_list_donation_pool(): void
    {
        [, $course] = $this->makeCourse();
        $pending = $this->join($course, 0);

        $this->withHeader('Authorization', 'Bearer '.JWTAuth::fromUser($pending))
            ->getJson("/api/courses/{$course->id}/donations/claimable")
            ->assertStatus(403);
    }

    public function test_pending_member_cannot_claim_manual_campaign(): void
    {
        [, $course] = $this->makeCourse();
        $campaign = $this->manualCampaign($course);
        $pending = $this->join($course, 0);

        $result = app(CoursePointAccountService::class)->claimManualCampaign($campaign->id, $pending);

        $this->assertFalse($result['success']);
        $this->assertEquals(0, (float) $pending->fresh()->pp);
        $this->assertSame(0, $campaign->claims()->count());
    }

    public function test_owner_and_approved_member_can_claim_manual_campaign(): void
    {
        [$owner, $course] = $this->makeCourse();
        $campaign = $this->manualCampaign($course);
        $approved = $this->join($course, 1);
        $service = app(CoursePointAccountService::class);

        $this->assertTrue($service->claimManualCampaign($campaign->id, $approved)['success']);
        $this->assertTrue($service->claimManualCampaign($campaign->id, $owner)['success']);
    }

    public function test_pending_member_gets_no_quiz_completion_reward(): void
    {
        [, $course] = $this->makeCourse();
        $quiz = CourseQuiz::factory()->create(['course_id' => $course->id]);
        $this->manualCampaign($course, 500, CoursePointCampaign::CAMPAIGN_TYPE_QUIZ, ['quiz_id' => $quiz->id]);
        $pending = $this->join($course, 0);

        $result = app(CoursePointAccountService::class)->grantQuizCompletionReward($quiz, $pending, 'quiz-gate-1');

        $this->assertFalse($result['rewarded']);
        $this->assertSame('not_an_approved_member', $result['reason']);
        $this->assertEquals(0, (float) $pending->fresh()->pp);
    }

    public function test_campaign_endpoints_treat_pending_member_as_not_enrolled(): void
    {
        [, $course] = $this->makeCourse();
        $campaign = $this->manualCampaign($course);
        $pending = $this->join($course, 0);
        $auth = ['Authorization' => 'Bearer '.JWTAuth::fromUser($pending)];

        $this->withHeaders($auth)
            ->getJson("/api/courses/{$course->id}/points/campaigns/available")
            ->assertOk()
            ->assertJsonPath('data.0.can_claim', false);

        $this->withHeaders($auth)
            ->postJson("/api/courses/{$course->id}/points/campaigns/{$campaign->id}/view")
            ->assertStatus(403);

        $this->withHeaders($auth)
            ->postJson("/api/courses/{$course->id}/points/campaigns/{$campaign->id}/claim")
            ->assertStatus(422)
            ->assertJson(['success' => false]);
    }
}
