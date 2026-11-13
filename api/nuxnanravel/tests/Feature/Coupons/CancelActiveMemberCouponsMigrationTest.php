<?php

namespace Tests\Feature\Coupons;

use App\Models\Coupon;
use App\Models\PlearndAdmin;
use App\Models\PointsTransaction;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Data migration that cancels still-active member-issued coupons and refunds
 * their creators (Phase 2 of .agents/plans/disable-p2p-value-transfers.md).
 */
class CancelActiveMemberCouponsMigrationTest extends TestCase
{
    use RefreshDatabase;

    private const TAG = '2026_11_12_000001_cancel_active_member_coupons';

    private function migration(): Migration
    {
        return require database_path('migrations/'.self::TAG.'.php');
    }

    private function makeMember(array $attributes = []): User
    {
        return User::factory()->create(array_merge(
            ['email_verified_at' => now(), 'pp' => 0, 'wallet' => 0],
            $attributes,
        ));
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

    public function test_up_cancels_member_coupons_and_refunds_creator(): void
    {
        $creator = $this->makeMember();
        $points = $this->makeCoupon($creator, ['amount' => 120]);
        // คูปองหลายใบของคนเดียวกัน — กันคำนวณจากยอดเก่าแล้วทับกัน
        $morePoints = $this->makeCoupon($creator, ['amount' => 80, 'expires_at' => now()->subDay()]);
        $wallet = $this->makeCoupon($creator, ['coupon_type' => 'wallet', 'amount' => 25.5]);

        $this->migration()->up();

        $this->assertSame('cancelled', $points->fresh()->status);
        $this->assertSame('cancelled', $morePoints->fresh()->status);
        $this->assertSame('cancelled', $wallet->fresh()->status);
        $this->assertEquals(200, (float) $creator->fresh()->pp);
        $this->assertEquals(25.5, (float) $creator->fresh()->wallet);

        $this->assertSame(2, PointsTransaction::where('user_id', $creator->id)
            ->where('source_type', 'coupon_cancellation')
            ->where('metadata->cancelled_by', self::TAG)
            ->count());
        $this->assertSame(1, WalletTransaction::where('user_id', $creator->id)
            ->where('metadata->cancelled_by', self::TAG)
            ->count());
    }

    public function test_up_leaves_admin_and_finished_coupons_alone(): void
    {
        $admin = $this->makeMember(['pp' => 0]);
        PlearndAdmin::create(['user_id' => $admin->id]);
        $adminCoupon = $this->makeCoupon($admin);

        $member = $this->makeMember();
        $redeemed = $this->makeCoupon($member, ['status' => 'redeemed']);
        $cancelled = $this->makeCoupon($member, ['status' => 'cancelled']);

        $this->migration()->up();

        $this->assertSame('active', $adminCoupon->fresh()->status);
        $this->assertSame('redeemed', $redeemed->fresh()->status);
        $this->assertSame('cancelled', $cancelled->fresh()->status);
        $this->assertEquals(0, (float) $admin->fresh()->pp);
        $this->assertEquals(0, (float) $member->fresh()->pp);
        $this->assertSame(0, PointsTransaction::count());
    }

    public function test_up_skips_wallet_coupon_of_frozen_wallet_and_continues(): void
    {
        $frozen = $this->makeMember(['wallet_suspended' => true]);
        $frozenCoupon = $this->makeCoupon($frozen, ['coupon_type' => 'wallet', 'amount' => 50]);

        $other = $this->makeMember();
        $otherCoupon = $this->makeCoupon($other);

        $this->migration()->up();

        $this->assertSame('active', $frozenCoupon->fresh()->status);
        $this->assertEquals(0, (float) $frozen->fresh()->wallet);
        $this->assertSame('cancelled', $otherCoupon->fresh()->status);
        $this->assertEquals(120, (float) $other->fresh()->pp);
    }

    public function test_running_up_twice_refunds_once(): void
    {
        $creator = $this->makeMember();
        $this->makeCoupon($creator);

        $this->migration()->up();
        $this->migration()->up();

        $this->assertEquals(120, (float) $creator->fresh()->pp);
    }

    public function test_down_restores_coupons_and_takes_refund_back(): void
    {
        $creator = $this->makeMember();
        $points = $this->makeCoupon($creator, ['amount' => 120]);
        $wallet = $this->makeCoupon($creator, ['coupon_type' => 'wallet', 'amount' => 30]);
        $cancelledByOwner = $this->makeCoupon($creator, ['status' => 'cancelled']);

        $this->migration()->up();
        $this->migration()->down();

        $this->assertSame('active', $points->fresh()->status);
        $this->assertSame('active', $wallet->fresh()->status);
        // ไม่ได้ถูกยกเลิกโดย migration นี้ → down() ไม่แตะ
        $this->assertSame('cancelled', $cancelledByOwner->fresh()->status);
        $this->assertEquals(0, (float) $creator->fresh()->pp);
        $this->assertEquals(0, (float) $creator->fresh()->wallet);
    }

    public function test_down_keeps_coupon_cancelled_when_refund_already_spent(): void
    {
        $creator = $this->makeMember();
        $coupon = $this->makeCoupon($creator, ['amount' => 120]);

        $this->migration()->up();
        $creator->fresh()->update(['pp' => 20]); // ใช้แต้มที่ได้คืนไปแล้ว

        $this->migration()->down();

        $this->assertSame('cancelled', $coupon->fresh()->status);
        $this->assertEquals(20, (float) $creator->fresh()->pp);
    }
}
