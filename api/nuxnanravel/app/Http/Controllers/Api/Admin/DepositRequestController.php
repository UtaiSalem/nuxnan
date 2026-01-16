<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\WalletDepositRequest;
use App\Models\User;
use App\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class DepositRequestController extends Controller
{
    protected WalletService $walletService;

    public function __construct(WalletService $walletService)
    {
        $this->walletService = $walletService;
    }

    /**
     * Get all deposit requests (with filters).
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (!$user || !$user->is_plearnd_admin) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $query = WalletDepositRequest::with(['user:id,name,email,reference_code,profile_photo_url'])
            ->orderBy('created_at', 'desc');

        // Filter by status
        if ($request->has('status') && in_array($request->status, ['pending', 'approved', 'rejected'])) {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->has('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        if ($request->has('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        // Search by user
        if ($request->has('search')) {
            $search = $request->search;
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('reference_code', 'like', "%{$search}%");
            });
        }

        $requests = $query->paginate($request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $requests->map(function ($req) {
                return [
                    'id' => $req->id,
                    'user' => [
                        'id' => $req->user->id,
                        'name' => $req->user->name,
                        'email' => $req->user->email,
                        'reference_code' => $req->user->reference_code,
                        'avatar' => $req->user->profile_photo_url,
                    ],
                    'amount' => $req->amount,
                    'payment_method' => $req->payment_method,
                    'payment_method_label' => $req->payment_method_label,
                    'bank_name' => $req->bank_name,
                    'account_number' => $req->account_number,
                    'account_name' => $req->account_name,
                    'transfer_slip' => $req->slip_url,
                    'transfer_date' => $req->transfer_date?->format('Y-m-d'),
                    'transfer_time' => $req->transfer_time,
                    'reference_number' => $req->reference_number,
                    'note' => $req->note,
                    'status' => $req->status,
                    'status_label' => $req->status_label,
                    'reviewed_by' => $req->reviewed_by,
                    'reviewed_at' => $req->reviewed_at?->format('Y-m-d H:i:s'),
                    'admin_note' => $req->admin_note,
                    'rejection_reason' => $req->rejection_reason,
                    'created_at' => $req->created_at->format('Y-m-d H:i:s'),
                ];
            }),
            'pagination' => [
                'current_page' => $requests->currentPage(),
                'last_page' => $requests->lastPage(),
                'per_page' => $requests->perPage(),
                'total' => $requests->total(),
            ],
            'stats' => [
                'pending' => WalletDepositRequest::pending()->count(),
                'approved_today' => WalletDepositRequest::approved()->whereDate('reviewed_at', today())->count(),
                'total_pending_amount' => WalletDepositRequest::pending()->sum('amount'),
            ],
        ]);
    }

    /**
     * Get a single deposit request.
     */
    public function show(int $id): JsonResponse
    {
        $user = Auth::user();

        if (!$user || !$user->is_plearnd_admin) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $req = WalletDepositRequest::with(['user', 'reviewer'])->find($id);

        if (!$req) {
            return response()->json([
                'success' => false,
                'message' => 'Deposit request not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $req->id,
                'user' => [
                    'id' => $req->user->id,
                    'name' => $req->user->name,
                    'email' => $req->user->email,
                    'reference_code' => $req->user->reference_code,
                    'avatar' => $req->user->profile_photo_url,
                    'wallet_balance' => $req->user->wallet ?? 0,
                ],
                'amount' => $req->amount,
                'payment_method' => $req->payment_method,
                'payment_method_label' => $req->payment_method_label,
                'bank_name' => $req->bank_name,
                'account_number' => $req->account_number,
                'account_name' => $req->account_name,
                'transfer_slip' => $req->slip_url,
                'transfer_date' => $req->transfer_date?->format('Y-m-d'),
                'transfer_time' => $req->transfer_time,
                'reference_number' => $req->reference_number,
                'note' => $req->note,
                'status' => $req->status,
                'status_label' => $req->status_label,
                'reviewer' => $req->reviewer ? [
                    'id' => $req->reviewer->id,
                    'name' => $req->reviewer->name,
                ] : null,
                'reviewed_at' => $req->reviewed_at?->format('Y-m-d H:i:s'),
                'admin_note' => $req->admin_note,
                'rejection_reason' => $req->rejection_reason,
                'created_at' => $req->created_at->format('Y-m-d H:i:s'),
            ],
        ]);
    }

    /**
     * Approve a deposit request.
     */
    public function approve(Request $request, int $id): JsonResponse
    {
        $admin = Auth::user();

        if (!$admin || !$admin->is_plearnd_admin) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $validated = $request->validate([
            'admin_note' => 'nullable|string|max:500',
        ]);

        $depositRequest = WalletDepositRequest::with('user')->find($id);

        if (!$depositRequest) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบคำขอเติมเงิน',
            ], 404);
        }

        if (!$depositRequest->isPending()) {
            return response()->json([
                'success' => false,
                'message' => 'คำขอนี้ถูกดำเนินการแล้ว',
            ], 400);
        }

        try {
            // Add money to user's wallet
            $transaction = $this->walletService->deposit(
                $depositRequest->user,
                $depositRequest->amount,
                $depositRequest->payment_method,
                'DEP-' . $depositRequest->id,
                'เติมเงินจากการโอน (Request #' . $depositRequest->id . ')',
                [
                    'deposit_request_id' => $depositRequest->id,
                    'approved_by' => $admin->id,
                ]
            );

            // Update deposit request status
            $depositRequest->update([
                'status' => 'approved',
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
                'admin_note' => $validated['admin_note'] ?? null,
                'wallet_transaction_id' => $transaction->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'อนุมัติการเติมเงินสำเร็จ',
                'data' => [
                    'id' => $depositRequest->id,
                    'amount' => $depositRequest->amount,
                    'user_new_balance' => $transaction->balance_after,
                    'transaction_id' => $transaction->id,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reject a deposit request.
     */
    public function reject(Request $request, int $id): JsonResponse
    {
        $admin = Auth::user();

        if (!$admin || !$admin->is_plearnd_admin) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $validated = $request->validate([
            'rejection_reason' => 'required|string|max:500',
            'admin_note' => 'nullable|string|max:500',
        ]);

        $depositRequest = WalletDepositRequest::find($id);

        if (!$depositRequest) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบคำขอเติมเงิน',
            ], 404);
        }

        if (!$depositRequest->isPending()) {
            return response()->json([
                'success' => false,
                'message' => 'คำขอนี้ถูกดำเนินการแล้ว',
            ], 400);
        }

        $depositRequest->update([
            'status' => 'rejected',
            'reviewed_by' => $admin->id,
            'reviewed_at' => now(),
            'rejection_reason' => $validated['rejection_reason'],
            'admin_note' => $validated['admin_note'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'ปฏิเสธคำขอเติมเงินแล้ว',
            'data' => [
                'id' => $depositRequest->id,
                'status' => 'rejected',
                'rejection_reason' => $validated['rejection_reason'],
            ],
        ]);
    }
}
