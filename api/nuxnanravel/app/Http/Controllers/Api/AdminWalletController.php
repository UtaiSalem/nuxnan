<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class AdminWalletController extends Controller
{
    protected WalletService $walletService;

    public function __construct(WalletService $walletService)
    {
        $this->walletService = $walletService;
    }

    /**
     * Get wallet statistics.
     */
    public function stats(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (!$user || !$user->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $totalWallet = User::sum('wallet');
        $totalWithdrawals = WalletTransaction::where('transaction_type', 'withdraw')
            ->where('status', '!=', 'cancelled')
            ->count();
        $pendingWithdrawals = WalletTransaction::where('transaction_type', 'withdraw')
            ->where('status', 'pending')
            ->count();
        $completedWithdrawals = WalletTransaction::where('transaction_type', 'withdraw')
            ->where('status', 'completed')
            ->count();
        $totalDeposits = WalletTransaction::where('transaction_type', 'deposit')
            ->where('status', 'completed')
            ->count();
        $totalTransfers = WalletTransaction::where('transaction_type', 'transfer')
            ->where('status', 'completed')
            ->count();
        $totalConversions = WalletTransaction::where('transaction_type', 'conversion')
            ->where('status', 'completed')
            ->count();

        return response()->json([
            'success' => true,
            'data' => [
                'total_wallet' => $totalWallet,
                'total_withdrawals' => $totalWithdrawals,
                'pending_withdrawals' => $pendingWithdrawals,
                'completed_withdrawals' => $completedWithdrawals,
                'total_deposits' => $totalDeposits,
                'total_transfers' => $totalTransfers,
                'total_conversions' => $totalConversions,
            ],
        ]);
    }

    /**
     * Get pending withdrawals.
     */
    public function pendingWithdrawals(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (!$user || !$user->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $perPage = $request->input('per_page', 20);
        $page = $request->input('page', 1);

        $withdrawals = WalletTransaction::with('user')
            ->where('transaction_type', 'withdraw')
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'success' => true,
            'data' => [
                'withdrawals' => $withdrawals->items(),
                'pagination' => [
                    'current_page' => $withdrawals->currentPage(),
                    'total_pages' => $withdrawals->lastPage(),
                    'per_page' => $withdrawals->perPage(),
                    'total_items' => $withdrawals->total(),
                ],
            ],
        ]);
    }

    /**
     * Approve withdrawal.
     */
    public function approveWithdrawal(Request $request, int $transactionId): JsonResponse
    {
        $user = Auth::user();

        if (!$user || !$user->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $transaction = WalletTransaction::find($transactionId);

        if (!$transaction) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction not found',
            ], 404);
        }

        $result = $this->walletService->approveWithdrawal($transaction);

        if (!$result) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot approve this transaction',
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Withdrawal approved successfully',
            'data' => [
                'transaction_id' => $transaction->id,
                'status' => 'completed',
            ],
        ]);
    }

    /**
     * Reject withdrawal.
     */
    public function rejectWithdrawal(Request $request, int $transactionId): JsonResponse
    {
        $user = Auth::user();

        if (!$user || !$user->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $validated = $request->validate([
            'reason' => 'required|string|max:255',
        ]);

        $transaction = WalletTransaction::find($transactionId);

        if (!$transaction) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction not found',
            ], 404);
        }

        $result = $this->walletService->rejectWithdrawal($transaction, $validated['reason']);

        if (!$result) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot reject this transaction',
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Withdrawal rejected successfully',
            'data' => [
                'transaction_id' => $transaction->id,
                'status' => 'cancelled',
                'reason' => $validated['reason'],
            ],
        ]);
    }

    /**
     * Adjust user wallet balance.
     */
    public function adjustWallet(Request $request, int $userId): JsonResponse
    {
        $adminUser = Auth::user();

        if (!$adminUser || !$adminUser->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $targetUser = User::find($userId);

        if (!$targetUser) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], 404);
        }

        $validated = $request->validate([
            'action_type' => 'required|in:add,deduct,set',
            'amount' => 'required|numeric|min:0',
            'reason' => 'required|string|max:255',
        ]);

        $result = $this->walletService->adminAdjust(
            $targetUser,
            $validated['amount'],
            $validated['action_type'],
            $validated['reason']
        );

        return response()->json([
            'success' => true,
            'message' => 'Wallet adjusted successfully',
            'data' => [
                'user_id' => $userId,
                'new_balance' => $targetUser->wallet,
                'transaction_id' => $result->id,
            ],
        ]);
    }

    /**
     * Get user wallet transactions.
     */
    public function userTransactions(Request $request, int $userId): JsonResponse
    {
        $adminUser = Auth::user();

        if (!$adminUser || !$adminUser->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $user = User::find($userId);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], 404);
        }

        $query = WalletTransaction::where('user_id', $userId);

        // Filter by type
        if ($request->has('type') && $request->type) {
            $query->where('transaction_type', $request->type);
        }

        // Filter by date range
        if ($request->has('date_from') && $request->date_from) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to') && $request->date_to) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Pagination
        $perPage = $request->input('per_page', 20);
        $page = $request->input('page', 1);

        $transactions = $query->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'success' => true,
            'data' => [
                'transactions' => $transactions->items(),
                'pagination' => [
                    'current_page' => $transactions->currentPage(),
                    'total_pages' => $transactions->lastPage(),
                    'per_page' => $transactions->perPage(),
                    'total_items' => $transactions->total(),
                ],
            ],
        ]);
    }

    /**
     * Get wallet analytics.
     */
    public function analytics(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (!$user || !$user->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        $query = WalletTransaction::query();

        if ($dateFrom) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        // Get daily trend
        $dailyTrend = WalletTransaction::selectRaw('DATE(created_at) as date, SUM(amount) as total')
            ->where('transaction_type', 'deposit')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(fn($item) => [
                'date' => $item->date,
                'total' => (float) $item->total,
            ]);

        // Get distribution by type
        $distribution = WalletTransaction::selectRaw('transaction_type, SUM(amount) as total')
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->groupBy('transaction_type')
            ->get()
            ->map(fn($item) => [
                'transaction_type' => $item->transaction_type,
                'total' => (float) $item->total,
            ]);

        // Get top users by wallet balance
        $topUsers = User::orderByDesc('wallet')
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'daily_trend' => $dailyTrend,
                'distribution' => $distribution,
                'top_users' => $topUsers,
            ],
        ]);
    }
}
