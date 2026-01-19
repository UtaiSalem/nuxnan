<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PointsTransaction;
use App\Models\User;
use App\Services\PointsService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class PointsController extends Controller
{
    protected PointsService $pointsService;

    public function __construct(PointsService $pointsService)
    {
        $this->pointsService = $pointsService;
    }

    /**
     * Get user points balance.
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

        $balance = $this->pointsService->getBalance($user);

        return response()->json([
            'success' => true,
            'data' => [
                'current_points' => $balance['current_points'],
                'total_earned' => $balance['total_earned'],
                'total_spent' => $balance['total_spent'],
                'level' => $balance['level'],
                'current_xp' => $balance['current_xp'],
                'xp_for_next_level' => $balance['xp_for_next_level'],
                'progress_percentage' => $balance['progress_percentage'],
            ],
        ]);
    }

    /**
     * Earn points.
     */
    public function earn(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated',
            ], 401);
        }

        $validated = $request->validate([
            'source_type' => 'required|string|max:50',
            'source_id' => 'nullable|integer',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:255',
            'metadata' => 'nullable|array',
        ]);

        try {
            $result = $this->pointsService->earn(
                $user,
                $validated['amount'],
                $validated['source_type'],
                $validated['source_id'] ?? null,
                $validated['description'] ?? null,
                $validated['metadata'] ?? null
            );

            return response()->json([
                'success' => true,
                'message' => 'Points earned successfully',
                'data' => [
                    'points_earned' => $result->amount,
                    'new_balance' => $result->balance_after,
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
     * Spend points.
     */
    public function spend(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated',
            ], 401);
        }

        $validated = $request->validate([
            'source_type' => 'required|string|max:50',
            'source_id' => 'nullable|integer',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:255',
            'metadata' => 'nullable|array',
        ]);

        try {
            $result = $this->pointsService->spend(
                $user,
                $validated['amount'],
                $validated['source_type'],
                $validated['source_id'] ?? null,
                $validated['description'] ?? null,
                $validated['metadata'] ?? null
            );

            if (!$result) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient points',
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => 'Points spent successfully',
                'data' => [
                    'points_spent' => $result->amount,
                    'new_balance' => $result->balance_after,
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
     * Refund points.
     */
    public function refund(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated',
            ], 401);
        }

        $validated = $request->validate([
            'transaction_id' => 'nullable|integer|exists:points_transactions,id',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:255',
            'metadata' => 'nullable|array',
        ]);

        try {
            $result = $this->pointsService->refund(
                $user,
                $validated['amount'],
                'refund',
                $validated['transaction_id'] ?? null,
                $validated['description'] ?? null,
                $validated['metadata'] ?? null
            );

            return response()->json([
                'success' => true,
                'message' => 'Points refunded successfully',
                'data' => [
                    'points_refunded' => $result->amount,
                    'new_balance' => $result->balance_after,
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
     * Transfer points to another user.
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
            'amount' => 'required|numeric|min:0.01',
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

            $result = $this->pointsService->transfer(
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
                'message' => 'Points transferred successfully',
                'data' => [
                    'points_transferred' => $validated['amount'],
                    'new_balance' => $user->pp,
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
    public function convert(Request $request): JsonResponse
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
            'target' => 'required|in:wallet',
        ]);

        try {
            $result = $this->pointsService->convertPointsToWallet($user, $validated['points']);

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
     * Get points transactions.
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

        $query = PointsTransaction::where('user_id', $user->id);

        // Filter by type
        if ($request->has('type') && $request->type) {
            $query->where('transaction_type', $request->type);
        }

        // Filter by source type
        if ($request->has('source_type') && $request->source_type) {
            $query->where('source_type', $request->source_type);
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

        // Transform transactions to include sender/receiver info for transfers
        $transformedTransactions = collect($transactions->items())->map(function ($tx) use ($user) {
            $txArray = $tx->toArray();
            
            // For transfer transactions, include sender and receiver info
            if (in_array($tx->transaction_type, ['transfer_in', 'transfer_out']) && $tx->source_id) {
                $relatedUser = User::find($tx->source_id);
                
                if ($tx->transaction_type === 'transfer_in') {
                    // User received points - sender is related user, receiver is current user
                    $txArray['sender'] = $relatedUser ? [
                        'id' => $relatedUser->id,
                        'name' => $relatedUser->name,
                        'avatar' => $relatedUser->profile_photo_url ?? $this->getUiAvatarUrl($relatedUser->name),
                    ] : null;
                    $txArray['receiver'] = [
                        'id' => $user->id,
                        'name' => $user->name,
                        'avatar' => $user->profile_photo_url ?? $this->getUiAvatarUrl($user->name),
                    ];
                } else {
                    // User sent points - sender is current user, receiver is related user
                    $txArray['sender'] = [
                        'id' => $user->id,
                        'name' => $user->name,
                        'avatar' => $user->profile_photo_url ?? $this->getUiAvatarUrl($user->name),
                    ];
                    $txArray['receiver'] = $relatedUser ? [
                        'id' => $relatedUser->id,
                        'name' => $relatedUser->name,
                        'avatar' => $relatedUser->profile_photo_url ?? $this->getUiAvatarUrl($relatedUser->name),
                    ] : null;
                }
            }
            
            return $txArray;
        });

        return response()->json([
            'success' => true,
            'data' => [
                'transactions' => $transformedTransactions,
                'current_page' => $transactions->currentPage(),
                'total_pages' => $transactions->lastPage(),
                'per_page' => $transactions->perPage(),
                'total' => $transactions->total(),
            ],
        ]);
    }

    /**
     * Get UI Avatar URL for fallback
     */
    private function getUiAvatarUrl(string $name): string
    {
        return 'https://ui-avatars.com/api/?name=' . urlencode($name) . '&background=random&color=fff';
    }

    /**
     * Get point rules.
     */
    public function rules(Request $request): JsonResponse
    {
        $query = \App\Models\PointRule::active();

        // Filter by action type
        if ($request->has('action_type') && $request->action_type) {
            $query->actionType($request->action_type);
        }

        // Filter by source type
        if ($request->has('source_type') && $request->source_type) {
            $query->sourceType($request->source_type);
        }

        $rules = $query->get();

        return response()->json([
            'success' => true,
            'data' => [
                'rules' => $rules,
            ],
        ]);
    }
}
