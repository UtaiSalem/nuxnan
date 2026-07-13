<?php

namespace Tests\Feature\Campaign;

use App\Enums\CampaignPaymentStatus;
use App\Enums\CampaignReviewStatus;
use App\Enums\CampaignScopeType;
use App\Enums\CampaignType;
use App\Models\Academy;
use App\Models\Advert;
use App\Models\Course;
use App\Models\Donate;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Tests\TestCase;

class CampaignSystemTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $user;

    private string $adminToken;

    private string $userToken;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        Role::firstOrCreate(['name' => 'SUPER_ADMIN']);
        Role::firstOrCreate(['name' => 'ADMIN']);
        Role::firstOrCreate(['name' => 'PLEARND_ADMIN']);
        Role::firstOrCreate(['name' => 'MEMBER']);
        Role::firstOrCreate(['name' => 'INSTRUCTOR']);

        // Create users with verified emails to bypass verified middleware
        $this->admin = User::factory()->create([
            'email' => 'admin@nuxnan.com',
            'email_verified_at' => now(),
            'personal_code' => '99999999',
            'wallet' => 1000.00,
            'pp' => 10000,
        ]);
        $this->admin->assignRole('SUPER_ADMIN');
        $this->adminToken = JWTAuth::fromUser($this->admin);

        $this->user = User::factory()->create([
            'email' => 'user@nuxnan.com',
            'email_verified_at' => now(),
            'wallet' => 5000.00,
            'pp' => 20000,
        ]);
        $this->user->assignRole('MEMBER');
        $this->userToken = JWTAuth::fromUser($this->user);
    }

    private function createAdvert(array $attributes = []): Advert
    {
        return Advert::create(array_merge([
            'user_id' => $this->user->id,
            'campaign_type' => CampaignType::ADVERTISEMENT,
            'scope_type' => CampaignScopeType::PUBLIC,
            'amounts' => 100,
            'budget_amount' => 100.00,
            'duration' => 10,
            'total_views' => 100,
            'remaining_views' => 100,
            'slip' => '',
            'media_image' => '',
            'status' => 0,
            'exchange_points' => 0,
            'transfer_date' => '2026-07-12',
            'transfer_time' => '12:00:00',
            'payment_status' => CampaignPaymentStatus::PAID,
            'review_status' => CampaignReviewStatus::PENDING,
        ], $attributes));
    }

    /** @test */
    public function it_creates_campaign_with_wallet_and_recalculates_pricing_correctly(): void
    {
        $payload = [
            'campaign_type' => 'advertisement',
            'scope_type' => 'public',
            'title' => 'Test Advertisement Campaign',
            'description' => 'A wonderful ad campaign',
            'budget_amount' => 100.00, // recalculated price must match budget
            'duration' => 10,
            'total_views' => 100,
            'payment_method' => 'wallet',
            'idempotency_key' => 'idemp-111',
            'media_image' => UploadedFile::fake()->image('ad.png'),
        ];

        // 100 views * 10 seconds * 0.10 THB/second = 100 THB
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->userToken}",
        ])->postJson('/api/campaigns', $payload);

        $response->assertStatus(201);
        $response->assertJsonPath('success', true);

        // Payer's wallet should be decremented by 100 THB
        $this->user->refresh();
        $this->assertEquals(4900.00, (float) $this->user->wallet);

        $campaign = Advert::where('idempotency_key', 'idemp-111')->first();
        $this->assertNotNull($campaign);
        $this->assertEquals(100.00, (float) $campaign->budget_amount);
        $this->assertEquals(CampaignPaymentStatus::PAID, $campaign->payment_status);
        $this->assertEquals(CampaignReviewStatus::PENDING, $campaign->review_status);
    }

    /** @test */
    public function it_validates_scope_integrity(): void
    {
        $academy = Academy::factory()->create(['user_id' => $this->user->id]);
        $course = Course::factory()->create([
            'user_id' => $this->user->id,
            'academy_id' => $academy->id,
        ]);

        // Use adminToken (bypasses check authorization and triggers validator 422 directly)
        // 1. Scope public but sending academy_id should fail validation
        $payload = [
            'campaign_type' => 'support',
            'scope_type' => 'public',
            'academy_id' => $academy->id,
            'budget_amount' => 500.00,
            'payment_method' => 'wallet',
        ];
        $this->withHeaders([
            'Authorization' => "Bearer {$this->adminToken}",
        ])->postJson('/api/campaigns', $payload)->assertStatus(422);

        // 2. Scope academy but missing academy_id should fail validation
        $payload = [
            'campaign_type' => 'support',
            'scope_type' => 'academy',
            'budget_amount' => 500.00,
            'payment_method' => 'wallet',
        ];
        $this->withHeaders([
            'Authorization' => "Bearer {$this->adminToken}",
        ])->postJson('/api/campaigns', $payload)->assertStatus(422);
    }

    /** @test */
    public function it_approves_slip_payment_and_marks_as_paid(): void
    {
        $campaign = $this->createAdvert([
            'amounts' => 150,
            'budget_amount' => 150.00,
            'payment_status' => CampaignPaymentStatus::PENDING_SLIP,
            'review_status' => CampaignReviewStatus::PENDING,
            'total_views' => 150,
            'remaining_views' => 150,
        ]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->adminToken}",
        ])->patchJson("/api/campaigns/{$campaign->id}/review", [
            'action' => 'approve',
        ]);

        $response->assertStatus(200);
        $campaign->refresh();
        $this->assertEquals(CampaignPaymentStatus::PAID, $campaign->payment_status);
        $this->assertEquals(CampaignReviewStatus::APPROVED, $campaign->review_status);
        $this->assertEquals(1, $campaign->status);
    }

    /** @test */
    public function it_rejects_and_refunds_wallet_correctly(): void
    {
        $campaign = $this->createAdvert([
            'amounts' => 200,
            'budget_amount' => 200.00,
            'payment_status' => CampaignPaymentStatus::PAID,
            'review_status' => CampaignReviewStatus::PENDING,
            'total_views' => 200,
            'remaining_views' => 200,
        ]);

        // Supporter starts with 5000 wallet balance. Let's make sure it's 5000.
        $this->user->refresh();
        $this->assertEquals(5000.00, (float) $this->user->wallet);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->adminToken}",
        ])->patchJson("/api/campaigns/{$campaign->id}/review", [
            'action' => 'reject',
            'rejection_reason' => 'Wrong campaign settings',
        ]);

        $response->assertStatus(200);
        $campaign->refresh();
        $this->assertEquals(CampaignPaymentStatus::REFUNDED, $campaign->payment_status);
        $this->assertEquals(CampaignReviewStatus::REJECTED, $campaign->review_status);
        $this->assertEquals(2, $campaign->status);

        // User should be refunded 200.00 THB -> wallet becomes 5200.00
        $this->user->refresh();
        $this->assertEquals(5200.00, (float) $this->user->wallet);
    }

    /** @test */
    public function it_limits_rewarded_views_per_user_per_day_to_five(): void
    {
        $campaign = $this->createAdvert([
            'user_id' => $this->admin->id,
            'amounts' => 100,
            'budget_amount' => 100.00,
            'payment_status' => CampaignPaymentStatus::PAID,
            'review_status' => CampaignReviewStatus::APPROVED,
            'duration' => 10, // requires 200 PP points from viewer
            'total_views' => 100,
            'remaining_views' => 10,
            'status' => 1,
        ]);

        // Viewer starts with 20000 PP and 5000.00 wallet
        $viewer = $this->user;

        // View 5 times
        for ($i = 1; $i <= 5; $i++) {
            $response = $this->withHeaders([
                'Authorization' => "Bearer {$this->userToken}",
            ])->postJson("/api/campaigns/{$campaign->id}/view", [
                'idempotency_key' => "view-key-{$i}",
            ]);
            $response->assertStatus(200);
        }

        // Refreshed campaign remaining views should decrease by 5
        $campaign->refresh();
        $this->assertEquals(5, $campaign->remaining_views);

        // Viewer PP and wallet
        $viewer->refresh();
        // 5 views * 200 PP = 1000 PP deducted -> 19000 PP remaining
        $this->assertEquals(19000, $viewer->pp);
        // Reward per view = 10s * 0.06 + 200 PP / 1200 = 0.6 + 0.16667 = 0.76667 THB.
        // 5 views -> 3.8333 THB added -> 5003.83 wallet
        $this->assertEqualsWithDelta(5003.83, (float) $viewer->wallet, 0.01);

        // 6th view should be rejected with 429 (daily quota reached), not a 500 server error
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->userToken}",
        ])->postJson("/api/campaigns/{$campaign->id}/view", [
            'idempotency_key' => 'view-key-6',
        ]);
        $response->assertStatus(429);

        // Idempotency check: view with the same key should return success and not decrement again
        $campaign->refresh();
        $remainingBefore = $campaign->remaining_views;

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->userToken}",
        ])->postJson("/api/campaigns/{$campaign->id}/view", [
            'idempotency_key' => 'view-key-3', // previously sent
        ]);
        $response->assertStatus(200);

        $campaign->refresh();
        $this->assertEquals($remainingBefore, $campaign->remaining_views);
    }

    /** @test */
    public function it_splits_support_revenue_70_20_10_correctly_to_accounts(): void
    {
        $academyOwner = User::factory()->create([
            'email' => 'academy_owner@nuxnan.com',
            'email_verified_at' => now(),
            'wallet' => 1000.00,
        ]);
        $instructor = User::factory()->create([
            'email' => 'instructor@nuxnan.com',
            'email_verified_at' => now(),
            'wallet' => 1000.00,
        ]);

        $academy = Academy::factory()->create(['user_id' => $academyOwner->id]);
        $course = Course::factory()->create([
            'instructor_id' => $instructor->id,
            'user_id' => $instructor->id,
            'academy_id' => $academy->id,
        ]);

        $campaign = $this->createAdvert([
            'campaign_type' => CampaignType::SUPPORT,
            'scope_type' => CampaignScopeType::COURSE,
            'academy_id' => $academy->id,
            'course_id' => $course->id,
            'amounts' => 1000,
            'budget_amount' => 1000.00, // 1000 THB budget
            'exchange_points' => 1000 * 1080, // supporter earns points at the course rate
            'payment_status' => CampaignPaymentStatus::PAID,
            'review_status' => CampaignReviewStatus::PENDING,
            'duration' => 0,
            'total_views' => 0,
            'remaining_views' => 0,
        ]);

        $supporterPpBefore = (int) $this->user->fresh()->pp;

        // Platform account starts with 1000.00 (from admin)
        $this->admin->refresh();
        $this->assertEquals(1000.00, (float) $this->admin->wallet);

        // Approve it
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->adminToken}",
        ])->patchJson("/api/campaigns/{$campaign->id}/review", [
            'action' => 'approve',
        ]);
        $response->assertStatus(200);

        // Check wallet balances:
        // Academy Owner should get 70% of 1000.00 = 700 THB -> 1700.00
        $academyOwner->refresh();
        $this->assertEquals(1700.00, (float) $academyOwner->wallet);

        // Instructor should get 20% of 1000.00 = 200 THB -> 1200.00
        $instructor->refresh();
        $this->assertEquals(1200.00, (float) $instructor->wallet);

        // Platform Admin should get 10% of 1000.00 = 100 THB -> 1100.00
        $this->admin->refresh();
        $this->assertEquals(1100.00, (float) $this->admin->wallet);

        // Supporter earns the configured points in exchange for their contribution
        $this->assertEquals($supporterPpBefore + (1000 * 1080), (int) $this->user->fresh()->pp);

        // Distribution is recorded exactly once
        $campaign->refresh();
        $this->assertNotNull($campaign->distributed_at);
    }

    /** @test */
    public function it_does_not_double_distribute_support_when_paused_then_reapproved(): void
    {
        $academyOwner = User::factory()->create(['email_verified_at' => now(), 'wallet' => 1000.00]);
        $academy = Academy::factory()->create(['user_id' => $academyOwner->id]);

        $campaign = $this->createAdvert([
            'campaign_type' => CampaignType::SUPPORT,
            'scope_type' => CampaignScopeType::ACADEMY,
            'academy_id' => $academy->id,
            'amounts' => 500,
            'budget_amount' => 500.00,
            'payment_status' => CampaignPaymentStatus::PAID,
            'review_status' => CampaignReviewStatus::PENDING,
            'duration' => 0,
            'total_views' => 0,
            'remaining_views' => 0,
        ]);

        $approve = fn () => $this->withHeaders(['Authorization' => "Bearer {$this->adminToken}"])
            ->patchJson("/api/campaigns/{$campaign->id}/review", ['action' => 'approve']);
        $pause = fn () => $this->withHeaders(['Authorization' => "Bearer {$this->adminToken}"])
            ->patchJson("/api/campaigns/{$campaign->id}/review", ['action' => 'pause']);

        $approve()->assertStatus(200);
        // Academy owner (70% + 20% instructor share, no instructor) = 90% of 500 = 450 -> 1450
        $academyOwner->refresh();
        $this->assertEquals(1450.00, (float) $academyOwner->wallet);

        // Pause then re-approve must NOT distribute funds again
        $pause()->assertStatus(200);
        $approve()->assertStatus(200);

        $academyOwner->refresh();
        $this->assertEquals(1450.00, (float) $academyOwner->wallet);
    }

    /** @test */
    public function it_blocks_reject_after_support_was_distributed(): void
    {
        $academyOwner = User::factory()->create(['email_verified_at' => now(), 'wallet' => 1000.00]);
        $academy = Academy::factory()->create(['user_id' => $academyOwner->id]);

        $campaign = $this->createAdvert([
            'campaign_type' => CampaignType::SUPPORT,
            'scope_type' => CampaignScopeType::ACADEMY,
            'academy_id' => $academy->id,
            'amounts' => 500,
            'budget_amount' => 500.00,
            'payment_status' => CampaignPaymentStatus::PAID,
            'review_status' => CampaignReviewStatus::PENDING,
            'duration' => 0,
            'total_views' => 0,
            'remaining_views' => 0,
        ]);

        $this->withHeaders(['Authorization' => "Bearer {$this->adminToken}"])
            ->patchJson("/api/campaigns/{$campaign->id}/review", ['action' => 'approve'])
            ->assertStatus(200);

        // Rejecting an already-distributed campaign is not allowed (would create money without clawback)
        $this->withHeaders(['Authorization' => "Bearer {$this->adminToken}"])
            ->patchJson("/api/campaigns/{$campaign->id}/review", ['action' => 'reject', 'rejection_reason' => 'too late'])
            ->assertStatus(422);

        $campaign->refresh();
        $this->assertEquals(CampaignReviewStatus::APPROVED, $campaign->review_status);
        $this->assertEquals(CampaignPaymentStatus::PAID, $campaign->payment_status);
        $academyOwner->refresh();
        $this->assertEquals(1450.00, (float) $academyOwner->wallet);
    }

    /** @test */
    public function it_isolates_academy_campaigns_and_respects_course_inherit_toggle(): void
    {
        $academyA = Academy::factory()->create(['user_id' => $this->admin->id]);
        $academyB = Academy::factory()->create(['user_id' => $this->user->id]);

        $courseA = Course::factory()->create([
            'user_id' => $this->admin->id,
            'academy_id' => $academyA->id,
        ]);

        // Campaign 1: targeted directly to Academy A
        $c1 = $this->createAdvert([
            'user_id' => $this->admin->id,
            'campaign_type' => CampaignType::ADVERTISEMENT,
            'scope_type' => CampaignScopeType::ACADEMY,
            'academy_id' => $academyA->id,
            'review_status' => CampaignReviewStatus::APPROVED,
        ]);

        // Campaign 2: course campaign in Academy A with inherit_to_academy = true
        $c2 = $this->createAdvert([
            'user_id' => $this->admin->id,
            'campaign_type' => CampaignType::ADVERTISEMENT,
            'scope_type' => CampaignScopeType::COURSE,
            'academy_id' => $academyA->id,
            'course_id' => $courseA->id,
            'inherit_to_academy' => true,
            'review_status' => CampaignReviewStatus::APPROVED,
        ]);

        // Campaign 3: course campaign in Academy A with inherit_to_academy = false
        $c3 = $this->createAdvert([
            'user_id' => $this->admin->id,
            'campaign_type' => CampaignType::ADVERTISEMENT,
            'scope_type' => CampaignScopeType::COURSE,
            'academy_id' => $academyA->id,
            'course_id' => $courseA->id,
            'inherit_to_academy' => false,
            'review_status' => CampaignReviewStatus::APPROVED,
        ]);

        // Campaign 4: targeted directly to Academy B
        $c4 = $this->createAdvert([
            'user_id' => $this->user->id,
            'campaign_type' => CampaignType::ADVERTISEMENT,
            'scope_type' => CampaignScopeType::ACADEMY,
            'academy_id' => $academyB->id,
            'review_status' => CampaignReviewStatus::APPROVED,
        ]);

        // Query Academy A widget: should return c1 and c2, but NOT c3 (no inherit) or c4 (different academy)
        $response = $this->getJson("/api/campaigns?scope=academy&academy_id={$academyA->id}");
        $response->assertStatus(200);

        // Pluck from flat campaigns collection directly instead of campaigns.data
        $ids = collect($response->json('campaigns'))->pluck('id')->toArray();

        $this->assertContains($c1->id, $ids);
        $this->assertContains($c2->id, $ids);
        $this->assertNotContains($c3->id, $ids);
        $this->assertNotContains($c4->id, $ids);
    }

    /** @test */
    public function it_does_not_round_decimals_for_campaign_budget(): void
    {
        $campaign = $this->createAdvert([
            'campaign_type' => CampaignType::SUPPORT,
            'scope_type' => CampaignScopeType::PUBLIC,
            'amounts' => 99,
            'budget_amount' => 99.55,
            'duration' => 0,
            'total_views' => 0,
            'remaining_views' => 0,
        ]);

        $campaign->refresh();
        $this->assertEquals(99.55, (float) $campaign->budget_amount);
    }

    /** @test */
    public function it_can_query_legacy_donates_table_without_regression(): void
    {
        // Insert dummy legacy donate record directly
        Donate::create([
            'user_id' => $this->user->id,
            'donor_id' => $this->user->id,
            'donor_name' => 'Payer Name',
            'amounts' => 1500,
            'payment_method' => 'wallet',
            'slip' => 'fake_slip.png',
            'transfer_date' => '2026-07-12',
            'transfer_time' => '12:00:00',
            'status' => 1,
        ]);

        $donates = Donate::all();
        $this->assertCount(1, $donates);
        $this->assertEquals(1500, $donates->first()->amounts);
    }

    /** @test */
    public function it_restricts_campaign_review_to_guest(): void
    {
        $campaign = $this->createAdvert([
            'campaign_type' => CampaignType::ADVERTISEMENT,
            'scope_type' => CampaignScopeType::PUBLIC,
            'review_status' => CampaignReviewStatus::PENDING,
        ]);

        // Guest (no token) should receive 401
        $this->patchJson("/api/campaigns/{$campaign->id}/review", ['action' => 'approve'])
            ->assertStatus(401);
    }

    /** @test */
    public function it_restricts_campaign_review_to_unauthorized_member(): void
    {
        $campaign = $this->createAdvert([
            'campaign_type' => CampaignType::ADVERTISEMENT,
            'scope_type' => CampaignScopeType::PUBLIC,
            'review_status' => CampaignReviewStatus::PENDING,
        ]);

        // Standard user / member (unauthorized) should receive 403
        $this->withHeaders([
            'Authorization' => "Bearer {$this->userToken}",
        ])->patchJson("/api/campaigns/{$campaign->id}/review", ['action' => 'approve'])
            ->assertStatus(403);
    }

    /** @test */
    public function it_allows_campaign_review_to_authorized_admin(): void
    {
        $campaign = $this->createAdvert([
            'campaign_type' => CampaignType::ADVERTISEMENT,
            'scope_type' => CampaignScopeType::PUBLIC,
            'review_status' => CampaignReviewStatus::PENDING,
        ]);

        // Superadmin/Admin should receive 200
        $this->withHeaders([
            'Authorization' => "Bearer {$this->adminToken}",
        ])->patchJson("/api/campaigns/{$campaign->id}/review", ['action' => 'approve'])
            ->assertStatus(200);
    }

    /** @test */
    public function an_outsider_can_create_academy_and_course_campaigns_but_cannot_review_them(): void
    {
        $academy = Academy::factory()->create();
        $course = Course::factory()->create(['academy_id' => $academy->id]);

        foreach ([
            ['scope_type' => 'academy', 'academy_id' => $academy->id],
            ['scope_type' => 'course', 'academy_id' => $academy->id, 'course_id' => $course->id],
        ] as $scope) {
            $response = $this->withHeaders(['Authorization' => "Bearer {$this->userToken}"])
                ->postJson('/api/campaigns', array_merge([
                    'campaign_type' => 'support',
                    'budget_amount' => 1,
                    'payment_method' => 'wallet',
                ], $scope));

            $response->assertCreated();
            $campaignId = $response->json('campaign.id');

            $this->withHeaders(['Authorization' => "Bearer {$this->userToken}"])
                ->patchJson("/api/campaigns/{$campaignId}/review", ['action' => 'approve'])
                ->assertForbidden();
        }
    }

    /** @test */
    public function authenticated_users_can_search_all_campaign_targets(): void
    {
        $academy = Academy::factory()->create(['name' => 'Target Academy']);
        $course = Course::factory()->create(['name' => 'Target Course', 'academy_id' => $academy->id]);

        $this->withHeaders(['Authorization' => "Bearer {$this->userToken}"])
            ->getJson('/api/campaigns/targets/academies?q=Target')
            ->assertOk()
            ->assertJsonPath('academies.0.id', $academy->id);

        $this->withHeaders(['Authorization' => "Bearer {$this->userToken}"])
            ->getJson('/api/campaigns/targets/courses?q=Target')
            ->assertOk()
            ->assertJsonPath('courses.0.id', $course->id);

        // Lookup by id (used by the create page to prefill from query params)
        $this->withHeaders(['Authorization' => "Bearer {$this->userToken}"])
            ->getJson("/api/campaigns/targets/academies?id={$academy->id}")
            ->assertOk()
            ->assertJsonCount(1, 'academies')
            ->assertJsonPath('academies.0.id', $academy->id);

        $this->withHeaders(['Authorization' => "Bearer {$this->userToken}"])
            ->getJson("/api/campaigns/targets/courses?id={$course->id}")
            ->assertOk()
            ->assertJsonCount(1, 'courses')
            ->assertJsonPath('courses.0.id', $course->id);
    }
}
