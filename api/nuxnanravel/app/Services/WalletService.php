<?php

namespace App\Services;

use App\Models\WalletTransaction;
use App\Models\PointsTransaction;
use App\Models\User;
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
            $balanceBefore = $user->wallet;
            $balanceAfter = $balanceBefore + $amount;

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
    public function withdraw(User $user, float $amount, string $method, array $bankAccount, ?string $description = null): ?WalletTransaction
    {
        return DB::transaction(function () use ($user, $amount, $method, $bankAccount, $description) {
            $balanceBefore = $user->wallet;

            // Check if user has enough balance
            if ($balanceBefore < $amount) {
                Log::warning('Insufficient wallet balance', [
                    'user_id' => $user->id,
                    'required' => $amount,
                    'available' => $balanceBefore,
                ]);
                return null;
            }

            // Calculate fee (0.5% min 10 THB)
            $fee = max($amount * 0.005, 10);
            $netAmount = $amount - $fee;
            $balanceAfter = $balanceBefore - $amount;

            // Update user wallet
            $user->update([
                'wallet' => $balanceAfter,
            ]);

            // Create transaction record
            $transaction = WalletTransaction::create([
                'user_id' => $user->id,
                'transaction_type' => 'withdraw',
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'currency' => 'THB',
                'description' => $description ?? "ถอนเงินผ่าน {$method}",
                'metadata' => [
                    'method' => $method,
                    'bank_account' => $bankAccount,
                    'fee' => $fee,
                    'net_amount' => $netAmount,
                ],
                'status' => 'pending', // Withdrawals need approval
            ]);

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
            $fromBalanceBefore = $fromUser->wallet;

            // Check if sender has enough balance
            if ($fromBalanceBefore < $amount) {
                return [
                    'success' => false,
                    'message' => 'ยอดเงินของคุณไม่เพียงพอ',
                ];
            }

            $fromBalanceAfter = $fromBalanceBefore - $amount;
            $toBalanceBefore = $toUser->wallet;
            $toBalanceAfter = $toBalanceBefore + $amount;

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
        $walletBalanceBefore = $user->wallet;
        $walletBalanceAfter = $walletBalanceBefore + $walletAmount;

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
    }

    /**
     * Convert wallet money to points - handles Wallet side, delegates Points side to PointsService
     */
    public function convertWalletToPoints(User $user, float $amount): array
    {
        return DB::transaction(function () use ($user, $amount) {
            $exchangeRate = 1080; // 1 THB = 1080 points
            $pointsAmount = $amount * $exchangeRate;

            $walletBalanceBefore = $user->wallet;

            // Check if user has enough wallet balance
            if ($walletBalanceBefore < $amount) {
                return [
                    'success' => false,
                    'message' => 'ยอดเงินของคุณไม่เพียงพอ',
                ];
            }

            $walletBalanceAfter = $walletBalanceBefore - $amount;

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
                'description' => "แปลง " . number_format($amount, 2) . " บาท เป็น {$pointsAmount} แต้ม",
                'metadata' => [
                    'exchange_rate' => $exchangeRate,
                    'conversion_type' => 'money_to_points',
                    'points_amount' => $pointsAmount,
                ],
                'status' => 'completed',
            ]);

            // Delegate points addition to PointsService
            $pointsService = new \App\Services\PointsService();
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
            $balanceBefore = $user->wallet;

            if ($actionType === 'add') {
                $balanceAfter = $balanceBefore + $amount;
                $transactionType = 'admin_adjust';
            } elseif ($actionType === 'deduct') {
                $balanceAfter = $balanceBefore - $amount;
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
                'description' => $reason ?? "การปรับจาก Admin",
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
        return [
            'cash_balance' => $user->wallet,
            'reward_balance' => 0, // Can be implemented later
            'locked_balance' => 0, // Can be implemented later
            'total_balance' => $user->wallet,
            'currency' => 'THB',
        ];
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
    public function approveWithdrawal(WalletTransaction $transaction): bool
    {
        if ($transaction->transaction_type !== 'withdraw' || $transaction->status !== 'pending') {
            return false;
        }

        $transaction->update([
            'status' => 'completed',
        ]);

        Log::info('Withdrawal approved', [
            'transaction_id' => $transaction->id,
            'user_id' => $transaction->user_id,
        ]);

        return true;
    }

    /**
     * Reject withdrawal
     */
    public function rejectWithdrawal(WalletTransaction $transaction, string $reason): bool
    {
        if ($transaction->transaction_type !== 'withdraw' || $transaction->status !== 'pending') {
            return false;
        }

        return DB::transaction(function () use ($transaction, $reason) {
            $user = $transaction->user;
            $balanceBefore = $user->wallet;
            $amount = $transaction->amount;

            // Refund to user wallet
            $user->update([
                'wallet' => $balanceBefore + $amount,
            ]);

            // Update transaction status
            $transaction->update([
                'status' => 'cancelled',
                'metadata' => array_merge($transaction->metadata ?? [], ['rejection_reason' => $reason]),
            ]);

            Log::info('Withdrawal rejected', [
                'transaction_id' => $transaction->id,
                'user_id' => $user->id,
                'reason' => $reason,
            ]);

            return true;
        });
    }
}
