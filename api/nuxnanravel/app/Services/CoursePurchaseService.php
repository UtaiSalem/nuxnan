<?php

namespace App\Services;

use App\Models\User;
use App\Models\Course;
use App\Models\WalletTransaction;
use App\Services\CourseCloneService;
use App\Services\PointsService;
use App\Services\WalletService;
use App\Jobs\CloneCourseJob;
use Illuminate\Support\Facades\DB;
use Exception;

class CoursePurchaseService
{
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
        $paymentMode = $paymentMode ?? 'auto';
        
        return DB::transaction(function () use ($buyer, $course, $paymentMode) {
            // 1. ตรวจว่า course อยู่ใน marketplace
            if (!$course->is_for_marketplace) {
                throw new Exception('Course is not available for purchase.');
            }

            // 2. ตรวจว่า buyer ไม่ใช่ seller
            if ($buyer->id === $course->user_id) {
                throw new Exception('You cannot purchase your own course.');
            }

            // 3. ตรวจว่ายังไม่เคยซื้อ (check source_course_id)
            $alreadyPurchased = Course::where('user_id', $buyer->id)
                ->where('source_course_id', $course->id)
                ->exists();
            
            if ($alreadyPurchased) {
                throw new Exception('You already own this course.');
            }

            // 4. คำนวณราคาตาม paymentMode + price_type
            $usedMode = $this->determinePaymentMode($buyer, $course, $paymentMode);
            
            // 5-7. ตรวจ balance, หักยอด buyer, โอนให้ seller
            $paymentResult = $this->processPayment($buyer, $course, $usedMode);

            // 8. Trigger Cloning
            $lessonCount = $course->courseLessons()->count();
            
            if ($lessonCount > 20) {
                // Dispatch Job if large
                CloneCourseJob::dispatch($course, $buyer, $paymentResult['transaction_id'], $usedMode);
                $newCourse = null;
            } else {
                // Sync clone if small
                $newCourse = $this->cloneService->clone($course, $buyer);
            }

            // 9. อัปเดต course->total_sales + 1
            $course->increment('total_sales');

            // 10. return ['new_course' => ..., 'payment' => ...]
            return [
                'new_course' => $newCourse,
                'is_queued' => ($newCourse === null),
                'payment' => [
                    'mode' => $usedMode,
                    'amount' => $paymentResult['amount'],
                    'transaction_id' => $paymentResult['transaction_id']
                ]
            ];
        });
    }

    protected function determinePaymentMode(User $buyer, Course $course, string $paymentMode): string
    {
        $priceType = $course->price_type;

        if ($priceType === 'free') {
            return 'free';
        }

        if ($priceType === 'points') {
            return 'points';
        }

        if ($priceType === 'wallet') {
            return 'wallet';
        }

        if ($priceType === 'both') {
            if ($paymentMode === 'auto') {
                if ($buyer->pp >= $course->price_points) {
                    return 'points';
                } else {
                    return 'wallet';
                }
            }
            return $paymentMode;
        }

        throw new Exception('This course cannot be purchased with the selected payment method.');
    }

    protected function processPayment(User $buyer, Course $course, string $usedMode): array
    {
        $seller = $course->user;
        if (!$seller) {
            throw new Exception('Course owner not found.');
        }

        if ($usedMode === 'free') {
            return ['amount' => 0, 'transaction_id' => null];
        }

        if ($usedMode === 'points') {
            $amount = $course->price_points;
            if ($buyer->pp < $amount) {
                throw new Exception('Insufficient points balance.');
            }

            // Deduct from buyer
            $transaction = $this->pointsService->spend(
                $buyer, 
                $amount, 
                'App\Models\Course', 
                $course->id, 
                "Purchased course copy: {$course->name}"
            );

            if (!$transaction) {
                throw new Exception('Failed to process points payment.');
            }

            // Earn for seller
            $this->pointsService->earn(
                $seller, 
                $amount, 
                'App\Models\Course', 
                $course->id, 
                "Sold course copy: {$course->name} to {$buyer->name}"
            );

            return ['amount' => $amount, 'transaction_id' => $transaction->id];
        }

        if ($usedMode === 'wallet') {
            $amount = $course->price;
            if ($buyer->wallet < $amount) {
                throw new Exception('Insufficient wallet balance.');
            }

            // Deduct from buyer
            $transaction = WalletTransaction::create([
                'user_id' => $buyer->id,
                'transaction_type' => 'purchase',
                'amount' => $amount,
                'balance_before' => $buyer->wallet,
                'balance_after' => $buyer->wallet - $amount,
                'currency' => 'THB',
                'description' => "Purchased course copy: {$course->name}",
                'metadata' => ['course_id' => $course->id],
                'status' => 'completed',
            ]);

            $buyer->decrement('wallet', $amount);

            // Earn for seller
            WalletTransaction::create([
                'user_id' => $seller->id,
                'transaction_type' => 'course_income',
                'amount' => $amount,
                'balance_before' => $seller->wallet,
                'balance_after' => $seller->wallet + $amount,
                'currency' => 'THB',
                'description' => "Sold course copy: {$course->name} to {$buyer->name}",
                'metadata' => ['course_id' => $course->id, 'buyer_id' => $buyer->id],
                'status' => 'completed',
            ]);

            $seller->increment('wallet', $amount);

            return ['amount' => $amount, 'transaction_id' => $transaction->id];
        }

        throw new Exception('Invalid payment method.');
    }
}
