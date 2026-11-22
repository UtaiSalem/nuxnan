<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class FeedPointsService
{
    public function __construct(private PointsService $points) {}

    /** Charge and perform domain writes atomically; failed spends never run work. */
    public function charge(User $payer, int $amount, string $sourceType, ?int $sourceId, string $description, array $credits, callable $work): array
    {
        return DB::transaction(function () use ($payer, $amount, $sourceType, $sourceId, $description, $credits, $work) {
            $locked = User::whereKey($payer->id)->lockForUpdate()->firstOrFail();
            if (! $this->points->spend($locked, $amount, $sourceType, $sourceId, $description)) {
                return ['ok' => false, 'current_pp' => (int) $locked->pp];
            }

            $data = $work();

            foreach ($credits as $credit) {
                $recipient = $credit['user'] ?? $credit[0] ?? null;
                $creditAmount = $credit['amount'] ?? $credit[1] ?? 0;
                $creditSource = $credit['sourceType'] ?? $credit[2] ?? 'feed_platform_fee';
                if (! $recipient || $creditAmount <= 0) {
                    continue;
                }

                // Reload even when payer and multiple recipients are the same user.
                $recipient = User::whereKey($recipient->id)->lockForUpdate()->firstOrFail();
                $this->points->earn($recipient, $creditAmount, $creditSource, $sourceId, $description);
            }

            return array_merge($data, ['ok' => true, 'user_pp' => (int) $locked->fresh()->pp]);
        });
    }

    public function fineIfAffordable(User $user, int $amount, string $sourceType, ?int $sourceId, string $description): int
    {
        return DB::transaction(function () use ($user, $amount, $sourceType, $sourceId, $description) {
            $locked = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            if ($amount <= 0 || $locked->pp < $amount) {
                return 0;
            }

            return $this->points->spend($locked, $amount, $sourceType, $sourceId, $description) ? $amount : 0;
        });
    }
}
