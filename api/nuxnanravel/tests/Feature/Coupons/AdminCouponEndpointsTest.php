<?php

namespace Tests\Feature\Coupons;

use App\Models\AuditLog;
use App\Models\Coupon;
use App\Models\PointsTransaction;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Coupons\Concerns\BuildsCouponActors;
use Tests\TestCase;

/**
 * /api/admin/coupons — list / show / store / update / destroy
 *
 * ก่อนหน้านี้ทุก endpoint พัง: index/show อ้าง relation creator/usedBy ที่ไม่มี (500),
 * store เขียนคอลัมน์ code/type/creator_id ที่ไม่มีจริง และ status 'unused' ที่ไม่อยู่ใน enum
 */
class AdminCouponEndpointsTest extends TestCase
{
    use BuildsCouponActors;
    use RefreshDatabase;

    protected function tearDown(): void
    {
        $this->deleteGeneratedQrCodes();
        parent::tearDown();
    }

    // ------------------------------------------------------------------
    // index
    // ------------------------------------------------------------------

    public function test_index_lists_coupons_with_creator_redeemer_and_summary(): void
    {
        $admin = $this->platformAdmin();
        $member = $this->member();

        $this->makeCoupon($admin, ['amount' => 50]);
        $this->makeCoupon($admin, ['coupon_type' => 'wallet', 'amount' => 20]);
        $this->makeCoupon($member, [
            'status' => 'redeemed',
            'redeemed_by' => $admin->id,
            'redeemed_at' => now(),
        ]);

        $response = $this->actingAs($admin, 'api')->getJson('/api/admin/coupons');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.meta.total', 3)
            ->assertJsonPath('summary.active_count', 2)
            ->assertJsonPath('summary.redeemed_count', 1)
            ->assertJsonStructure([
                'data' => [
                    'data' => [[
                        'id', 'coupon_code', 'coupon_type', 'amount', 'status', 'display_status',
                        'status_label', 'expires_at', 'can_edit', 'can_cancel', 'creator',
                    ]],
                    'links',
                    'meta',
                ],
                'summary' => ['active_count', 'active_points', 'active_wallet', 'redeemed_count'],
            ]);

        $this->assertEquals(50, (float) $response->json('summary.active_points'));
        $this->assertEquals(20, (float) $response->json('summary.active_wallet'));

        $redeemed = collect($response->json('data.data'))->firstWhere('status', 'redeemed');
        $this->assertSame($member->id, $redeemed['creator']['id']);
        $this->assertFalse($redeemed['creator']['is_plearnd_admin']);
        $this->assertSame($admin->id, $redeemed['redeemer']['id']);
    }

    public function test_index_filters_by_status_type_and_search(): void
    {
        $admin = $this->platformAdmin();

        $live = $this->makeCoupon($admin, ['description' => 'โปรเปิดเทอม']);
        $pastDue = $this->makeCoupon($admin, ['expires_at' => now()->subDay()]);
        $wallet = $this->makeCoupon($admin, ['coupon_type' => 'wallet']);
        $this->makeCoupon($admin, ['status' => 'cancelled']);

        // active = ยังไม่หมดอายุจริง ๆ (คูปองที่เลยวันหมดอายุแต่ status ยังเป็น active ต้องไม่ติดมา)
        $active = $this->actingAs($admin, 'api')->getJson('/api/admin/coupons?status=active');
        $active->assertOk();
        $this->assertEqualsCanonicalizing([$live->id, $wallet->id], array_column($active->json('data.data'), 'id'));

        $expired = $this->actingAs($admin, 'api')->getJson('/api/admin/coupons?status=expired');
        $expired->assertOk()->assertJsonPath('data.meta.total', 1)
            ->assertJsonPath('data.data.0.id', $pastDue->id)
            ->assertJsonPath('data.data.0.display_status', 'expired');

        $this->actingAs($admin, 'api')->getJson('/api/admin/coupons?type=wallet')
            ->assertOk()->assertJsonPath('data.meta.total', 1)->assertJsonPath('data.data.0.id', $wallet->id);

        $this->actingAs($admin, 'api')->getJson('/api/admin/coupons?search='.$live->coupon_code)
            ->assertOk()->assertJsonPath('data.meta.total', 1)->assertJsonPath('data.data.0.id', $live->id);

        $this->actingAs($admin, 'api')->getJson('/api/admin/coupons?search=เปิดเทอม')
            ->assertOk()->assertJsonPath('data.meta.total', 1)->assertJsonPath('data.data.0.id', $live->id);

        $this->actingAs($admin, 'api')->getJson('/api/admin/coupons?status=unused')->assertStatus(422);
    }

    public function test_index_requires_platform_admin_and_coupon_list_permission(): void
    {
        $this->getJson('/api/admin/coupons')->assertStatus(401);

        // ผู้ใช้ทั่วไป → ไม่ผ่านด่าน admin role
        $this->actingAs($this->member(), 'api')->getJson('/api/admin/coupons')->assertStatus(403);

        // VENDOR มี coupon-list แต่ไม่ใช่ Plearnd admin → ห้ามเห็นรหัสคูปองของคนอื่น
        $this->actingAs($this->vendor(), 'api')->getJson('/api/admin/coupons')->assertStatus(403);

        // Plearnd admin ที่ไม่มีสิทธิ์ coupon-list
        $this->actingAs($this->plearndWithoutCouponPermission(), 'api')->getJson('/api/admin/coupons')->assertStatus(403);
    }

    // ------------------------------------------------------------------
    // show
    // ------------------------------------------------------------------

    public function test_show_returns_coupon_with_creator(): void
    {
        $admin = $this->platformAdmin();
        $coupon = $this->makeCoupon($admin, ['description' => 'รางวัลประกวด']);

        $this->actingAs($admin, 'api')->getJson("/api/admin/coupons/{$coupon->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $coupon->id)
            ->assertJsonPath('data.coupon_code', $coupon->coupon_code)
            ->assertJsonPath('data.description', 'รางวัลประกวด')
            ->assertJsonPath('data.creator.id', $admin->id)
            ->assertJsonPath('data.creator.is_plearnd_admin', true)
            ->assertJsonPath('data.redeemer', null);

        $this->actingAs($admin, 'api')->getJson('/api/admin/coupons/999999')->assertStatus(404);
        $this->actingAs($this->vendor(), 'api')->getJson("/api/admin/coupons/{$coupon->id}")->assertStatus(403);
    }

    // ------------------------------------------------------------------
    // store
    // ------------------------------------------------------------------

    public function test_store_points_coupons_deducts_admin_points_and_writes_ledger(): void
    {
        $admin = $this->platformAdmin(['pp' => 1000]);

        $response = $this->actingAs($admin, 'api')->postJson('/api/admin/coupons', [
            'type' => 'points',
            'amount' => 150,
            'quantity' => 3,
            'description' => 'แจกงานกีฬาสี',
            'expires_in_days' => 30,
            // ฟิลด์ของฟอร์มเก่า/คอลัมน์ที่ไม่ควรรับ ต้องถูกเมิน
            'code' => 'HACKME',
            'status' => 'redeemed',
            'user_id' => 999,
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.count', 3)
            ->assertJsonCount(3, 'data.coupons');

        $this->assertEquals(450, (float) $response->json('data.total_amount'));
        $this->assertEquals(550, (float) $admin->fresh()->pp);

        $coupons = Coupon::where('user_id', $admin->id)->get();
        $this->assertCount(3, $coupons);
        foreach ($coupons as $coupon) {
            $this->assertSame('points', $coupon->coupon_type);
            $this->assertSame('active', $coupon->status);
            $this->assertEquals(150, (float) $coupon->amount);
            $this->assertSame('แจกงานกีฬาสี', $coupon->description);
            $this->assertMatchesRegularExpression('/^\d{8}$/', $coupon->coupon_code);
            $this->assertTrue($coupon->expires_at->between(now()->addDays(29), now()->addDays(31)));
        }
        $this->assertDatabaseMissing('coupons', ['coupon_code' => 'HACKME']);

        $this->assertDatabaseHas('points_transactions', [
            'user_id' => $admin->id,
            'transaction_type' => 'spend',
            'source_type' => 'coupon_creation',
        ]);
        $this->assertEquals(450, (float) PointsTransaction::where('user_id', $admin->id)->where('source_type', 'coupon_creation')->value('amount'));

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => AuditLog::ACTION_CREATED,
            'entity_type' => Coupon::class,
            'module' => 'coupons',
        ]);
    }

    public function test_store_wallet_coupon_deducts_admin_wallet_and_writes_ledger(): void
    {
        $admin = $this->platformAdmin(['wallet' => 500]);

        $this->actingAs($admin, 'api')->postJson('/api/admin/coupons', [
            'type' => 'wallet',
            'amount' => 120.50,
        ])->assertCreated()->assertJsonPath('data.count', 1)
            ->assertJsonPath('data.coupons.0.coupon_type', 'wallet')
            ->assertJsonPath('data.coupons.0.expires_at', null);

        $this->assertEquals(379.50, (float) $admin->fresh()->wallet);

        $tx = WalletTransaction::where('user_id', $admin->id)->sole();
        $this->assertSame('purchase', $tx->transaction_type);
        $this->assertEquals(120.50, (float) $tx->amount);
        $this->assertSame('coupon_creation', $tx->metadata['reason'] ?? null);
    }

    public function test_store_rejects_when_admin_balance_is_insufficient(): void
    {
        $admin = $this->platformAdmin(['pp' => 100, 'wallet' => 10]);

        $this->actingAs($admin, 'api')->postJson('/api/admin/coupons', [
            'type' => 'points', 'amount' => 60, 'quantity' => 2,
        ])->assertStatus(422)->assertJsonPath('error_code', 'INSUFFICIENT_BALANCE');

        $this->actingAs($admin, 'api')->postJson('/api/admin/coupons', [
            'type' => 'wallet', 'amount' => 11,
        ])->assertStatus(422)->assertJsonPath('error_code', 'INSUFFICIENT_BALANCE');

        $this->assertSame(0, Coupon::count());
        $this->assertEquals(100, (float) $admin->fresh()->pp);
        $this->assertEquals(10, (float) $admin->fresh()->wallet);
        $this->assertSame(0, PointsTransaction::count());
        $this->assertSame(0, WalletTransaction::count());
    }

    public function test_store_validates_input(): void
    {
        $admin = $this->platformAdmin();

        $this->actingAs($admin, 'api')->postJson('/api/admin/coupons', [])
            ->assertStatus(422)->assertJsonValidationErrors(['type', 'amount']);

        // แต้มต้องเป็นจำนวนเต็ม
        $this->actingAs($admin, 'api')->postJson('/api/admin/coupons', ['type' => 'points', 'amount' => 10.5])
            ->assertStatus(422)->assertJsonValidationErrors(['amount']);

        // เงินทศนิยมเกิน 2 ตำแหน่ง
        $this->actingAs($admin, 'api')->postJson('/api/admin/coupons', ['type' => 'wallet', 'amount' => 10.555])
            ->assertStatus(422)->assertJsonValidationErrors(['amount']);

        $this->actingAs($admin, 'api')->postJson('/api/admin/coupons', ['type' => 'points', 'amount' => 10, 'quantity' => 101])
            ->assertStatus(422)->assertJsonValidationErrors(['quantity']);

        $this->actingAs($admin, 'api')->postJson('/api/admin/coupons', ['type' => 'percentage', 'amount' => 10])
            ->assertStatus(422)->assertJsonValidationErrors(['type']);

        $this->assertSame(0, Coupon::count());
    }

    public function test_store_is_limited_to_platform_admins_with_coupon_create(): void
    {
        $payload = ['type' => 'points', 'amount' => 100];

        // VENDOR มี coupon-create แต่ไม่ใช่ Plearnd admin → คูปองของเขาจะแลกไม่ได้หลัง Phase 2 จึงห้ามสร้างที่นี่
        $vendor = $this->vendor();
        $this->actingAs($vendor, 'api')->postJson('/api/admin/coupons', $payload)->assertStatus(403);
        $this->assertEquals(1000, (float) $vendor->fresh()->pp);

        $noPermission = $this->plearndWithoutCouponPermission();
        $this->actingAs($noPermission, 'api')->postJson('/api/admin/coupons', $payload)->assertStatus(403);
        $this->assertEquals(1000, (float) $noPermission->fresh()->pp);

        // Plearnd admin ที่ยังไม่ยืนยันอีเมล → isPlearndAdmin() = false
        $unverified = $this->platformAdmin(['email_verified_at' => null]);
        $this->actingAs($unverified, 'api')->postJson('/api/admin/coupons', $payload)->assertStatus(403);

        $this->assertSame(0, Coupon::count());
    }

    // ------------------------------------------------------------------
    // update
    // ------------------------------------------------------------------

    public function test_update_changes_only_description_and_expiry(): void
    {
        $admin = $this->platformAdmin();
        $coupon = $this->makeCoupon($admin, ['amount' => 100, 'description' => 'เดิม']);
        $newExpiry = now()->addDays(10)->startOfMinute();

        $this->actingAs($admin, 'api')->putJson("/api/admin/coupons/{$coupon->id}", [
            'description' => 'ใหม่',
            'expires_at' => $newExpiry->toIso8601String(),
            // ห้ามแก้ — มูลค่าหักจากผู้สร้างไปแล้ว / รหัสแจกไปแล้ว / ยกเลิกต้องผ่าน DELETE เพื่อคืนยอด
            'amount' => 99999,
            'coupon_type' => 'wallet',
            'coupon_code' => '00000000',
            'status' => 'redeemed',
            'user_id' => 1,
        ])->assertOk()
            ->assertJsonPath('data.description', 'ใหม่')
            ->assertJsonPath('data.status', 'active');

        $coupon->refresh();
        $this->assertSame('ใหม่', $coupon->description);
        $this->assertTrue($coupon->expires_at->equalTo($newExpiry));
        $this->assertEquals(100, (float) $coupon->amount);
        $this->assertSame('points', $coupon->coupon_type);
        $this->assertSame('active', $coupon->status);
        $this->assertSame($admin->id, $coupon->user_id);
        $this->assertNotSame('00000000', $coupon->coupon_code);

        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditLog::ACTION_UPDATED,
            'entity_type' => Coupon::class,
            'entity_id' => $coupon->id,
        ]);

        // ลบวันหมดอายุ (ไม่มีวันหมดอายุ)
        $this->actingAs($admin, 'api')->putJson("/api/admin/coupons/{$coupon->id}", ['expires_at' => null])
            ->assertOk()->assertJsonPath('data.expires_at', null);
    }

    public function test_update_rejects_past_expiry_and_non_active_coupons(): void
    {
        $admin = $this->platformAdmin();
        $coupon = $this->makeCoupon($admin);

        $this->actingAs($admin, 'api')->putJson("/api/admin/coupons/{$coupon->id}", [
            'expires_at' => now()->subHour()->toIso8601String(),
        ])->assertStatus(422)->assertJsonValidationErrors(['expires_at']);

        $redeemed = $this->makeCoupon($admin, ['status' => 'redeemed', 'redeemed_at' => now()]);
        $this->actingAs($admin, 'api')->putJson("/api/admin/coupons/{$redeemed->id}", ['description' => 'x'])
            ->assertStatus(409)->assertJsonPath('error_code', 'COUPON_NOT_EDITABLE');

        $this->actingAs($admin, 'api')->putJson('/api/admin/coupons/999999', ['description' => 'x'])->assertStatus(404);
        $this->actingAs($this->vendor(), 'api')->putJson("/api/admin/coupons/{$coupon->id}", ['description' => 'x'])->assertStatus(403);
    }

    // ------------------------------------------------------------------
    // destroy (= ยกเลิก + คืนยอดผู้สร้าง)
    // ------------------------------------------------------------------

    public function test_destroy_cancels_points_coupon_and_refunds_its_creator(): void
    {
        $creator = $this->platformAdmin(['pp' => 0]);
        $otherAdmin = $this->platformAdmin(['pp' => 0]);
        $coupon = $this->makeCoupon($creator, ['amount' => 80]);

        // แอดมินอีกคนกดยกเลิก → ยอดคืนเข้าผู้สร้าง ไม่ใช่คนกด
        $this->actingAs($otherAdmin, 'api')->deleteJson("/api/admin/coupons/{$coupon->id}")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'cancelled')
            ->assertJsonPath('data.can_cancel', false);

        $this->assertDatabaseHas('coupons', ['id' => $coupon->id, 'status' => 'cancelled']);
        $this->assertEquals(80, (float) $creator->fresh()->pp);
        $this->assertEquals(0, (float) $otherAdmin->fresh()->pp);

        $refund = PointsTransaction::where('user_id', $creator->id)->sole();
        $this->assertSame('refund', $refund->transaction_type);
        $this->assertSame('coupon_cancellation', $refund->source_type);
        $this->assertSame($coupon->id, (int) $refund->source_id);
        $this->assertSame($otherAdmin->id, $refund->metadata['cancelled_by_admin_id'] ?? null);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $otherAdmin->id,
            'action' => AuditLog::ACTION_DELETED,
            'entity_id' => $coupon->id,
        ]);

        // ยกเลิกซ้ำ → 409 ไม่คืนยอดรอบสอง
        $this->actingAs($otherAdmin, 'api')->deleteJson("/api/admin/coupons/{$coupon->id}")
            ->assertStatus(409)->assertJsonPath('error_code', 'COUPON_NOT_CANCELLABLE');
        $this->assertEquals(80, (float) $creator->fresh()->pp);
    }

    public function test_destroy_refunds_wallet_coupon_including_past_due_ones(): void
    {
        $creator = $this->platformAdmin(['wallet' => 0]);
        $coupon = $this->makeCoupon($creator, [
            'coupon_type' => 'wallet',
            'amount' => 45.25,
            'expires_at' => now()->subDay(),
        ]);

        $this->actingAs($creator, 'api')->deleteJson("/api/admin/coupons/{$coupon->id}")->assertOk();

        $this->assertEquals(45.25, (float) $creator->fresh()->wallet);
        $tx = WalletTransaction::where('user_id', $creator->id)->sole();
        $this->assertSame('deposit', $tx->transaction_type);
        $this->assertSame($coupon->coupon_code, $tx->reference_number);
    }

    public function test_destroy_refuses_redeemed_coupon_and_keeps_history(): void
    {
        $admin = $this->platformAdmin(['pp' => 0]);
        $member = $this->member();
        $coupon = $this->makeCoupon($admin, ['status' => 'redeemed', 'redeemed_by' => $member->id, 'redeemed_at' => now()]);

        $this->actingAs($admin, 'api')->deleteJson("/api/admin/coupons/{$coupon->id}")
            ->assertStatus(409)->assertJsonPath('error_code', 'COUPON_NOT_CANCELLABLE');

        $this->assertDatabaseHas('coupons', ['id' => $coupon->id, 'status' => 'redeemed']);
        $this->assertEquals(0, (float) $admin->fresh()->pp);
        $this->assertSame(0, PointsTransaction::count());
    }

    public function test_destroy_requires_coupon_delete_permission(): void
    {
        $admin = $this->platformAdmin();
        $coupon = $this->makeCoupon($admin);

        // VENDOR (ไม่มี coupon-delete + ไม่ใช่ Plearnd admin) และ Plearnd admin ที่ไม่มีสิทธิ์
        $this->actingAs($this->vendor(), 'api')->deleteJson("/api/admin/coupons/{$coupon->id}")->assertStatus(403);
        $this->actingAs($this->plearndWithoutCouponPermission(), 'api')->deleteJson("/api/admin/coupons/{$coupon->id}")->assertStatus(403);
        $this->actingAs($admin, 'api')->deleteJson('/api/admin/coupons/999999')->assertStatus(404);

        $this->assertDatabaseHas('coupons', ['id' => $coupon->id, 'status' => 'active']);
    }

    public function test_super_admin_passes_every_gate(): void
    {
        $super = User::factory()->create(['pp' => 300]);
        $this->grantRole($super, 'SUPER_ADMIN', []);

        $this->actingAs($super, 'api')->postJson('/api/admin/coupons', ['type' => 'points', 'amount' => 100])->assertCreated();
        $id = Coupon::sole()->id;

        $this->actingAs($super, 'api')->getJson('/api/admin/coupons')->assertOk()->assertJsonPath('data.meta.total', 1);
        $this->actingAs($super, 'api')->putJson("/api/admin/coupons/{$id}", ['description' => 'ok'])->assertOk();
        $this->actingAs($super, 'api')->deleteJson("/api/admin/coupons/{$id}")->assertOk();

        $this->assertEquals(300, (float) $super->fresh()->pp);
    }
}
