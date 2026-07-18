<?php

namespace App\Services;

use App\Models\AcademyPointAccount;
use App\Models\AcademyPointTransaction;
use App\Models\Course;
use App\Models\CoursePointAccount;
use App\Models\CoursePointTransaction;
use App\Models\PointsTransaction;
use App\Models\User;
use DomainException;
use Illuminate\Database\DatabaseManager;

/**
 * Central point ledger used by donations, ad revenue and future point flows.
 *
 * All point movements (user wallet, course account, academy account) go through
 * this service so balances, transaction rows and idempotency keys stay
 * consistent. Account-level credits lock the destination row first to avoid
 * deadlocks: the lock order is always user wallet -> course account -> academy
 * account, matching how the donate/ad flows acquire them.
 */
class PointLedgerService
{
    public function __construct(
        protected PointsService $points,
        protected CoursePointAccountService $courseAccounts,
        protected AcademyPointAccountService $academyAccounts,
        protected DatabaseManager $db,
    ) {}

    /**
     * Debit points from a user's wallet. Returns the points transaction or null
     * when the balance is insufficient.
     */
    public function debitUserPoints(User $user, int $amount, string $sourceType, ?int $sourceId, string $description, array $metadata = [], ?string $idempotencyKey = null): ?PointsTransaction
    {
        if ($amount < 1) {
            throw new DomainException('Amount must be at least 1 point.');
        }
        if ($user->pp < $amount) {
            return null;
        }

        return $this->points->spend($user, $amount, $sourceType, $sourceId, $description, $metadata, $idempotencyKey);
    }

    /**
     * Credit points into a user's wallet.
     */
    public function creditUserPoints(User $user, int $amount, string $sourceType, ?int $sourceId, string $description, array $metadata = [], int $xpAmount = 0): PointsTransaction
    {
        return $this->points->earn($user, $amount, $sourceType, $sourceId, $description, $metadata, $xpAmount);
    }

    /**
     * Credit points into a course point account.
     */
    public function creditCoursePoints(int $courseId, int $sourceUserId, int $amount, ?string $idempotencyKey = null, array $metadata = []): CoursePointTransaction
    {
        return $this->courseAccounts->creditFromDonation($courseId, $sourceUserId, $amount, $idempotencyKey, $metadata);
    }

    /**
     * Credit points into an academy point account.
     */
    public function creditAcademyPoints(int $academyId, int $sourceUserId, int $amount, ?string $idempotencyKey = null, array $metadata = [], string $type = AcademyPointTransaction::TYPE_DONATION_POINT_CREDIT): AcademyPointTransaction
    {
        return $this->academyAccounts->credit($academyId, $sourceUserId, $amount, $type, $idempotencyKey, $metadata);
    }

    /**
     * Standard point-donation flow shared by course and academy donations.
     *
     * Locks the user wallet (debit) then credits the destination account,
     * returning both transaction ids so the caller can persist its donation
     * record inside the same transaction window.
     *
     * @param  'course'|'academy'  $destinationType
     * @return array{points_transaction_id: ?int, destination_transaction_id: int}
     */
    public function donatePoints(User $donor, string $destinationType, int $destinationId, int $amount, string $sourceType, ?string $idempotencyKey = null, array $metadata = []): array
    {
        if (! in_array($destinationType, ['course', 'academy'], true)) {
            throw new DomainException('Unsupported donation destination.');
        }
        if ($amount < 1) {
            throw new DomainException('Donation must be at least 1 point.');
        }

        return $this->db->transaction(function () use ($donor, $destinationType, $destinationId, $amount, $sourceType, $idempotencyKey, $metadata) {
            // Idempotency: a replayed request with the same key must not debit the
            // user wallet again. The user points transaction carries the key, so we
            // check it before touching the balance.
            if ($idempotencyKey && ($existing = PointsTransaction::where('user_id', $donor->id)->where('idempotency_key', $idempotencyKey)->first())) {
                $creditTx = $destinationType === 'course'
                    ? CoursePointTransaction::where('metadata', 'like', '%"related_points_transaction_id":'.$existing->id.'%')->first()
                    : AcademyPointTransaction::where('metadata', 'like', '%"related_points_transaction_id":'.$existing->id.'%')->first();

                return ['points_transaction_id' => $existing->id, 'destination_transaction_id' => $creditTx?->id ?? 0];
            }

            $spent = $this->debitUserPoints($donor, $amount, $sourceType, $destinationId, 'Point donation', $metadata, $idempotencyKey);
            if (! $spent) {
                throw new DomainException('Insufficient points for this donation.');
            }

            if ($destinationType === 'course') {
                $tx = $this->creditCoursePoints($destinationId, $donor->id, $amount, $idempotencyKey, ['related_points_transaction_id' => $spent->id] + $metadata);
                $destinationTxId = $tx->id;
            } else {
                $tx = $this->creditAcademyPoints($destinationId, $donor->id, $amount, $idempotencyKey, ['related_points_transaction_id' => $spent->id] + $metadata);
                $destinationTxId = $tx->id;
            }

            return ['points_transaction_id' => $spent->id, 'destination_transaction_id' => $destinationTxId];
        });
    }

    /**
     * Reconcile a course account: sum of transactions must equal the stored balance.
     */
    public function reconcileCourseAccount(int $courseId): array
    {
        $account = CoursePointAccount::where('course_id', $courseId)->first();
        if (! $account) {
            return ['exists' => false, 'balanced' => true, 'stored' => 0, 'computed' => 0];
        }
        $computed = (int) CoursePointTransaction::where('course_point_account_id', $account->id)->sum('amount');

        return ['exists' => true, 'balanced' => $computed === (int) $account->balance, 'stored' => (int) $account->balance, 'computed' => $computed];
    }

    /**
     * Reconcile an academy account: sum of transactions must equal the stored balance.
     */
    public function reconcileAcademyAccount(int $academyId): array
    {
        $account = AcademyPointAccount::where('academy_id', $academyId)->first();
        if (! $account) {
            return ['exists' => false, 'balanced' => true, 'stored' => 0, 'computed' => 0];
        }
        $computed = (int) AcademyPointTransaction::where('academy_point_account_id', $account->id)->sum('amount');

        return ['exists' => true, 'balanced' => $computed === (int) $account->balance, 'stored' => (int) $account->balance, 'computed' => $computed];
    }
}
