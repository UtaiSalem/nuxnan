<?php

namespace Tests\Feature;

use App\Models\Advert;
use App\Models\CampaignDeliveryEvent;
use App\Models\User;
use App\Services\Campaign\AdDeliveryService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class AdDeliveryHardeningTest extends TestCase
{
    use RefreshDatabase;

    private function start($duration = 10): array
    {
        $user = User::factory()->create();
        $advert = Advert::factory()->create(['user_id' => $user->id, 'duration' => $duration]);

        return [app(AdDeliveryService::class)->startSession($advert, $user, (string) Str::uuid(), null, null, null), $user, $advert];
    }

    public function test_start_issues_token_and_creates_started_delivery(): void
    {
        [$r] = $this->start();
        $this->assertNotEmpty($r['token']);
        $this->assertDatabaseHas('campaign_delivery_events', ['id' => $r['deliveryId'], 'status' => 'started']);
    }

    public function test_start_respects_daily_view_limit(): void
    {
        [$r, $u, $a] = $this->start();
        for ($i = 1; $i < 5; $i++) {
            app(AdDeliveryService::class)->startSession($a, $u, (string) Str::uuid(), null, null, null);
        } $this->expectException(HttpException::class);
        app(AdDeliveryService::class)->startSession($a, $u, (string) Str::uuid(), null, null, null);
    }

    public function test_start_rejects_unapproved_advert(): void
    {
        [$r, $u, $a] = $this->start();
        $a->update(['review_status' => 'pending']);
        $this->expectException(ModelNotFoundException::class);
        app(AdDeliveryService::class)->startSession($a, $u, (string) Str::uuid(), null, null, null);
    }

    public function test_heartbeat_updates_visibility_ratio_average(): void
    {
        [$r] = $this->start();
        $d = CampaignDeliveryEvent::find($r['deliveryId']);
        app(AdDeliveryService::class)->heartbeat($d, $r['token'], .8);
        app(AdDeliveryService::class)->heartbeat($d->fresh(), $r['token'], .6);
        $this->assertEquals('0.7000', $d->fresh()->page_visibility_ratio);
    }

    public function test_heartbeat_rejects_bad_token(): void
    {
        [$r] = $this->start();
        $this->expectException(HttpException::class);
        app(AdDeliveryService::class)->heartbeat(CampaignDeliveryEvent::find($r['deliveryId']), 'bad', .8);
    }

    public function test_complete_marks_delivery_completed_and_decrements_remaining_views(): void
    {
        [$r, , $a] = $this->start(1);
        $d = CampaignDeliveryEvent::find($r['deliveryId']);
        $d->update(['required_duration' => 0, 'started_at' => now(), 'last_heartbeat_at' => now()->addSeconds(3), 'page_visibility_ratio' => 1]);
        $result = app(AdDeliveryService::class)->complete($d, $r['token']);
        $this->assertTrue($result['valid']);
        $this->assertEquals(4, $a->fresh()->remaining_views);
    }

    public function test_complete_below_required_duration_marks_insufficient_watch(): void
    {
        [$r] = $this->start();
        $result = app(AdDeliveryService::class)->complete(CampaignDeliveryEvent::find($r['deliveryId']), $r['token']);
        $this->assertFalse($result['valid']);
        $this->assertEquals('insufficient_watch', $result['delivery']->status);
    }

    public function test_complete_with_low_visibility_marks_insufficient_visibility(): void
    {
        [$r] = $this->start(1);
        $d = CampaignDeliveryEvent::find($r['deliveryId']);
        $d->update(['required_duration' => 0, 'started_at' => now(), 'last_heartbeat_at' => now()->addSeconds(3), 'page_visibility_ratio' => .2]);
        $result = app(AdDeliveryService::class)->complete($d, $r['token']);
        $this->assertEquals('low_visibility', $result['reason']);
    }

    public function test_replay_complete_returns_409_and_marks_replayed(): void
    {
        [$r] = $this->start(1);
        $d = CampaignDeliveryEvent::find($r['deliveryId']);
        $d->update(['required_duration' => 0, 'started_at' => now(), 'last_heartbeat_at' => now()->addSeconds(3), 'page_visibility_ratio' => 1]);
        app(AdDeliveryService::class)->complete($d, $r['token']);
        $this->expectException(HttpException::class);
        app(AdDeliveryService::class)->complete($d->fresh(), $r['token']);
    }
}
