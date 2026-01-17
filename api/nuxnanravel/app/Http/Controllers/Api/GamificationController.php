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

    /**
     * Record login for streak.
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

        $result = $this->gamificationService->recordLogin($user);

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    /**
     * Get streak info.
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

        $streak = $this->gamificationService->getUserStreak($user);

        return response()->json([
            'success' => true,
            'data' => $streak,
        ]);
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

        $achievements = $this->achievementService->getUserAchievements($user);

        return response()->json([
            'success' => true,
            'data' => [
                'achievements' => $achievements,
            ],
        ]);
    }

    /**
     * Get available achievements.
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

        $achievements = $this->achievementService->getAvailableAchievements($user);

        return response()->json([
            'success' => true,
            'data' => [
                'achievements' => $achievements,
            ],
        ]);
    }

    /**
     * Get achievement stats.
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

        $stats = $this->achievementService->getAchievementStats($user);

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Get points leaderboard.
     */
    public function getPointsLeaderboard(Request $request): JsonResponse
    {
        $limit = $request->input('limit', 10);

        $users = \App\Models\User::orderBy('pp', 'desc')
            ->limit($limit)
            ->get(['id', 'name', 'pp', 'profile_photo_path'])
            ->map(function ($user, $index) {
                return [
                    'rank' => $index + 1,
                    'id' => $user->id,
                    'name' => $user->name,
                    'username' => $user->name,
                    'points' => $user->pp,
                    'avatar' => $user->profile_photo_url,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'leaderboard' => $users,
            ],
        ]);
    }

    /**
     * Get streak leaderboard.
     */
    public function getStreakLeaderboard(Request $request): JsonResponse
    {
        $limit = $request->input('limit', 10);

        $streaks = \App\Models\PointStreak::with('user:id,name,profile_photo_path')
            ->orderBy('current_streak', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($streak, $index) {
                return [
                    'rank' => $index + 1,
                    'id' => $streak->user->id ?? 0,
                    'name' => $streak->user->name ?? 'Unknown',
                    'username' => $streak->user->name ?? '',
                    'streak' => $streak->current_streak,
                    'avatar' => $streak->user->profile_photo_url ?? '/images/default-avatar.png',
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'leaderboard' => $streaks,
            ],
        ]);
    }

    /**
     * Get achievement leaderboard.
     */
    public function getAchievementLeaderboard(Request $request): JsonResponse
    {
        $limit = $request->input('limit', 10);

        $users = \App\Models\User::withCount('userAchievements')
            ->orderBy('user_achievements_count', 'desc')
            ->limit($limit)
            ->get(['id', 'name', 'profile_photo_path'])
            ->map(function ($user, $index) {
                return [
                    'rank' => $index + 1,
                    'id' => $user->id,
                    'name' => $user->name,
                    'username' => $user->name,
                    'achievements' => $user->user_achievements_count,
                    'avatar' => $user->profile_photo_url,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'leaderboard' => $users,
            ],
        ]);
    }

    /**
     * Get level leaderboard.
     */
    public function getLevelLeaderboard(Request $request): JsonResponse
    {
        $limit = $request->input('limit', 10);

        $users = \App\Models\User::orderBy('level', 'desc')
            ->orderBy('pp', 'desc')
            ->limit($limit)
            ->get(['id', 'name', 'level', 'profile_photo_path'])
            ->map(function ($user, $index) {
                return [
                    'rank' => $index + 1,
                    'id' => $user->id,
                    'name' => $user->name,
                    'username' => $user->name,
                    'level' => $user->level ?? 1,
                    'avatar' => $user->profile_photo_url,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'leaderboard' => $users,
            ],
        ]);
    }

    /**
     * Get leaderboard summary.
     */
    public function getLeaderboardSummary(Request $request): JsonResponse
    {
        $user = Auth::user();

        return response()->json([
            'success' => true,
            'data' => [
                'points_rank' => $user ? \App\Models\User::where('pp', '>', $user->pp)->count() + 1 : null,
                'total_users' => \App\Models\User::count(),
            ],
        ]);
    }
}
