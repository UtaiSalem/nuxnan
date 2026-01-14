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

        $balance = $this->pointsService->getBalance($user->id);

        return response()->json([
            'success' => true,
            'data' => [
                'current_points' => $balance['current_points'],
                'bonus_points' => $balance['bonus_points'],
                'available_points' => $balance['available_points'],
                'locked_points' => $balance['locked_points'],
                'total_points_earned' => $user->total_points_earned ?? 0,
                'total_points_spent' => $user->total_points_spent ?? 0,
                'level' => $user->level ?? 1,
                'current_xp' => $user->current_xp ?? 0,
                'xp_for_next_level' => $user->xp_for_next_level ?? 100,
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
            'amount' => 'required|integer|min:1',
            'description' => 'nullable|string|max:255',
            'metadata' => 'nullable|array',
        ]);

        try {
            $result = $this->pointsService->earn($user->id, $validated);

            return response()->json([
                'success' => true,
                'message' => 'Points earned successfully',
                'data' => [
                    'points_earned' => $result['points_earned'],
                    'new_balance' => $result['new_balance'],
                    'level' => $result['level'],
                    'leveled_up' => $result['leveled_up'],
                    'achievements_unlocked' => $result['achievements_unlocked'] ?? [],
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
            'amount' => 'required|integer|min:1',
            'description' => 'nullable|string|max:255',
            'metadata' => 'nullable|array',
        ]);

        try {
            $result = $this->pointsService->spend($user->id, $validated);

            return response()->json([
                'success' => true,
                'message' => 'Points spent successfully',
                'data' => [
                    'points_spent' => $result['points_spent'],
                    'new_balance' => $result['new_balance'],
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
            'transaction_id' => 'required|integer|exists:points_transactions,id',
            'amount' => 'required|integer|min:1',
            'description' => 'nullable|string|max:255',
            'metadata' => 'nullable|array',
        ]);

        try {
            $result = $this->pointsService->refund($user->id, $validated);

            return response()->json([
                'success' => true,
                'message' => 'Points refunded successfully',
                'data' => [
                    'points_refunded' => $result['points_refunded'],
                    'new_balance' => $result['new_balance'],
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
            'amount' => 'required|integer|min:1',
            'message' => 'nullable|string|max:255',
            'metadata' => 'nullable|array',
        ]);

        try {
            $result = $this->pointsService->transfer($user->id, $validated);

            return response()->json([
                'success' => true,
                'message' => 'Points transferred successfully',
                'data' => [
                    'points_transferred' => $result['points_transferred'],
                    'new_balance' => $result['new_balance'],
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
            'points' => 'required|integer|min:1080', // Minimum 1080 points (1 THB)
            'target' => 'required|in:wallet',
        ]);

        try {
            $result = $this->pointsService->convertPointsToWallet($user->id, $validated['points']);

            return response()->json([
                'success' => true,
                'message' => 'Points converted successfully',
                'data' => [
                    'points_converted' => $result['points_converted'],
                    'wallet_amount' => $result['wallet_amount'],
                    'new_points_balance' => $result['new_points_balance'],
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
