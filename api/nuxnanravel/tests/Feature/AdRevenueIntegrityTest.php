<?php

namespace Tests\Feature;

use App\Models\Academy;
use App\Models\AcademyPointAccount;
use App\Models\Advert;
use App\Models\CampaignDeliveryEvent;
use App\Models\RiskEvent;
use App\Models\User;
use App\Services\FraudDetectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class AdRevenueIntegrityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('campaign.gross_reward_per_view_per_second', 1);
    }

    public function test_scan_ad_revenue_policy_flags_tampered_split(): void
    {
        $advertiser = User::factory()->create();
        $advert = Advert::factory()->create(['user_id' => $advertiser->id, 'advertiser_id' => $advertiser->id, 'duration' => 10]);
        $delivery = CampaignDeliveryEvent::create([
            'advert_id' => $advert->id,
            'user_id' => User::factory()->create()->id,
            'event_type' => 'rewarded_view',
            'session_id' => 's1',
            'required_duration' => 10,
            'status' => CampaignDeliveryEvent::STATUS_COMPLETED,
            'metadata' => ['reward_splits' => ['student' => 6, 'course' => 2, 'academy' => 1, 'platform' => 1, 'policy_id' => 1, 'policy_version' => 1]],
        ]);

        // gross = 10, but recorded split sums to 10 (6+2+1+1=10) -> OK, no event
        $created = app(FraudDetectionService::class)->scanAdRevenuePolicy(24);
        $this->assertSame(0, $created);

        // Tamper: make split sum to 9 (missing 1 point)
        $delivery->update(['metadata' => ['reward_splits' => ['student' => 6, 'course' => 2, 'academy' => 1, 'platform' => 0, 'policy_id' => 1, 'policy_version' => 1]]]);
        $created = app(FraudDetectionService::class)->scanAdRevenuePolicy(24);
        $this->assertSame(1, $created);
        $this->assertDatabaseHas('risk_events', ['rule_name' => 'ad_revenue_policy', 'subject_id' => $delivery->id]);
    }

    public function test_scan_academy_negative_balance_flags_negative_account(): void
    {
        $academy = Academy::factory()->create();
        $account = AcademyPointAccount::create(['academy_id' => $academy->id, 'balance' => -5]);

        $created = app(FraudDetectionService::class)->scanAcademyNegativeBalance();
        $this->assertSame(1, $created);
        $this->assertDatabaseHas('risk_events', ['rule_name' => 'academy_negative_balance', 'severity' => RiskEvent::SEVERITY_CRITICAL]);
    }

    public function test_scan_academy_negative_balance_ignores_positive(): void
    {
        $academy = Academy::factory()->create();
        AcademyPointAccount::create(['academy_id' => $academy->id, 'balance' => 100]);

        $this->assertSame(0, app(FraudDetectionService::class)->scanAcademyNegativeBalance());
    }

    public function test_reconcile_all_includes_academy_and_ad_gross_checks(): void
    {
        // Should run without error and include the two new checks.
        $this->artisan('reconcile:all')->assertSuccessful();
    }
}
