<?php

namespace Tests\Feature;

use App\Models\Academy;
use App\Models\AcademyAdmin;
use App\Models\AcademyDonate;
use App\Models\AcademyPointAccount;
use App\Models\Advert;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AcademyRevenueTest extends TestCase
{
    use RefreshDatabase;

    private function createAcademyWithAdmin(): array
    {
        $owner = User::factory()->create();
        $academy = Academy::factory()->create(['user_id' => $owner->id, 'donation_enabled' => true]);

        return [$owner, $academy];
    }

    private function createDonation(Academy $academy, User $donor, array $overrides = []): AcademyDonate
    {
        return AcademyDonate::create(array_merge([
            'academy_id' => $academy->id,
            'donor_id' => $donor->id,
            'donation_type' => 'point',
            'points_amount' => 100,
            'cash_amount' => 0,
            'currency' => 'THB',
            'status' => 'pending',
            'anonymous' => false,
            'donor_display_name' => $donor->name,
        ], $overrides));
    }

    public function test_normal_user_can_view_public_support_summary(): void
    {
        [$owner, $academy] = $this->createAcademyWithAdmin();
        $donor = User::factory()->create(['pp' => 1000]);
        $this->createDonation($academy, $donor, ['status' => 'approved']);

        $user = User::factory()->create();
        $response = $this->actingAs($user, 'api')->getJson("/api/academies/{$academy->id}/revenue/support-summary");

        $response->assertOk()
            ->assertJsonPath('data.approved_points_total', 100)
            ->assertJsonPath('data.supporter_count', 1)
            ->assertJsonPath('data.recent_donations.0.id', AcademyDonate::first()->id);
    }

    public function test_public_support_summary_excludes_pending_and_rejected(): void
    {
        [$owner, $academy] = $this->createAcademyWithAdmin();
        $donor = User::factory()->create(['pp' => 1000]);
        $this->createDonation($academy, $donor, ['status' => 'pending']);
        $this->createDonation($academy, $donor, ['status' => 'rejected']);
        $approved = $this->createDonation($academy, $donor, ['status' => 'approved', 'points_amount' => 200]);

        $user = User::factory()->create();
        $response = $this->actingAs($user, 'api')->getJson("/api/academies/{$academy->id}/revenue/support-summary");

        $response->assertOk()
            ->assertJsonPath('data.approved_points_total', 200)
            ->assertJsonPath('data.recent_donations.0.id', $approved->id);
    }

    public function test_normal_user_cannot_access_donations_endpoint(): void
    {
        [$owner, $academy] = $this->createAcademyWithAdmin();
        $donor = User::factory()->create(['pp' => 1000]);
        $this->createDonation($academy, $donor, ['status' => 'pending']);
        $this->createDonation($academy, $donor, ['status' => 'approved', 'points_amount' => 200]);

        $user = User::factory()->create();
        $this->actingAs($user, 'api')->getJson("/api/academies/{$academy->id}/revenue/donations")
            ->assertStatus(403);
    }

    public function test_admin_sees_all_donations(): void
    {
        [$owner, $academy] = $this->createAcademyWithAdmin();
        $donor = User::factory()->create(['pp' => 1000]);
        $pending = $this->createDonation($academy, $donor, ['status' => 'pending']);
        $rejected = $this->createDonation($academy, $donor, ['status' => 'rejected']);
        $approved = $this->createDonation($academy, $donor, ['status' => 'approved']);

        $response = $this->actingAs($owner, 'api')->getJson("/api/academies/{$academy->id}/revenue/donations");

        $response->assertOk();
        $response->assertJsonCount(3, 'donations');
        $ids = collect($response->json('donations'))->pluck('id')->toArray();
        $this->assertContains($pending->id, $ids);
        $this->assertContains($rejected->id, $ids);
        $this->assertContains($approved->id, $ids);
    }

    public function test_public_donation_resource_requires_finance_view(): void
    {
        [$owner, $academy] = $this->createAcademyWithAdmin();
        $donor = User::factory()->create();
        $this->createDonation($academy, $donor, ['status' => 'approved', 'anonymous' => true]);

        $user = User::factory()->create();
        $this->actingAs($user, 'api')->getJson("/api/academies/{$academy->id}/revenue/donations")
            ->assertStatus(403);
    }

    public function test_admin_can_view_revenue_dashboard(): void
    {
        [$owner, $academy] = $this->createAcademyWithAdmin();
        AcademyPointAccount::create(['academy_id' => $academy->id, 'balance' => 5000, 'total_earned' => 10000]);
        $donor = User::factory()->create(['pp' => 1000]);
        $this->createDonation($academy, $donor, ['status' => 'pending', 'donation_type' => 'cash', 'points_amount' => 0, 'cash_amount' => 50]);

        $response = $this->actingAs($owner, 'api')->getJson("/api/academies/{$academy->id}/revenue/revenue");

        $response->assertOk()
            ->assertJsonPath('data.points.balance', 5000)
            ->assertJsonPath('data.donations.pending_count', 1)
            ->assertJsonPath('data.donations.pending_cash', 50);
    }

    public function test_normal_user_cannot_access_revenue_dashboard(): void
    {
        [$owner, $academy] = $this->createAcademyWithAdmin();

        $user = User::factory()->create();
        $this->actingAs($user, 'api')->getJson("/api/academies/{$academy->id}/revenue/revenue")
            ->assertStatus(403);
    }

    public function test_academy_admin_can_approve_donation(): void
    {
        [$owner, $academy] = $this->createAcademyWithAdmin();
        $donor = User::factory()->create(['pp' => 1000]);
        $donation = $this->createDonation($academy, $donor, ['status' => 'pending', 'donation_type' => 'cash', 'points_amount' => 0, 'cash_amount' => 50]);
        $admin = User::factory()->create();
        AcademyAdmin::create(['academy_id' => $academy->id, 'user_id' => $admin->id]);

        $response = $this->actingAs($admin, 'api')->postJson("/api/academies/{$academy->id}/revenue/donations/{$donation->id}/approve", [
            'note' => 'ok',
        ]);

        $response->assertOk();
        $this->assertSame('completed', $donation->fresh()->status);
    }

    public function test_academy_admin_can_reject_donation(): void
    {
        [$owner, $academy] = $this->createAcademyWithAdmin();
        $donor = User::factory()->create(['pp' => 1000]);
        $donation = $this->createDonation($academy, $donor, ['status' => 'pending', 'donation_type' => 'cash', 'points_amount' => 0, 'cash_amount' => 50]);

        $response = $this->actingAs($owner, 'api')->postJson("/api/academies/{$academy->id}/revenue/donations/{$donation->id}/reject", [
            'reason' => 'bad slip',
        ]);

        $response->assertOk();
        $this->assertSame('rejected', $donation->fresh()->status);
    }

    public function test_normal_user_cannot_approve_or_reject_donation(): void
    {
        [$owner, $academy] = $this->createAcademyWithAdmin();
        $donor = User::factory()->create(['pp' => 1000]);
        $donation = $this->createDonation($academy, $donor, ['status' => 'pending']);

        $user = User::factory()->create();
        $this->actingAs($user, 'api')->postJson("/api/academies/{$academy->id}/revenue/donations/{$donation->id}/approve")
            ->assertStatus(403);
        $this->actingAs($user, 'api')->postJson("/api/academies/{$academy->id}/revenue/donations/{$donation->id}/reject")
            ->assertStatus(403);
    }

    public function test_academy_admin_cannot_access_other_academy_revenue(): void
    {
        [$owner, $academy] = $this->createAcademyWithAdmin();
        [, $otherAcademy] = $this->createAcademyWithAdmin();

        $this->actingAs($owner, 'api')->getJson("/api/academies/{$otherAcademy->id}/revenue/revenue")
            ->assertStatus(403);
    }

    public function test_academy_admin_can_create_campaign(): void
    {
        [$owner, $academy] = $this->createAcademyWithAdmin();

        $response = $this->actingAs($owner, 'api')->postJson("/api/academies/{$academy->id}/revenue/campaigns", [
            'campaign_type' => 'advertisement',
            'title' => 'Test Campaign',
            'description' => 'Desc',
            'budget_amount' => 100,
            'total_views' => 100,
            'duration' => 10,
            'payment_method' => 'wallet',
            'amounts' => 100,
            'slip' => '',
            'media_image' => '',
            'remaining_views' => 100,
            'transfer_time' => '12:00',
        ]);

        $response->assertStatus(201);
        $this->assertSame($academy->id, $response->json('campaign.academy.id'));
    }

    public function test_academy_admin_can_update_campaign(): void
    {
        [$owner, $academy] = $this->createAcademyWithAdmin();
        $campaign = Advert::create([
            'user_id' => $owner->id,
            'advertiser_id' => $owner->id,
            'campaign_type' => 'advertisement',
            'scope_type' => 'academy',
            'academy_id' => $academy->id,
            'title' => 'Old',
            'amounts' => 100,
            'budget_amount' => 100,
            'total_views' => 100,
            'remaining_views' => 100,
            'duration' => 10,
            'slip' => '',
            'media_image' => '',
            'transfer_date' => '2026-07-19',
            'transfer_time' => '12:00',
            'review_status' => 'approved',
            'payment_status' => 'paid',
            'status' => 1,
        ]);

        $response = $this->actingAs($owner, 'api')->patchJson("/api/academies/{$academy->id}/revenue/campaigns/{$campaign->id}", [
            'title' => 'New',
        ]);

        $response->assertOk();
        $this->assertSame('New', $response->json('campaign.title'));
    }

    public function test_normal_user_cannot_create_or_update_campaign(): void
    {
        [$owner, $academy] = $this->createAcademyWithAdmin();
        $user = User::factory()->create();

        $this->actingAs($user, 'api')->postJson("/api/academies/{$academy->id}/revenue/campaigns", [
            'campaign_type' => 'advertisement',
            'title' => 'Test',
            'budget_amount' => 100,
            'total_views' => 100,
            'duration' => 10,
            'payment_method' => 'wallet',
            'amounts' => 100,
            'slip' => '',
            'media_image' => '',
            'remaining_views' => 100,
            'transfer_time' => '12:00',
        ])->assertStatus(403);

        $campaign = Advert::create([
            'user_id' => $owner->id,
            'advertiser_id' => $owner->id,
            'campaign_type' => 'advertisement',
            'scope_type' => 'academy',
            'academy_id' => $academy->id,
            'title' => 'Old',
            'amounts' => 100,
            'budget_amount' => 100,
            'total_views' => 100,
            'remaining_views' => 100,
            'duration' => 10,
            'slip' => '',
            'media_image' => '',
            'transfer_date' => '2026-07-19',
            'transfer_time' => '12:00',
            'review_status' => 'approved',
            'payment_status' => 'paid',
            'status' => 1,
        ]);

        $this->actingAs($user, 'api')->patchJson("/api/academies/{$academy->id}/revenue/campaigns/{$campaign->id}", [
            'title' => 'New',
        ])->assertStatus(403);
    }

    public function test_campaign_scope_is_enforced_on_update(): void
    {
        [$owner, $academy] = $this->createAcademyWithAdmin();
        [, $otherAcademy] = $this->createAcademyWithAdmin();

        $campaign = Advert::create([
            'user_id' => $owner->id,
            'advertiser_id' => $owner->id,
            'campaign_type' => 'advertisement',
            'scope_type' => 'academy',
            'academy_id' => $academy->id,
            'title' => 'Old',
            'amounts' => 100,
            'budget_amount' => 100,
            'total_views' => 100,
            'remaining_views' => 100,
            'duration' => 10,
            'slip' => '',
            'media_image' => '',
            'transfer_date' => '2026-07-19',
            'transfer_time' => '12:00',
            'review_status' => 'approved',
            'payment_status' => 'paid',
            'status' => 1,
        ]);

        $this->actingAs($owner, 'api')->patchJson("/api/academies/{$otherAcademy->id}/revenue/campaigns/{$campaign->id}", [
            'title' => 'Hacked',
        ])->assertStatus(403);
    }
}
