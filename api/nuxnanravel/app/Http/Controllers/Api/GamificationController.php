<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AchievementService;
use App\Services\StreakService;
use App\Services\LeaderboardService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class GamificationController extends Controller
{
    protected AchievementService $achievementService;
    protected StreakService $streakService;
    protected LeaderboardService $leaderboardService;

    public function __construct(
        AchievementService $achievementService,
        StreakService $streakService,
        LeaderboardService $leaderboardService
    ) {
        $this->achievementService = $achievementService;
        $this->streakService = $streakService;
        $this->leaderboardService = $leaderboardService;
    }

    /**
     * Record user login and update streak.
     */
    public function recordLogin(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated',
            ], 401);
        }

        try {
            $result = $this->streakService->recordLogin($user->id);

            return response()->json([
                'success' => true,
                'message' => $result['message'] ?? 'Login recorded successfully',
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get user streak information.
     */
    public function getStreakInfo(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated',
            ], 401);
        }

        try {
            $streakInfo = $this->streakService->getStreakInfo($user->id);

            return response()->json([
                'success' => true,
                'data' => $streakInfo,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get user achievements.
     */
    public function getAchievements(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated',
            ], 401);
        }

        try {
            $achievements = $this->achievementService->getUserAchievements($user->id);

            return response()->json([
                'success' => true,
                'data' => [
                    'achievements' => $achievements,
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
     * Get available achievements (not yet unlocked).
     */
    public function getAvailableAchievements(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated',
            ], 401);
        }

        try {
            $achievements = $this->achievementService->getAvailableAchievements($user->id);

            return response()->json([
                'success' => true,
                'data' => [
                    'achievements' => $achievements,
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
     * Get achievement statistics.
     */
    public function getAchievementStats(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated',
            ], 401);
        }

        try {
            $stats = $this->achievementService->getAchievementStats($user->id);

            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get points leaderboard.
     */
    public function getPointsLeaderboard(Request $request): JsonResponse
    {
        $limit = $request->input('limit', 10);
        $page = $request->input('page', 1);

        try {
            $leaderboard = $this->leaderboardService->getPointsLeaderboard($limit, $page);

            return response()->json([
                'success' => true,
                'data' => $leaderboard,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get streak leaderboard.
     */
    public function getStreakLeaderboard(Request $request): JsonResponse
    {
        $limit = $request->input('limit', 10);
        $page = $request->input('page', 1);

        try {
            $leaderboard = $this->leaderboardService->getStreakLeaderboard($limit, $page);

            return response()->json([
                'success' => true,
                'data' => $leaderboard,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get achievement leaderboard.
     */
    public function getAchievementLeaderboard(Request $request): JsonResponse
    {
        $limit = $request->input('limit', 10);
        $page = $request->input('page', 1);

        try {
            $leaderboard = $this->leaderboardService->getAchievementLeaderboard($limit, $page);

            return response()->json([
                'success' => true,
                'data' => $leaderboard,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get level leaderboard.
     */
    public function getLevelLeaderboard(Request $request): JsonResponse
    {
        $limit = $request->input('limit', 10);
        $page = $request->input('page', 1);

        try {
            $leaderboard = $this->leaderboardService->getLevelLeaderboard($limit, $page);

            return response()->json([
                'success' => true,
                'data' => $leaderboard,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get user's leaderboard summary.
     */
    public function getLeaderboardSummary(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated',
            ], 401);
        }

        try {
            $summary = $this->leaderboardService->getUserLeaderboardSummary($user->id);

            return response()->json([
                'success' => true,
                'data' => $summary,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
