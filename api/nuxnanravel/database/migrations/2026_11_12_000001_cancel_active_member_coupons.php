<?php

use App\Models\Coupon;
use App\Models\PointsTransaction;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Services\CouponService;
use App\Services\PointsService;
use App\Services\WalletService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * ปิดคูปองที่สมาชิกสร้าง — Phase 2 ของ .agents/plans/disable-p2p-value-transfers.md (D2)
 *
 * คูปองที่สมาชิกสร้างและยังไม่มีใครแลก (status = active) ถูกยกเลิก และคืนแต้ม/เงินให้ผู้สร้าง
 * ผ่าน CouponService::cancelCoupon — ผลเหมือนผู้สร้างกดปุ่ม "ยกเลิก" เองทุกประการ
 * (รวมคูปองที่เลยวันหมดอายุแต่สถานะยังเป็น active เพราะปุ่มยกเลิกก็คืนให้)
 * คูปองที่ Plearnd admin ออก ไม่ถูกแตะ
 *
 * ข้าม + log: รายการที่ cancelCoupon ปฏิเสธ เช่น คูปองเงินของผู้ที่ถูกระงับ wallet —
 * คูปองนั้นยังเป็น active แต่แลกไม่ได้แล้ว (redeem รับเฉพาะคูปองแอดมิน) ผู้สร้างยกเลิกเองได้หลังปลดระงับ
 *
 * แถว ledger ที่คืนทุกแถวมี metadata.cancelled_by = TAG → down() ใช้หาแล้วย้อนกลับ
 */
return new class extends Migration
{
    private const TAG = '2026_11_12_000001_cancel_active_member_coupons';

    public function up(): void
    {
        $couponService = app(CouponService::class);
        $counts = ['cancelled' => 0, 'kept_admin' => 0, 'skipped' => 0];

        Coupon::query()
            ->where('status', 'active')
            ->select('id')
            ->chunkById(100, function ($rows) use ($couponService, &$counts) {
                foreach ($rows as $row) {
                    try {
                        $outcome = DB::transaction(function () use ($row, $couponService) {
                            $coupon = Coupon::whereKey($row->id)->lockForUpdate()->first();
                            if (! $coupon || $coupon->status !== 'active') {
                                return 'skipped';
                            }

                            // อ่านยอดสดพร้อม lock — PointsService::refund คำนวณจาก $user->pp ที่ส่งเข้าไป
                            $creator = User::whereKey($coupon->user_id)->lockForUpdate()->first();
                            if (! $creator) {
                                return 'skipped';
                            }
                            if ($creator->isPlearndAdmin()) {
                                return 'kept_admin';
                            }

                            $result = $couponService->cancelCoupon($coupon, $creator, ['cancelled_by' => self::TAG]);

                            return $result['success'] ? 'cancelled' : 'skipped';
                        });
                    } catch (Throwable $e) {
                        $outcome = 'skipped';
                        Log::warning('Member coupon not cancelled by migration', [
                            'coupon_id' => $row->id,
                            'exception' => $e->getMessage(),
                        ]);
                    }

                    $counts[$outcome]++;
                }
            });

        Log::info('Member coupons cancelled and refunded', $counts + ['migration' => self::TAG]);
    }

    public function down(): void
    {
        $pointsService = app(PointsService::class);
        $walletService = app(WalletService::class);

        // pluck() คืน base collection — อย่า merge Eloquent collection ตรง ๆ เพราะมัน dedupe ตาม primary key
        $couponIds = collect()
            ->merge(PointsTransaction::query()
                ->where('source_type', 'coupon_cancellation')
                ->where('metadata->cancelled_by', self::TAG)
                ->get(['metadata'])
                ->pluck('metadata.coupon_id'))
            ->merge(WalletTransaction::query()
                ->where('metadata->cancelled_by', self::TAG)
                ->get(['metadata'])
                ->pluck('metadata.coupon_id'))
            ->filter()
            ->unique()
            ->values();

        $counts = ['restored' => 0, 'skipped' => 0];

        foreach ($couponIds as $couponId) {
            try {
                $restored = DB::transaction(function () use ($couponId, $pointsService, $walletService) {
                    $coupon = Coupon::whereKey($couponId)->lockForUpdate()->first();
                    if (! $coupon || $coupon->status !== 'cancelled') {
                        return false;
                    }

                    $creator = User::whereKey($coupon->user_id)->lockForUpdate()->first();
                    if (! $creator) {
                        return false;
                    }

                    // หักคืนเข้าคูปองแบบเดียวกับตอนสร้าง — ถ้ายอดไม่พอ (ใช้ไปแล้ว) ปล่อยให้คูปองยกเลิกไว้ ไม่ทำให้ติดลบ
                    $description = "นำคูปอง {$coupon->coupon_code} กลับมาใช้งาน (ย้อน migration ยกเลิกคูปองสมาชิก)";
                    $metadata = [
                        'coupon_id' => $coupon->id,
                        'coupon_code' => $coupon->coupon_code,
                        'restored_by' => self::TAG,
                    ];

                    $transaction = $coupon->coupon_type === 'points'
                        ? $pointsService->spend($creator, (float) $coupon->amount, 'coupon_creation', $coupon->id, $description, $metadata)
                        : $walletService->deductForPurchase($creator, (string) $coupon->amount, 'coupon_creation', $description, $metadata);

                    if (! $transaction) {
                        Log::warning('Member coupon not restored: creator balance too low', [
                            'coupon_id' => $coupon->id,
                            'user_id' => $creator->id,
                        ]);

                        return false;
                    }

                    $coupon->update(['status' => 'active']);

                    return true;
                });
            } catch (Throwable $e) {
                $restored = false;
                Log::warning('Member coupon not restored by migration rollback', [
                    'coupon_id' => $couponId,
                    'exception' => $e->getMessage(),
                ]);
            }

            $counts[$restored ? 'restored' : 'skipped']++;
        }

        Log::info('Member coupon cancellation rolled back', $counts + ['migration' => self::TAG]);
    }
};
