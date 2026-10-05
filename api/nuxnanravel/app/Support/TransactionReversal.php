<?php

namespace App\Support;

use App\Models\PointsTransaction;
use App\Models\WalletTransaction;

/**
 * Single source of truth for "can an admin still cancel/claw back this ledger
 * row, and was it already clawed back". Used by every admin listing that shows
 * a reverse button (points & wallet lists, the user-profile transfer lens) so
 * the rules never drift between screens.
 *
 * Two row kinds are reversible:
 *  - transfer   → a peer transfer; only the *incoming* (received) leg, since the
 *                 claw-back pulls value back from the recipient to the sender.
 *  - conversion → an intra-user points↔money conversion; either leg, because the
 *                 reversal undoes both sides for that one user.
 *
 * A reversal itself writes correction rows (points source_type=fraud_reversal,
 * wallet metadata.fraud_reversal=true); those are never themselves reversible.
 */
class TransactionReversal
{
    /**
     * @return array{reversible: bool, reversed: bool, reversal: array|null, reversal_kind: string|null}
     */
    public static function annotatePoints(PointsTransaction $t): array
    {
        $meta = $t->metadata ?? [];
        $isCorrection = ($t->source_type ?? null) === 'fraud_reversal';

        $kind = null;
        if (! $isCorrection) {
            if ($t->transaction_type === 'transfer_in' && $t->source_id) {
                $kind = 'transfer';
            } elseif ($t->transaction_type === 'conversion') {
                $kind = 'conversion';
            }
        }

        return self::build($kind, $t->status === 'completed', $meta);
    }

    /**
     * @return array{reversible: bool, reversed: bool, reversal: array|null, reversal_kind: string|null}
     */
    public static function annotateWallet(WalletTransaction $t): array
    {
        $meta = $t->metadata ?? [];
        $isCorrection = ! empty($meta['fraud_reversal']);

        $kind = null;
        if (! $isCorrection) {
            if ($t->transaction_type === 'transfer' && isset($meta['from_user_id'])) {
                $kind = 'transfer';
            } elseif ($t->transaction_type === 'conversion') {
                $kind = 'conversion';
            }
        }

        return self::build($kind, $t->status === 'completed', $meta);
    }

    private static function build(?string $kind, bool $completed, array $meta): array
    {
        $reversed = ($meta['fraud_reversed_at'] ?? null) !== null;

        return [
            'reversible' => $kind !== null && $completed && ! $reversed,
            'reversed' => $reversed,
            'reversal' => $reversed ? [
                'at' => $meta['fraud_reversed_at'] ?? null,
                'amount' => $meta['fraud_reversal_amount'] ?? null,
                'shortfall' => $meta['fraud_reversal_shortfall'] ?? null,
                'reason' => $meta['fraud_reversal_reason'] ?? null,
                'by' => $meta['fraud_reversed_by'] ?? null,
            ] : null,
            'reversal_kind' => $kind,
        ];
    }
}
