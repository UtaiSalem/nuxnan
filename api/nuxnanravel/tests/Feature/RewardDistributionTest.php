<?php

namespace Tests\Feature;

use App\Models\Advert;
use App\Models\CampaignDeliveryEvent;
use App\Models\Course;
use App\Models\CoursePointAccount;
use App\Models\CoursePointTransaction;
use App\Models\User;
use App\Services\Campaign\AdDeliveryService;
use App\Services\Campaign\RewardDistributionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class RewardDistributionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['campaign.gross_reward_per_view_per_second' => 1]);
    }

    private function completeDelivery(?int $courseId = null, ?int $academyId = null, int $budget = 1000, int $duration = 10, float $visibility = 1.0): array
    {
        $advertiser = User::factory()->create();
        $viewer = User::factory()->create(['pp' => 1000]);
        $advert = Advert::factory()->create([
            'course_id' => $courseId,
            'academy_id' => $academyId,
            'user_id' => $advertiser->id,
            'advertiser_id' => $advertiser->id,
            'budget_amount' => $budget,
            'duration' => $duration,
        ]);
        $service = app(AdDeliveryService::class);
        $r = $service->startSession($advert, $viewer, (string) Str::uuid(), null, null, null);
        $d = CampaignDeliveryEvent::find($r['deliveryId']);
        $d->update([
            'started_at' => now()->subSeconds($duration + 1),
            'last_heartbeat_at' => now(),
            'page_visibility_ratio' => $visibility,
        ]);
        $result = $service->complete($d->fresh(), $r['token']);

        return ['viewer' => $viewer, 'advert' => $advert, 'delivery' => $d->fresh(), 'result' => $result];
    }

    public function test_distribute_credits_student_course_and_platform_per_policy(): void
    {
        $course = Course::factory()->create();
        $data = $this->completeDelivery($course->id, budget: 1000, duration: 10);

        $this->assertTrue($data['result']['valid']);
        $splits = $data['result']['reward']['splits'];
        $this->assertSame(6, $splits['student']);   // 60% of 10
        $this->assertSame(2, $splits['course']);    // 25% of 10
        $this->assertSame(1, $splits['platform']);  // 5% of 10, plus remainder
        $this->assertSame(6, (int) $data['viewer']->fresh()->pp - 1000);
        $account = CoursePointAccount::where('course_id', $course->id)->first();
        $this->assertSame(2, (int) $account->balance);
        $this->assertSame(1, (int) $account->platform_earned);
        $this->assertSame(1, CoursePointTransaction::where('type', 'ad_revenue')->count());
    }

    public function test_distribute_credits_academy_when_advert_is_academy_scoped(): void
    {
        $academy = \App\Models\Academy::factory()->create();
        $data = $this->completeDelivery(null, $academy->id, budget: 1000, duration: 10);

        $this->assertTrue($data['result']['valid']);
        $splits = $data['result']['reward']['splits'];
        $this->assertSame(6, $splits['student']);    // 60% of 10
        $this->assertSame(1, $splits['academy']);    // 10% of 10
        $this->assertSame(1, $splits['platform']);   // 5% of 10, plus remainder

        // No course ledger activity for an academy-scoped ad
        $this->assertSame(0, CoursePointTransaction::where('type', 'ad_revenue')->count());

        // Academy receives its share directly
        $academyAccount = \App\Models\AcademyPointAccount::where('academy_id', $academy->id)->first();
        $this->assertNotNull($academyAccount);
        $this->assertSame(1, (int) $academyAccount->balance);
        $this->assertSame(1, \App\Models\AcademyPointTransaction::where('type', 'ad_revenue')->count());
        $this->assertSame('ad-'.$data['delivery']->id.'-academy', \App\Models\AcademyPointTransaction::where('type', 'ad_revenue')->first()->idempotency_key);
    }

    public function test_distribute_is_idempotent_on_repeated_call(): void
    {
        $course = Course::factory()->create();
        $data = $this->completeDelivery($course->id);
        $delivery = $data['delivery']->fresh();

        $first = app(RewardDistributionService::class)->distribute($delivery);
        $second = app(RewardDistributionService::class)->distribute($delivery->fresh());

        $this->assertSame($first['policy_id'], $second['policy_id']);
        $this->assertSame(1, CoursePointTransaction::where('type', 'ad_revenue')->count());
    }

    public function test_distribute_falls_back_to_no_course_ledger_when_advert_has_no_course(): void
    {
        $data = $this->completeDelivery(null);
        $this->assertTrue($data['result']['valid']);
        // No course_point_transactions row for a null course scope
        $this->assertSame(0, CoursePointTransaction::where('type', 'ad_revenue')->count());
        // Student still credited
        $this->assertGreaterThan(0, (int) $data['viewer']->fresh()->pp - 1000);
    }

    public function test_distribute_caps_gross_by_advert_budget(): void
    {
        $course = Course::factory()->create();
        $data = $this->completeDelivery($course->id, budget: 3, duration: 10);
        $splits = $data['result']['reward']['splits'];
        $this->assertLessThanOrEqual(3, $splits['student'] + $splits['course'] + $splits['academy'] + $splits['platform']);
    }
}
