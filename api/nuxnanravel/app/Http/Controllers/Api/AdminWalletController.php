<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MarkWithdrawalPaidRequest;
use App\Models\User;
use App\Models\WalletDepositRequest;
use App\Models\WalletTransaction;
use App\Services\AuditLogService;
use App\Services\WalletService;
use App\Support\BankAccountNameMatcher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class AdminWalletController extends Controller
{
    protected WalletService $walletService;

    public function __construct(WalletService $walletService)
    {
        $this->walletService = $walletService;
    }

    /**
     * Check if user is admin (SUPER_ADMIN, ADMIN, MODERATOR or Plearnd Admin)
     */
    protected function isAdminUser($user): bool
    {
        if (! $user) {
            return false;
        }

        return $user->isSuperAdmin() || $user->hasAnyRole(['ADMIN', 'MODERATOR']) || $user->isPlearndAdmin();
    }

    /**
     * Get wallet statistics.
     */
    public function stats(Request $request): JsonResponse
    {
        $user = Auth::user();

        // Allow SUPER_ADMIN, ADMIN, or Plearnd Admin
        if (! $this->isAdminUser($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $totalWallet = User::sum('wallet');
        $totalWithdrawals = WalletTransaction::where('transaction_type', 'withdraw')
            ->whereNotIn('status', ['rejected', 'cancelled', 'failed'])
            ->count();
        $pendingWithdrawals = WalletTransaction::where('transaction_type', 'withdraw')
            ->whereIn('status', ['pending', 'under_review'])
            ->count();
        $completedWithdrawals = WalletTransaction::where('transaction_type', 'withdraw')
            ->whereIn('status', ['completed', 'paid'])
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

        // Deposit request statistics
        $pendingDepositRequests = WalletDepositRequest::where('status', WalletDepositRequest::STATUS_PENDING)->count();
        $approvedDepositRequests = WalletDepositRequest::where('status', WalletDepositRequest::STATUS_APPROVED)->count();
        $rejectedDepositRequests = WalletDepositRequest::where('status', WalletDepositRequest::STATUS_REJECTED)->count();

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
                'deposit_requests' => [
                    'pending' => $pendingDepositRequests,
                    'approved' => $approvedDepositRequests,
                    'rejected' => $rejectedDepositRequests,
                    'total' => $pendingDepositRequests + $approvedDepositRequests + $rejectedDepositRequests,
                ],
            ],
        ]);
    }

    /**
     * Get pending withdrawals.
     */
    public function pendingWithdrawals(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (! $this->isAdminUser($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $perPage = $request->input('per_page', 20);
        $page = $request->input('page', 1);
        $status = $request->input('status');

        $query = WalletTransaction::with(['user', 'reviewer'])
            ->where('transaction_type', 'withdraw');

        if ($status === 'awaiting-payout') {
            $query->whereIn('status', ['approved', 'processing']);
        } elseif ($status) {
            $statusArr = is_array($status) ? $status : explode(',', $status);
            $query->whereIn('status', $statusArr);
        } else {
            $query->whereIn('status', ['pending', 'under_review']);
        }

        $withdrawals = $query->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        $withdrawals->getCollection()->transform(fn (WalletTransaction $withdrawal) => $this->withAdminDestinationDetails($withdrawal));

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
     * Get pending deposit requests.
     */
    public function pendingDepositRequests(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (! $this->isAdminUser($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $perPage = $request->input('per_page', 20);
        $page = $request->input('page', 1);

        $depositRequests = WalletDepositRequest::with('user')
            ->where('status', WalletDepositRequest::STATUS_PENDING)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'success' => true,
            'data' => [
                'deposit_requests' => $depositRequests->items(),
                'pagination' => [
                    'current_page' => $depositRequests->currentPage(),
                    'total_pages' => $depositRequests->lastPage(),
                    'per_page' => $depositRequests->perPage(),
                    'total_items' => $depositRequests->total(),
                ],
            ],
        ]);
    }

    /**
     * Get a single deposit request details.
     */
    public function showDepositRequest(int $id): JsonResponse
    {
        $user = Auth::user();

        if (! $this->isAdminUser($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $depositRequest = WalletDepositRequest::with('user')->find($id);

        if (! $depositRequest) {
            return response()->json([
                'success' => false,
                'message' => 'Deposit request not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $depositRequest,
        ]);
    }

    /**
     * Approve withdrawal.
     */
    public function approveWithdrawal(Request $request, int $transactionId): JsonResponse
    {
        $user = Auth::user();
        $transaction = WalletTransaction::find($transactionId);

        if (! $transaction) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction not found',
            ], 404);
        }

        if (! $user || $user->cannot('approve', $transaction)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        // Fraud guard: refuse approval when the payout destination name does
        // not match the user's legal name on file.
        $transaction->load('user.profile');
        $accountName = null;
        if ($transaction->destination_snapshot) {
            try {
                $accountName = decrypt($transaction->destination_snapshot)['account_name'] ?? null;
            } catch (\Throwable) {
                $accountName = null;
            }
        }
        $accountName = $accountName ?? ($transaction->metadata['bank_account']['account_name'] ?? null);
        $firstName = $transaction->user?->profile?->first_name;
        $lastName = $transaction->user?->profile?->last_name;
        if (empty($firstName) || empty($lastName) || ! BankAccountNameMatcher::matches($firstName, $lastName, $accountName)) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถอนุมัติได้ เนื่องจากชื่อบัญชีปลายทางไม่ตรงกับชื่อ-นามสกุลของผู้ใช้',
            ], 422);
        }

        $validated = $request->validate(['admin_note' => 'nullable|string|max:500', 'payment_reference' => 'nullable|string|max:100']);

        try {
            $result = $this->walletService->approveWithdrawal($transaction, $user, $validated['admin_note'] ?? null, $validated['payment_reference'] ?? null);
        } catch (\DomainException|\RuntimeException $e) {
            return $this->mapWithdrawalException($e);
        }

        if (! $result) {
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
                'status' => $transaction->refresh()->status,
            ],
        ]);
    }

    /**
     * Map service-layer domain/concurrency exceptions to HTTP responses
     * (see Shared Contract §B/§E): DomainException -> 422, RuntimeException -> 409.
     */
    private function mapWithdrawalException(\Throwable $e): JsonResponse
    {
        if ($e instanceof \DomainException) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json(['success' => false, 'message' => 'มีการแก้ไขรายการพร้อมกัน กรุณาลองใหม่'], 409);
    }

    /**
     * Approve deposit request.
     */
    public function approveDepositRequest(Request $request, int $requestId): JsonResponse
    {
        $user = Auth::user();

        if (! $user || ! ($user->isSuperAdmin() || $user->hasRole('ADMIN'))) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $depositRequest = WalletDepositRequest::find($requestId);

        if (! $depositRequest) {
            return response()->json([
                'success' => false,
                'message' => 'Deposit request not found',
            ], 404);
        }

        $validated = $request->validate([
            'admin_note' => 'nullable|string|max:500',
        ]);

        $result = $this->walletService->approveDepositRequest($depositRequest, $validated['admin_note'] ?? null);

        if (! $result) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot approve this deposit request',
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Deposit request approved successfully',
            'data' => [
                'request_id' => $depositRequest->id,
                'status' => 'approved',
                'wallet_transaction_id' => $depositRequest->wallet_transaction_id,
            ],
        ]);
    }

    /**
     * Reject withdrawal.
     */
    public function rejectWithdrawal(Request $request, int $transactionId): JsonResponse
    {
        $user = Auth::user();
        $transaction = WalletTransaction::find($transactionId);

        if (! $transaction) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction not found',
            ], 404);
        }

        if (! $user || $user->cannot('reject', $transaction)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $validated = $request->validate(['reason' => 'required|string|max:255', 'admin_note' => 'nullable|string|max:500']);

        try {
            $result = $this->walletService->rejectWithdrawal($transaction, $validated['reason'], $user, $validated['admin_note'] ?? null);
        } catch (\DomainException|\RuntimeException $e) {
            return $this->mapWithdrawalException($e);
        }

        if (! $result) {
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
                'status' => $transaction->refresh()->status,
                'reason' => $validated['reason'],
            ],
        ]);
    }

    public function showWithdrawal(int $transactionId): JsonResponse
    {
        $user = Auth::user();
        $transaction = WalletTransaction::with(['user.profile', 'reviewer'])->find($transactionId);

        if (! $transaction || $transaction->transaction_type !== 'withdraw') {
            return response()->json(['success' => false, 'message' => 'Transaction not found'], 404);
        }

        if (! $user || $user->cannot('view', $transaction)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        // Records the first reviewer and moves pending -> under_review, which
        // the maker-checker control depends on.
        $this->walletService->viewWithdrawal($transaction, $user);

        $transaction = $transaction->refresh();
        $transaction->load('user.profile');

        $data = $this->withAdminDestinationDetails($transaction);
        $this->attachNameMismatch($data);

        return response()->json(['success' => true, 'data' => $data]);
    }

    /**
     * Attach a name-mismatch flag to a withdrawal so the admin UI can warn and
     * block approval when the payout account name doesn't match the user's
     * legal name on file (defense-in-depth for legacy pre-guard requests).
     */
    private function attachNameMismatch(WalletTransaction $transaction): void
    {
        $accountName = $transaction->destination_details['account_name']
            ?? $transaction->metadata['bank_account']['account_name']
            ?? null;

        $profile = $transaction->user?->profile;
        $firstName = $profile->first_name ?? null;
        $lastName = $profile->last_name ?? null;

        $hasProfile = ! empty($firstName) && ! empty($lastName);
        $matches = $hasProfile && BankAccountNameMatcher::matches($firstName, $lastName, $accountName);

        $transaction->setAttribute('name_mismatch', ! $matches);
        $transaction->setAttribute('expected_account_name', $hasProfile ? trim($firstName.' '.$lastName) : null);
        $transaction->setAttribute('has_profile', $hasProfile);
    }

    /**
     * Reveal the encrypted payout destination only to an authorized admin.
     * The public metadata remains masked by design.
     */
    private function withAdminDestinationDetails(WalletTransaction $transaction): WalletTransaction
    {
        if ($transaction->destination_snapshot) {
            try {
                $transaction->setAttribute('destination_details', decrypt($transaction->destination_snapshot));
            } catch (\Throwable $e) {
                // Keep the masked metadata available if an old/corrupt snapshot cannot be decrypted.
            }
        }

        return $transaction;
    }

    public function processWithdrawal(Request $request, int $transactionId): JsonResponse
    {
        $user = Auth::user();
        $transaction = WalletTransaction::find($transactionId);
        if (! $transaction) {
            return response()->json(['success' => false, 'message' => 'Transaction not found'], 404);
        }
        if (! $user || $user->cannot('approve', $transaction)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }
        $ok = $this->walletService->processWithdrawal($transaction, $user);

        return response()->json(['success' => $ok, 'data' => ['transaction_id' => $transaction->id, 'status' => $transaction->refresh()->status]], $ok ? 200 : 409);
    }

    public function markWithdrawalPaid(MarkWithdrawalPaidRequest $request, int $transactionId): JsonResponse
    {
        $user = Auth::user();
        $transaction = WalletTransaction::find($transactionId);
        if (! $transaction) {
            return response()->json(['success' => false, 'message' => 'Transaction not found'], 404);
        }
        if (! $user || $user->cannot('approve', $transaction)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $file = $request->file('proof');
        $path = Storage::disk('local')->putFile("withdrawal-proofs/{$transaction->user_id}/{$transaction->id}", $file);

        $proofData = [
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime' => $file->getMimeType(),
            'size' => $file->getSize(),
        ];

        try {
            $ok = $this->walletService->markWithdrawalPaid($transaction, $request->payment_reference, $user, $proofData);
            if (! $ok) {
                Storage::disk('local')->delete($path);
            }

            return response()->json(['success' => $ok, 'data' => ['transaction_id' => $transaction->id, 'status' => $transaction->refresh()->status]], $ok ? 200 : 409);
        } catch (\Exception $e) {
            Storage::disk('local')->delete($path);
            throw $e;
        }
    }

    public function downloadWithdrawalProof(int $transactionId)
    {
        $user = Auth::user();
        $transaction = WalletTransaction::find($transactionId);

        if (! $transaction || $transaction->transaction_type !== 'withdraw') {
            return response()->json(['success' => false, 'message' => 'Transaction not found'], 404);
        }

        if (! $user || $user->cannot('view', $transaction)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        if (empty($transaction->payout_proof_path) || ! Storage::disk('local')->exists($transaction->payout_proof_path)) {
            return response()->json(['success' => false, 'message' => 'Payout proof not found'], 404);
        }

        app(AuditLogService::class)->log(
            'withdrawal.proof_viewed',
            $transaction,
            [],
            [],
            'wallet',
            ['admin_id' => $user->id]
        );

        return Storage::disk('local')->response($transaction->payout_proof_path, $transaction->payout_proof_original_name);
    }

    public function markWithdrawalFailed(Request $request, int $transactionId): JsonResponse
    {
        $user = Auth::user();
        $transaction = WalletTransaction::find($transactionId);
        if (! $transaction) {
            return response()->json(['success' => false, 'message' => 'Transaction not found'], 404);
        }
        if (! $user || $user->cannot('approve', $transaction)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }
        $data = $request->validate(['reason' => 'required|string|max:255']);
        $ok = $this->walletService->markWithdrawalFailed($transaction, $data['reason'], $user);

        return response()->json(['success' => $ok, 'data' => ['transaction_id' => $transaction->id, 'status' => $transaction->refresh()->status]], $ok ? 200 : 409);
    }

    /**
     * Reject deposit request.
     */
    public function rejectDepositRequest(Request $request, int $requestId): JsonResponse
    {
        $user = Auth::user();

        if (! $this->isAdminUser($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $validated = $request->validate([
            'reason' => 'required|string|max:255',
            'admin_note' => 'nullable|string|max:500',
        ]);

        $depositRequest = WalletDepositRequest::find($requestId);

        if (! $depositRequest) {
            return response()->json([
                'success' => false,
                'message' => 'Deposit request not found',
            ], 404);
        }

        $result = $this->walletService->rejectDepositRequest(
            $depositRequest,
            $validated['reason'],
            $validated['admin_note'] ?? null
        );

        if (! $result) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot reject this deposit request',
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Deposit request rejected successfully',
            'data' => [
                'request_id' => $depositRequest->id,
                'status' => 'rejected',
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

        if (! $adminUser || ! $adminUser->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $targetUser = User::find($userId);

        if (! $targetUser) {
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

        if (! $adminUser || ! $adminUser->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $user = User::find($userId);

        if (! $user) {
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

        if (! $user || ! $user->isSuperAdmin()) {
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
            ->map(fn ($item) => [
                'date' => $item->date,
                'total' => (float) $item->total,
            ]);

        // Get distribution by type
        $distribution = WalletTransaction::selectRaw('transaction_type, SUM(amount) as total')
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->groupBy('transaction_type')
            ->get()
            ->map(fn ($item) => [
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
