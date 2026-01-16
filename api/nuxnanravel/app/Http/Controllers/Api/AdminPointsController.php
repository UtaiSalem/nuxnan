<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\PointsService;
use App\Models\PointsTransaction;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class AdminPointsController extends Controller
{
    protected PointsService $pointsService;

    public function __construct(PointsService $pointsService)
    {
        $this->pointsService = $pointsService;
    }

    /**
     * Get points dashboard statistics.
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

        $totalPointsEarned = User::sum('total_points_earned');
        $totalPointsSpent = User::sum('total_points_spent');
        $totalPoints = User::sum('pp');
        $activeUsers = User::where('pp', '>', 0)->count();

        // Calculate daily earnings
        $today = now()->toDateString();
        $dailyEarnings = PointsTransaction::where('transaction_type', 'earn')
            ->whereDate('created_at', $today)
            ->sum('amount');

        // Calculate weekly earnings
        $weekStart = now()->startOfWeek();
        $weekEnd = now()->endOfWeek();
        $weeklyEarnings = PointsTransaction::where('transaction_type', 'earn')
            ->whereBetween('created_at', [$weekStart, $weekEnd])
            ->sum('amount');

        // Calculate monthly earnings
        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();
        $monthlyEarnings = PointsTransaction::where('transaction_type', 'earn')
            ->whereBetween('created_at', [$monthStart, $monthEnd])
            ->sum('amount');

        return response()->json([
            'success' => true,
            'data' => [
                'total_points' => $totalPoints,
                'total_points_earned' => $totalPointsEarned,
                'total_points_spent' => $totalPointsSpent,
                'active_users' => $activeUsers,
                'daily_earnings' => $dailyEarnings,
                'weekly_earnings' => $weeklyEarnings,
                'monthly_earnings' => $monthlyEarnings,
            ],
        ]);
    }

    /**
     * Get all point rules.
     */
    public function rules(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (!$user || !$user->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $rules = \App\Models\PointRule::withTrashed()->get();

        return response()->json([
            'success' => true,
            'data' => [
                'rules' => $rules,
            ],
        ]);
    }

    /**
     * Create new point rule.
     */
    public function createRule(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (!$user || !$user->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $validated = $request->validate([
            'rule_key' => 'required|string|max:100|unique:point_rules,rule_key',
            'rule_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'action_type' => 'required|in:earn,spend',
            'source_type' => 'required|string|max:50',
            'base_amount' => 'required|numeric|min:0',
            'multiplier' => 'nullable|numeric|min:0|max:10',
            'max_daily_earnings' => 'nullable|integer|min:0',
            'max_monthly_earnings' => 'nullable|integer|min:0',
            'cooldown_minutes' => 'nullable|integer|min:0',
            'effective_date' => 'nullable|date',
            'expiry_date' => 'nullable|date|after:effective_date',
            'is_active' => 'boolean',
        ]);

        $rule = \App\Models\PointRule::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Rule created successfully',
            'data' => [
                'rule' => $rule,
            ],
        ], 201);
    }

    /**
     * Update point rule.
     */
    public function updateRule(Request $request, int $id): JsonResponse
    {
        $user = Auth::user();

        if (!$user || !$user->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $rule = \App\Models\PointRule::find($id);

        if (!$rule) {
            return response()->json([
                'success' => false,
                'message' => 'Rule not found',
            ], 404);
        }

        $validated = $request->validate([
            'rule_name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'base_amount' => 'nullable|numeric|min:0',
            'multiplier' => 'nullable|numeric|min:0|max:10',
            'max_daily_earnings' => 'nullable|integer|min:0',
            'max_monthly_earnings' => 'nullable|integer|min:0',
            'cooldown_minutes' => 'nullable|integer|min:0',
            'effective_date' => 'nullable|date',
            'expiry_date' => 'nullable|date|after:effective_date',
            'is_active' => 'boolean',
        ]);

        $rule->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Rule updated successfully',
            'data' => [
                'rule' => $rule,
            ],
        ]);
    }

    /**
     * Delete point rule.
     */
    public function deleteRule(Request $request, int $id): JsonResponse
    {
        $user = Auth::user();

        if (!$user || !$user->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $rule = \App\Models\PointRule::find($id);

        if (!$rule) {
            return response()->json([
                'success' => false,
                'message' => 'Rule not found',
            ], 404);
        }

        $rule->delete();

        return response()->json([
            'success' => true,
            'message' => 'Rule deleted successfully',
        ]);
    }

    /**
     * Adjust user points.
     */
    public function adjustPoints(Request $request, int $userId): JsonResponse
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

        $result = $this->pointsService->adminAdjust(
            $targetUser,
            $validated['amount'],
            $validated['action_type'],
            $validated['reason']
        );

        return response()->json([
            'success' => true,
            'message' => 'Points adjusted successfully',
            'data' => [
                'user_id' => $userId,
                'new_balance' => $targetUser->pp,
                'transaction_id' => $result->id,
            ],
        ]);
    }

    /**
     * Get user points transactions.
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

        $query = PointsTransaction::where('user_id', $userId);

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
     * Get leaderboard.
     */
    public function leaderboard(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (!$user || !$user->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $type = $request->input('type', 'points');
        $page = $request->input('page', 1);
        $perPage = $request->input('per_page', 20);

        $query = User::query();

        switch ($type) {
            case 'points':
                $query->orderByDesc('pp');
                break;
            case 'weekly':
                $query->with(['pointsTransactions' => function ($query) {
                    $query->where('transaction_type', 'earn')
                        ->where('created_at', '>=', now()->startOfWeek())
                        ->where('created_at', '<=', now()->endOfWeek());
                }])
                ->selectRaw('users.*, COALESCE(SUM(points_transactions.amount), 0) as weekly_points')
                ->groupBy('users.id')
                ->orderByDesc('weekly_points');
                break;
            case 'monthly':
                $query->with(['pointsTransactions' => function ($query) {
                    $query->where('transaction_type', 'earn')
                        ->where('created_at', '>=', now()->startOfMonth())
                        ->where('created_at', '<=', now()->endOfMonth());
                }])
                ->selectRaw('users.*, COALESCE(SUM(points_transactions.amount), 0) as monthly_points')
                ->groupBy('users.id')
                ->orderByDesc('monthly_points');
                break;
            default:
                $query->orderByDesc('pp');
                break;
        }

        $users = $query->paginate($perPage, ['id', 'username', 'pp', 'level', 'profile_photo_path'], 'page', $page);

        $leaderboard = [];
        $rank = 1;

        foreach ($users->items() as $userItem) {
            $leaderboard[] = [
                'rank' => $rank++,
                'user_id' => $userItem->id,
                'username' => $userItem->username,
                'avatar' => $userItem->profile_photo_path,
                'score' => match($type) {
                    'points' => $userItem->pp,
                    'weekly' => $userItem->weekly_points ?? $userItem->pp,
                    'monthly' => $userItem->monthly_points ?? $userItem->pp,
                    default => $userItem->pp,
                },
                'level' => $userItem->level ?? 1,
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'type' => $type,
                'leaderboard' => $leaderboard,
                'pagination' => [
                    'current_page' => $users->currentPage(),
                    'total_pages' => $users->lastPage(),
                    'per_page' => $users->perPage(),
                    'total_items' => $users->total(),
                ],
            ],
        ]);
    }

    /**
     * Get analytics.
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

        $query = PointsTransaction::query();

        if ($dateFrom) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        // Get daily trend
        $dailyTrend = PointsTransaction::selectRaw('DATE(created_at) as date, SUM(amount) as total')
            ->where('transaction_type', 'earn')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(fn($item) => [
                'date' => $item->date,
                'total' => (float) $item->total,
            ]);

        // Get distribution by source type
        $distribution = PointsTransaction::selectRaw('source_type, SUM(amount) as total')
            ->where('transaction_type', 'earn')
            ->groupBy('source_type')
            ->get()
            ->map(fn($item) => [
                'source_type' => $item->source_type,
                'total' => (float) $item->total,
            ]);

        // Get top earners
        $topEarners = User::selectRaw('users.*, SUM(total_points_earned) as total_earned')
            ->orderByDesc('total_earned')
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'daily_trend' => $dailyTrend,
                'distribution' => $distribution,
                'top_earners' => $topEarners,
            ],
        ]);
    }
}
