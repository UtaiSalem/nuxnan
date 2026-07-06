<?php

namespace App\Services;

use App\Models\Lesson;
use App\Models\LessonAccess;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LessonAccessService
{
    public function __construct(
        protected PointsService $pointsService,
        protected WalletService $walletService,
        protected CoursePointAccountService $coursePointAccountService,
    ) {}

    /**
     * ตรวจสถานะการเข้าถึงของ user สำหรับ lesson นี้
     * คืน array ที่ frontend ใช้ได้เลย
     */
    public function getAccessStatus(User $user, Lesson $lesson, bool $isCourseAdmin = false): array
    {
        // Admin เห็นทุกอย่าง
        if ($isCourseAdmin) {
            return [
                'is_locked' => false,
                'access_type' => $lesson->access_type,
                'access_status' => 'admin',
                'price_label' => null,
            ];
        }

        // Draft/Archived
        if ($lesson->publication_status !== Lesson::STATUS_PUBLISHED) {
            return [
                'is_locked' => true,
                'access_type' => $lesson->access_type,
                'access_status' => 'unavailable',
                'price_label' => null,
            ];
        }

        // Free lesson
        if ($lesson->access_type === Lesson::ACCESS_FREE) {
            // Auto-create access record ถ้ายังไม่มี
            $this->grantFreeAccess($user, $lesson);

            return [
                'is_locked' => false,
                'access_type' => 'free',
                'access_status' => 'unlocked',
                'price_label' => 'ฟรี',
            ];
        }

        // Points / Money — ตรวจ record
        $hasAccess = $lesson->hasActiveAccessFor($user);

        if ($lesson->access_type === Lesson::ACCESS_POINTS) {
            return [
                'is_locked' => ! $hasAccess,
                'access_type' => 'points',
                'access_status' => $hasAccess ? 'unlocked' : 'locked',
                'price_label' => $lesson->point_tuition_fee.' แต้ม',
                'price_points' => $lesson->point_tuition_fee,
            ];
        }

        if ($lesson->access_type === Lesson::ACCESS_MONEY) {
            return [
                'is_locked' => ! $hasAccess,
                'access_type' => 'money',
                'access_status' => $hasAccess ? 'unlocked' : 'locked',
                'price_label' => '฿'.number_format($lesson->money_tuition_fee, 2),
                'price_money' => $lesson->money_tuition_fee,
            ];
        }

        return ['is_locked' => false, 'access_type' => 'free', 'access_status' => 'unlocked', 'price_label' => null];
    }

    /**
     * ปลดล็อกด้วยแต้ม — หักแต้มและสร้าง access record
     */
    public function unlockWithPoints(User $user, Lesson $lesson): array
    {
        // ตรวจซ้ำ
        if ($lesson->hasActiveAccessFor($user)) {
            return ['success' => false, 'message' => 'คุณปลดล็อกบทเรียนนี้แล้ว'];
        }

        if ($lesson->access_type !== Lesson::ACCESS_POINTS) {
            return ['success' => false, 'message' => 'บทเรียนนี้ไม่ได้ใช้แต้มในการเข้าถึง'];
        }

        $fee = (float) $lesson->point_tuition_fee;
        if ($user->pp < $fee) {
            return [
                'success' => false,
                'message' => 'แต้มของคุณไม่เพียงพอ',
                'required' => $fee,
                'current' => $user->pp,
                'shortage' => $fee - $user->pp,
            ];
        }

        return DB::transaction(function () use ($user, $lesson, $fee) {
            // 🔐 Lock user row — ป้องกัน concurrent unlock หักแต้มซ้ำ
            $user = User::lockForUpdate()->find($user->id);

            if ($user->pp < $fee) {
                return [
                    'success' => false,
                    'message' => 'แต้มของคุณไม่เพียงพอ (ตรวจสอบซ้ำ)',
                    'required' => $fee,
                    'current' => $user->pp,
                ];
            }

            // หักแต้ม
            $transaction = $this->pointsService->spend(
                $user,
                $fee,
                'lesson_unlock',
                $lesson->id,
                "ปลดล็อกบทเรียน: {$lesson->title}"
            );

            // 🔐 ตรวจ null
            if (! $transaction) {
                throw new \RuntimeException('Failed to deduct points — transaction returned null');
            }

            // สร้าง access record
            LessonAccess::create([
                'user_id' => $user->id,
                'course_id' => $lesson->course_id,
                'lesson_id' => $lesson->id,
                'access_type' => LessonAccess::TYPE_POINTS,
                'points_transaction_id' => $transaction->id,
                'amount' => $fee,
                'status' => LessonAccess::STATUS_ACTIVE,
                'unlocked_at' => now(),
            ]);

            // ✅ Credit แต้มเข้า course account ในระบบเดียวกัน
            $this->coursePointAccountService->credit(
                courseId: $lesson->course_id,
                lessonId: $lesson->id,
                userId: $user->id,
                amount: (int) $fee,
                pointsTransactionId: $transaction->id,
            );

            Log::info('Lesson unlocked with points', [
                'user_id' => $user->id,
                'lesson_id' => $lesson->id,
                'points' => $fee,
            ]);

            return ['success' => true, 'message' => 'ปลดล็อกบทเรียนสำเร็จ'];
        });
    }

    /**
     * ปลดล็อกด้วยเงิน — ตัดกระเป๋าและสร้าง access record
     */
    public function unlockWithMoney(User $user, Lesson $lesson): array
    {
        if ($lesson->hasActiveAccessFor($user)) {
            return ['success' => false, 'message' => 'คุณปลดล็อกบทเรียนนี้แล้ว'];
        }

        if ($lesson->access_type !== Lesson::ACCESS_MONEY) {
            return ['success' => false, 'message' => 'บทเรียนนี้ไม่ได้ใช้เงินในการเข้าถึง'];
        }

        $fee = $lesson->money_tuition_fee;
        if ($user->wallet < $fee) {
            return [
                'success' => false,
                'message' => 'ยอดเงินในกระเป๋าไม่เพียงพอ',
                'required' => $fee,
                'current' => $user->wallet,
                'shortage' => $fee - $user->wallet,
            ];
        }

        return DB::transaction(function () use ($user, $lesson, $fee) {
            // ตัดเงิน
            $transaction = $this->walletService->withdraw(
                $user,
                $fee,
                'lesson_purchase',
                [],
                "ซื้อบทเรียน: {$lesson->title}"
            );

            // สร้าง access record
            LessonAccess::create([
                'user_id' => $user->id,
                'course_id' => $lesson->course_id,
                'lesson_id' => $lesson->id,
                'access_type' => LessonAccess::TYPE_MONEY,
                'wallet_transaction_id' => $transaction->id,
                'amount' => $fee,
                'status' => LessonAccess::STATUS_ACTIVE,
                'unlocked_at' => now(),
            ]);

            return ['success' => true, 'message' => 'ชำระเงินและปลดล็อกบทเรียนสำเร็จ'];
        });
    }

    /**
     * Auto-grant สำหรับ free lesson (idempotent — เรียกซ้ำได้)
     */
    public function grantFreeAccess(User $user, Lesson $lesson): LessonAccess
    {
        return LessonAccess::firstOrCreate(
            ['user_id' => $user->id, 'lesson_id' => $lesson->id],
            [
                'course_id' => $lesson->course_id,
                'access_type' => LessonAccess::TYPE_FREE,
                'amount' => 0,
                'status' => LessonAccess::STATUS_ACTIVE,
                'unlocked_at' => now(),
            ]
        );
    }
}
