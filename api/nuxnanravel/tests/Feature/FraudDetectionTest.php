<?php

namespace Tests\Feature;

use App\Models\Advert;
use App\Models\CampaignDeliveryEvent;
use App\Models\Course;
use App\Models\CourseDonate;
use App\Models\RiskEvent;
use App\Models\User;
use App\Services\FraudDetectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class FraudDetectionTest extends TestCase
{
    use RefreshDatabase;

    private function donation(User $donor, Course $course): void
    {
        CourseDonate::create(['course_id' => $course->id, 'donor_id' => $donor->id, 'donation_type' => 'point', 'points_amount' => 10, 'currency' => 'THB', 'status' => 'completed', 'anonymous' => false]);
    }

    public function test_donation_velocity_creates_risk_event_when_threshold_exceeded(): void
    {
        $donor = User::factory()->create();
        $course = Course::factory()->create();
        for ($i = 0; $i < 6; $i++) {
            $this->donation($donor, $course);
        }
        $this->assertSame(1, app(FraudDetectionService::class)->scanDonationVelocity());
        $this->assertDatabaseHas('risk_events', ['rule_name' => 'donation_velocity', 'severity' => 'medium']);
    }

    public function test_donation_velocity_is_idempotent(): void
    {
        $donor = User::factory()->create();
        $course = Course::factory()->create();
        for ($i = 0; $i < 6; $i++) {
            $this->donation($donor, $course);
        }
        $service = app(FraudDetectionService::class);
        $service->scanDonationVelocity();
        $service->scanDonationVelocity();
        $this->assertSame(1, RiskEvent::count());
    }

    public function test_donation_velocity_below_threshold_creates_nothing(): void
    {
        $donor = User::factory()->create();
        $course = Course::factory()->create();
        for ($i = 0; $i < 4; $i++) {
            $this->donation($donor, $course);
        }
        $this->assertSame(0, app(FraudDetectionService::class)->scanDonationVelocity());
        $this->assertSame(0, RiskEvent::count());
    }

    public function test_self_donation_cluster_creates_high_severity(): void
    {
        $owner = User::factory()->create();
        $course = Course::factory()->create(['user_id' => $owner->id]);
        for ($i = 0; $i < 3; $i++) {
            $this->donation($owner, $course);
        }
        $this->assertSame(1, app(FraudDetectionService::class)->scanSelfDonationCluster());
        $this->assertDatabaseHas('risk_events', ['rule_name' => 'self_donation_cluster', 'severity' => 'high']);
    }

    public function test_ad_fraud_scan_creates_event_from_fraud_reason(): void
    {
        $user = User::factory()->create();
        $advert = Advert::factory()->create(['user_id' => $user->id]);
        CampaignDeliveryEvent::create(['advert_id' => $advert->id, 'event_type' => 'complete', 'fraud_reason' => 'below_required_duration', 'metadata' => ['duration' => 2], 'created_at' => Carbon::now()]);
        $this->assertSame(1, app(FraudDetectionService::class)->scanAdFraud());
        $this->assertDatabaseHas('risk_events', ['rule_name' => 'ad_fraud', 'severity' => 'medium']);
    }

    public function test_ad_fraud_scan_maps_severity_from_reason(): void
    {
        $user = User::factory()->create();
        $advert = Advert::factory()->create(['user_id' => $user->id]);
        foreach (['low_visibility', 'replayed'] as $reason) {
            CampaignDeliveryEvent::create(['advert_id' => $advert->id, 'event_type' => 'complete', 'fraud_reason' => $reason, 'created_at' => Carbon::now()]);
        }
        app(FraudDetectionService::class)->scanAdFraud();
        $this->assertDatabaseHas('risk_events', ['severity' => 'low']);
        $this->assertDatabaseHas('risk_events', ['severity' => 'high']);
    }

    public function test_ad_fraud_scan_is_idempotent(): void
    {
        $user = User::factory()->create();
        $advert = Advert::factory()->create(['user_id' => $user->id]);
        CampaignDeliveryEvent::create(['advert_id' => $advert->id, 'event_type' => 'complete', 'fraud_reason' => 'replayed', 'created_at' => Carbon::now()]);
        $service = app(FraudDetectionService::class);
        $service->scanAdFraud();
        $service->scanAdFraud();
        $this->assertSame(1, RiskEvent::count());
    }
}
