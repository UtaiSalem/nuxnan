<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PointsService;
use App\Services\AchievementService;
use App\Services\StreakService;
use App\Services\LeaderboardService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\PointsTransaction;
use App\Models\PointRule;
use App\Models\DailyPointLimit;

class AdminPointsController extends Controller
{
    protected PointsService $pointsService;
    protected AchievementService $achievementService;
    protected StreakService $streakService;
    protected LeaderboardService $leaderboardService;

    public function __construct(
        PointsService $pointsService,
        AchievementService $achievementService,
        StreakService $streakService,
        LeaderboardService $leaderboardService
    ) {
        $this->pointsService = $pointsService;
        $this->achievementService = $achievementService;
        $this->streakService = $streakService;
        $this->leaderboardService = $leaderboardService;
    }

    /**
     * Get points statistics
     */
    public function stats(): JsonResponse
    {
        $totalPoints = User::sum('pp');
        $totalPointsEarned = User::sum('total_points_earned');
        $totalPointsSpent = User::sum('total_points_spent');
        $totalUsers = User::count();
        $activeUsers = User::where('pp', '>', 0)->count();

        // Get daily stats
        $today = now()->toDateString();
        $todayEarned = DailyPointLimit::where('date', $today)->sum('points_earned');
        $todaySpent = DailyPointLimit::where('date', $today)->sum('points_spent');

        // Get transaction counts
        $totalTransactions = PointsTransaction::count();
        $pendingTransactions = PointsTransaction::where('status', 'pending')->count();

        return response()->json([
            'success' => true,
            'data' => [
                'total_points' => $totalPoints,
                'total_points_earned' => $totalPointsEarned,
                'total_points_spent' => $totalPointsSpent,
                'net_points' => $totalPointsEarned - $totalPointsSpent,
                'total_users' => $totalUsers,
                'active_users' => $activeUsers,
                'today_earned' => $todayEarned,
                'today_spent' => $todaySpent,
                'total_transactions' => $totalTransactions,
                'pending_transactions' => $pendingTransactions,
            ]
        ]);
    }

    /**
     * Get all point rules
     */
    public function rules(): JsonResponse
    {
        $rules = PointRule::orderBy('is_active', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $rules
        ]);
    }

    /**
     * Create a new point rule
     */
    public function createRule(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'rule_key' => 'required|string|unique:point_rules,rule_key',
            'rule_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'action_type' => 'required|in:earn,spend',
            'source_type' => 'required|string|max:50',
            'base_amount' => 'required|numeric|min:0',
            'multiplier' => 'nullable|numeric|min:0|max:10',
            'max_daily_earnings' => 'nullable|integer|min:0',
            'max_monthly_earnings' => 'nullable|integer|min:0',
            'cooldown_minutes' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
            'effective_date' => 'nullable|date',
            'expiry_date' => 'nullable|date|after:effective_date',
        ]);

        $rule = PointRule::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Point rule created successfully',
            'data' => $rule
        ], 201);
    }

    /**
     * Update a point rule
     */
    public function updateRule(Request $request, $id): JsonResponse
    {
        $rule = PointRule::findOrFail($id);

        $validated = $request->validate([
            'rule_key' => 'required|string|unique:point_rules,rule_key,' . $id,
            'rule_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'action_type' => 'required|in:earn,spend',
            'source_type' => 'required|string|max:50',
            'base_amount' => 'required|numeric|min:0',
            'multiplier' => 'nullable|numeric|min:0|max:10',
            'max_daily_earnings' => 'nullable|integer|min:0',
            'max_monthly_earnings' => 'nullable|integer|min:0',
            'cooldown_minutes' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
            'effective_date' => 'nullable|date',
            'expiry_date' => 'nullable|date|after:effective_date',
        ]);

        $rule->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Point rule updated successfully',
            'data' => $rule
        ]);
    }

    /**
     * Delete a point rule
     */
    public function deleteRule($id): JsonResponse
    {
        $rule = PointRule::findOrFail($id);
        $rule->delete();

        return response()->json([
            'success' => true,
            'message' => 'Point rule deleted successfully'
        ]);
    }

    /**
     * Adjust user points
     */
    public function adjustPoints(Request $request, $userId): JsonResponse
    {
        $validated = $request->validate([
            'action' => 'required|in:add,deduct,set',
            'amount' => 'required|numeric|min:0',
            'reason' => 'required|string|max:500',
        ]);

        $user = User::findOrFail($userId);

        try {
            DB::beginTransaction();

            $balanceBefore = $user->pp;
            $pointsToAdjust = $validated['amount'];

            switch ($validated['action']) {
                case 'add':
                    $user->pp += $pointsToAdjust;
                    $user->total_points_earned += $pointsToAdjust;
                    $transactionType = 'admin_adjust';
                    $amount = $pointsToAdjust;
                    break;

                case 'deduct':
                    $user->pp = max(0, $user->pp - $pointsToAdjust);
                    $user->total_points_spent += $pointsToAdjust;
                    $transactionType = 'admin_adjust';
                    $amount = -$pointsToAdjust;
                    break;

                case 'set':
                    $difference = $pointsToAdjust - $user->pp;
                    $user->pp = $pointsToAdjust;
                    if ($difference > 0) {
                        $user->total_points_earned += $difference;
                    } else {
                        $user->total_points_spent += abs($difference);
                    }
                    $transactionType = 'admin_adjust';
                    $amount = $difference;
                    break;
            }

            $balanceAfter = $user->pp;
            $user->save();

            // Record transaction
            PointsTransaction::create([
                'user_id' => $user->id,
                'transaction_type' => $transactionType,
                'amount' => abs($amount),
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'source_type' => 'admin',
                'source_id' => auth()->id(),
                'description' => $validated['reason'],
                'metadata' => json_encode([
                    'admin_id' => auth()->id(),
                    'action' => $validated['action'],
                    'original_amount' => $pointsToAdjust
                ]),
                'status' => 'completed',
            ]);

            // Update user level
            $this->pointsService->updateUserLevel($user);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Points adjusted successfully',
                'data' => [
                    'user_id' => $user->id,
                    'balance_before' => $balanceBefore,
                    'balance_after' => $balanceAfter,
                    'amount_adjusted' => $amount,
                    'new_level' => $user->level,
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to adjust points: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user transactions
     */
    public function userTransactions(Request $request, $userId): JsonResponse
    {
        $user = User::findOrFail($userId);

        $transactions = PointsTransaction::where('user_id', $userId)
            ->with(['user:id,name,avatar'])
            ->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'current_points' => $user->pp,
                    'total_earned' => $user->total_points_earned,
                    'total_spent' => $user->total_points_spent,
                    'level' => $user->level,
                ],
                'transactions' => $transactions
            ]
        ]);
    }

    /**
     * Get leaderboard
     */
    public function leaderboard(Request $request): JsonResponse
    {
        $type = $request->input('type', 'points');
        $period = $request->input('period', 'all_time');
        $limit = $request->input('limit', 50);

        $leaderboard = match ($type) {
            'points' => $this->leaderboardService->getPointsLeaderboard($limit),
            'streak' => $this->leaderboardService->getStreakLeaderboard($limit),
            'achievements' => $this->leaderboardService->getAchievementLeaderboard($limit),
            'level' => $this->leaderboardService->getLevelLeaderboard($limit),
            default => $this->leaderboardService->getPointsLeaderboard($limit),
        };

        return response()->json([
            'success' => true,
            'data' => [
                'type' => $type,
                'period' => $period,
                'leaderboard' => $leaderboard
            ]
        ]);
    }

    /**
     * Get analytics
     */
    public function analytics(Request $request): JsonResponse
    {
        $startDate = $request->input('start_date', now()->subDays(30)->toDateString());
        $endDate = $request->input('end_date', now()->toDateString());

        // Daily trend
        $dailyTrend = PointsTransaction::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('SUM(CASE WHEN transaction_type = "earn" THEN amount ELSE 0 END) as earned'),
            DB::raw('SUM(CASE WHEN transaction_type = "spend" THEN amount ELSE 0 END) as spent'),
            DB::raw('COUNT(*) as total_transactions')
        )
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Distribution by source type
        $distribution = PointsTransaction::select(
            'source_type',
            DB::raw('COUNT(*) as count'),
            DB::raw('SUM(amount) as total_amount')
        )
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('source_type')
            ->orderByDesc('total_amount')
            ->get();

        // Top earners
        $topEarners = User::orderByDesc('total_points_earned')
            ->limit(10)
            ->get(['id', 'name', 'avatar', 'pp', 'total_points_earned', 'level']);

        // Top spenders
        $topSpenders = User::orderByDesc('total_points_spent')
            ->limit(10)
            ->get(['id', 'name', 'avatar', 'pp', 'total_points_spent', 'level']);

        // Transaction types breakdown
        $transactionTypes = PointsTransaction::select(
            'transaction_type',
            DB::raw('COUNT(*) as count'),
            DB::raw('SUM(amount) as total_amount')
        )
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('transaction_type')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'period' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                ],
                'daily_trend' => $dailyTrend,
                'source_distribution' => $distribution,
                'top_earners' => $topEarners,
                'top_spenders' => $topSpenders,
                'transaction_types' => $transactionTypes,
            ]
        ]);
    }
}
