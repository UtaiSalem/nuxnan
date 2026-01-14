<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Models\Reward;

class AdminWalletController extends Controller
{
    protected WalletService $walletService;

    public function __construct(WalletService $walletService)
    {
        $this->walletService = $walletService;
    }

    /**
     * Get wallet statistics
     */
    public function stats(): JsonResponse
    {
        $totalBalance = User::sum('wallet');
        $totalUsers = User::count();
        $activeUsers = User::where('wallet', '>', 0)->count();

        // Get transaction stats
        $totalTransactions = WalletTransaction::count();
        $pendingTransactions = WalletTransaction::where('status', 'pending')->count();
        $completedTransactions = WalletTransaction::where('status', 'completed')->count();
        $failedTransactions = WalletTransaction::where('status', 'failed')->count();

        // Get today's stats
        $today = now()->toDateString();
        $todayDeposits = WalletTransaction::whereDate('created_at', $today)
            ->where('transaction_type', 'deposit')
            ->sum('amount');
        $todayWithdrawals = WalletTransaction::whereDate('created_at', $today)
            ->where('transaction_type', 'withdraw')
            ->sum('amount');
        $todayConversions = WalletTransaction::whereDate('created_at', $today)
            ->where('transaction_type', 'conversion')
            ->sum('amount');

        // Get pending withdrawals amount
        $pendingWithdrawalsAmount = WalletTransaction::where('status', 'pending')
            ->where('transaction_type', 'withdraw')
            ->sum('amount');

        return response()->json([
            'success' => true,
            'data' => [
                'total_balance' => $totalBalance,
                'total_users' => $totalUsers,
                'active_users' => $activeUsers,
                'total_transactions' => $totalTransactions,
                'pending_transactions' => $pendingTransactions,
                'completed_transactions' => $completedTransactions,
                'failed_transactions' => $failedTransactions,
                'today_deposits' => $todayDeposits,
                'today_withdrawals' => $todayWithdrawals,
                'today_conversions' => $todayConversions,
                'pending_withdrawals_amount' => $pendingWithdrawalsAmount,
            ]
        ]);
    }

    /**
     * Get pending withdrawals
     */
    public function pendingWithdrawals(Request $request): JsonResponse
    {
        $withdrawals = WalletTransaction::with(['user:id,name,email,avatar'])
            ->where('transaction_type', 'withdraw')
            ->where('status', 'pending')
            ->orderBy('created_at', 'asc')
            ->paginate($request->input('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $withdrawals
        ]);
    }

    /**
     * Approve a withdrawal
     */
    public function approveWithdrawal(Request $request, $transactionId): JsonResponse
    {
        $validated = $request->validate([
            'admin_notes' => 'nullable|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            $transaction = WalletTransaction::findOrFail($transactionId);

            if ($transaction->transaction_type !== 'withdraw') {
                return response()->json([
                    'success' => false,
                    'message' => 'Transaction is not a withdrawal'
                ], 400);
            }

            if ($transaction->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Transaction is not in pending status'
                ], 400);
            }

            $result = $this->walletService->approveWithdrawal($transactionId, $validated['admin_notes'] ?? null);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Withdrawal approved successfully',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to approve withdrawal: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reject a withdrawal
     */
    public function rejectWithdrawal(Request $request, $transactionId): JsonResponse
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            $transaction = WalletTransaction::findOrFail($transactionId);

            if ($transaction->transaction_type !== 'withdraw') {
                return response()->json([
                    'success' => false,
                    'message' => 'Transaction is not a withdrawal'
                ], 400);
            }

            if ($transaction->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Transaction is not in pending status'
                ], 400);
            }

            $result = $this->walletService->rejectWithdrawal($transactionId, $validated['reason']);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Withdrawal rejected successfully',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to reject withdrawal: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Adjust user wallet balance
     */
    public function adjustWallet(Request $request, $userId): JsonResponse
    {
        $validated = $request->validate([
            'action' => 'required|in:add,deduct,set',
            'amount' => 'required|numeric|min:0',
            'reason' => 'required|string|max:500',
        ]);

        $user = User::findOrFail($userId);

        try {
            DB::beginTransaction();

            $balanceBefore = $user->wallet;
            $amountToAdjust = $validated['amount'];

            switch ($validated['action']) {
                case 'add':
                    $user->wallet += $amountToAdjust;
                    $transactionType = 'admin_adjust';
                    $amount = $amountToAdjust;
                    break;

                case 'deduct':
                    $user->wallet = max(0, $user->wallet - $amountToAdjust);
                    $transactionType = 'admin_adjust';
                    $amount = -$amountToAdjust;
                    break;

                case 'set':
                    $difference = $amountToAdjust - $user->wallet;
                    $user->wallet = $amountToAdjust;
                    $transactionType = 'admin_adjust';
                    $amount = $difference;
                    break;
            }

            $balanceAfter = $user->wallet;
            $user->save();

            // Record transaction
            WalletTransaction::create([
                'user_id' => $user->id,
                'transaction_type' => $transactionType,
                'amount' => abs($amount),
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'currency' => 'THB',
                'description' => $validated['reason'],
                'metadata' => json_encode([
                    'admin_id' => auth()->id(),
                    'action' => $validated['action'],
                    'original_amount' => $amountToAdjust
                ]),
                'status' => 'completed',
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Wallet adjusted successfully',
                'data' => [
                    'user_id' => $user->id,
                    'balance_before' => $balanceBefore,
                    'balance_after' => $balanceAfter,
                    'amount_adjusted' => $amount,
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to adjust wallet: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user wallet transactions
     */
    public function userTransactions(Request $request, $userId): JsonResponse
    {
        $user = User::findOrFail($userId);

        $transactions = WalletTransaction::where('user_id', $userId)
            ->with(['user:id,name,avatar'])
            ->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 20));

        // Calculate stats
        $totalDeposits = WalletTransaction::where('user_id', $userId)
            ->where('transaction_type', 'deposit')
            ->where('status', 'completed')
            ->sum('amount');
        $totalWithdrawals = WalletTransaction::where('user_id', $userId)
            ->where('transaction_type', 'withdraw')
            ->where('status', 'completed')
            ->sum('amount');
        $totalConversions = WalletTransaction::where('user_id', $userId)
            ->where('transaction_type', 'conversion')
            ->where('status', 'completed')
            ->sum('amount');

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'current_balance' => $user->wallet,
                ],
                'stats' => [
                    'total_deposits' => $totalDeposits,
                    'total_withdrawals' => $totalWithdrawals,
                    'total_conversions' => $totalConversions,
                    'net_balance' => $totalDeposits + $totalConversions - $totalWithdrawals,
                ],
                'transactions' => $transactions
            ]
        ]);
    }

    /**
     * Get wallet analytics
     */
    public function analytics(Request $request): JsonResponse
    {
        $startDate = $request->input('start_date', now()->subDays(30)->toDateString());
        $endDate = $request->input('end_date', now()->toDateString());

        // Daily trend
        $dailyTrend = WalletTransaction::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('SUM(CASE WHEN transaction_type = "deposit" THEN amount ELSE 0 END) as deposited'),
            DB::raw('SUM(CASE WHEN transaction_type = "withdraw" THEN amount ELSE 0 END) as withdrawn'),
            DB::raw('SUM(CASE WHEN transaction_type = "conversion" THEN amount ELSE 0 END) as converted'),
            DB::raw('COUNT(*) as total_transactions')
        )
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Transaction types breakdown
        $transactionTypes = WalletTransaction::select(
            'transaction_type',
            DB::raw('COUNT(*) as count'),
            DB::raw('SUM(amount) as total_amount')
        )
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('transaction_type')
            ->get();

        // Status breakdown
        $statusBreakdown = WalletTransaction::select(
            'status',
            DB::raw('COUNT(*) as count'),
            DB::raw('SUM(amount) as total_amount')
        )
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('status')
            ->get();

        // Top depositors
        $topDepositors = User::orderByDesc('wallet')
            ->limit(10)
            ->get(['id', 'name', 'avatar', 'wallet']);

        // Top withdrawers
        $topWithdrawers = WalletTransaction::select(
            'user_id',
            DB::raw('SUM(amount) as total_withdrawn'),
            DB::raw('COUNT(*) as withdrawal_count')
        )
            ->where('transaction_type', 'withdraw')
            ->where('status', 'completed')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->with('user:id,name,avatar')
            ->groupBy('user_id')
            ->orderByDesc('total_withdrawn')
            ->limit(10)
            ->get();

        // Reward redemptions
        $rewardRedemptions = Reward::select(
            'name',
            DB::raw('COUNT(user_rewards.id) as redemption_count'),
            DB::raw('SUM(user_rewards.points_spent) as total_points_spent')
        )
            ->join('user_rewards', 'rewards.id', '=', 'user_rewards.reward_id')
            ->whereBetween('user_rewards.redeemed_at', [$startDate, $endDate])
            ->groupBy('rewards.id', 'rewards.name')
            ->orderByDesc('redemption_count')
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'period' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                ],
                'daily_trend' => $dailyTrend,
                'transaction_types' => $transactionTypes,
                'status_breakdown' => $statusBreakdown,
                'top_depositors' => $topDepositors,
                'top_withdrawers' => $topWithdrawers,
                'reward_redemptions' => $rewardRedemptions,
            ]
        ]);
    }
}
