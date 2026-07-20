<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\WalletDepositRequest;
use App\Models\WalletTransaction;
use App\Services\WalletService;
use App\Support\BankAccountNameMatcher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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

        if (! $user) {
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

        if (! $user) {
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
     * Deduct money from wallet (Internal use for exams/purchases).
     */
    public function deduct(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated',
            ], 401);
        }

        $validated = $request->validate([
            'amount' => 'required|numeric|min:1',
            'reason' => 'required|string',
            'metadata' => 'nullable|array',
        ]);

        try {
            $transaction = $this->walletService->deductForPurchase(
                $user,
                (string) $validated['amount'],
                $validated['reason'],
                null,
                $validated['metadata'] ?? []
            );

            if (! $transaction) {
                return response()->json([
                    'success' => false,
                    'message' => 'ยอดเงินในกระเป๋าไม่เพียงพอ',
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => 'Deduction successful',
                'data' => [
                    'amount' => $transaction->amount,
                    'new_balance' => $transaction->balance_after,
                    'transaction_id' => $transaction->id,
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

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated',
            ], 401);
        }

        $validated = $request->validate([
            'amount' => 'required|numeric|min:'.config('wallet.withdraw.min_amount'),
            'method' => 'required|string|in:bank_transfer,promptpay',
            'bank_account' => 'required|array',
            'bank_account.bank_name' => 'required|string|max:50',
            'bank_account.account_number' => 'required|string|max:20',
            'bank_account.account_name' => 'required|string|max:100',
            'description' => 'nullable|string|max:255',
        ]);

        // Fraud guard: the payout account name must match the user's own legal
        // name on file. A user without a completed profile cannot withdraw.
        $profile = $user->profile()->first();
        $hasProfile = ! empty($profile?->first_name) && ! empty($profile?->last_name);
        if (! $hasProfile && empty($user->name)) {
            return response()->json([
                'success' => false,
                'message' => 'ยังถอนเงินไม่ได้ — กรุณากรอก "ชื่อจริง" และ "นามสกุล" ในหน้าตั้งค่าโปรไฟล์ก่อน (ระบบใช้ตรวจสอบว่าบัญชีรับเงินเป็นของคุณ) ข้อมูลในฟอร์มถอนเงินของคุณครบถ้วนแล้ว',
                'errors' => [
                    'profile' => ['โปรไฟล์ยังไม่มีชื่อจริง-นามสกุล'],
                ],
                'error_code' => 'profile_name_required',
            ], 422);
        }

        $matches = $hasProfile
            ? BankAccountNameMatcher::matches($profile->first_name, $profile->last_name, $validated['bank_account']['account_name'])
            : BankAccountNameMatcher::matchesFullName($user->name, $validated['bank_account']['account_name']);
        if (! $matches) {
            return response()->json([
                'success' => false,
                'message' => 'ชื่อบัญชีผู้รับเงินต้องตรงกับชื่อ-นามสกุลของเจ้าของบัญชีผู้ใช้งาน',
                'errors' => [
                    'bank_account.account_name' => ['ชื่อบัญชีปลายทางไม่ตรงกับชื่อ-นามสกุลในโปรไฟล์'],
                ],
            ], 422);
        }

        // Method-specific validation and normalization of the destination.
        if ($validated['method'] === 'promptpay') {
            // Normalize the PromptPay number: keep digits only.
            $number = preg_replace('/\D/', '', $validated['bank_account']['account_number']);

            // Accept a Thai mobile number (10 digits, 0[689]xxxxxxxx) or a
            // national ID (13 digits).
            if (! preg_match('/^(0[689]\d{8}|\d{13})$/', $number)) {
                return response()->json([
                    'success' => false,
                    'message' => 'หมายเลขพร้อมเพย์ไม่ถูกต้อง (ต้องเป็นเบอร์มือถือ 10 หลัก หรือเลขบัตรประชาชน 13 หลัก)',
                    'errors' => [
                        'bank_account.account_number' => ['หมายเลขพร้อมเพย์ไม่ถูกต้อง'],
                    ],
                ], 422);
            }

            $validated['bank_account']['account_number'] = $number;
            $validated['bank_account']['bank_name'] = 'promptpay';
        } else {
            // bank_transfer: guard against a spoofed PromptPay marker.
            $allowedBanks = ['kbank', 'scb', 'bbl', 'ktb', 'bay', 'tmb', 'ttb', 'gsb', 'baac', 'uob', 'cimb', 'lhbank', 'tisco', 'kkp'];

            if (! in_array(strtolower($validated['bank_account']['bank_name']), $allowedBanks, true)) {
                return response()->json([
                    'success' => false,
                    'message' => 'กรุณาเลือกธนาคารที่รองรับ',
                    'errors' => [
                        'bank_account.bank_name' => ['ธนาคารไม่ถูกต้อง'],
                    ],
                ], 422);
            }

            // Store the account number as digits only for consistency.
            $validated['bank_account']['account_number'] = preg_replace('/\D/', '', $validated['bank_account']['account_number']);
        }

        try {
            $result = $this->walletService->withdraw(
                $user,
                $validated['amount'],
                $validated['method'],
                $validated['bank_account'],
                $validated['description'] ?? null,
                $request->header('Idempotency-Key', $request->input('idempotency_key'))
            );

            if (! $result) {
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
     * List the authenticated user's withdrawal requests with their statuses.
     */
    public function myWithdrawals(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (! $user) {
            return response()->json(['success' => false, 'message' => 'User not authenticated'], 401);
        }

        $query = WalletTransaction::where('user_id', $user->id)
            ->where('transaction_type', 'withdraw');

        if ($request->filled('status')) {
            $query->whereIn('status', explode(',', (string) $request->input('status')));
        }

        $withdrawals = $query->orderByDesc('created_at')
            ->paginate((int) $request->input('per_page', 20), ['*'], 'page', (int) $request->input('page', 1));

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
     * Cancel the authenticated user's own pending withdrawal. Money is
     * refunded back to the wallet by the service with a ledger entry.
     */
    public function cancelWithdrawal(int $id): JsonResponse
    {
        $user = Auth::user();

        if (! $user) {
            return response()->json(['success' => false, 'message' => 'User not authenticated'], 401);
        }

        $transaction = WalletTransaction::where('user_id', $user->id)
            ->where('transaction_type', 'withdraw')
            ->find($id);

        if (! $transaction) {
            return response()->json(['success' => false, 'message' => 'ไม่พบคำขอถอนเงิน'], 404);
        }

        $ok = $this->walletService->cancelWithdrawal($transaction, $user);

        if (! $ok) {
            return response()->json([
                'success' => false,
                'message' => 'ยกเลิกไม่ได้ — คำขอนี้อยู่ระหว่างการโอนเงินหรือดำเนินการเสร็จสิ้นแล้ว หากมีปัญหากรุณาติดต่อผู้ดูแลระบบ',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'ยกเลิกคำขอถอนเงินแล้ว เงินถูกคืนเข้ากระเป๋าเรียบร้อย',
            'data' => [
                'transaction_id' => $transaction->id,
                'status' => $transaction->refresh()->status,
            ],
        ]);
    }

    /**
     * Let the owner download the payout slip the admin attached when the
     * withdrawal was paid.
     */
    public function myWithdrawalProof(int $id)
    {
        $user = Auth::user();

        if (! $user) {
            return response()->json(['success' => false, 'message' => 'User not authenticated'], 401);
        }

        $transaction = WalletTransaction::where('user_id', $user->id)
            ->where('transaction_type', 'withdraw')
            ->find($id);

        if (! $transaction) {
            return response()->json(['success' => false, 'message' => 'ไม่พบคำขอถอนเงิน'], 404);
        }

        if (empty($transaction->payout_proof_path) || ! Storage::disk('local')->exists($transaction->payout_proof_path)) {
            return response()->json(['success' => false, 'message' => 'ยังไม่มีหลักฐานการโอนเงินสำหรับรายการนี้'], 404);
        }

        return Storage::disk('local')->response($transaction->payout_proof_path, $transaction->payout_proof_original_name);
    }

    /**
     * Transfer money to another user.
     */
    public function transfer(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated',
            ], 401);
        }

        $validated = $request->validate([
            'recipient_id' => 'required|integer|exists:users,id|different:'.$user->id,
            'amount' => 'required|numeric|min:10',
            'message' => 'nullable|string|max:255',
            'metadata' => 'nullable|array',
        ]);

        try {
            $toUser = User::find($validated['recipient_id']);
            if (! $toUser) {
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

            if (! $result['success']) {
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

        if (! $user) {
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

            if (! $result['success']) {
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

        if (! $user) {
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

            if (! $result['success']) {
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

        if (! $user) {
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

        // Transform transactions to include sender/receiver details
        $transactions->getCollection()->transform(function ($transaction) {
            if ($transaction->transaction_type === 'transfer') {
                $metadata = $transaction->metadata;

                if (isset($metadata['to_user_id'])) {
                    // Outgoing transfer
                    $transaction->transaction_type = 'transfer_out';
                    $receiver = User::find($metadata['to_user_id']);
                    if ($receiver) {
                        $transaction->receiver = [
                            'id' => $receiver->id,
                            'name' => $receiver->name,
                            'username' => $receiver->username,
                            'profile_photo_url' => $receiver->profile_photo_url,
                        ];
                    }
                } elseif (isset($metadata['from_user_id'])) {
                    // Incoming transfer
                    $transaction->transaction_type = 'transfer_in';
                    $sender = User::find($metadata['from_user_id']);
                    if ($sender) {
                        $transaction->sender = [
                            'id' => $sender->id,
                            'name' => $sender->name,
                            'username' => $sender->username,
                            'profile_photo_url' => $sender->profile_photo_url,
                        ];
                    }
                }
            }

            return $transaction;
        });

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
     * Create a deposit request (requires admin approval).
     */
    public function createDepositRequest(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (! $user) {
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
                $slipPath = $request->file('transfer_slip')->store('deposit-slips/'.$user->id, 'public');
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

        if (! $user) {
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

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated',
            ], 401);
        }

        $depositRequest = WalletDepositRequest::where('id', $requestId)
            ->where('user_id', $user->id)
            ->where('status', 'pending')
            ->first();

        if (! $depositRequest) {
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
