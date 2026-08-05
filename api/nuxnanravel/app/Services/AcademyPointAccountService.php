<?php

namespace App\Services;

use App\Models\AcademyPointAccount;
use App\Models\AcademyPointTransaction;
use DomainException;
use Illuminate\Support\Facades\DB;

/**
 * Manages the lifecycle of an academy's point account: crediting donation and
 * ad-revenue points with proper row locking and idempotency. Mirrors
 * CoursePointAccountService so course and academy accounts share one shape.
 */
class AcademyPointAccountService
{
    public function __construct(protected PointsService $pointsService) {}

    public function getAccount(int $academyId): ?AcademyPointAccount
    {
        return AcademyPointAccount::where('academy_id', $academyId)->first();
    }

    public function credit(int $academyId, ?int $userId, int $amount, string $type, ?string $idempotencyKey = null, array $metadata = []): AcademyPointTransaction
    {
        if ($idempotencyKey && ($existing = AcademyPointTransaction::where('idempotency_key', $idempotencyKey)->first())) {
            return $existing;
        }

        return DB::transaction(function () use ($academyId, $userId, $amount, $type, $idempotencyKey, $metadata) {
            if ($idempotencyKey && ($existing = AcademyPointTransaction::where('idempotency_key', $idempotencyKey)->first())) {
                return $existing;
            }

            $account = AcademyPointAccount::where('academy_id', $academyId)->lockForUpdate()->first()
                ?? AcademyPointAccount::create(['academy_id' => $academyId]);

            $before = (int) $account->balance;
            $after = $before + $amount;
            $account->update(['balance' => $after, 'total_earned' => $account->total_earned + $amount, 'version' => $account->version + 1]);

            return AcademyPointTransaction::create([
                'academy_point_account_id' => $account->id,
                'academy_id' => $academyId,
                'user_id' => $userId,
                'type' => $type,
                'amount' => $amount,
                'balance_before' => $before,
                'balance_after' => $after,
                'idempotency_key' => $idempotencyKey,
                'metadata' => $metadata,
                'created_by' => $userId,
            ]);
        });
    }

    /**
     * Move points OUT of the academy account. Claims must debit here before any
     * points are granted to users, otherwise the payout mints new points.
     */
    public function debit(int $academyId, ?int $userId, int $amount, string $type, ?string $idempotencyKey = null, array $metadata = [], bool $releaseReservation = false): AcademyPointTransaction
    {
        if ($idempotencyKey && ($existing = AcademyPointTransaction::where('idempotency_key', $idempotencyKey)->first())) {
            return $existing;
        }

        return DB::transaction(function () use ($academyId, $userId, $amount, $type, $idempotencyKey, $metadata, $releaseReservation) {
            if ($idempotencyKey && ($existing = AcademyPointTransaction::where('idempotency_key', $idempotencyKey)->first())) {
                return $existing;
            }

            $account = AcademyPointAccount::where('academy_id', $academyId)->lockForUpdate()->first();
            if (! $account || (int) $account->balance < $amount) {
                throw new DomainException('insufficient_pool');
            }

            $before = (int) $account->balance;
            $after = $before - $amount;
            $updateData = ['balance' => $after, 'total_distributed' => $account->total_distributed + $amount, 'version' => $account->version + 1];
            if ($releaseReservation) {
                $updateData['reserved_balance'] = max(0, (int) $account->reserved_balance - $amount);
            }
            $account->update($updateData);

            return AcademyPointTransaction::create([
                'academy_point_account_id' => $account->id,
                'academy_id' => $academyId,
                'user_id' => $userId,
                'type' => $type,
                // `amount` is an unsigned column: direction is carried by the type and
                // the before/after balances, never by the sign. Matches credit().
                'amount' => $amount,
                'balance_before' => $before,
                'balance_after' => $after,
                'idempotency_key' => $idempotencyKey,
                'metadata' => $metadata,
                'created_by' => $userId,
            ]);
        });
    }

    /**
     * Earmark donated points for member claims. Reserved points stay in the
     * balance but drop out of available_balance, so withdrawals and allocations
     * cannot spend what claimers are entitled to.
     */
    public function reserveForClaims(int $academyId, int $amount, ?int $userId = null, array $metadata = []): AcademyPointTransaction
    {
        return DB::transaction(function () use ($academyId, $amount, $userId, $metadata) {
            $account = AcademyPointAccount::where('academy_id', $academyId)->lockForUpdate()->first()
                ?? AcademyPointAccount::create(['academy_id' => $academyId]);

            $balance = (int) $account->balance;
            $account->update(['reserved_balance' => (int) $account->reserved_balance + $amount, 'version' => $account->version + 1]);

            return AcademyPointTransaction::create([
                'academy_point_account_id' => $account->id,
                'academy_id' => $academyId,
                'user_id' => $userId,
                'type' => AcademyPointTransaction::TYPE_DONATION_RESERVE,
                'amount' => $amount,
                'balance_before' => $balance,
                'balance_after' => $balance,
                'idempotency_key' => null,
                'metadata' => $metadata,
                'created_by' => $userId,
            ]);
        });
    }

    public function creditFromDonation(int $academyId, int $donorId, int $amount, ?string $idempotencyKey = null, array $metadata = []): AcademyPointTransaction
    {
        return $this->credit($academyId, $donorId, $amount, AcademyPointTransaction::TYPE_DONATION_POINT_CREDIT, $idempotencyKey, $metadata);
    }

    public function creditFromAdRevenue(int $academyId, int $sourceUserId, int $amount, ?string $idempotencyKey = null, array $metadata = []): AcademyPointTransaction
    {
        return $this->credit($academyId, $sourceUserId, $amount, AcademyPointTransaction::TYPE_AD_REVENUE, $idempotencyKey, $metadata);
    }

    public function creditFromCashDonation(int $academyId, int $donorId, int $amount, ?string $idempotencyKey = null, array $metadata = []): AcademyPointTransaction
    {
        return $this->credit($academyId, $donorId, $amount, AcademyPointTransaction::TYPE_DONATION_CASH_CREDIT, $idempotencyKey, $metadata);
    }
}
