<?php

namespace App\Services;

use App\Models\Course;
use App\Models\User;
use App\Models\WalletTransaction;
use Exception;
use Illuminate\Support\Facades\DB;

/**
 * Payment for course ENROLMENT — joining a course and paying its fee.
 *
 * CoursePurchaseService covers the different case of buying a marketplace
 * licence. Both deliberately share one exchange rate and one set of payment
 * modes so a learner is offered the same choices wherever they pay.
 */
class CourseEnrollmentPaymentService
{
    public function __construct(
        protected PointsService $pointsService,
    ) {}

    /**
     * The enrolment price in THB, after the course discount.
     *
     * The join and pay-after-approval paths each used to compute this
     * themselves and had already drifted apart, so it lives here only.
     */
    public function priceFor(Course $course): float
    {
        $price = (float) ($course->tuition_fees ?? $course->price ?? 0);

        if ($price > 0 && $course->discount > 0) {
            $price = $price - ($price * (float) $course->discount / 100);
        }

        return max(0, round($price, 2));
    }

    /**
     * What the learner can pay with, for the UI to render before enrolling.
     * Shape matches CourseMarketplaceController's checkout payload on purpose.
     */
    public function quote(User $user, Course $course): array
    {
        $priceTHB = $this->priceFor($course);
        $pricePoints = (int) ceil($priceTHB * CoursePurchaseService::POINTS_PER_THB);

        $walletBalance = (float) ($user->wallet ?? 0);
        $pointsBalance = (int) ($user->pp ?? 0);

        $canPayWallet = $walletBalance >= $priceTHB;
        $canPayPoints = $pointsBalance >= $pricePoints;

        $thbShortfall = max(0, $priceTHB - $walletBalance);
        $mixedPointsNeeded = (int) ceil($thbShortfall * CoursePurchaseService::POINTS_PER_THB);
        $canPayMixed = $walletBalance > 0
            && $pointsBalance >= $mixedPointsNeeded
            && ! $canPayWallet;

        return [
            'is_free' => $priceTHB <= 0,
            'price_thb' => $priceTHB,
            'price_points' => $pricePoints,
            'exchange_rate' => CoursePurchaseService::POINTS_PER_THB,
            'balance' => [
                'wallet' => $walletBalance,
                'points' => $pointsBalance,
            ],
            'can_pay' => [
                'wallet' => $canPayWallet,
                'points' => $canPayPoints,
                'mixed' => $canPayMixed,
            ],
            'mixed_breakdown' => [
                'wallet_portion' => min($walletBalance, $priceTHB),
                'points_portion' => $mixedPointsNeeded,
            ],
        ];
    }

    /**
     * Charge the learner and credit the course owner.
     *
     * @throws Exception on an unknown mode or an insufficient balance
     */
    public function charge(User $user, Course $course, string $mode, ?string $idempotencyKey = null): array
    {
        $priceTHB = $this->priceFor($course);

        if ($priceTHB <= 0) {
            return [
                'mode' => 'free',
                'amount_thb' => 0.0,
                'wallet_portion' => 0.0,
                'points_portion' => 0,
                'wallet_transaction_id' => null,
                'points_transaction_id' => null,
            ];
        }

        if (! in_array($mode, ['wallet', 'points', 'mixed'], true)) {
            throw new Exception('วิธีชำระเงินไม่ถูกต้อง');
        }

        return DB::transaction(function () use ($user, $course, $mode, $priceTHB, $idempotencyKey) {
            // Lock the buyer so two concurrent enrolments cannot both pass the
            // balance check against the same funds.
            $buyer = User::where('id', $user->id)->lockForUpdate()->firstOrFail();

            $pricePoints = (int) ceil($priceTHB * CoursePurchaseService::POINTS_PER_THB);

            $payFromWallet = 0.0;
            $payFromPoints = 0;

            if ($mode === 'wallet') {
                if ((float) $buyer->wallet < $priceTHB) {
                    throw new Exception('ยอดเงินในกระเป๋าไม่เพียงพอ');
                }
                $payFromWallet = $priceTHB;
            } elseif ($mode === 'points') {
                if ((int) $buyer->pp < $pricePoints) {
                    throw new Exception('แต้มไม่เพียงพอ');
                }
                $payFromPoints = $pricePoints;
            } else {
                // Spend the wallet down first, cover the remainder with points.
                $payFromWallet = min((float) $buyer->wallet, $priceTHB);
                $payFromPoints = (int) ceil(($priceTHB - $payFromWallet) * CoursePurchaseService::POINTS_PER_THB);

                if ((int) $buyer->pp < $payFromPoints) {
                    throw new Exception('ยอดเงินและแต้มรวมกันไม่เพียงพอ');
                }
            }

            $walletTxId = null;
            $pointsTxId = null;

            if ($payFromWallet > 0) {
                $balanceBefore = (float) $buyer->wallet;

                $tx = WalletTransaction::create([
                    'user_id' => $buyer->id,
                    'transaction_type' => 'purchase',
                    'amount' => $payFromWallet,
                    'balance_before' => $balanceBefore,
                    'balance_after' => $balanceBefore - $payFromWallet,
                    'currency' => 'THB',
                    'description' => "ค่าธรรมเนียมเข้าเรียน: {$course->name}",
                    'metadata' => [
                        'course_id' => $course->id,
                        'payment_mode' => $mode,
                        'thb_portion' => $payFromWallet,
                        'points_portion' => $payFromPoints,
                    ],
                    'status' => 'completed',
                ]);

                $buyer->decrement('wallet', $payFromWallet);
                $walletTxId = $tx->id;
            }

            if ($payFromPoints > 0) {
                $ptsTx = $this->pointsService->spend(
                    $buyer,
                    $payFromPoints,
                    'App\Models\Course',
                    $course->id,
                    "ค่าธรรมเนียมเข้าเรียน (แต้ม): {$course->name}",
                    [
                        'course_id' => $course->id,
                        'payment_mode' => $mode,
                        'thb_equivalent' => $payFromPoints / CoursePurchaseService::POINTS_PER_THB,
                    ],
                    $idempotencyKey,
                );

                if (! $ptsTx) {
                    throw new Exception('หักแต้มไม่สำเร็จ');
                }

                $pointsTxId = $ptsTx->id;
            }

            // Credit the owner the full THB amount however the learner paid.
            // Skipped when the buyer is the owner, matching WalletService.
            $owner = $course->user;
            if ($owner && $owner->id !== $buyer->id) {
                $ownerBalanceBefore = (float) $owner->wallet;

                WalletTransaction::create([
                    'user_id' => $owner->id,
                    'transaction_type' => 'course_income',
                    'amount' => $priceTHB,
                    'balance_before' => $ownerBalanceBefore,
                    'balance_after' => $ownerBalanceBefore + $priceTHB,
                    'currency' => 'THB',
                    'description' => "ค่าธรรมเนียมเข้าเรียน: {$course->name} จาก {$buyer->name}",
                    'metadata' => [
                        'course_id' => $course->id,
                        'buyer_id' => $buyer->id,
                        'buyer_payment_mode' => $mode,
                    ],
                    'status' => 'completed',
                ]);

                $owner->increment('wallet', $priceTHB);
            }

            return [
                'mode' => $mode,
                'amount_thb' => $priceTHB,
                'wallet_portion' => $payFromWallet,
                'points_portion' => $payFromPoints,
                'wallet_transaction_id' => $walletTxId,
                'points_transaction_id' => $pointsTxId,
            ];
        });
    }
}
