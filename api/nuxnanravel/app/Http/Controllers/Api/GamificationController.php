<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\GamificationService;
use App\Services\AchievementService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class GamificationController extends Controller
{
    protected GamificationService $gamificationService;
    protected AchievementService $achievementService;

    public function __construct(
        GamificationService $gamificationService,
        AchievementService $achievementService
    ) {
        $this->gamificationService = $gamificationService;
        $this->achievementService = $achievementService;
    }

    /**
     * Get user achievements.
     */
    public function achievements(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated',
            ], 401);
        }

        $achievements = $this->achievementService->getUserAchievements($user);

        return response()->json([
            'success' => true,
            'data' => [
                'achievements' => $achievements,
                'stats' => $this->achievementService->getAchievementStats($user),
            ],
        ]);
    }

    /**
     * Get leaderboard.
     */
    public function leaderboard(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated',
            ], 401);
        }

        $validated = $request->validate([
            'type' => 'required|in:points,weekly,monthly,streak,achievements',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $type = $validated['type'] ?? 'points';
        $page = $validated['page'] ?? 1;
        $perPage = $validated['per_page'] ?? 20;

        $leaderboard = $this->gamificationService->getLeaderboard($type, $page, $perPage);
        $userRank = $this->gamificationService->getUserRank($user, $type);

        return response()->json([
            'success' => true,
            'data' => array_merge($leaderboard, [
                'user_rank' => $userRank,
            ]),
        ]);
    }

    /**
     * Get user streak.
     */
    public function streak(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated',
            ], 401);
        }

        $streak = $this->gamificationService->getUserStreak($user);

        return response()->json([
            'success' => true,
            'data' => $streak,
        ]);
    }

    /**
     * Get user level.
     */
    public function level(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated',
            ], 401);
        }

        $level = $this->gamificationService->getUserLevel($user);

        return response()->json([
            'success' => true,
            'data' => $level,
        ]);
    }

    /**
     * Record user activity.
     */
    public function recordActivity(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated',
            ], 401);
        }

        $result = $this->gamificationService->recordActivity($user);

        return response()->json([
            'success' => true,
            'message' => 'Activity recorded successfully',
            'data' => $result,
        ]);
    }

    /**
     * Initialize default data.
     */
    public function initialize(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated',
            ], 401);
        }

        if (!$user->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $this->gamificationService->initializeDefaultData();

        return response()->json([
            'success' => true,
            'message' => 'Default data initialized successfully',
        ]);
    }
}
