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
