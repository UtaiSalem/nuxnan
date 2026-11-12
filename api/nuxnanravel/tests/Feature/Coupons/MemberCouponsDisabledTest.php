<?php

namespace Tests\Feature\Coupons;

use App\Exceptions\AccountEconomyRestrictedException;
use App\Models\Coupon;
use App\Models\PlearndAdmin;
use App\Models\User;
use App\Services\CouponService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Tests\TestCase;

/**
 * Member-issued coupons are an anonymous peer-to-peer transfer (A pays to mint
 * a code, B redeems it), so they are closed together with direct transfers
 * (economy.p2p_transfers_enabled). Coupons issued by a platform admin still work.
 */
class MemberCouponsDisabledTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // ระบุชัดเจน เพื่อไม่ให้ค่าใน .env ของนักพัฒนาเปลี่ยนผลการทดสอบ
        config(['economy.p2p_transfers_enabled' => false]);
    }

    private function makeMember(array $attributes = []): User
    {
        return User::factory()->create(array_merge(
            ['email_verified_at' => now(), 'pp' => 1000, 'wallet' => 1000],
            $attributes,
        ));
    }

    private function makeAdmin(array $attributes = []): User
    {
        $admin = $this->makeMember($attributes);
        PlearndAdmin::create(['user_id' => $admin->id]);

        return $admin;
    }

    private function makeCoupon(User $creator, array $attributes = []): Coupon
    {
        return Coupon::create(array_merge([
            'user_id' => $creator->id,
            'coupon_code' => (string) random_int(10000000, 99999999),
            'coupon_type' => 'points',
            'amount' => 120,
            'status' => 'active',
        ], $attributes));
    }

    private function actingWithJwt(User $actor)
    {
        return $this->withHeader('Authorization', 'Bearer '.JWTAuth::fromUser($actor));
    }

    public function test_member_cannot_create_points_coupon(): void
    {
        $member = $this->makeMember();

        $this->actingWithJwt($member)
            ->postJson('/api/coupons', ['type' => 'points', 'amount' => 100])
            ->assertStatus(403)
            ->assertJson(['success' => false]);

        $this->assertEquals(1000, (float) $member->fresh()->pp);
        $this->assertDatabaseCount('coupons', 0);
        $this->assertDatabaseMissing('points_transactions', ['user_id' => $member->id]);
    }

    public function test_member_cannot_create_wallet_coupon(): void
    {
        $member = $this->makeMember();

        $this->actingWithJwt($member)
            ->postJson('/api/coupons', ['type' => 'wallet', 'amount' => 100])
            ->assertStatus(403);

        $this->assertEquals(1000, (float) $member->fresh()->wallet);
        $this->assertDatabaseCount('coupons', 0);
        $this->assertDatabaseMissing('wallet_transactions', ['user_id' => $member->id]);
    }

    public function test_403_happens_before_validation(): void
    {
        $member = $this->makeMember();

        $this->actingWithJwt($member)
            ->postJson('/api/coupons', ['type' => 'not-a-type'])
            ->assertStatus(403);
    }

    public function test_service_refuses_member_even_when_called_directly(): void
    {
        $member = $this->makeMember();

        $this->expectException(AccountEconomyRestrictedException::class);
        app(CouponService::class)->createPointsCoupon($member, 100);
    }

    public function test_platform_admin_can_still_create_coupon(): void
    {
        $admin = $this->makeAdmin();

        // ตัดการสร้างไฟล์ QR ออก (ไม่เกี่ยวกับสิทธิ์ และเขียนลง storage จริง)
        $this->partialMock(CouponService::class, function ($mock) {
            $mock->shouldReceive('generateQRCode')->andReturn('qr-codes/test.svg');
        });

        $this->actingWithJwt($admin)
            ->postJson('/api/coupons', ['type' => 'points', 'amount' => 100])
            ->assertStatus(201)
            ->assertJson(['success' => true]);

        $this->assertEquals(900, (float) $admin->fresh()->pp);
        $this->assertDatabaseHas('coupons', ['user_id' => $admin->id, 'status' => 'active']);
    }

    public function test_member_issued_coupon_cannot_be_redeemed(): void
    {
        $creator = $this->makeMember();
        $redeemer = $this->makeMember(['pp' => 0]);
        $coupon = $this->makeCoupon($creator);

        $this->actingWithJwt($redeemer)
            ->postJson('/api/coupons/redeem', ['coupon_code' => $coupon->coupon_code])
            ->assertStatus(400)
            ->assertJson(['success' => false, 'error_code' => 'COUPON_MEMBER_ISSUED_DISABLED']);

        $this->assertEquals(0, (float) $redeemer->fresh()->pp);
        $this->assertSame('active', $coupon->fresh()->status);
        $this->assertDatabaseCount('coupon_redemptions', 0);
    }

    public function test_admin_issued_coupon_can_still_be_redeemed(): void
    {
        $admin = $this->makeAdmin();
        $redeemer = $this->makeMember(['pp' => 0]);
        $coupon = $this->makeCoupon($admin);

        $this->actingWithJwt($redeemer)
            ->postJson('/api/coupons/redeem', ['coupon_code' => $coupon->coupon_code])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertEquals(120, (float) $redeemer->fresh()->pp);
        $this->assertSame('redeemed', $coupon->fresh()->status);
    }

    public function test_member_can_still_cancel_own_coupon_for_refund(): void
    {
        $creator = $this->makeMember(['pp' => 0]);
        $coupon = $this->makeCoupon($creator);

        $this->actingWithJwt($creator)
            ->postJson("/api/coupons/{$coupon->id}/cancel")
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertEquals(120, (float) $creator->fresh()->pp);
        $this->assertSame('cancelled', $coupon->fresh()->status);
    }

    public function test_flag_on_restores_member_coupons(): void
    {
        config(['economy.p2p_transfers_enabled' => true]);
        $creator = $this->makeMember();
        $redeemer = $this->makeMember(['pp' => 0]);

        $result = app(CouponService::class)->createPointsCoupon($creator, 100);
        $this->assertTrue($result['success']);

        $redeem = app(CouponService::class)->redeemCoupon($redeemer, $result['coupons'][0]->coupon_code);
        $this->assertTrue($redeem['success']);
        $this->assertEquals(100, (float) $redeemer->fresh()->pp);
    }
}
