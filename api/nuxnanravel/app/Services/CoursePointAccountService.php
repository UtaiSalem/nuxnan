<?php
namespace App\Services;

use App\Models\Course;
use App\Models\CoursePointAccount;
use App\Models\CoursePointCampaign;
use App\Models\CoursePointCampaignClaim;
use App\Models\CoursePointTransaction;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CoursePointAccountService
{
    public function __construct(
        protected PointsService $pointsService,
    ) {}

    // ─── 1. Credit (เรียกจาก LessonAccessService หลัง unlock) ───

    public function credit(
        int $courseId,
        int $lessonId,
        int $userId,
        int $amount,
        int $pointsTransactionId,
    ): CoursePointTransaction {
        // 🔐 lockForUpdate — ป้องกัน concurrent credit ทำ balance ผิด
        $account = CoursePointAccount::lockForUpdate()
            ->firstOrCreate(
                ['course_id' => $courseId],
                [
                    'balance'             => 0,
                    'total_earned'        => 0,
                    'total_withdrawn'     => 0,
                    'total_distributed'   => 0,
                    'reserved_balance'    => 0,
                    'commission_rate'     => 0.0000,
                    'minimum_withdrawal'  => CoursePointAccount::MINIMUM_WITHDRAWAL,
                ]
            );

        $balanceBefore = $account->balance;
        $balanceAfter  = $balanceBefore + $amount;

        $account->update([
            'balance'      => $balanceAfter,
            'total_earned' => $account->total_earned + $amount,
        ]);

        return CoursePointTransaction::create([
            'course_point_account_id'      => $account->id,
            'course_id'                    => $courseId,
            'lesson_id'                    => $lessonId,
            'user_id'                      => $userId,
            'type'                         => CoursePointTransaction::TYPE_LESSON_INCOME,
            'amount'                       => $amount,
            'balance_before'               => $balanceBefore,
            'balance_after'                => $balanceAfter,
            'related_points_transaction_id' => $pointsTransactionId,
            'metadata'                     => ['source' => 'lesson_unlock'],
            'created_by'                   => $userId,
        ]);
    }

    // ─── 2. Withdraw (owner ถอนเข้าแต้มตัวเอง) ───

    public function withdraw(
        int $courseId,
        User $recipient,
        int $amount,
        int $performedBy,
    ): array {
        return DB::transaction(function () use ($courseId, $recipient, $amount, $performedBy) {
            // 🔐 Lock account ก่อนตรวจ balance
            $account = CoursePointAccount::where('course_id', $courseId)
                ->lockForUpdate()
                ->firstOrFail();

            if (!$account->canWithdraw($amount)) {
                $minAmount = $account->minimum_withdrawal;
                return [
                    'success' => false,
                    'message' => "ถอนขั้นต่ำ {$minAmount} แต้ม (Available: {$account->available_balance} แต้ม)",
                ];
            }

            $balanceBefore = $account->balance;
            $balanceAfter  = $balanceBefore - $amount;

            // อัพเดท course account
            $account->update([
                'balance'         => $balanceAfter,
                'total_withdrawn' => $account->total_withdrawn + $amount,
            ]);

            // สร้าง ledger รายวิชา
            $courseTx = CoursePointTransaction::create([
                'course_point_account_id' => $account->id,
                'course_id'               => $courseId,
                'user_id'                 => $recipient->id,
                'type'                    => CoursePointTransaction::TYPE_OWNER_WITHDRAW,
                'amount'                  => $amount,
                'balance_before'          => $balanceBefore,
                'balance_after'           => $balanceAfter,
                'metadata'                => ['recipient' => $recipient->name],
                'created_by'              => $performedBy,
            ]);

            // เพิ่มแต้มให้ owner (ฝั่ง user)
            $userTx = $this->pointsService->earn(
                $recipient,
                $amount,
                'course_withdraw',
                $courseId,
                "ถอนแต้มจากรายวิชา #{$courseId}",
                ['course_point_transaction_id' => $courseTx->id],
            );

            // อัพเดท FK กลับ
            $courseTx->update(['related_points_transaction_id' => $userTx->id]);

            Log::info('Course points withdrawn', [
                'course_id'    => $courseId,
                'recipient_id' => $recipient->id,
                'amount'       => $amount,
            ]);

            return [
                'success'         => true,
                'message'         => "ถอน {$amount} แต้มสำเร็จ",
                'new_balance'     => $balanceAfter,
                'user_new_points' => $recipient->fresh()->pp,
            ];
        });
    }

    // ─── 3. สร้าง Campaign (Manual) ───

    public function createCampaign(int $courseId, array $data, int $createdBy): array
    {
        return DB::transaction(function () use ($courseId, $data, $createdBy) {
            $account = CoursePointAccount::where('course_id', $courseId)
                ->lockForUpdate()
                ->firstOrCreate(
                    ['course_id' => $courseId],
                    [
                        'balance' => 0, 'total_earned' => 0, 'total_withdrawn' => 0,
                        'total_distributed' => 0, 'reserved_balance' => 0,
                        'commission_rate' => 0.0000,
                        'minimum_withdrawal' => CoursePointAccount::MINIMUM_WITHDRAWAL,
                    ]
                );

            // ถ้ากำหนด max_claims ต้องจองงบ
            $reserveAmount = 0;
            if (isset($data['max_claims']) && $data['max_claims']) {
                $reserveAmount = $data['points_per_claim'] * $data['max_claims'];
                if (!$account->canReserve($reserveAmount)) {
                    return [
                        'success' => false,
                        'message' => "แต้มไม่พอ ต้องการ {$reserveAmount} แต้ม (Available: {$account->available_balance} แต้ม)",
                    ];
                }
                $this->reserve($account, $reserveAmount);
            }

            $campaign = CoursePointCampaign::create([
                'course_point_account_id' => $account->id,
                'course_id'               => $courseId,
                'campaign_type'           => CoursePointCampaign::CAMPAIGN_TYPE_MANUAL,
                'title'                   => $data['title'],
                'description'             => $data['description'] ?? null,
                'points_per_claim'        => $data['points_per_claim'],
                'max_claims'              => $data['max_claims'] ?? null,
                'eligible_type'           => 'all_enrolled',
                'starts_at'               => $data['starts_at'] ?? null,
                'ends_at'                 => $data['ends_at'] ?? null,
                'status'                  => CoursePointCampaign::STATUS_ACTIVE,
                'created_by'              => $createdBy,
            ]);

            return ['success' => true, 'campaign' => $campaign];
        });
    }

    // ─── 4. สร้าง Lesson Reward Campaign ───

    public function createLessonRewardCampaign(int $courseId, int $lessonId, array $data, int $createdBy): array
    {
        return DB::transaction(function () use ($courseId, $lessonId, $data, $createdBy) {
            $account = CoursePointAccount::where('course_id', $courseId)
                ->lockForUpdate()
                ->firstOrCreate(
                    ['course_id' => $courseId],
                    [
                        'balance' => 0, 'total_earned' => 0, 'total_withdrawn' => 0,
                        'total_distributed' => 0, 'reserved_balance' => 0,
                        'commission_rate' => 0.0000,
                        'minimum_withdrawal' => CoursePointAccount::MINIMUM_WITHDRAWAL,
                    ]
                );

            $existing = CoursePointCampaign::where('course_id', $courseId)
                ->where('lesson_id', $lessonId)
                ->where('campaign_type', CoursePointCampaign::CAMPAIGN_TYPE_LESSON)
                ->whereIn('status', [CoursePointCampaign::STATUS_ACTIVE, CoursePointCampaign::STATUS_PAUSED])
                ->first();

            if ($existing) {
                return ['success' => false, 'message' => 'บทเรียนนี้มี Reward Campaign ที่ใช้งานอยู่แล้ว'];
            }

            $reserveAmount = 0;
            if (isset($data['max_claims']) && $data['max_claims']) {
                $reserveAmount = $data['points_per_claim'] * $data['max_claims'];
                if (!$account->canReserve($reserveAmount)) {
                    return [
                        'success' => false,
                        'message' => "แต้มไม่พอต้องการจอง {$reserveAmount} แต้ม (Available: {$account->available_balance} แต้ม)",
                    ];
                }
                $this->reserve($account, $reserveAmount);
            }

            $campaign = CoursePointCampaign::create([
                'course_point_account_id' => $account->id,
                'course_id'               => $courseId,
                'lesson_id'               => $lessonId,
                'campaign_type'           => CoursePointCampaign::CAMPAIGN_TYPE_LESSON,
                'title'                   => $data['title'] ?? "รางวัลอ่านจบบทเรียน",
                'description'             => $data['description'] ?? null,
                'points_per_claim'        => $data['points_per_claim'],
                'max_claims'              => $data['max_claims'] ?? null,
                'eligible_type'           => 'all_enrolled',
                'starts_at'               => $data['starts_at'] ?? null,
                'ends_at'                 => $data['ends_at'] ?? null,
                'status'                  => CoursePointCampaign::STATUS_ACTIVE,
                'created_by'              => $createdBy,
            ]);

            return ['success' => true, 'campaign' => $campaign];
        });
    }

    // ─── 5. Auto-Grant Lesson Reward ───

    public function grantLessonCompletionReward(Lesson $lesson, User $student): array
    {
        $campaign = CoursePointCampaign::where('lesson_id', $lesson->id)
            ->where('campaign_type', CoursePointCampaign::CAMPAIGN_TYPE_LESSON)
            ->where('status', CoursePointCampaign::STATUS_ACTIVE)
            ->first();

        if (!$campaign || !$campaign->isClaimable()) {
            return ['rewarded' => false, 'reason' => 'no_active_campaign'];
        }

        return DB::transaction(function () use ($campaign, $lesson, $student) {
            // ลำดับ lock: campaign -> account เพื่อเลี่ยง deadlock
            $campaign = CoursePointCampaign::lockForUpdate()->find($campaign->id);
            if (!$campaign->isClaimable()) return ['rewarded' => false, 'reason' => 'not_claimable'];

            $alreadyClaimed = CoursePointCampaignClaim::where('campaign_id', $campaign->id)
                ->where('user_id', $student->id)
                ->exists();
            if ($alreadyClaimed) return ['rewarded' => false, 'reason' => 'already_claimed'];

            $account = CoursePointAccount::lockForUpdate()->find($campaign->course_point_account_id);
            $amount = $campaign->points_per_claim;

            if ($campaign->max_claims) {
                if ($account->balance < $amount || $account->reserved_balance < $amount) {
                    $campaign->update(['status' => CoursePointCampaign::STATUS_DEPLETED]);
                    return ['rewarded' => false, 'reason' => 'depleted'];
                }
            } else {
                if ($account->available_balance < $amount) return ['rewarded' => false, 'reason' => 'insufficient_balance'];
            }

            $balanceBefore = $account->balance;
            $balanceAfter  = $balanceBefore - $amount;

            $updateData = [
                'balance'          => $balanceAfter,
                'total_distributed' => $account->total_distributed + $amount,
            ];
            if ($campaign->max_claims) {
                $updateData['reserved_balance'] = max(0, $account->reserved_balance - $amount);
            }
            $account->update($updateData);

            $courseTx = CoursePointTransaction::create([
                'course_point_account_id' => $account->id,
                'course_id'               => $lesson->course_id,
                'lesson_id'               => $lesson->id,
                'user_id'                 => $student->id,
                'type'                    => CoursePointTransaction::TYPE_STUDENT_CLAIM,
                'amount'                  => $amount,
                'balance_before'          => $balanceBefore,
                'balance_after'           => $balanceAfter,
                'related_campaign_id'     => $campaign->id,
                'metadata'                => ['source' => 'lesson_completion'],
                'created_by'              => $student->id,
            ]);

            $userTx = $this->pointsService->earn(
                $student,
                $amount,
                'lesson_completion_reward',
                $lesson->id,
                "รางวัลอ่านจบบทเรียน: {$lesson->title}",
                ['campaign_id' => $campaign->id],
            );

            CoursePointCampaignClaim::create([
                'campaign_id'                 => $campaign->id,
                'user_id'                     => $student->id,
                'points_amount'               => $amount,
                'points_transaction_id'       => $userTx->id,
                'course_point_transaction_id' => $courseTx->id,
                'claimed_at'                  => now(),
            ]);

            $campaign->increment('total_claimed');
            $campaign->increment('total_points_claimed', $amount);

            if ($campaign->max_claims && $campaign->fresh()->total_claimed >= $campaign->max_claims) {
                $campaign->update(['status' => CoursePointCampaign::STATUS_DEPLETED]);
            }

            return [
                'rewarded'        => true,
                'points_received' => $amount,
                'campaign_title'  => $campaign->title,
            ];
        });
    }

    // ─── 6. Cancel Campaign ───

    public function cancelCampaign(int $campaignId): array
    {
        return DB::transaction(function () use ($campaignId) {
            $campaign = CoursePointCampaign::lockForUpdate()->findOrFail($campaignId);
            if ($campaign->status === CoursePointCampaign::STATUS_ENDED) return ['success' => true];

            $account = CoursePointAccount::lockForUpdate()->find($campaign->course_point_account_id);
            
            // คืน reserved
            if ($campaign->max_claims) {
                $remaining = ($campaign->max_claims - $campaign->total_claimed) * $campaign->points_per_claim;
                if ($remaining > 0 && $account) {
                    $this->releaseReserve($account, $remaining);
                }
            }

            $campaign->update(['status' => CoursePointCampaign::STATUS_ENDED]);
            return ['success' => true];
        });
    }

    // ─── Internal Helpers ───

    protected function reserve(CoursePointAccount $account, int $amount): void
    {
        $account->update(['reserved_balance' => $account->reserved_balance + $amount]);
    }

    protected function releaseReserve(CoursePointAccount $account, int $amount): void
    {
        $account->update(['reserved_balance' => max(0, $account->reserved_balance - $amount)]);
    }

    public function getAccount(int $courseId): ?CoursePointAccount
    {
        return CoursePointAccount::where('course_id', $courseId)->first();
    }

    // Wrap claimCampaign legacy support
    public function claimCampaign(int $campaignId, User $student): array
    {
        // Re-use logic or just redirect to specific claim
        return $this->grantLessonCompletionReward(CoursePointCampaign::find($campaignId)->lesson ?? new Lesson(), $student);
    }
}
