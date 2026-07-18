<?php

namespace App\Services;

use App\Models\AcademyPointAccount;
use App\Models\AcademyPointTransaction;
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
