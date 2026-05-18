<?php

namespace App\Services;

use App\Models\User;
use App\Models\Course;
use App\Models\WalletTransaction;
use App\Services\CourseCloneService;
use App\Services\Support\CourseCloneContext;
use App\Services\PointsService;
use App\Services\WalletService;
use App\Jobs\CloneCourseJob;
use Illuminate\Support\Facades\DB;
use Exception;

class CoursePurchaseService
{
    const POINTS_PER_THB = 1200;

    public function __construct(
        protected CourseCloneService $cloneService,
        protected PointsService $pointsService,
        protected WalletService $walletService,
    ) {}

    /**
     * Purchase a course from marketplace.
     */
    public function purchase(User $buyer, Course $course, ?string $paymentMode = null): array
    {
        $paymentMode = $paymentMode ?? 'wallet';

        return DB::transaction(function () use ($buyer, $course, $paymentMode) {
            if (!$course->is_for_marketplace) {
                throw new Exception('Course is not available for purchase.');
            }
            if ($buyer->id === $course->user_id) {
                throw new Exception('You cannot purchase your own course.');
            }

            $alreadyPurchased = \App\Models\CoursePurchase::where('buyer_id', $buyer->id)
                ->where('source_course_id', $course->id)
                ->whereIn('status', ['completed', 'pending_clone', 'paid'])
                ->exists();
            
            if (!$alreadyPurchased) {
                // Fallback check for legacy data
                $alreadyPurchased = Course::where('user_id', $buyer->id)
                    ->where('source_course_id', $course->id)
                    ->exists();
            }

            if ($alreadyPurchased) {
                throw new Exception('You already own this course.');
            }

            // Create purchase record
            $purchase = \App\Models\CoursePurchase::create([
                'source_course_id' => $course->id,
                'buyer_id' => $buyer->id,
                'seller_id' => $course->user_id,
                'payment_mode' => $paymentMode,
                'status' => 'pending_payment',
            ]);

            // ราคา 0 = ฟรี (skip payment)
            $usedMode = ((float) $course->price) <= 0 ? 'free' : $paymentMode;
            $paymentResult = $this->processPayment($buyer, $course, $usedMode, $purchase);

            $purchase->update([
                'status' => 'paid',
                'paid_at' => now(),
            ]);

            // Clone
            $lessonCount = $course->courseLessons()->count();
            if ($lessonCount > 20) {
                $purchase->update(['status' => 'pending_clone']);
                
                CloneCourseJob::dispatch($purchase->id)->afterCommit();

                $newCourse = null;
            } else {
                $context = new CourseCloneContext(mode: 'purchase');
                $newCourse = $this->cloneService->clone($course, $buyer, $context);
                
                $purchase->update([
                    'cloned_course_id' => $newCourse->id,
                    'status' => 'completed',
                    'cloned_at' => now(),
                ]);
            }

            $course->increment('total_sales');

            return [
                'new_course' => $newCourse,
                'is_queued' => ($newCourse === null),
                'payment' => $paymentResult,
                'purchase_id' => $purchase->id,
            ];
        });
    }

    protected function processPayment(User $buyer, Course $course, string $usedMode, \App\Models\CoursePurchase $purchase): array
    {
        $seller = $course->user;
        if (!$seller) {
            throw new Exception('Course owner not found.');
        }

        $priceTHB = (float) $course->price;

        // ฟรี
        if ($priceTHB <= 0) {
            $purchase->update(['payment_mode' => 'free']);
            return ['amount' => 0, 'transaction_id' => null, 'mode' => 'free'];
        }

        $pricePoints = (int) ceil($priceTHB * self::POINTS_PER_THB);

        // คำนวณการหักตามวิธีจ่าย
        $payFromWallet = 0;
        $payFromPoints = 0;

        if ($usedMode === 'wallet') {
            if ($buyer->wallet < $priceTHB) {
                throw new Exception('ยอดเงินไม่เพียงพอ');
            }
            $payFromWallet = $priceTHB;
        } elseif ($usedMode === 'points') {
            if ($buyer->pp < $pricePoints) {
                throw new Exception('แต้มไม่เพียงพอ');
            }
            $payFromPoints = $pricePoints;
        } elseif ($usedMode === 'mixed') {
            // ใช้ wallet ให้หมดก่อน แล้วค่อยเอาแต้มมาเติม
            $payFromWallet = min($buyer->wallet, $priceTHB);
            $thbShortfall = $priceTHB - $payFromWallet;
            $payFromPoints = (int) ceil($thbShortfall * self::POINTS_PER_THB);

            if ($buyer->pp < $payFromPoints) {
                throw new Exception('ยอดเงินและแต้มรวมกันไม่เพียงพอ');
            }
        } else {
            throw new Exception('Invalid payment method.');
        }

        // หักจาก buyer
        $transactionId = null;
        if ($payFromWallet > 0) {
            $tx = WalletTransaction::create([
                'user_id' => $buyer->id,
                'transaction_type' => 'purchase',
                'amount' => $payFromWallet,
                'balance_before' => $buyer->wallet,
                'balance_after' => $buyer->wallet - $payFromWallet,
                'currency' => 'THB',
                'description' => "ซื้อลิขสิทธิ์: {$course->name}",
                'metadata' => [
                    'course_id' => $course->id,
                    'purchase_id' => $purchase->id,
                    'payment_mode' => $usedMode,
                    'thb_portion' => $payFromWallet,
                    'points_portion' => $payFromPoints,
                ],
                'status' => 'completed',
            ]);
            $buyer->decrement('wallet', $payFromWallet);
            $transactionId = $tx->id;
            
            $purchase->wallet_transaction_id = $tx->id;
            $purchase->amount_wallet = $payFromWallet;
        }

        if ($payFromPoints > 0) {
            $ptsTx = $this->pointsService->spend(
                $buyer,
                $payFromPoints,
                'App\Models\Course',
                $course->id,
                "ซื้อลิขสิทธิ์ (แต้ม): {$course->name}",
                [
                    'course_id' => $course->id,
                    'purchase_id' => $purchase->id,
                    'payment_mode' => $usedMode,
                    'thb_equivalent' => $payFromPoints / self::POINTS_PER_THB,
                ]
            );
            if (!$ptsTx) {
                throw new Exception('Failed to deduct points.');
            }
            $transactionId = $transactionId ?? $ptsTx->id;
            
            $purchase->points_transaction_id = $ptsTx->id;
            $purchase->amount_points = $payFromPoints;
        }

        // เครดิตให้ seller เป็น THB เต็มจำนวนเสมอ
        $incomeTx = WalletTransaction::create([
            'user_id' => $seller->id,
            'transaction_type' => 'course_income',
            'amount' => $priceTHB,
            'balance_before' => $seller->wallet,
            'balance_after' => $seller->wallet + $priceTHB,
            'currency' => 'THB',
            'description' => "ขายลิขสิทธิ์: {$course->name} ให้ {$buyer->name}",
            'metadata' => [
                'course_id' => $course->id,
                'purchase_id' => $purchase->id,
                'buyer_id' => $buyer->id,
                'buyer_payment_mode' => $usedMode,
            ],
            'status' => 'completed',
        ]);
        $seller->increment('wallet', $priceTHB);
        
        $purchase->seller_income_transaction_id = $incomeTx->id;
        $purchase->payment_mode = $usedMode;
        $purchase->save();

        return [
            'amount' => $priceTHB,
            'transaction_id' => $transactionId,
            'mode' => $usedMode,
            'breakdown' => [
                'thb' => $payFromWallet,
                'points' => $payFromPoints,
            ],
        ];
    }
}
