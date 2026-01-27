<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WalletTransaction;
use App\Models\WalletDepositRequest;
use App\Models\User;
use App\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

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

        $balance = $this->walletService->getBalance($user);

        return response()->json([
            'success' => true,
            'data' => [
                'cash_balance' => $balance['cash_balance'],
                'reward_balance' => $balance['reward_balance'],
                'total_balance' => $balance['total_balance'],
                'locked_balance' => $balance['locked_balance'],
                'currency' => $balance['currency'],
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
            $result = $this->walletService->deposit(
                $user,
                $validated['amount'],
                $validated['method'],
                $validated['reference'] ?? null,
                $validated['description'] ?? null,
                $validated['metadata'] ?? null
            );

            return response()->json([
                'success' => true,
                'message' => 'Deposit successful',
                'data' => [
                    'amount' => $result->amount,
                    'new_balance' => $result->balance_after,
                    'reference_number' => $result->reference_number,
                    'transaction_id' => $result->id,
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
            'amount' => 'required|numeric|min:100', // Minimum 100 THB
            'method' => 'required|string|max:50',
            'bank_account' => 'required|array',
            'bank_account.bank_name' => 'required|string|max:50',
            'bank_account.account_number' => 'required|string|max:20',
            'bank_account.account_name' => 'required|string|max:100',
            'description' => 'nullable|string|max:255',
        ]);

        try {
            $result = $this->walletService->withdraw(
                $user,
                $validated['amount'],
                $validated['method'],
                $validated['bank_account'],
                $validated['description'] ?? null
            );

            if (!$result) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient wallet balance',
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => 'Withdrawal request submitted',
                'data' => [
                    'amount' => $result->amount,
                    'fee' => $result->metadata['fee'] ?? 0,
                    'net_amount' => $result->metadata['net_amount'] ?? $result->amount,
                    'new_balance' => $result->balance_after,
                    'reference_number' => $result->reference_number,
                    'transaction_id' => $result->id,
                    'status' => $result->status,
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
            $toUser = User::find($validated['recipient_id']);
            if (!$toUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'Recipient not found',
                ], 404);
            }

            $result = $this->walletService->transfer(
                $user,
                $toUser,
                $validated['amount'],
                $validated['message'] ?? null
            );

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'],
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => 'Transfer successful',
                'data' => [
                    'amount' => $validated['amount'],
                    'new_balance' => $user->wallet,
                    'recipient_id' => $validated['recipient_id'],
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
            'points' => 'required|integer|min:1200', // Minimum 1200 points (1 THB)
        ]);

        try {
            $result = $this->walletService->convertPointsToWallet($user, $validated['points']);

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'],
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => 'Points converted successfully',
                'data' => [
                    'points_converted' => $result['points_converted'],
                    'wallet_amount' => $result['wallet_amount'],
                    'new_points_balance' => $result['new_points_balance'],
                    'new_wallet_balance' => $result['new_wallet_balance'],
                    'exchange_rate' => '1200 points = 1 THB',
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
     * Convert wallet to points (for advertising support).
     */
    public function convertToPoints(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated',
            ], 401);
        }

        $validated = $request->validate([
            'amount' => 'required|numeric|min:10', // Minimum 10 THB
        ]);

        try {
            $result = $this->walletService->convertWalletToPoints($user, $validated['amount']);

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'],
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => 'Wallet converted to points successfully',
                'data' => [
                    'wallet_amount' => $result['wallet_amount'],
                    'points_received' => $result['points_received'],
                    'new_wallet_balance' => $result['new_wallet_balance'],
                    'new_points_balance' => $result['new_points_balance'],
                    'exchange_rate' => '1 THB = 1080 points',
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

        if (!$user || !$user->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        try {
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

        if (!$user || !$user->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $validated = $request->validate([
            'reason' => 'required|string|max:255',
        ]);

        try {
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
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Create a deposit request (requires admin approval).
     */
    public function createDepositRequest(Request $request): JsonResponse
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
            'payment_method' => 'required|string|in:bank_transfer,promptpay,credit_card',
            'bank_name' => 'nullable|string|max:100',
            'account_number' => 'nullable|string|max:50',
            'account_name' => 'nullable|string|max:100',
            'transfer_slip' => 'required|image|max:5120', // 5MB max
            'transfer_date' => 'required|date',
            'transfer_time' => 'nullable|string|max:10',
            'reference_number' => 'nullable|string|max:100',
            'note' => 'nullable|string|max:500',
        ]);

        try {
            // Store the transfer slip
            $slipPath = null;
            if ($request->hasFile('transfer_slip')) {
                $slipPath = $request->file('transfer_slip')->store('deposit-slips/' . $user->id, 'public');
            }

            // Create the deposit request
            $depositRequest = WalletDepositRequest::create([
                'user_id' => $user->id,
                'amount' => $validated['amount'],
                'payment_method' => $validated['payment_method'],
                'bank_name' => $validated['bank_name'] ?? null,
                'account_number' => $validated['account_number'] ?? null,
                'account_name' => $validated['account_name'] ?? null,
                'transfer_slip' => $slipPath,
                'transfer_date' => $validated['transfer_date'],
                'transfer_time' => $validated['transfer_time'] ?? null,
                'reference_number' => $validated['reference_number'] ?? null,
                'note' => $validated['note'] ?? null,
                'status' => 'pending',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'คำขอเติมเงินถูกส่งแล้ว รอการตรวจสอบจาก Admin',
                'data' => [
                    'id' => $depositRequest->id,
                    'amount' => $depositRequest->amount,
                    'status' => $depositRequest->status,
                    'status_label' => $depositRequest->status_label,
                    'created_at' => $depositRequest->created_at,
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
     * Get user's deposit requests.
     */
    public function getDepositRequests(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated',
            ], 401);
        }

        $requests = WalletDepositRequest::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 10));

        return response()->json([
            'success' => true,
            'data' => $requests->map(function ($req) {
                return [
                    'id' => $req->id,
                    'amount' => $req->amount,
                    'payment_method' => $req->payment_method,
                    'payment_method_label' => $req->payment_method_label,
                    'bank_name' => $req->bank_name,
                    'transfer_slip' => $req->slip_url,
                    'transfer_date' => $req->transfer_date?->format('Y-m-d'),
                    'status' => $req->status,
                    'status_label' => $req->status_label,
                    'rejection_reason' => $req->rejection_reason,
                    'reviewed_at' => $req->reviewed_at?->format('Y-m-d H:i:s'),
                    'created_at' => $req->created_at->format('Y-m-d H:i:s'),
                ];
            }),
            'pagination' => [
                'current_page' => $requests->currentPage(),
                'last_page' => $requests->lastPage(),
                'per_page' => $requests->perPage(),
                'total' => $requests->total(),
            ],
        ]);
    }

    /**
     * Cancel a pending deposit request (user can cancel their own).
     */
    public function cancelDepositRequest(int $requestId): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated',
            ], 401);
        }

        $depositRequest = WalletDepositRequest::where('id', $requestId)
            ->where('user_id', $user->id)
            ->where('status', 'pending')
            ->first();

        if (!$depositRequest) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบคำขอเติมเงินหรือคำขอถูกดำเนินการแล้ว',
            ], 404);
        }

        // Delete the slip file
        if ($depositRequest->transfer_slip) {
            Storage::disk('public')->delete($depositRequest->transfer_slip);
        }

        $depositRequest->delete();

        return response()->json([
            'success' => true,
            'message' => 'ยกเลิกคำขอเติมเงินสำเร็จ',
        ]);
    }
}
