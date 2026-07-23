<?php

namespace App\Services;

use App\Models\Course;
use App\Models\CourseDonate;
use App\Models\CourseMember;
use App\Models\CoursePointAccount;
use App\Models\CoursePointCampaign;
use App\Models\CoursePointCampaignClaim;
use App\Models\CoursePointTransaction;
use App\Models\CourseQuiz;
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
        ?string $idempotencyKey = null,
    ): CoursePointTransaction {
        if ($idempotencyKey && ($existing = CoursePointTransaction::where('idempotency_key', $idempotencyKey)->first())) {
            return $existing;
        }

        // 🔐 lockForUpdate — ป้องกัน concurrent credit ทำ balance ผิด
        $account = CoursePointAccount::lockForUpdate()
            ->firstOrCreate(
                ['course_id' => $courseId],
                [
                    'balance' => 0,
                    'total_earned' => 0,
                    'total_withdrawn' => 0,
                    'total_distributed' => 0,
                    'reserved_balance' => 0,
                    'commission_rate' => 0.0000,
                    'minimum_withdrawal' => CoursePointAccount::MINIMUM_WITHDRAWAL,
                ]
            );

        $balanceBefore = $account->balance;
        $balanceAfter = $balanceBefore + $amount;

        $account->update([
            'balance' => $balanceAfter,
            'total_earned' => $account->total_earned + $amount,
            'version' => $account->version + 1,
        ]);

        return CoursePointTransaction::create([
            'course_point_account_id' => $account->id,
            'course_id' => $courseId,
            'lesson_id' => $lessonId,
            'user_id' => $userId,
            'type' => CoursePointTransaction::TYPE_LESSON_INCOME,
            'amount' => $amount,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
            'related_points_transaction_id' => $pointsTransactionId,
            'metadata' => ['source' => 'lesson_unlock'],
            'created_by' => $userId,
            'idempotency_key' => $idempotencyKey,
        ]);
    }

    public function creditFromDonation(int $courseId, int $donorId, int $amount, ?string $idempotencyKey = null, array $metadata = []): CoursePointTransaction
    {
        if ($idempotencyKey && ($existing = CoursePointTransaction::where('idempotency_key', $idempotencyKey)->first())) {
            return $existing;
        }

        $account = CoursePointAccount::firstOrCreate(['course_id' => $courseId], [
            'balance' => 0, 'total_earned' => 0, 'total_withdrawn' => 0, 'total_distributed' => 0,
            'reserved_balance' => 0, 'commission_rate' => 0.0000,
            'minimum_withdrawal' => CoursePointAccount::MINIMUM_WITHDRAWAL,
        ]);
        $account = CoursePointAccount::lockForUpdate()->findOrFail($account->id);
        $before = $account->balance;
        $account->update(['balance' => $before + $amount, 'total_earned' => $account->total_earned + $amount, 'version' => $account->version + 1]);

        return CoursePointTransaction::create([
            'course_point_account_id' => $account->id, 'course_id' => $courseId, 'user_id' => $donorId,
            'type' => (($metadata['source'] ?? '') === 'donation_cash') ? CoursePointTransaction::TYPE_DONATION_CASH_CREDIT : CoursePointTransaction::TYPE_DONATION_POINT_CREDIT,
            'amount' => $amount, 'balance_before' => $before, 'balance_after' => $before + $amount,
            'related_points_transaction_id' => $metadata['related_points_transaction_id'] ?? null,
            'metadata' => $metadata, 'created_by' => $donorId, 'idempotency_key' => $idempotencyKey,
        ]);
    }

    public function creditFromAdRevenue(int $courseId, int $sourceUserId, int $amount, ?string $idempotencyKey = null, array $metadata = []): CoursePointTransaction
    {
        if ($idempotencyKey && ($existing = CoursePointTransaction::where('idempotency_key', $idempotencyKey)->first())) {
            return $existing;
        }

        $account = CoursePointAccount::firstOrCreate(['course_id' => $courseId], [
            'balance' => 0, 'total_earned' => 0, 'total_withdrawn' => 0, 'total_distributed' => 0,
            'reserved_balance' => 0, 'commission_rate' => 0.0000,
            'minimum_withdrawal' => CoursePointAccount::MINIMUM_WITHDRAWAL,
        ]);
        $account = CoursePointAccount::lockForUpdate()->findOrFail($account->id);
        $before = $account->balance;
        $account->update(['balance' => $before + $amount, 'total_earned' => $account->total_earned + $amount, 'version' => $account->version + 1]);

        return CoursePointTransaction::create([
            'course_point_account_id' => $account->id, 'course_id' => $courseId, 'user_id' => $sourceUserId,
            'type' => CoursePointTransaction::TYPE_AD_REVENUE, 'amount' => $amount,
            'balance_before' => $before, 'balance_after' => $before + $amount,
            'metadata' => $metadata, 'created_by' => $sourceUserId, 'idempotency_key' => $idempotencyKey,
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

            if (! $account->canWithdraw($amount)) {
                $minAmount = $account->minimum_withdrawal;

                return [
                    'success' => false,
                    'message' => "ถอนขั้นต่ำ {$minAmount} แต้ม (Available: {$account->available_balance} แต้ม)",
                ];
            }

            $balanceBefore = $account->balance;
            $balanceAfter = $balanceBefore - $amount;

            // อัพเดท course account
            $account->update([
                'balance' => $balanceAfter,
                'total_withdrawn' => $account->total_withdrawn + $amount,
                'version' => $account->version + 1,
            ]);

            // สร้าง ledger รายวิชา
            $courseTx = CoursePointTransaction::create([
                'course_point_account_id' => $account->id,
                'course_id' => $courseId,
                'user_id' => $recipient->id,
                'type' => CoursePointTransaction::TYPE_OWNER_WITHDRAW,
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'metadata' => ['recipient' => $recipient->name],
                'created_by' => $performedBy,
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
                'course_id' => $courseId,
                'recipient_id' => $recipient->id,
                'amount' => $amount,
            ]);

            return [
                'success' => true,
                'message' => "ถอน {$amount} แต้มสำเร็จ",
                'new_balance' => $balanceAfter,
                'user_new_points' => $recipient->fresh()->pp,
            ];
        });
    }

    // ─── 3. สร้าง Campaign (Manual) ───

    public function createCampaign(int $courseId, array $data, int $createdBy): array
    {
        return DB::transaction(function () use ($courseId, $data, $createdBy) {
            CoursePointAccount::firstOrCreate(
                ['course_id' => $courseId],
                [
                    'balance' => 0, 'total_earned' => 0, 'total_withdrawn' => 0,
                    'total_distributed' => 0, 'reserved_balance' => 0,
                    'commission_rate' => 0.0000, 'minimum_withdrawal' => CoursePointAccount::MINIMUM_WITHDRAWAL,
                ]
            );
            $account = CoursePointAccount::where('course_id', $courseId)->lockForUpdate()->firstOrFail();

            // ถ้ากำหนด max_claims ต้องจองงบ
            $reserveAmount = 0;
            if (isset($data['max_claims']) && $data['max_claims']) {
                $reserveAmount = $data['points_per_claim'] * $data['max_claims'];
                if (! $account->canReserve($reserveAmount)) {
                    return [
                        'success' => false,
                        'message' => "แต้มไม่พอ ต้องการ {$reserveAmount} แต้ม (Available: {$account->available_balance} แต้ม)",
                    ];
                }
            }

            $campaign = CoursePointCampaign::create([
                'course_point_account_id' => $account->id,
                'course_id' => $courseId,
                'campaign_type' => CoursePointCampaign::CAMPAIGN_TYPE_MANUAL,
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'points_per_claim' => $data['points_per_claim'],
                'max_claims' => $data['max_claims'] ?? null,
                'eligible_type' => 'all_enrolled',
                'starts_at' => $data['starts_at'] ?? null,
                'ends_at' => $data['ends_at'] ?? null,
                'status' => CoursePointCampaign::STATUS_ACTIVE,
                'created_by' => $createdBy,
            ]);

            if ($reserveAmount > 0) {
                $this->reserve($account, $reserveAmount, $campaign->id, $createdBy);
            }

            return ['success' => true, 'campaign' => $campaign];
        });
    }

    // ─── 4. สร้าง Lesson Reward Campaign ───

    public function createLessonRewardCampaign(int $courseId, int $lessonId, array $data, int $createdBy): array
    {
        return DB::transaction(function () use ($courseId, $lessonId, $data, $createdBy) {
            CoursePointAccount::firstOrCreate(
                ['course_id' => $courseId],
                [
                    'balance' => 0, 'total_earned' => 0, 'total_withdrawn' => 0,
                    'total_distributed' => 0, 'reserved_balance' => 0,
                    'commission_rate' => 0.0000, 'minimum_withdrawal' => CoursePointAccount::MINIMUM_WITHDRAWAL,
                ]
            );
            $account = CoursePointAccount::where('course_id', $courseId)->lockForUpdate()->firstOrFail();

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
                if (! $account->canReserve($reserveAmount)) {
                    return [
                        'success' => false,
                        'message' => "แต้มไม่พอต้องการจอง {$reserveAmount} แต้ม (Available: {$account->available_balance} แต้ม)",
                    ];
                }
            }

            $campaign = CoursePointCampaign::create([
                'course_point_account_id' => $account->id,
                'course_id' => $courseId,
                'lesson_id' => $lessonId,
                'campaign_type' => CoursePointCampaign::CAMPAIGN_TYPE_LESSON,
                'title' => $data['title'] ?? 'รางวัลอ่านจบบทเรียน',
                'description' => $data['description'] ?? null,
                'points_per_claim' => $data['points_per_claim'],
                'max_claims' => $data['max_claims'] ?? null,
                'eligible_type' => 'all_enrolled',
                'starts_at' => $data['starts_at'] ?? null,
                'ends_at' => $data['ends_at'] ?? null,
                'status' => CoursePointCampaign::STATUS_ACTIVE,
                'created_by' => $createdBy,
            ]);

            if ($reserveAmount > 0) {
                $this->reserve($account, $reserveAmount, $campaign->id, $createdBy);
            }

            return ['success' => true, 'campaign' => $campaign];
        });
    }

    public function createQuizRewardCampaign(int $courseId, int $quizId, array $data, int $createdBy): array
    {
        return DB::transaction(function () use ($courseId, $quizId, $data, $createdBy) {
            $account = CoursePointAccount::firstOrCreate(['course_id' => $courseId], ['balance' => 0, 'total_earned' => 0, 'total_withdrawn' => 0, 'total_distributed' => 0, 'reserved_balance' => 0, 'commission_rate' => 0.0000, 'minimum_withdrawal' => CoursePointAccount::MINIMUM_WITHDRAWAL]);
            $account = CoursePointAccount::where('course_id', $courseId)->lockForUpdate()->firstOrFail();
            if (CoursePointCampaign::where('course_id', $courseId)->where('quiz_id', $quizId)->where('campaign_type', CoursePointCampaign::CAMPAIGN_TYPE_QUIZ)->whereIn('status', [CoursePointCampaign::STATUS_ACTIVE, CoursePointCampaign::STATUS_PAUSED])->exists()) {
                return ['success' => false, 'message' => 'Quiz reward campaign already exists'];
            }
            $reserve = ($data['max_claims'] ?? 0) * $data['points_per_claim'];
            if ($reserve && ! $account->canReserve($reserve)) {
                return ['success' => false, 'message' => 'แต้มไม่พอสำหรับจองแคมเปญ'];
            }
            $campaign = CoursePointCampaign::create(['course_point_account_id' => $account->id, 'course_id' => $courseId, 'quiz_id' => $quizId, 'campaign_type' => CoursePointCampaign::CAMPAIGN_TYPE_QUIZ, 'title' => $data['title'] ?? 'รางวัลทำแบบทดสอบ', 'description' => $data['description'] ?? null, 'points_per_claim' => $data['points_per_claim'], 'max_claims' => $data['max_claims'] ?? null, 'eligible_type' => 'all_enrolled', 'starts_at' => $data['starts_at'] ?? null, 'ends_at' => $data['ends_at'] ?? null, 'status' => CoursePointCampaign::STATUS_ACTIVE, 'created_by' => $createdBy]);
            if ($reserve) {
                $this->reserve($account, $reserve, $campaign->id, $createdBy);
            }

            return ['success' => true, 'campaign' => $campaign];
        });
    }

    // ─── 5. Auto-Grant Lesson Reward ───

    public function grantLessonCompletionReward(Lesson $lesson, User $student, ?string $idempotencyKey = null): array
    {
        $campaign = CoursePointCampaign::where('lesson_id', $lesson->id)->where('campaign_type', CoursePointCampaign::CAMPAIGN_TYPE_LESSON)->where('status', CoursePointCampaign::STATUS_ACTIVE)->first();
        if (! $campaign || ! $campaign->isClaimable()) {
            return ['rewarded' => false, 'reason' => 'no_active_campaign'];
        }

        return $this->grantCampaignClaim($campaign, $student, 'lesson_completion', 'lesson_completion_reward', $lesson->id, "Lesson reward: {$lesson->title}", $idempotencyKey, ['lesson_id' => $lesson->id]);
    }

    public function grantQuizCompletionReward(CourseQuiz $quiz, User $student, ?string $idempotencyKey = null): array
    {
        $campaign = CoursePointCampaign::where('quiz_id', $quiz->id)->where('campaign_type', CoursePointCampaign::CAMPAIGN_TYPE_QUIZ)->where('status', CoursePointCampaign::STATUS_ACTIVE)->first();
        if (! $campaign || ! $campaign->isClaimable()) {
            return ['rewarded' => false, 'reason' => 'no_active_campaign'];
        }

        return $this->grantCampaignClaim($campaign, $student, 'quiz_completion', 'quiz_completion_reward', $quiz->id, "Quiz reward: {$quiz->title}", $idempotencyKey, ['quiz_id' => $quiz->id]);
    }

    protected function grantCampaignClaim(CoursePointCampaign $campaign, User $student, string $txSource, string $earnReason, int $earnRefId, string $earnDescription, ?string $idempotencyKey = null, array $extraCourseTxMeta = [], array $extraClaimFields = []): array
    {
        return DB::transaction(function () use ($campaign, $student, $txSource, $earnReason, $earnRefId, $earnDescription, $idempotencyKey, $extraCourseTxMeta, $extraClaimFields) {
            if ($idempotencyKey && ($existing = CoursePointTransaction::where('idempotency_key', $idempotencyKey)->first())) {
                return ['rewarded' => true, 'points_received' => $existing->amount, 'campaign_title' => $campaign->title];
            }
            // ลำดับ lock: campaign -> account เพื่อเลี่ยง deadlock
            $campaign = CoursePointCampaign::lockForUpdate()->find($campaign->id);
            if (! $campaign->isClaimable()) {
                return ['rewarded' => false, 'reason' => 'not_claimable'];
            }

            $alreadyClaimed = CoursePointCampaignClaim::where('campaign_id', $campaign->id)
                ->where('user_id', $student->id)
                ->exists();
            if ($alreadyClaimed) {
                return ['rewarded' => false, 'reason' => 'already_claimed'];
            }

            $account = CoursePointAccount::lockForUpdate()->find($campaign->course_point_account_id);
            $amount = $campaign->points_per_claim;

            if ($campaign->max_claims) {
                if ($account->balance < $amount || $account->reserved_balance < $amount) {
                    $campaign->update(['status' => CoursePointCampaign::STATUS_DEPLETED]);

                    return ['rewarded' => false, 'reason' => 'depleted'];
                }
            } else {
                if ($account->available_balance < $amount) {
                    return ['rewarded' => false, 'reason' => 'insufficient_balance'];
                }
            }

            $balanceBefore = $account->balance;
            $balanceAfter = $balanceBefore - $amount;

            $updateData = [
                'balance' => $balanceAfter,
                'total_distributed' => $account->total_distributed + $amount,
                'version' => $account->version + 1,
            ];
            if ($campaign->max_claims) {
                $updateData['reserved_balance'] = max(0, $account->reserved_balance - $amount);
            }
            $account->update($updateData);

            $courseTx = CoursePointTransaction::create([
                'course_point_account_id' => $account->id,
                'course_id' => $campaign->course_id,
                'lesson_id' => $extraCourseTxMeta['lesson_id'] ?? null,
                'user_id' => $student->id,
                'type' => CoursePointTransaction::TYPE_STUDENT_CLAIM,
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'related_campaign_id' => $campaign->id,
                'metadata' => array_merge(['source' => $txSource], $extraCourseTxMeta),
                'created_by' => $student->id,
                'idempotency_key' => $idempotencyKey,
            ]);

            $userTx = $this->pointsService->earn(
                $student,
                $amount,
                $earnReason,
                $earnRefId,
                $earnDescription,
                ['campaign_id' => $campaign->id],
            );

            CoursePointCampaignClaim::create(array_merge([
                'campaign_id' => $campaign->id,
                'user_id' => $student->id,
                'points_amount' => $amount,
                'points_transaction_id' => $userTx->id,
                'course_point_transaction_id' => $courseTx->id,
                'claimed_at' => now(),
            ], $extraClaimFields));

            if (! empty($extraClaimFields['viewed_donation_id'])) {
                $donation = CourseDonate::lockForUpdate()->find($extraClaimFields['viewed_donation_id']);
                if ($donation) {
                    $donation->update(['remaining_points' => max(0, $donation->remaining_points - min($amount, $donation->remaining_points))]);
                }
            }

            $campaign->increment('total_claimed');
            $campaign->increment('total_points_claimed', $amount);

            if ($campaign->max_claims && $campaign->fresh()->total_claimed >= $campaign->max_claims) {
                $campaign->update(['status' => CoursePointCampaign::STATUS_DEPLETED]);
            }

            return [
                'rewarded' => true,
                'points_received' => $amount,
                'campaign_title' => $campaign->title,
            ];
        });
    }

    // ─── 6. Cancel Campaign ───

    public function cancelCampaign(int $campaignId): array
    {
        return DB::transaction(function () use ($campaignId) {
            $campaign = CoursePointCampaign::lockForUpdate()->findOrFail($campaignId);
            if ($campaign->status === CoursePointCampaign::STATUS_ENDED) {
                return ['success' => true];
            }

            $account = CoursePointAccount::lockForUpdate()->find($campaign->course_point_account_id);

            // คืน reserved
            if ($campaign->max_claims) {
                $remaining = ($campaign->max_claims - $campaign->total_claimed) * $campaign->points_per_claim;
                if ($remaining > 0 && $account) {
                    $this->releaseReserve($account, $remaining, $campaign->id, $campaign->created_by);
                }
            }

            $campaign->update(['status' => CoursePointCampaign::STATUS_ENDED]);

            return ['success' => true];
        });
    }

    // ─── Internal Helpers ───

    protected function reserve(CoursePointAccount $account, int $amount, int $campaignId, int $performedBy): void
    {
        if (DB::transactionLevel() === 0) {
            throw new \RuntimeException('Course point reservation requires an open transaction.');
        }
        $account = CoursePointAccount::lockForUpdate()->findOrFail($account->id);
        $before = $account->reserved_balance;
        $after = $before + $amount;
        $account->update(['reserved_balance' => $after, 'version' => $account->version + 1]);
        CoursePointTransaction::create(['course_point_account_id' => $account->id, 'course_id' => $account->course_id, 'type' => CoursePointTransaction::TYPE_CAMPAIGN_RESERVE, 'amount' => $amount, 'balance_before' => $before, 'balance_after' => $after, 'related_campaign_id' => $campaignId, 'created_by' => $performedBy, 'metadata' => ['source' => 'campaign_reserve']]);
    }

    protected function releaseReserve(CoursePointAccount $account, int $amount, int $campaignId, int $performedBy): void
    {
        if (DB::transactionLevel() === 0) {
            throw new \RuntimeException('Course point reservation requires an open transaction.');
        }
        $account = CoursePointAccount::lockForUpdate()->findOrFail($account->id);
        $before = $account->reserved_balance;
        $after = max(0, $before - $amount);
        $account->update(['reserved_balance' => $after, 'version' => $account->version + 1]);
        CoursePointTransaction::create(['course_point_account_id' => $account->id, 'course_id' => $account->course_id, 'type' => CoursePointTransaction::TYPE_CAMPAIGN_RELEASE, 'amount' => $amount, 'balance_before' => $before, 'balance_after' => $after, 'related_campaign_id' => $campaignId, 'created_by' => $performedBy, 'metadata' => ['source' => 'campaign_release']]);
    }

    public function getAccount(int $courseId): ?CoursePointAccount
    {
        return CoursePointAccount::where('course_id', $courseId)->first();
    }

    public function claimManualCampaign(int $campaignId, User $student, ?int $viewedDonorId = null, ?int $viewedDonationId = null): array
    {
        $campaign = CoursePointCampaign::findOrFail($campaignId);
        if ($campaign->campaign_type !== CoursePointCampaign::CAMPAIGN_TYPE_MANUAL) {
            return ['success' => false, 'message' => 'Campaign is not manual'];
        }
        if (! CourseMember::where('course_id', $campaign->course_id)->where('user_id', $student->id)->exists()) {
            return ['success' => false, 'message' => 'ต้องเข้าร่วมรายวิชาก่อน'];
        }
        if (! $campaign->isClaimable()) {
            return ['success' => false, 'message' => 'Campaign is not claimable'];
        }
        $extraClaimFields = $viewedDonorId || $viewedDonationId ? ['viewed_donor_id' => $viewedDonorId, 'viewed_donation_id' => $viewedDonationId, 'viewed_at' => now()] : [];
        $result = $this->grantCampaignClaim($campaign, $student, 'manual_claim', 'course_manual_claim', $campaign->id, "Campaign reward: {$campaign->title}", null, [], $extraClaimFields);

        return ['success' => $result['rewarded'], 'message' => $result['rewarded'] ? 'รับแต้มสำเร็จ' : ($result['reason'] ?? 'ไม่สามารถรับแต้มได้'), 'points_received' => $result['points_received'] ?? null, 'user_new_points' => $result['rewarded'] ? $student->fresh()->pp : null];
    }

    // Wrap claimCampaign legacy support
    public function claimCampaign(int $campaignId, User $student): array
    {
        // Re-use logic or just redirect to specific claim
        return $this->claimManualCampaign($campaignId, $student);
    }
}
