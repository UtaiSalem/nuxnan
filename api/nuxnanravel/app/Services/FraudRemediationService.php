<?php

namespace App\Services;

use App\Models\PointsTransaction;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;

/**
 * Admin fraud-remediation actions: reverse a fraudulent peer transfer (clawing
 * the value back from the recipient to the original sender) and freeze/unfreeze
 * a member's points and money wallets.
 *
 * Reversal policy (owner decision): claw back up to the recipient's *available*
 * balance. If the fraudster has already spent or withdrawn part of it, only the
 * remainder is returned and the shortfall is recorded on the original row — the
 * freeze is the tool that stops further loss.
 *
 * Every action runs in a locked DB transaction, is idempotent (a transfer can
 * only be reversed once), and is audit-logged.
 */
class FraudRemediationService
{
    public function __construct(protected AuditLogService $audit) {}

    /**
     * Reverse a fraudulent POINTS transfer. $transaction is the recipient's
     * `transfer_in` row (user_id = recipient, source_id = original sender).
     */
    public function reversePointsTransfer(PointsTransaction $transaction, User $admin, string $reason): array
    {
        if ($transaction->transaction_type !== 'transfer_in' || ! $transaction->source_id) {
            throw new \DomainException('รายการนี้ไม่ใช่การรับโอนแต้มที่ย้อนกลับได้');
        }

        return DB::transaction(function () use ($transaction, $admin, $reason) {
            $tx = PointsTransaction::whereKey($transaction->id)->lockForUpdate()->firstOrFail();
            if (($tx->metadata['fraud_reversed_at'] ?? null) !== null) {
                throw new \DomainException('รายการนี้ถูกย้อนกลับไปแล้ว');
            }

            $recipientId = (int) $tx->user_id;
            $senderId = (int) $tx->source_id;
            $amount = (float) $tx->amount;

            // Lock both users in a deterministic order to avoid deadlocks.
            [$lowId, $highId] = $recipientId < $senderId ? [$recipientId, $senderId] : [$senderId, $recipientId];
            $locked = User::whereIn('id', [$lowId, $highId])->lockForUpdate()->get()->keyBy('id');
            $recipient = $locked->get($recipientId);
            $sender = $locked->get($senderId);
            if (! $recipient || ! $sender) {
                throw new \DomainException('ไม่พบบัญชีต้นทาง/ปลายทางของการโอน');
            }

            $available = (float) $recipient->pp;
            $reversed = max(0.0, min($amount, $available));
            $shortfall = round($amount - $reversed, 2);

            if ($reversed > 0) {
                $rBefore = (float) $recipient->pp;
                $rAfter = round($rBefore - $reversed, 2);
                $recipient->update(['pp' => $rAfter]);
                PointsTransaction::create([
                    'user_id' => $recipientId,
                    'transaction_type' => 'transfer_out',
                    'amount' => $reversed,
                    'balance_before' => $rBefore,
                    'balance_after' => $rAfter,
                    'source_type' => 'fraud_reversal',
                    'source_id' => $senderId,
                    'description' => "ยกเลิก/ดึงแต้มคืนจากการทุจริต (อ้างอิง #{$tx->id})",
                    'metadata' => ['reversal_of' => $tx->id, 'admin_id' => $admin->id],
                    'status' => 'completed',
                ]);

                $sBefore = (float) $sender->pp;
                $sAfter = round($sBefore + $reversed, 2);
                $sender->update(['pp' => $sAfter]);
                PointsTransaction::create([
                    'user_id' => $senderId,
                    'transaction_type' => 'transfer_in',
                    'amount' => $reversed,
                    'balance_before' => $sBefore,
                    'balance_after' => $sAfter,
                    'source_type' => 'fraud_reversal',
                    'source_id' => $recipientId,
                    'description' => "รับแต้มคืนจากการย้อนรายการทุจริต (อ้างอิง #{$tx->id})",
                    'metadata' => ['reversal_of' => $tx->id, 'admin_id' => $admin->id],
                    'status' => 'completed',
                ]);
            }

            $tx->update(['metadata' => array_merge($tx->metadata ?? [], [
                'fraud_reversed_at' => now()->toIso8601String(),
                'fraud_reversed_by' => $admin->id,
                'fraud_reversal_amount' => $reversed,
                'fraud_reversal_shortfall' => $shortfall,
                'fraud_reversal_reason' => $reason,
            ])]);

            $this->audit->log('points.transfer_reversed', $tx, null, null, 'fraud', [
                'admin_id' => $admin->id, 'recipient_id' => $recipientId, 'sender_id' => $senderId,
                'amount' => $amount, 'reversed' => $reversed, 'shortfall' => $shortfall, 'reason' => $reason,
            ]);

            return ['success' => true, 'unit' => 'points', 'amount' => $amount, 'reversed' => $reversed, 'shortfall' => $shortfall];
        });
    }

    /**
     * Reverse a fraudulent WALLET transfer. $transaction is the recipient's
     * `transfer` row (user_id = recipient, metadata.from_user_id = sender).
     */
    public function reverseWalletTransfer(WalletTransaction $transaction, User $admin, string $reason): array
    {
        $senderId = $transaction->metadata['from_user_id'] ?? null;
        if ($transaction->transaction_type !== 'transfer' || ! $senderId) {
            throw new \DomainException('รายการนี้ไม่ใช่การรับโอนเงินที่ย้อนกลับได้');
        }

        return DB::transaction(function () use ($transaction, $admin, $reason, $senderId) {
            $tx = WalletTransaction::whereKey($transaction->id)->lockForUpdate()->firstOrFail();
            if (($tx->metadata['fraud_reversed_at'] ?? null) !== null) {
                throw new \DomainException('รายการนี้ถูกย้อนกลับไปแล้ว');
            }

            $recipientId = (int) $tx->user_id;
            $senderId = (int) $senderId;
            $amount = (string) $tx->amount;

            [$lowId, $highId] = $recipientId < $senderId ? [$recipientId, $senderId] : [$senderId, $recipientId];
            $locked = User::whereIn('id', [$lowId, $highId])->lockForUpdate()->get()->keyBy('id');
            $recipient = $locked->get($recipientId);
            $sender = $locked->get($senderId);
            if (! $recipient || ! $sender) {
                throw new \DomainException('ไม่พบบัญชีต้นทาง/ปลายทางของการโอน');
            }

            // Claw back up to the recipient's available (spendable) balance.
            $available = (string) $recipient->wallet;
            $reversed = bccomp($amount, $available, 2) <= 0 ? bcround($amount, 2) : bcround($available, 2);
            if (bccomp($reversed, '0', 2) < 0) {
                $reversed = '0.00';
            }
            $shortfall = bcsub($amount, $reversed, 2);

            if (bccomp($reversed, '0', 2) > 0) {
                $rBefore = (string) $recipient->wallet;
                $rAfter = bcsub($rBefore, $reversed, 2);
                $recipient->update(['wallet' => $rAfter]);
                WalletTransaction::create([
                    'user_id' => $recipientId,
                    'transaction_type' => 'transfer',
                    'amount' => $reversed,
                    'balance_before' => $rBefore,
                    'balance_after' => $rAfter,
                    'currency' => 'THB',
                    'description' => "ยกเลิก/ดึงเงินคืนจากการทุจริต (อ้างอิง #{$tx->id})",
                    'metadata' => ['to_user_id' => $senderId, 'reversal_of' => $tx->id, 'fraud_reversal' => true, 'admin_id' => $admin->id],
                    'status' => 'completed',
                ]);

                $sBefore = (string) $sender->wallet;
                $sAfter = bcadd($sBefore, $reversed, 2);
                $sender->update(['wallet' => $sAfter]);
                WalletTransaction::create([
                    'user_id' => $senderId,
                    'transaction_type' => 'transfer',
                    'amount' => $reversed,
                    'balance_before' => $sBefore,
                    'balance_after' => $sAfter,
                    'currency' => 'THB',
                    'description' => "รับเงินคืนจากการย้อนรายการทุจริต (อ้างอิง #{$tx->id})",
                    'metadata' => ['from_user_id' => $recipientId, 'reversal_of' => $tx->id, 'fraud_reversal' => true, 'admin_id' => $admin->id],
                    'status' => 'completed',
                ]);
            }

            $tx->update(['metadata' => array_merge($tx->metadata ?? [], [
                'fraud_reversed_at' => now()->toIso8601String(),
                'fraud_reversed_by' => $admin->id,
                'fraud_reversal_amount' => $reversed,
                'fraud_reversal_shortfall' => $shortfall,
                'fraud_reversal_reason' => $reason,
            ])]);

            $this->audit->log('wallet.transfer_reversed', $tx, null, null, 'fraud', [
                'admin_id' => $admin->id, 'recipient_id' => $recipientId, 'sender_id' => $senderId,
                'amount' => $amount, 'reversed' => $reversed, 'shortfall' => $shortfall, 'reason' => $reason,
            ]);

            return ['success' => true, 'unit' => 'THB', 'amount' => (float) $amount, 'reversed' => (float) $reversed, 'shortfall' => (float) $shortfall];
        });
    }

    /**
     * Reverse an intra-user POINTS↔MONEY conversion, given either ledger leg.
     *
     * Same owner-approved policy as a transfer: claw back up to the recipient
     * side's *available* balance and restore the other side at the stored
     * exchange rate (proportional to what could be clawed back); any shortfall
     * is recorded on the row. Both legs are marked so neither can be reversed
     * again, and correction rows are written on each side.
     */
    public function reverseConversion(PointsTransaction|WalletTransaction $transaction, User $admin, string $reason): array
    {
        $meta = $transaction->metadata ?? [];
        if ($transaction->transaction_type !== 'conversion' || ! empty($meta['fraud_reversal'])) {
            throw new \DomainException('รายการนี้ไม่ใช่การแปลงแต้ม/เงินที่ย้อนกลับได้');
        }

        $conversionType = $meta['conversion_type'] ?? null;
        if (! in_array($conversionType, ['points_to_money', 'money_to_points'], true)) {
            throw new \DomainException('ไม่พบทิศทางการแปลงของรายการนี้');
        }

        $rate = (int) ($meta['exchange_rate'] ?? 0);
        if ($rate <= 0) {
            throw new \DomainException('ไม่พบอัตราแลกเปลี่ยนของรายการนี้');
        }

        // Derive the points and money amounts from whichever leg we were given.
        $isPointsLeg = $transaction instanceof PointsTransaction;
        if ($isPointsLeg) {
            $pointsAmount = (float) $transaction->amount;
            $walletNominal = bcround((string) ($meta['wallet_amount'] ?? 0), 2);
        } else {
            $walletNominal = bcround((string) $transaction->amount, 2);
            $pointsAmount = (float) ($meta['points_amount'] ?? 0);
        }
        if ($pointsAmount <= 0 || bccomp($walletNominal, '0', 2) <= 0) {
            throw new \DomainException('ไม่พบจำนวนที่ใช้แปลงของรายการนี้');
        }

        return DB::transaction(function () use ($transaction, $admin, $reason, $conversionType, $rate, $pointsAmount, $walletNominal) {
            $class = get_class($transaction);
            $given = $class::whereKey($transaction->id)->lockForUpdate()->firstOrFail();
            if (($given->metadata['fraud_reversed_at'] ?? null) !== null) {
                throw new \DomainException('รายการนี้ถูกย้อนกลับไปแล้ว');
            }

            // Find (and lock) the matching leg in the other ledger.
            [$counterpart, $exhausted] = $this->findConversionCounterpart($given, $conversionType, $pointsAmount, $walletNominal);
            if ($counterpart === null && $exhausted) {
                throw new \DomainException('รายการนี้ถูกย้อนกลับไปแล้ว');
            }

            $user = User::whereKey($given->user_id)->lockForUpdate()->firstOrFail();
            $userId = (int) $user->id;

            if ($conversionType === 'points_to_money') {
                // User gave points, received money. Credited side = wallet.
                $availWallet = (string) $user->wallet;
                $clawWallet = bccomp($walletNominal, $availWallet, 2) <= 0 ? $walletNominal : bcround($availWallet, 2);
                if (bccomp($clawWallet, '0', 2) < 0) {
                    $clawWallet = '0.00';
                }
                $restorePoints = min($pointsAmount, round((float) $clawWallet * $rate, 2));
                $shortfall = bcsub($walletNominal, $clawWallet, 2); // THB still owed

                $wBefore = (string) $user->wallet;
                $wAfter = bcsub($wBefore, $clawWallet, 2);
                $pBefore = (float) $user->pp;
                $pAfter = round($pBefore + $restorePoints, 2);
                $user->update(['wallet' => $wAfter, 'pp' => $pAfter]);

                if (bccomp($clawWallet, '0', 2) > 0) {
                    $this->writeWalletCorrection($userId, $clawWallet, $wBefore, $wAfter, $given->id, $admin, 'ยกเลิกการแปลง: ดึงเงินคืน');
                }
                if ($restorePoints > 0) {
                    $this->writePointsCorrection($userId, $restorePoints, $pBefore, $pAfter, $given->id, $admin, 'ยกเลิกการแปลง: คืนแต้ม');
                }

                $clawed = (float) $clawWallet;
                $shortfallNum = (float) $shortfall;
                $result = [
                    'success' => true, 'kind' => 'conversion', 'unit' => 'THB',
                    'amount' => (float) $walletNominal, 'reversed' => $clawed, 'shortfall' => $shortfallNum,
                    'restored' => $restorePoints, 'restored_unit' => 'points',
                ];
            } else {
                // User gave money, received points. Credited side = points.
                $availPoints = (float) $user->pp;
                $clawPoints = max(0.0, min($pointsAmount, $availPoints));
                $restoreWalletStr = bcround((string) min((float) $walletNominal, round($clawPoints / $rate, 2)), 2);
                $shortfall = round($pointsAmount - $clawPoints, 2); // points still owed

                $pBefore = (float) $user->pp;
                $pAfter = round($pBefore - $clawPoints, 2);
                $wBefore = (string) $user->wallet;
                $wAfter = bcadd($wBefore, $restoreWalletStr, 2);
                $user->update(['pp' => $pAfter, 'wallet' => $wAfter]);

                if ($clawPoints > 0) {
                    $this->writePointsCorrection($userId, $clawPoints, $pBefore, $pAfter, $given->id, $admin, 'ยกเลิกการแปลง: ดึงแต้มคืน');
                }
                if (bccomp($restoreWalletStr, '0', 2) > 0) {
                    $this->writeWalletCorrection($userId, $restoreWalletStr, $wBefore, $wAfter, $given->id, $admin, 'ยกเลิกการแปลง: คืนเงิน');
                }

                $result = [
                    'success' => true, 'kind' => 'conversion', 'unit' => 'points',
                    'amount' => $pointsAmount, 'reversed' => $clawPoints, 'shortfall' => $shortfall,
                    'restored' => (float) $restoreWalletStr, 'restored_unit' => 'THB',
                ];
            }

            // Stamp both legs so neither can be reversed twice.
            $this->stampReversed($given, $result['reversed'], $result['shortfall'], $admin, $reason, $given->id);
            if ($counterpart) {
                $this->stampReversed($counterpart, $result['reversed'], $result['shortfall'], $admin, $reason, $given->id);
            }

            $this->audit->log('conversion.reversed', $given, null, null, 'fraud', [
                'admin_id' => $admin->id, 'user_id' => $userId, 'conversion_type' => $conversionType,
                'reversed' => $result['reversed'], 'shortfall' => $result['shortfall'], 'restored' => $result['restored'],
                'reason' => $reason, 'counterpart_id' => $counterpart?->id,
            ]);

            return $result;
        });
    }

    /**
     * Locate (and lock) the opposite ledger leg of a conversion. Returns
     * [match|null, exhausted] where `exhausted` means candidates exist but are
     * all already reversed (so the conversion was handled from the other side).
     */
    private function findConversionCounterpart(PointsTransaction|WalletTransaction $given, string $conversionType, float $pointsAmount, string $walletNominal): array
    {
        if ($given instanceof PointsTransaction) {
            $lo = bcsub($walletNominal, '0.01', 2);
            $hi = bcadd($walletNominal, '0.01', 2);
            $candidates = WalletTransaction::where('user_id', $given->user_id)
                ->where('transaction_type', 'conversion')
                ->whereBetween('amount', [$lo, $hi])
                ->lockForUpdate()->get()
                ->filter(function (WalletTransaction $w) use ($conversionType, $pointsAmount) {
                    $m = $w->metadata ?? [];

                    return empty($m['fraud_reversal'])
                        && ($m['conversion_type'] ?? null) === $conversionType
                        && abs((float) ($m['points_amount'] ?? 0) - $pointsAmount) < 0.5;
                })->values();
        } else {
            $candidates = PointsTransaction::where('user_id', $given->user_id)
                ->where('transaction_type', 'conversion')
                ->whereBetween('amount', [$pointsAmount - 0.5, $pointsAmount + 0.5])
                ->lockForUpdate()->get()
                ->filter(function (PointsTransaction $p) use ($conversionType, $walletNominal) {
                    $m = $p->metadata ?? [];

                    return ($p->source_type ?? null) !== 'fraud_reversal'
                        && ($m['conversion_type'] ?? null) === $conversionType
                        && abs((float) ($m['wallet_amount'] ?? 0) - (float) $walletNominal) < 0.01;
                })->values();
        }

        $match = $candidates->first(fn ($c) => (($c->metadata['fraud_reversed_at'] ?? null) === null));

        return [$match, $match === null && $candidates->isNotEmpty()];
    }

    private function writePointsCorrection(int $userId, float $amount, float $before, float $after, int $refId, User $admin, string $description): void
    {
        PointsTransaction::create([
            'user_id' => $userId,
            'transaction_type' => 'conversion',
            'amount' => $amount,
            'balance_before' => $before,
            'balance_after' => $after,
            'source_type' => 'fraud_reversal',
            'description' => "{$description} (อ้างอิง #{$refId})",
            'metadata' => ['reversal_of' => $refId, 'fraud_reversal' => true, 'admin_id' => $admin->id],
            'status' => 'completed',
        ]);
    }

    private function writeWalletCorrection(int $userId, string $amount, string $before, string $after, int $refId, User $admin, string $description): void
    {
        WalletTransaction::create([
            'user_id' => $userId,
            'transaction_type' => 'conversion',
            'amount' => $amount,
            'balance_before' => $before,
            'balance_after' => $after,
            'currency' => 'THB',
            'description' => "{$description} (อ้างอิง #{$refId})",
            'metadata' => ['reversal_of' => $refId, 'fraud_reversal' => true, 'admin_id' => $admin->id],
            'status' => 'completed',
        ]);
    }

    private function stampReversed(PointsTransaction|WalletTransaction $row, float $reversed, float $shortfall, User $admin, string $reason, int $refId): void
    {
        $row->update(['metadata' => array_merge($row->metadata ?? [], [
            'fraud_reversed_at' => now()->toIso8601String(),
            'fraud_reversed_by' => $admin->id,
            'fraud_reversal_amount' => $reversed,
            'fraud_reversal_shortfall' => $shortfall,
            'fraud_reversal_reason' => $reason,
            'fraud_reversal_of' => $refId,
        ])]);
    }

    /**
     * Freeze one or both of a member's wallets. $scope ∈ points|wallet|both.
     */
    public function freeze(User $target, string $scope, User $admin, string $reason): User
    {
        return DB::transaction(function () use ($target, $scope, $admin, $reason) {
            $user = User::whereKey($target->id)->lockForUpdate()->firstOrFail();
            $now = now();
            $data = ['freeze_reason' => $reason, 'frozen_by' => $admin->id];
            if (in_array($scope, ['points', 'both'], true)) {
                $data['points_frozen_at'] = $now;
            }
            if (in_array($scope, ['wallet', 'both'], true)) {
                $data['wallet_frozen_at'] = $now;
            }
            $user->update($data);

            $this->audit->log('user.wallet_frozen', $user, null, null, 'fraud', [
                'admin_id' => $admin->id, 'scope' => $scope, 'reason' => $reason,
            ]);

            return $user;
        });
    }

    /**
     * Lift a freeze on one or both wallets. $scope ∈ points|wallet|both.
     */
    public function unfreeze(User $target, string $scope, User $admin): User
    {
        return DB::transaction(function () use ($target, $scope, $admin) {
            $user = User::whereKey($target->id)->lockForUpdate()->firstOrFail();
            $data = [];
            if (in_array($scope, ['points', 'both'], true)) {
                $data['points_frozen_at'] = null;
            }
            if (in_array($scope, ['wallet', 'both'], true)) {
                $data['wallet_frozen_at'] = null;
            }
            $user->update($data);

            // Clear the shared reason/actor once nothing remains frozen.
            if (! $user->isPointsFrozen() && ! $user->isWalletFrozen()) {
                $user->update(['freeze_reason' => null, 'frozen_by' => null]);
            }

            $this->audit->log('user.wallet_unfrozen', $user, null, null, 'fraud', [
                'admin_id' => $admin->id, 'scope' => $scope,
            ]);

            return $user;
        });
    }
}
