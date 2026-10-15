<?php

namespace Tests\Feature\Academy;

use App\Enums\CampaignPaymentStatus;
use App\Enums\CampaignReviewStatus;
use App\Models\Academy;
use App\Models\AcademyDonate;
use App\Models\AcademyMember;
use App\Models\AcademyRole;
use App\Models\Advert;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RevenueAuthzTest extends TestCase
{
    use RefreshDatabase;

    private function academyOwnedBy(): array
    {
        $owner = User::factory()->create();
        $academy = Academy::factory()->create([
            'user_id' => $owner->id,
            'donation_enabled' => true,
        ]);

        return [$academy, $owner];
    }

    /** สมาชิกที่อนุมัติของโรงเรียน พร้อม permission ที่ระบุ (ว่าง = สมาชิกธรรมดา) */
    private function memberOf(Academy $academy, array $permissions = []): User
    {
        $user = User::factory()->create();
        $role = AcademyRole::create([
            'academy_id' => $academy->id,
            'name' => 'role-'.uniqid(),
            'display_name_th' => 'บทบาททดสอบ',
            'permissions' => $permissions,
        ]);
        AcademyMember::create([
            'academy_id' => $academy->id,
            'user_id' => $user->id,
            'academy_role_id' => $role->id,
            'status' => 2,
        ]);

        return $user;
    }

    private function pendingCashDonationIn(Academy $academy): AcademyDonate
    {
        $donor = User::factory()->create();

        return AcademyDonate::create([
            'academy_id' => $academy->id,
            'donor_id' => $donor->id,
            'donation_type' => 'cash',
            'cash_amount' => 100,
            'currency' => 'THB',
            'status' => 'pending',
            'version' => 1,
        ]);
    }

    private function campaignIn(Academy $academy, User $creator): Advert
    {
        $campaign = new Advert;
        $campaign->forceFill([
            'user_id' => $creator->id,
            'advertiser_id' => $creator->id,
            'campaign_type' => 'advertisement',
            'scope_type' => 'academy',
            'academy_id' => $academy->id,
            'course_id' => null,
            'inherit_to_academy' => false,
            'title' => 'แคมเปญทดสอบเดิม',
            'slip' => '',
            'media_image' => '',
            'transfer_time' => '',
            'amounts' => 100,
            'budget_amount' => 100,
            'duration' => 15,
            'total_views' => 1000,
            'remaining_views' => 1000,
            'payment_status' => CampaignPaymentStatus::UNPAID,
            'review_status' => CampaignReviewStatus::PENDING,
            'status' => 0,
        ]);
        $campaign->save();

        return $campaign;
    }

    // 1. finance_view holder (non-admin) can list campaigns
    public function test_finance_view_holder_non_admin_can_list_campaigns(): void
    {
        [$academy] = $this->academyOwnedBy();
        $finance = $this->memberOf($academy, ['finance.view']);

        $response = $this->actingAs($finance, 'api')
            ->getJson("/api/academies/{$academy->id}/revenue/campaigns");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    // 2. finance_view holder can list donations (degraded ok)
    public function test_finance_view_holder_can_list_donations_degraded(): void
    {
        [$academy] = $this->academyOwnedBy();
        $finance = $this->memberOf($academy, ['finance.view']);

        $pending = $this->pendingCashDonationIn($academy);
        $completed = AcademyDonate::create([
            'academy_id' => $academy->id,
            'donor_id' => User::factory()->create()->id,
            'donation_type' => 'cash',
            'cash_amount' => 200,
            'currency' => 'THB',
            'status' => 'completed',
            'version' => 1,
        ]);

        $response = $this->actingAs($finance, 'api')
            ->getJson("/api/academies/{$academy->id}/revenue/donations");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $ids = collect($response->json('donations'))->pluck('id')->all();
        $this->assertContains($completed->id, $ids);
        $this->assertNotContains($pending->id, $ids);
    }

    // 3. finance_manage holder (non-admin) can approve donation
    public function test_finance_manage_holder_non_admin_can_approve_donation(): void
    {
        [$academy] = $this->academyOwnedBy();
        $finance = $this->memberOf($academy, ['finance.manage']);
        $donation = $this->pendingCashDonationIn($academy);

        $response = $this->actingAs($finance, 'api')
            ->postJson("/api/academies/{$academy->id}/revenue/donations/{$donation->id}/approve", [
                'note' => 'อนุมัติเรียบร้อย',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('academy_donates', [
            'id' => $donation->id,
            'status' => 'completed',
            'approved_by' => $finance->id,
        ]);
    }

    // 4. finance_manage holder can create campaign
    public function test_finance_manage_holder_can_create_campaign(): void
    {
        [$academy] = $this->academyOwnedBy();
        $finance = $this->memberOf($academy, ['finance.manage']);

        $response = $this->actingAs($finance, 'api')
            ->postJson("/api/academies/{$academy->id}/revenue/campaigns", [
                'campaign_type' => 'advertisement',
                'title' => 'แคมเปญระดมทุนคอมพิวเตอร์',
                'budget_amount' => 500,
                'total_views' => 1000,
                'duration' => 15,
                'payment_method' => 'wallet',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('adverts', [
            'academy_id' => $academy->id,
            'title' => 'แคมเปญระดมทุนคอมพิวเตอร์',
        ]);
    }

    // 5. finance_view only holder cannot approve donation
    public function test_finance_view_only_holder_cannot_approve_donation(): void
    {
        [$academy] = $this->academyOwnedBy();
        $viewer = $this->memberOf($academy, ['finance.view']);
        $donation = $this->pendingCashDonationIn($academy);

        $response = $this->actingAs($viewer, 'api')
            ->postJson("/api/academies/{$academy->id}/revenue/donations/{$donation->id}/approve", [
                'note' => 'พยายามอนุมัติ',
            ]);

        $response->assertStatus(403);
        $this->assertSame('pending', $donation->fresh()->status);
    }

    // 6. plain member without finance keys cannot list campaigns
    public function test_plain_member_without_finance_keys_cannot_list_campaigns(): void
    {
        [$academy] = $this->academyOwnedBy();
        $plainMember = $this->memberOf($academy, []);

        $response = $this->actingAs($plainMember, 'api')
            ->getJson("/api/academies/{$academy->id}/revenue/campaigns");

        $response->assertStatus(403);
    }

    // 7. admin of another academy cannot approve donation (tenant)
    public function test_admin_of_another_academy_cannot_approve_donation_tenant(): void
    {
        [$academyA] = $this->academyOwnedBy();
        $donationA = $this->pendingCashDonationIn($academyA);

        [$academyB, $ownerB] = $this->academyOwnedBy();

        // 1) เรียกผ่าน URL ของ academyB แต่ส่ง donation ของ academyA -> controller tenant check บล็อก 403
        $responseB = $this->actingAs($ownerB, 'api')
            ->postJson("/api/academies/{$academyB->id}/revenue/donations/{$donationA->id}/approve", [
                'note' => 'ข้ามโรงเรียน',
            ]);
        $responseB->assertStatus(403);

        // 2) เรียกผ่าน URL ของ academyA ตรงๆ -> middleware บล็อก 403
        $responseA = $this->actingAs($ownerB, 'api')
            ->postJson("/api/academies/{$academyA->id}/revenue/donations/{$donationA->id}/approve", [
                'note' => 'ข้ามโรงเรียน',
            ]);
        $responseA->assertStatus(403);

        $this->assertSame('pending', $donationA->fresh()->status);
    }

    // Helper tests for remaining finance.manage routes
    public function test_finance_manage_holder_can_reject_donation(): void
    {
        [$academy] = $this->academyOwnedBy();
        $finance = $this->memberOf($academy, ['finance.manage']);
        $donation = $this->pendingCashDonationIn($academy);

        $response = $this->actingAs($finance, 'api')
            ->postJson("/api/academies/{$academy->id}/revenue/donations/{$donation->id}/reject", [
                'reason' => 'สลิปไม่ถูกต้อง',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('academy_donates', [
            'id' => $donation->id,
            'status' => 'rejected',
            'rejection_reason' => 'สลิปไม่ถูกต้อง',
        ]);
    }

    public function test_finance_manage_holder_can_update_campaign(): void
    {
        [$academy, $owner] = $this->academyOwnedBy();
        $finance = $this->memberOf($academy, ['finance.manage']);
        $campaign = $this->campaignIn($academy, $owner);

        $response = $this->actingAs($finance, 'api')
            ->patchJson("/api/academies/{$academy->id}/revenue/campaigns/{$campaign->id}", [
                'title' => 'ชื่อแคมเปญใหม่',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('adverts', [
            'id' => $campaign->id,
            'title' => 'ชื่อแคมเปญใหม่',
        ]);
    }

    public function test_admin_of_another_academy_cannot_update_campaign_tenant(): void
    {
        [$academyA, $ownerA] = $this->academyOwnedBy();
        $campaignA = $this->campaignIn($academyA, $ownerA);

        [$academyB, $ownerB] = $this->academyOwnedBy();

        $response = $this->actingAs($ownerB, 'api')
            ->patchJson("/api/academies/{$academyB->id}/revenue/campaigns/{$campaignA->id}", [
                'title' => 'แฮกแคมเปญ',
            ]);

        $response->assertStatus(403);
    }
}
