<?php

namespace Tests\Feature\Coupons;

use App\Models\Coupon;
use App\Models\CouponRedemption;
use App\Models\PointsTransaction;
use App\Models\WalletTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Coupons\Concerns\BuildsCouponActors;
use Tests\TestCase;

/**
 * คูปองที่ออกจากหน้าแอดมิน → สมาชิกแลกผ่าน POST /api/coupons/redeem → ledger ครบทั้งสองฝั่ง
 *
 * ผู้สร้างในเทสต์เป็น Plearnd admin เสมอ เพื่อให้ยังผ่านเมื่อ Phase 2
 * (แลกได้เฉพาะคูปองที่ผู้สร้าง isPlearndAdmin()) ถูกเปิดใช้
 */
class AdminCouponRedemptionTest extends TestCase
{
    use BuildsCouponActors;
    use RefreshDatabase;

    protected function tearDown(): void
    {
        $this->deleteGeneratedQrCodes();
        parent::tearDown();
    }

    private function issue(string $type, float $amount): Coupon
    {
        $admin = $this->platformAdmin(['pp' => 1000, 'wallet' => 1000]);

        $response = $this->actingAs($admin, 'api')->postJson('/api/admin/coupons', [
            'type' => $type,
            'amount' => $amount,
        ])->assertCreated();

        $coupon = Coupon::findOrFail($response->json('data.coupons.0.id'));
        $this->assertTrue($coupon->user->isPlearndAdmin());

        return $coupon;
    }

    public function test_member_redeems_admin_points_coupon_and_ledger_is_written(): void
    {
        $coupon = $this->issue('points', 200);
        $creator = $coupon->user;
        $member = $this->member(['pp' => 5]);

        $this->actingAs($member, 'api')->postJson('/api/coupons/redeem', ['coupon_code' => $coupon->coupon_code])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.type', 'points');

        $this->assertEquals(205, (float) $member->fresh()->pp);
        $this->assertEquals(800, (float) $creator->fresh()->pp);

        $coupon->refresh();
        $this->assertSame('redeemed', $coupon->status);
        $this->assertSame($member->id, (int) $coupon->redeemed_by);
        $this->assertNotNull($coupon->redeemed_at);

        // ฝั่งผู้แลก: earn จาก coupon_redemption · ฝั่งผู้สร้าง: spend จาก coupon_creation
        $earn = PointsTransaction::where('user_id', $member->id)->sole();
        $this->assertSame('earn', $earn->transaction_type);
        $this->assertSame('coupon_redemption', $earn->source_type);
        $this->assertSame($coupon->id, (int) $earn->source_id);
        $this->assertEquals(200, (float) $earn->amount);
        $this->assertEquals(205, (float) $earn->balance_after);

        $this->assertDatabaseHas('points_transactions', [
            'user_id' => $creator->id,
            'transaction_type' => 'spend',
            'source_type' => 'coupon_creation',
        ]);

        $this->assertSame(1, CouponRedemption::where('coupon_id', $coupon->id)->where('user_id', $member->id)->count());

        // แลกซ้ำไม่ได้
        $this->actingAs($this->member(), 'api')->postJson('/api/coupons/redeem', ['coupon_code' => $coupon->coupon_code])
            ->assertStatus(400)->assertJsonPath('error_code', 'COUPON_ALREADY_REDEEMED');
        $this->assertSame(1, PointsTransaction::where('source_type', 'coupon_redemption')->count());
    }

    public function test_member_redeems_admin_wallet_coupon_and_ledger_is_written(): void
    {
        $coupon = $this->issue('wallet', 75.5);
        $member = $this->member(['wallet' => 0]);

        $this->actingAs($member, 'api')->postJson('/api/coupons/redeem', ['coupon_code' => $coupon->coupon_code])
            ->assertOk()->assertJsonPath('data.type', 'wallet');

        $this->assertEquals(75.5, (float) $member->fresh()->wallet);

        $deposit = WalletTransaction::where('user_id', $member->id)->sole();
        $this->assertSame('deposit', $deposit->transaction_type);
        $this->assertSame($coupon->coupon_code, $deposit->reference_number);
        $this->assertEquals(75.5, (float) $deposit->amount);
        $this->assertSame('completed', $deposit->status);
    }

    public function test_admin_cannot_redeem_own_coupon(): void
    {
        $coupon = $this->issue('points', 100);

        $this->actingAs($coupon->user, 'api')->postJson('/api/coupons/redeem', ['coupon_code' => $coupon->coupon_code])
            ->assertStatus(400)->assertJsonPath('error_code', 'COUPON_OWN');

        $this->assertSame('active', $coupon->fresh()->status);
    }

    public function test_coupon_cancelled_from_admin_panel_can_no_longer_be_redeemed(): void
    {
        $coupon = $this->issue('points', 100);
        $creator = $coupon->user;
        $member = $this->member();

        $this->actingAs($creator, 'api')->deleteJson("/api/admin/coupons/{$coupon->id}")->assertOk();
        $this->assertEquals(1000, (float) $creator->fresh()->pp);

        $this->actingAs($member, 'api')->postJson('/api/coupons/redeem', ['coupon_code' => $coupon->coupon_code])
            ->assertStatus(400)->assertJsonPath('error_code', 'COUPON_CANCELLED');

        $this->assertEquals(0, (float) $member->fresh()->pp);
        $this->assertSame(0, PointsTransaction::where('source_type', 'coupon_redemption')->count());
    }

    public function test_coupon_whose_expiry_was_moved_into_the_past_cannot_be_redeemed(): void
    {
        $coupon = $this->issue('points', 100);
        $coupon->update(['expires_at' => now()->subMinute()]);

        $this->actingAs($this->member(), 'api')->postJson('/api/coupons/redeem', ['coupon_code' => $coupon->coupon_code])
            ->assertStatus(400)->assertJsonPath('error_code', 'COUPON_EXPIRED');
    }
}
