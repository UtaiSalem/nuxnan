<?php

namespace App\Services;

use App\Models\Course;
use App\Models\CoursePurchase;
use App\Models\User;
use App\Models\WalletDepositRequest;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WalletService
{
    /**
     * Deposit money to user wallet
     */
    public function deposit(User $user, float $amount, string $method, ?string $reference = null, ?string $description = null, ?array $metadata = null): WalletTransaction
    {
        return DB::transaction(function () use ($user, $amount, $method, $reference, $description, $metadata) {
            $user = $this->lockUser($user->id);
            $amount = bcround((string) $amount, 2);
            $balanceBefore = (string) $user->wallet;
            $balanceAfter = bcadd($balanceBefore, $amount, 2);

            // Update user wallet
            $user->update([
                'wallet' => $balanceAfter,
            ]);

            // Create transaction record
            $transaction = WalletTransaction::create([
                'user_id' => $user->id,
                'transaction_type' => 'deposit',
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'currency' => 'THB',
                'description' => $description ?? "ฝากเงินผ่าน {$method}",
                'metadata' => $metadata,
                'reference_number' => $reference,
                'status' => 'completed',
            ]);

            Log::info('Wallet deposit', [
                'user_id' => $user->id,
                'amount' => $amount,
                'method' => $method,
                'reference' => $reference,
            ]);

            return $transaction;
        });
    }

    /**
     * Withdraw money from user wallet
     */
    public function withdraw(User $user, string $amount, string $method, array $bankAccount, ?string $description = null, ?string $idempotencyKey = null): ?WalletTransaction
    {
        return DB::transaction(function () use ($user, $amount, $method, $bankAccount, $description, $idempotencyKey) {
            $user = User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();

            if ($idempotencyKey) {
                $existing = WalletTransaction::where('idempotency_key', $idempotencyKey)->lockForUpdate()->first();
                if ($existing) {
                    return $existing;
                }
            }

            $activeStatuses = ['pending', 'under_review', 'approved', 'processing'];
            $maxPending = (int) config('wallet.withdraw.max_pending_requests', 1);
            if ($maxPending > 0 && WalletTransaction::where('user_id', $user->id)
                ->where('transaction_type', 'withdraw')
                ->whereIn('status', $activeStatuses)
                ->count() >= $maxPending) {
                throw new \DomainException('A withdrawal is already pending');
            }

            $amount = bcround((string) $amount, 2);

            $this->assertWithinWithdrawLimits($user, $amount);

            $balanceBefore = (string) $user->wallet;

            // Check if user has enough spendable balance.
            if (bccomp($balanceBefore, $amount, 2) < 0) {
                Log::warning('Insufficient wallet balance', [
                    'user_id' => $user->id,
                    'required' => $amount,
                    'available' => $balanceBefore,
                ]);

                return null;
            }

            // Calculate fee. Internal deductions incur no fee; real withdrawals use
            // the configured percentage with a minimum floor (see config/wallet.php).
            $fee = $method === 'internal_deduction'
                ? '0.00'
                : bcround(bcmax(
                    bcmul($amount, (string) config('wallet.withdraw.fee_rate'), 6),
                    (string) config('wallet.withdraw.fee_min'),
                    6
                ), 2);
            $netAmount = bcsub($amount, $fee, 2);
            $balanceAfter = bcsub($balanceBefore, $amount, 2);

            // Determine destination channel from the bank_name marker
            $destinationType = ($bankAccount['bank_name'] ?? null) === 'promptpay'
                ? 'promptpay'
                : 'bank_transfer';

            // Move the amount out of spendable wallet and into locked_balance
            // (held until the withdrawal is paid or refunded).
            $user->update([
                'wallet' => $balanceAfter,
                'locked_balance' => bcadd((string) $user->locked_balance, $amount, 2),
            ]);

            // Create transaction record
            $transaction = WalletTransaction::create([
                'user_id' => $user->id,
                'transaction_type' => 'withdraw',
                'amount' => $amount,
                'fee' => $fee,
                'net_amount' => $netAmount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'currency' => 'THB',
                'description' => $description ?? "ถอนเงินผ่าน {$method}",
                'metadata' => [
                    'method' => $method,
                    'destination_type' => $destinationType,
                    'bank_account' => $this->maskBankAccount($bankAccount),
                    'fee' => $fee,
                    'net_amount' => $netAmount,
                ],
                'destination_type' => $destinationType,
                'destination_snapshot' => encrypt($bankAccount),
                'idempotency_key' => $idempotencyKey,
                'status' => 'pending', // Withdrawals need approval
            ]);

            app(AuditLogService::class)->log('withdrawal.created', $transaction, null, [
                'status' => $transaction->status,
                'amount' => $transaction->amount,
                'fee' => $transaction->fee,
            ], 'wallet');

            Log::info('Wallet withdrawal request', [
                'user_id' => $user->id,
                'amount' => $amount,
                'fee' => $fee,
                'net_amount' => $netAmount,
                'method' => $method,
            ]);

            return $transaction;
        });
    }

    /**
     * Transfer money between users
     */
    public function transfer(User $fromUser, User $toUser, float $amount, ?string $message = null): array
    {
        return DB::transaction(function () use ($fromUser, $toUser, $amount, $message) {
            // Lock both rows in a deterministic order (ascending id) to avoid
            // deadlocks when two transfers touch the same pair of users.
            [$fromUser, $toUser] = $this->lockUserPair($fromUser->id, $toUser->id);

            $amount = bcround((string) $amount, 2);
            $fromBalanceBefore = (string) $fromUser->wallet;

            // Check if sender has enough balance
            if (bccomp($fromBalanceBefore, $amount, 2) < 0) {
                return [
                    'success' => false,
                    'message' => 'ยอดเงินของคุณไม่เพียงพอ',
                ];
            }

            $fromBalanceAfter = bcsub($fromBalanceBefore, $amount, 2);
            $toBalanceBefore = (string) $toUser->wallet;
            $toBalanceAfter = bcadd($toBalanceBefore, $amount, 2);

            // Update sender wallet
            $fromUser->update([
                'wallet' => $fromBalanceAfter,
            ]);

            // Update receiver wallet
            $toUser->update([
                'wallet' => $toBalanceAfter,
            ]);

            // Create sender transaction
            WalletTransaction::create([
                'user_id' => $fromUser->id,
                'transaction_type' => 'transfer',
                'amount' => $amount,
                'balance_before' => $fromBalanceBefore,
                'balance_after' => $fromBalanceAfter,
                'currency' => 'THB',
                'description' => $message ?? "โอนเงินให้ {$toUser->username}",
                'metadata' => ['to_user_id' => $toUser->id],
                'status' => 'completed',
            ]);

            // Create receiver transaction
            WalletTransaction::create([
                'user_id' => $toUser->id,
                'transaction_type' => 'transfer',
                'amount' => $amount,
                'balance_before' => $toBalanceBefore,
                'balance_after' => $toBalanceAfter,
                'currency' => 'THB',
                'description' => $message ?? "รับเงินจาก {$fromUser->username}",
                'metadata' => ['from_user_id' => $fromUser->id],
                'status' => 'completed',
            ]);

            Log::info('Wallet transfer', [
                'from_user_id' => $fromUser->id,
                'to_user_id' => $toUser->id,
                'amount' => $amount,
            ]);

            return [
                'success' => true,
                'message' => 'โอนเงินสำเร็จ',
            ];
        });
    }

    /**
     * Add wallet balance from points conversion (called by PointsService)
     * Only handles Wallet side - Points side is handled by PointsService
     */
    public function addFromPointsConversion(User $user, float $walletAmount, int $points, int $exchangeRate): array
    {
        return DB::transaction(function () use ($user, $walletAmount, $points, $exchangeRate) {
            $user = $this->lockUser($user->id);
            $walletAmount = bcround((string) $walletAmount, 2);
            $walletBalanceBefore = (string) $user->wallet;
            $walletBalanceAfter = bcadd($walletBalanceBefore, $walletAmount, 2);

            // Update user wallet
            $user->update([
                'wallet' => $walletBalanceAfter,
            ]);

            // Create wallet transaction (WalletService responsibility)
            $walletTransaction = WalletTransaction::create([
                'user_id' => $user->id,
                'transaction_type' => 'conversion',
                'amount' => $walletAmount,
                'balance_before' => $walletBalanceBefore,
                'balance_after' => $walletBalanceAfter,
                'currency' => 'THB',
                'description' => "รับจากการแปลง {$points} แต้ม",
                'metadata' => [
                    'exchange_rate' => $exchangeRate,
                    'conversion_type' => 'points_to_money',
                    'points_amount' => $points,
                ],
                'status' => 'completed',
            ]);

            return [
                'new_balance' => $walletBalanceAfter,
                'transaction_id' => $walletTransaction->id,
            ];
        });
    }

    /**
     * Convert wallet money to points - handles Wallet side, delegates Points side to PointsService
     */
    public function convertWalletToPoints(User $user, float $amount): array
    {
        return DB::transaction(function () use ($user, $amount) {
            $user = $this->lockUser($user->id);
            $exchangeRate = 1200; // 1 THB = 1200 points
            $amount = bcround((string) $amount, 2);
            $pointsAmount = $amount * $exchangeRate;

            $walletBalanceBefore = (string) $user->wallet;

            // Check if user has enough wallet balance
            if (bccomp($walletBalanceBefore, $amount, 2) < 0) {
                return [
                    'success' => false,
                    'message' => 'ยอดเงินของคุณไม่เพียงพอ',
                ];
            }

            $walletBalanceAfter = bcsub($walletBalanceBefore, $amount, 2);

            // Update user wallet
            $user->update([
                'wallet' => $walletBalanceAfter,
            ]);

            // Create wallet transaction (WalletService responsibility)
            $walletTransaction = WalletTransaction::create([
                'user_id' => $user->id,
                'transaction_type' => 'conversion',
                'amount' => $amount,
                'balance_before' => $walletBalanceBefore,
                'balance_after' => $walletBalanceAfter,
                'currency' => 'THB',
                'description' => 'แปลง '.number_format($amount, 2)." บาท เป็น {$pointsAmount} แต้ม",
                'metadata' => [
                    'exchange_rate' => $exchangeRate,
                    'conversion_type' => 'money_to_points',
                    'points_amount' => $pointsAmount,
                ],
                'status' => 'completed',
            ]);

            // Delegate points addition to PointsService
            $pointsService = new PointsService;
            $pointsResult = $pointsService->addFromWalletConversion($user, $pointsAmount, $amount, $exchangeRate);

            Log::info('Wallet converted to points', [
                'user_id' => $user->id,
                'amount' => $amount,
                'points' => $pointsAmount,
            ]);

            return [
                'success' => true,
                'wallet_amount' => $amount,
                'points_received' => $pointsAmount,
                'new_wallet_balance' => $walletBalanceAfter,
                'new_points_balance' => $pointsResult['new_balance'],
                'wallet_transaction_id' => $walletTransaction->id,
                'points_transaction_id' => $pointsResult['transaction_id'],
            ];
        });
    }

    /**
     * Admin adjust wallet balance
     */
    public function adminAdjust(User $user, float $amount, string $actionType, ?string $reason = null): WalletTransaction
    {
        return DB::transaction(function () use ($user, $amount, $actionType, $reason) {
            $user = $this->lockUser($user->id);
            $amount = bcround((string) $amount, 2);
            $balanceBefore = (string) $user->wallet;

            if ($actionType === 'add') {
                $balanceAfter = bcadd($balanceBefore, $amount, 2);
                $transactionType = 'admin_adjust';
            } elseif ($actionType === 'deduct') {
                $balanceAfter = bcsub($balanceBefore, $amount, 2);
                $transactionType = 'admin_adjust';
            } elseif ($actionType === 'set') {
                $balanceAfter = $amount;
                $transactionType = 'admin_adjust';
            } else {
                throw new \InvalidArgumentException('Invalid action type');
            }

            // Update user wallet
            $user->update([
                'wallet' => $balanceAfter,
            ]);

            // Create transaction record
            $transaction = WalletTransaction::create([
                'user_id' => $user->id,
                'transaction_type' => $transactionType,
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'currency' => 'THB',
                'description' => $reason ?? 'การปรับจาก Admin',
                'metadata' => ['admin_action' => $actionType],
                'status' => 'completed',
            ]);

            Log::info('Wallet adjusted by admin', [
                'user_id' => $user->id,
                'action' => $actionType,
                'amount' => $amount,
                'reason' => $reason,
            ]);

            return $transaction;
        });
    }

    /**
     * Get user wallet balance
     */
    public function getBalance(User $user): array
    {
        $available = bcround((string) $user->wallet, 2);
        $locked = bcround((string) $user->locked_balance, 2);

        return [
            'cash_balance' => $available,
            'reward_balance' => 0, // Can be implemented later
            'locked_balance' => $locked,
            'available_balance' => $available,
            'total_balance' => bcadd($available, $locked, 2),
            'currency' => 'THB',
        ];
    }

    /**
     * Admin opens a pending withdrawal to review it. This records the first
     * reviewer (reviewed_by) and moves it to under_review, which the
     * maker-checker control on approveWithdrawal() depends on.
     */
    public function viewWithdrawal(WalletTransaction $transaction, User $viewer): void
    {
        DB::transaction(function () use ($transaction, $viewer) {
            $tx = WalletTransaction::whereKey($transaction->id)->lockForUpdate()->first();

            if ($tx && $tx->transaction_type === 'withdraw' && $tx->status === 'pending') {
                $tx->update([
                    'status' => 'under_review',
                    'reviewed_by' => $viewer->id,
                    'reviewed_at' => now(),
                    'version' => $tx->version + 1,
                ]);
                app(AuditLogService::class)->log('withdrawal.submitted_for_review', $tx, ['status' => 'pending'], ['status' => 'under_review', 'reviewed_by' => $viewer->id], 'wallet');
            }

            app(AuditLogService::class)->log('withdrawal.viewed', $tx ?? $transaction, null, null, 'wallet', ['viewer_id' => $viewer->id]);
        });
    }

    public function cancelWithdrawal(WalletTransaction $transaction, User $user): bool
    {
        return DB::transaction(function () use ($transaction, $user) {
            $tx = WalletTransaction::whereKey($transaction->id)->lockForUpdate()->first();
            if (! $tx || $tx->transaction_type !== 'withdraw' || $tx->user_id !== $user->id || $tx->status !== 'pending') {
                return false;
            }
            $account = User::whereKey($user->id)->lockForUpdate()->firstOrFail();

            // Money was already deducted from the wallet when the request was
            // created, so a cancellation must refund it with a matching ledger
            // entry (keep this consistent with rejectWithdrawal()).
            $refund = $this->refundWithdrawalToWallet($tx, $account, "คืนเงินจากการยกเลิกคำขอถอน #{$tx->id}", 'withdrawal_cancellation');

            $tx->update([
                'status' => 'cancelled',
                'metadata' => array_merge($tx->metadata ?? [], ['refund_transaction_id' => $refund->id]),
                'version' => $tx->version + 1,
            ]);
            app(AuditLogService::class)->log('withdrawal.cancelled', $tx, ['status' => 'pending'], ['status' => 'cancelled'], 'wallet');

            return true;
        });
    }

    public function processWithdrawal(WalletTransaction $transaction, User $admin): bool
    {
        return $this->transitionWithdrawal($transaction, 'processing', $admin, 'withdrawal.processing');
    }

    public function markWithdrawalPaid(WalletTransaction $transaction, string $paymentReference, User $admin, array $proofData = []): bool
    {
        return DB::transaction(function () use ($transaction, $paymentReference, $admin, $proofData) {
            $tx = WalletTransaction::whereKey($transaction->id)->lockForUpdate()->first();
            if (! $tx || $tx->transaction_type !== 'withdraw' || $tx->status !== 'processing') {
                return false;
            }

            if (! empty($tx->payout_proof_path)) {
                return false;
            }

            // The money genuinely leaves now: release it from locked_balance
            // (it was already removed from spendable wallet at request time).
            $user = User::query()->whereKey($tx->user_id)->lockForUpdate()->firstOrFail();
            $user->update(['locked_balance' => bcsub((string) $user->locked_balance, (string) $tx->amount, 2)]);

            $updateData = [
                'status' => 'paid',
                'payment_reference' => $paymentReference,
                'processed_at' => now(),
                'version' => $tx->version + 1,
            ];

            if (! empty($proofData)) {
                $updateData['payout_proof_path'] = $proofData['path'];
                $updateData['payout_proof_original_name'] = $proofData['original_name'];
                $updateData['payout_proof_mime'] = $proofData['mime'];
                $updateData['payout_proof_size'] = $proofData['size'];
                $updateData['payout_proof_uploaded_by'] = $admin->id;
                $updateData['payout_proof_uploaded_at'] = now();
            }

            $tx->update($updateData);

            app(AuditLogService::class)->log(
                'withdrawal.paid',
                $tx,
                ['status' => 'processing'],
                ['status' => 'paid'],
                'wallet',
                array_merge(['admin_id' => $admin->id], $proofData)
            );

            return true;
        });
    }

    public function markWithdrawalFailed(WalletTransaction $transaction, string $reason, User $admin): bool
    {
        return DB::transaction(function () use ($transaction, $reason, $admin) {
            $tx = WalletTransaction::whereKey($transaction->id)->lockForUpdate()->first();
            if (! $tx || $tx->transaction_type !== 'withdraw' || ! in_array($tx->status, ['approved', 'processing'], true)) {
                return false;
            }

            // The payout failed, but the money was already deducted from the
            // wallet when the request was created. Refund it (with a ledger
            // entry) so the user is not charged for a transfer that never
            // happened.
            $user = User::query()->whereKey($tx->user_id)->lockForUpdate()->firstOrFail();
            $refund = $this->refundWithdrawalToWallet($tx, $user, "คืนเงินจากการโอนล้มเหลว #{$tx->id}", 'withdrawal_failure');

            $tx->update([
                'status' => 'failed',
                'failed_at' => now(),
                'rejection_reason' => $reason,
                'metadata' => array_merge($tx->metadata ?? [], ['refund_transaction_id' => $refund->id]),
                'version' => $tx->version + 1,
            ]);
            app(AuditLogService::class)->log('withdrawal.failed', $tx, null, ['status' => 'failed', 'reason' => $reason, 'refund_transaction_id' => $refund->id], 'wallet', ['admin_id' => $admin->id]);

            return true;
        });
    }

    private function transitionWithdrawal(WalletTransaction $transaction, string $status, User $actor, string $event): bool
    {
        return DB::transaction(function () use ($transaction, $status, $actor, $event) {
            $tx = WalletTransaction::whereKey($transaction->id)->lockForUpdate()->first();
            if (! $tx || ! $tx->canTransitionTo($status)) {
                return false;
            }
            $old = $tx->status;
            $tx->update(['status' => $status, 'version' => $tx->version + 1]);
            app(AuditLogService::class)->log($event, $tx, ['status' => $old], ['status' => $status], 'wallet', ['admin_id' => $actor->id]);

            return true;
        });
    }

    /**
     * Get user wallet transactions
     */
    public function getTransactions(User $user, ?string $type = null, ?string $dateFrom = null, ?string $dateTo = null, int $page = 1, int $perPage = 20): array
    {
        $query = WalletTransaction::where('user_id', $user->id);

        if ($type) {
            $query->where('transaction_type', $type);
        }

        if ($dateFrom && $dateTo) {
            $query->whereBetween('created_at', [$dateFrom, $dateTo]);
        }

        $transactions = $query->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        return [
            'data' => $transactions->items(),
            'pagination' => [
                'current_page' => $transactions->currentPage(),
                'total_pages' => $transactions->lastPage(),
                'total_items' => $transactions->total(),
                'per_page' => $transactions->perPage(),
            ],
        ];
    }

    /**
     * Approve withdrawal
     */
    public function approveWithdrawal(WalletTransaction $transaction, User $reviewer, ?string $adminNote = null, ?string $paymentReference = null): bool
    {
        return DB::transaction(function () use ($transaction, $reviewer, $adminNote, $paymentReference) {
            $tx = WalletTransaction::query()->whereKey($transaction->id)->lockForUpdate()->first();
            if (! $tx || $tx->transaction_type !== 'withdraw' || ! in_array($tx->status, ['pending', 'under_review'], true)) {
                return false;
            }

            // Maker-checker: high-value withdrawals require two distinct admins.
            // A first reviewer must have opened it (reviewed_by, set by
            // viewWithdrawal()) and the approver must be a different person.
            $threshold = (float) config('wallet.withdraw.maker_checker_threshold', PHP_INT_MAX);
            if ((float) $tx->amount >= $threshold
                && ($tx->reviewed_by === null || $tx->reviewed_by === $reviewer->id)) {
                throw new \DomainException('ยอดถอนนี้ต้องให้ผู้ดูแลระบบอีกคนเป็นผู้ตรวจก่อนอนุมัติ (maker-checker)');
            }

            $old = ['status' => $tx->status];
            $tx->update([
                'status' => 'approved',
                'reviewed_by' => $tx->reviewed_by ?? $reviewer->id,
                'reviewed_at' => now(),
                'admin_note' => $adminNote,
                'payment_reference' => $paymentReference,
                'metadata' => array_merge($tx->metadata ?? [], ['approved_by' => $reviewer->id]),
                'version' => $tx->version + 1,
            ]);
            app(AuditLogService::class)->log('withdrawal.approved', $tx, $old, ['status' => $tx->status, 'approved_by' => $reviewer->id], 'wallet');

            return true;
        });
    }

    /**
     * Reject withdrawal
     */
    public function rejectWithdrawal(WalletTransaction $transaction, string $reason, User $reviewer, ?string $adminNote = null): bool
    {
        return DB::transaction(function () use ($transaction, $reason, $reviewer, $adminNote) {
            $tx = WalletTransaction::query()->whereKey($transaction->id)->lockForUpdate()->first();
            if (! $tx || $tx->transaction_type !== 'withdraw' || ! in_array($tx->status, ['pending', 'under_review'], true)) {
                return false;
            }
            $user = User::query()->whereKey($tx->user_id)->lockForUpdate()->firstOrFail();
            $oldStatus = $tx->status;

            // Refund to user wallet with a matching ledger entry.
            $refund = $this->refundWithdrawalToWallet($tx, $user, "คืนเงินจากการปฏิเสธคำขอถอน #{$tx->id}", 'withdrawal_reversal');

            $tx->update([
                'status' => 'rejected',
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
                'rejection_reason' => $reason,
                'admin_note' => $adminNote,
                'metadata' => array_merge($tx->metadata ?? [], ['refund_transaction_id' => $refund->id]),
                'version' => $tx->version + 1,
            ]);
            app(AuditLogService::class)->log('withdrawal.rejected', $tx, ['status' => $oldStatus], ['status' => $tx->status, 'reason' => $reason], 'wallet');

            Log::info('Withdrawal rejected', [
                'transaction_id' => $tx->id,
                'user_id' => $user->id,
                'reason' => $reason,
            ]);

            return true;
        });
    }

    /**
     * Refund a withdrawal's amount back to the user's wallet and record a
     * compensating ledger entry + audit event. Callers MUST already hold row
     * locks on both $tx and $user inside a DB transaction.
     */
    private function refundWithdrawalToWallet(WalletTransaction $tx, User $user, string $description, string $refundType): WalletTransaction
    {
        $balanceBefore = (string) $user->wallet;
        $amount = (string) $tx->amount;
        $balanceAfter = bcadd($balanceBefore, $amount, 2);

        // Return the money to spendable wallet and release it from locked_balance.
        $user->update([
            'wallet' => $balanceAfter,
            'locked_balance' => bcsub((string) $user->locked_balance, $amount, 2),
        ]);

        $refund = WalletTransaction::create([
            'user_id' => $user->id,
            'transaction_type' => 'refund',
            'amount' => $amount,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
            'currency' => $tx->currency,
            'description' => $description,
            'metadata' => ['withdrawal_transaction_id' => $tx->id, 'refund_type' => $refundType],
            'reference_number' => "WDR-{$tx->id}",
            'status' => 'completed',
        ]);

        app(AuditLogService::class)->log('withdrawal.refunded', $refund, null, [
            'withdrawal_transaction_id' => $tx->id,
            'refund_type' => $refundType,
            'amount' => $amount,
        ], 'wallet');

        return $refund;
    }

    /**
     * Enforce per-day and per-month withdrawal ceilings (config/wallet.php).
     * Counts only withdrawals that still hold value (excludes rejected/
     * cancelled/failed which were refunded).
     */
    private function assertWithinWithdrawLimits(User $user, string $amount): void
    {
        $counted = ['pending', 'under_review', 'approved', 'processing', 'completed', 'paid'];

        $daily = (string) config('wallet.withdraw.daily_limit', 0);
        if (bccomp($daily, '0', 2) > 0) {
            $sum = (string) WalletTransaction::where('user_id', $user->id)
                ->where('transaction_type', 'withdraw')
                ->whereIn('status', $counted)
                ->whereDate('created_at', now()->toDateString())
                ->sum('amount');
            if (bccomp(bcadd($sum, $amount, 2), $daily, 2) > 0) {
                throw new \DomainException('เกินวงเงินถอนสูงสุดต่อวัน');
            }
        }

        $monthly = (string) config('wallet.withdraw.monthly_limit', 0);
        if (bccomp($monthly, '0', 2) > 0) {
            $sum = (string) WalletTransaction::where('user_id', $user->id)
                ->where('transaction_type', 'withdraw')
                ->whereIn('status', $counted)
                ->whereYear('created_at', now()->year)
                ->whereMonth('created_at', now()->month)
                ->sum('amount');
            if (bccomp(bcadd($sum, $amount, 2), $monthly, 2) > 0) {
                throw new \DomainException('เกินวงเงินถอนสูงสุดต่อเดือน');
            }
        }
    }

    /**
     * Mask a bank account so only the last 4 digits remain, for safe storage
     * in the (unencrypted) metadata column. Full details live encrypted in
     * destination_snapshot.
     */
    private function maskBankAccount(array $bankAccount): array
    {
        $number = (string) ($bankAccount['account_number'] ?? '');
        $masked = strlen($number) > 4
            ? str_repeat('*', strlen($number) - 4).substr($number, -4)
            : $number;

        return [
            'bank_name' => $bankAccount['bank_name'] ?? '',
            'account_number' => $masked,
            'account_name' => $bankAccount['account_name'] ?? '',
        ];
    }

    /**
     * Record a one-time opening-balance baseline for a user whose current
     * wallet is not fully backed by ledger history (e.g. seeded balances or
     * pre-hardening legacy data). This does NOT move money — it inserts a
     * single reconciling ledger row so that afterwards:
     *
     *     users.wallet == Σ (balance_after - balance_before)
     *
     * Idempotent: a user who already has an opening_balance row, or who is
     * already balanced, is skipped (returns null).
     */
    public function recordOpeningBalance(User $user): ?WalletTransaction
    {
        return DB::transaction(function () use ($user) {
            // Locking blocks any concurrent wallet mutation for this user, so
            // the ledger sum we read below is stable for the insert.
            $user = $this->lockUser($user->id);

            $alreadyBaselined = WalletTransaction::where('user_id', $user->id)
                ->where('transaction_type', 'opening_balance')
                ->exists();
            if ($alreadyBaselined) {
                return null;
            }

            $ledger = (float) WalletTransaction::where('user_id', $user->id)
                ->sum(DB::raw('balance_after - balance_before'));
            $wallet = (float) $user->wallet;
            $diff = round($wallet - $ledger, 2);

            if (abs($diff) < 0.01) {
                return null; // already reconciled — nothing to record
            }

            $tx = WalletTransaction::create([
                'user_id' => $user->id,
                'transaction_type' => 'opening_balance',
                'amount' => number_format(abs($diff), 2, '.', ''),
                'balance_before' => number_format($ledger, 2, '.', ''),
                'balance_after' => number_format($wallet, 2, '.', ''),
                'currency' => 'THB',
                'description' => 'ยอดยกมา (opening balance baseline)',
                'metadata' => [
                    'baseline' => true,
                    'ledger_before' => number_format($ledger, 2, '.', ''),
                    'recorded_at' => now()->toDateTimeString(),
                ],
                'reference_number' => 'OPENING_BALANCE',
                'status' => 'completed',
            ]);

            app(AuditLogService::class)->log('wallet.opening_balance', $tx, null, [
                'wallet' => number_format($wallet, 2, '.', ''),
                'ledger_before' => number_format($ledger, 2, '.', ''),
                'diff' => number_format($diff, 2, '.', ''),
            ], 'wallet');

            return $tx;
        });
    }

    /**
     * Flag a legacy returned withdrawal (rejected/cancelled/failed created by
     * pre-hardening code) that has no paired refund ledger row. Its money is
     * accounted for by the opening-balance baseline, so this only marks it so
     * the refund-integrity report stops treating it as an unrefunded payout.
     * Does not move money. Idempotent.
     */
    public function flagLegacyWithdrawal(WalletTransaction $withdrawal): ?WalletTransaction
    {
        return DB::transaction(function () use ($withdrawal) {
            $tx = WalletTransaction::whereKey($withdrawal->id)->lockForUpdate()->first();

            if (! $tx || $tx->transaction_type !== 'withdraw'
                || ! in_array($tx->status, ['rejected', 'cancelled', 'failed'], true)) {
                return null;
            }

            // A properly refunded (post-hardening) or already-flagged row is skipped.
            if (isset($tx->metadata['refund_transaction_id']) || ! empty($tx->metadata['legacy_no_refund_ledger'])) {
                return null;
            }

            $tx->update([
                'metadata' => array_merge($tx->metadata ?? [], [
                    'legacy_no_refund_ledger' => true,
                    'legacy_flagged_at' => now()->toDateTimeString(),
                ]),
            ]);

            app(AuditLogService::class)->log('withdrawal.flagged_legacy', $tx, null, [
                'reason' => 'returned withdrawal predates the refund-ledger requirement; money reconciled via opening-balance baseline',
            ], 'wallet');

            return $tx;
        });
    }

    /**
     * Pessimistically lock a single user row for a read-modify-write on the
     * wallet. MUST be called inside a DB transaction. Every method that mutates
     * users.wallet goes through a lock so concurrent operations serialize and
     * can never overdraw (money out can never exceed money in).
     */
    private function lockUser(int $id): User
    {
        return User::query()->whereKey($id)->lockForUpdate()->firstOrFail();
    }

    /**
     * Lock several user rows in ascending-id order (deadlock-safe) and return
     * them keyed by id. Used by operations that touch two wallets at once.
     */
    private function lockUsers(array $ids): array
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));
        sort($ids);

        $users = [];
        foreach ($ids as $id) {
            $users[$id] = $this->lockUser($id);
        }

        return $users;
    }

    /**
     * Lock a pair of users deadlock-safely and return them in [from, to] order.
     */
    private function lockUserPair(int $fromId, int $toId): array
    {
        $locked = $this->lockUsers([$fromId, $toId]);

        return [$locked[$fromId], $locked[$toId]];
    }

    /**
     * Approve deposit request
     */
    public function approveDepositRequest(WalletDepositRequest $request, ?string $adminNote = null): bool
    {
        if (! $request->isPending()) {
            return false;
        }

        return DB::transaction(function () use ($request, $adminNote) {
            $user = $this->lockUser($request->user_id);
            $balanceBefore = (string) $user->wallet;
            $balanceAfter = bcadd($balanceBefore, (string) $request->amount, 2);

            // Update user wallet
            $user->update([
                'wallet' => $balanceAfter,
            ]);

            // Create wallet transaction
            $transaction = WalletTransaction::create([
                'user_id' => $user->id,
                'transaction_type' => 'deposit',
                'amount' => $request->amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'currency' => 'THB',
                'description' => "ฝากเงินผ่าน {$request->payment_method_label}",
                'metadata' => [
                    'deposit_request_id' => $request->id,
                    'payment_method' => $request->payment_method,
                    'bank_name' => $request->bank_name,
                    'account_number' => $request->account_number,
                    'reference_number' => $request->reference_number,
                ],
                'reference_number' => $request->reference_number,
                'status' => 'completed',
            ]);

            // Update deposit request
            $request->update([
                'status' => WalletDepositRequest::STATUS_APPROVED,
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
                'admin_note' => $adminNote,
                'wallet_transaction_id' => $transaction->id,
            ]);

            Log::info('Deposit request approved', [
                'request_id' => $request->id,
                'user_id' => $user->id,
                'amount' => $request->amount,
                'transaction_id' => $transaction->id,
            ]);

            return true;
        });
    }

    /**
     * Reject deposit request
     */
    public function rejectDepositRequest(WalletDepositRequest $request, string $reason, ?string $adminNote = null): bool
    {
        if (! $request->isPending()) {
            return false;
        }

        $request->update([
            'status' => WalletDepositRequest::STATUS_REJECTED,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'rejection_reason' => $reason,
            'admin_note' => $adminNote,
        ]);

        Log::info('Deposit request rejected', [
            'request_id' => $request->id,
            'user_id' => $request->user_id,
            'amount' => $request->amount,
            'reason' => $reason,
        ]);

        return true;
    }

    /**
     * Purchase a course - deduct wallet balance and create transaction
     *
     * @param  User  $user  The user making the purchase
     * @param  Course  $course  The course being purchased
     * @param  float|null  $overridePrice  Optional override price (for discounts)
     *
     * @throws \Exception If insufficient balance
     */
    public function purchaseCourse(User $user, Course $course, ?float $overridePrice = null): WalletTransaction
    {
        // Calculate price - use override, tuition_fees, or price
        $originalPrice = bcround((string) ($course->tuition_fees ?? $course->price ?? 0), 2);
        $finalPrice = $overridePrice !== null ? bcround((string) $overridePrice, 2) : $originalPrice;

        // Apply discount if exists and no override
        if ($overridePrice === null && $course->discount > 0) {
            $discountAmount = bcdiv(bcmul($originalPrice, (string) $course->discount, 6), '100', 2);
            $finalPrice = bcsub($originalPrice, $discountAmount, 2);
        }

        return DB::transaction(function () use ($user, $course, $finalPrice, $originalPrice) {
            // Lock the buyer (and the course owner, if a payout is due) up front
            // in a deadlock-safe order so balances can't be raced.
            $ownerId = ($course->user_id && $course->user_id !== $user->id && bccomp($finalPrice, '0', 2) > 0)
                ? (int) $course->user_id
                : null;
            $locked = $this->lockUsers(array_filter([$user->id, $ownerId]));
            $user = $locked[$user->id];

            $balanceBefore = (string) $user->wallet;

            // Check if user has enough balance
            if (bccomp($balanceBefore, $finalPrice, 2) < 0) {
                throw new \Exception('ยอดเงินในกระเป๋าไม่เพียงพอ');
            }

            $balanceAfter = bcsub($balanceBefore, $finalPrice, 2);

            // Update user wallet
            $user->update([
                'wallet' => $balanceAfter,
            ]);

            // Create purchase transaction for buyer
            $transaction = WalletTransaction::create([
                'user_id' => $user->id,
                'transaction_type' => 'purchase',
                'amount' => $finalPrice,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'currency' => 'THB',
                'description' => "ซื้อรายวิชา: {$course->name}",
                'metadata' => [
                    'course_id' => $course->id,
                    'course_name' => $course->name,
                    'original_price' => $originalPrice,
                    'discount' => $course->discount ?? 0,
                    'final_price' => $finalPrice,
                ],
                'status' => 'completed',
            ]);

            // Pay to course owner if course has user_id
            if ($ownerId) {
                $owner = $locked[$ownerId] ?? null;
                if ($owner) {
                    $ownerBalanceBefore = (string) $owner->wallet;
                    $ownerBalanceAfter = bcadd($ownerBalanceBefore, $finalPrice, 2);

                    $owner->update([
                        'wallet' => $ownerBalanceAfter,
                    ]);

                    // Create income transaction for course owner
                    WalletTransaction::create([
                        'user_id' => $owner->id,
                        'transaction_type' => 'course_income',
                        'amount' => $finalPrice,
                        'balance_before' => $ownerBalanceBefore,
                        'balance_after' => $ownerBalanceAfter,
                        'currency' => 'THB',
                        'description' => "รายได้จากการขายรายวิชา: {$course->name}",
                        'metadata' => [
                            'course_id' => $course->id,
                            'course_name' => $course->name,
                            'buyer_id' => $user->id,
                            'buyer_name' => $user->name,
                        ],
                        'status' => 'completed',
                    ]);
                }
            }

            Log::info('Course purchased', [
                'user_id' => $user->id,
                'course_id' => $course->id,
                'original_price' => $originalPrice,
                'final_price' => $finalPrice,
                'transaction_id' => $transaction->id,
            ]);

            return $transaction;
        });
    }

    /**
     * Refund a course purchase
     *
     * @param  User  $user  The user to refund
     * @param  Course  $course  The course being refunded
     * @param  float  $amount  The amount to refund
     * @param  string|null  $reason  The reason for refund
     */
    public function refundCoursePurchase(User $user, Course $course, float $amount, ?string $reason = null): WalletTransaction
    {
        return DB::transaction(function () use ($user, $course, $amount, $reason) {
            $user = $this->lockUser($user->id);
            $amount = bcround((string) $amount, 2);
            $balanceBefore = (string) $user->wallet;
            $balanceAfter = bcadd($balanceBefore, $amount, 2);

            // Update user wallet
            $user->update([
                'wallet' => $balanceAfter,
            ]);

            // Create refund transaction
            $transaction = WalletTransaction::create([
                'user_id' => $user->id,
                'transaction_type' => 'refund',
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'currency' => 'THB',
                'description' => "คืนเงินจากรายวิชา: {$course->name}",
                'metadata' => [
                    'course_id' => $course->id,
                    'course_name' => $course->name,
                    'refund_reason' => $reason,
                ],
                'status' => 'completed',
            ]);

            Log::info('Course refunded', [
                'user_id' => $user->id,
                'course_id' => $course->id,
                'amount' => $amount,
                'reason' => $reason,
            ]);

            return $transaction;
        });
    }

    /**
     * Check if user has purchased a course
     */
    public function hasPurchased(User $user, Course $course): bool
    {
        // First check CoursePurchase table (new reliable source)
        $hasRecord = CoursePurchase::where('buyer_id', $user->id)
            ->where('source_course_id', $course->id)
            ->whereIn('status', ['completed', 'pending_clone', 'paid'])
            ->exists();

        if ($hasRecord) {
            return true;
        }

        // Fallback for old data: check WalletTransaction
        $hasLegacyTransaction = WalletTransaction::where('user_id', $user->id)
            ->where('transaction_type', 'purchase')
            ->where('status', 'completed')
            ->whereJsonContains('metadata->course_id', $course->id)
            ->exists();

        if ($hasLegacyTransaction) {
            return true;
        }

        // Double check by looking for actual cloned course owned by user
        return Course::where('user_id', $user->id)
            ->where('source_course_id', $course->id)
            ->exists();
    }
}
