<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WalletTransaction;
use App\Models\User;
use App\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class WalletController extends Controller
{
    protected WalletService $walletService;

    public function __construct(WalletService $walletService)
    {
        $this->walletService = $walletService;
    }

    /**
     * Get user wallet balance.
     */
    public function balance(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated',
            ], 401);
        }

        $balance = $this->walletService->getBalance($user->id);

        return response()->json([
            'success' => true,
            'data' => [
                'cash_balance' => $balance['cash_balance'],
                'reward_balance' => $balance['reward_balance'],
                'total_balance' => $balance['total_balance'],
                'locked_balance' => $balance['locked_balance'],
                'currency' => 'THB',
            ],
        ]);
    }

    /**
     * Deposit money to wallet.
     */
    public function deposit(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated',
            ], 401);
        }

        $validated = $request->validate([
            'amount' => 'required|numeric|min:10',
            'method' => 'required|string|max:50',
            'reference' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:255',
            'metadata' => 'nullable|array',
        ]);

        try {
            $result = $this->walletService->deposit($user->id, $validated);

            return response()->json([
                'success' => true,
                'message' => 'Deposit successful',
                'data' => [
                    'amount' => $result['amount'],
                    'new_balance' => $result['new_balance'],
                    'reference_number' => $result['reference_number'],
                    'transaction_id' => $result['transaction_id'],
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Withdraw money from wallet.
     */
    public function withdraw(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated',
            ], 401);
        }

        $validated = $request->validate([
            'amount' => 'required|numeric|min:10',
            'method' => 'required|string|max:50',
            'bank_account' => 'required|array',
            'bank_account.bank_name' => 'required|string|max:50',
            'bank_account.account_number' => 'required|string|max:20',
            'bank_account.account_name' => 'required|string|max:100',
            'description' => 'nullable|string|max:255',
        ]);

        try {
            $result = $this->walletService->withdraw($user->id, $validated);

            return response()->json([
                'success' => true,
                'message' => 'Withdrawal request submitted',
                'data' => [
                    'amount' => $result['amount'],
                    'fee' => $result['fee'],
                    'net_amount' => $result['net_amount'],
                    'new_balance' => $result['new_balance'],
                    'reference_number' => $result['reference_number'],
                    'transaction_id' => $result['transaction_id'],
                    'status' => 'pending',
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Transfer money to another user.
     */
    public function transfer(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated',
            ], 401);
        }

        $validated = $request->validate([
            'recipient_id' => 'required|integer|exists:users,id|different:' . $user->id,
            'amount' => 'required|numeric|min:10',
            'message' => 'nullable|string|max:255',
            'metadata' => 'nullable|array',
        ]);

        try {
            $result = $this->walletService->transfer($user->id, $validated);

            return response()->json([
                'success' => true,
                'message' => 'Transfer successful',
                'data' => [
                    'amount' => $result['amount'],
                    'new_balance' => $result['new_balance'],
                    'recipient_id' => $validated['recipient_id'],
                    'reference_number' => $result['reference_number'],
                    'transaction_id' => $result['transaction_id'],
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Convert points to wallet.
     */
    public function convertPoints(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated',
            ], 401);
        }

        $validated = $request->validate([
            'points' => 'required|integer|min:1080', // Minimum 1080 points (1 THB)
        ]);

        try {
            $result = $this->walletService->convertPointsToWallet($user->id, $validated['points']);

            return response()->json([
                'success' => true,
                'message' => 'Points converted successfully',
                'data' => [
                    'points_converted' => $result['points_converted'],
                    'wallet_amount' => $result['wallet_amount'],
                    'new_points_balance' => $result['new_points_balance'],
                    'new_wallet_balance' => $result['new_wallet_balance'],
                    'exchange_rate' => '1080 points = 1 THB',
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get wallet transactions.
     */
    public function transactions(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated',
            ], 401);
        }

        $query = WalletTransaction::where('user_id', $user->id);

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

        // Order by latest first
        $query->orderBy('created_at', 'desc');

        // Pagination
        $perPage = $request->input('per_page', 20);
        $page = $request->input('page', 1);

        $transactions = $query->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'success' => true,
            'data' => [
                'transactions' => $transactions->items(),
                'current_page' => $transactions->currentPage(),
                'total_pages' => $transactions->lastPage(),
                'per_page' => $transactions->perPage(),
                'total' => $transactions->total(),
            ],
        ]);
    }

    /**
     * Approve withdrawal (Admin only).
     */
    public function approveWithdrawal(Request $request, int $transactionId): JsonResponse
    {
        $user = Auth::user();

        if (!$user || !$user->hasRole('admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        try {
            $result = $this->walletService->approveWithdrawal($transactionId);

            return response()->json([
                'success' => true,
                'message' => 'Withdrawal approved successfully',
                'data' => [
                    'transaction_id' => $result['transaction_id'],
                    'status' => 'completed',
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Reject withdrawal (Admin only).
     */
    public function rejectWithdrawal(Request $request, int $transactionId): JsonResponse
    {
        $user = Auth::user();

        if (!$user || !$user->hasRole('admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $validated = $request->validate([
            'reason' => 'required|string|max:255',
        ]);

        try {
            $result = $this->walletService->rejectWithdrawal($transactionId, $validated['reason']);

            return response()->json([
                'success' => true,
                'message' => 'Withdrawal rejected successfully',
                'data' => [
                    'transaction_id' => $result['transaction_id'],
                    'status' => 'failed',
                    'reason' => $validated['reason'],
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
