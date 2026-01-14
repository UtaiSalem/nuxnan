<?php

namespace App\Services;

use App\Models\User;
use App\Models\PointStreak;
use App\Models\UserAchievement;
use Illuminate\Support\Facades\DB;

class LeaderboardService
{
    /**
     * Get points leaderboard.
     */
    public function getPointsLeaderboard(int $limit = 10, int $page = 1): array
    {
        $offset = ($page - 1) * $limit;

        $users = User::select('id', 'name', 'profile_photo_path', 'total_points_earned', 'level')
            ->orderBy('total_points_earned', 'desc')
            ->offset($offset)
            ->limit($limit)
            ->get();

        $totalUsers = User::count();
        $totalPages = ceil($totalUsers / $limit);

        $leaderboard = [];

        foreach ($users as $index => $user) {
            $leaderboard[] = [
                'rank' => $offset + $index + 1,
                'user_id' => $user->id,
                'user_name' => $user->name,
                'profile_photo_path' => $user->profile_photo_path,
                'total_points' => $user->total_points_earned ?? 0,
                'level' => $user->level ?? 1,
            ];
        }

        return [
            'leaderboard' => $leaderboard,
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_users' => $totalUsers,
            'per_page' => $limit,
        ];
    }

    /**
     * Get streak leaderboard.
     */
    public function getStreakLeaderboard(int $limit = 10, int $page = 1): array
    {
        $offset = ($page - 1) * $limit;

        $streaks = PointStreak::with('user:id,name,profile_photo_path')
            ->orderBy('current_streak', 'desc')
            ->offset($offset)
            ->limit($limit)
            ->get();

        $totalStreaks = PointStreak::count();
        $totalPages = ceil($totalStreaks / $limit);

        $leaderboard = [];

        foreach ($streaks as $index => $streak) {
            $leaderboard[] = [
                'rank' => $offset + $index + 1,
                'user_id' => $streak->user_id,
                'user_name' => $streak->user->name,
                'profile_photo_path' => $streak->user->profile_photo_path,
                'current_streak' => $streak->current_streak,
                'longest_streak' => $streak->longest_streak,
                'streak_level' => $streak->getStreakLevel(),
                'streak_icon' => $streak->getStreakIcon(),
                'streak_level_color' => $streak->getStreakLevelColor(),
            ];
        }

        return [
            'leaderboard' => $leaderboard,
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_users' => $totalStreaks,
            'per_page' => $limit,
        ];
    }

    /**
     * Get achievement leaderboard.
     */
    public function getAchievementLeaderboard(int $limit = 10, int $page = 1): array
    {
        $offset = ($page - 1) * $limit;

        $achievementCounts = DB::table('user_achievements')
            ->select('user_id', DB::raw('COUNT(*) as achievement_count'))
            ->where('is_completed', true)
            ->groupBy('user_id')
            ->orderBy('achievement_count', 'desc')
            ->offset($offset)
            ->limit($limit)
            ->get();

        $totalUsers = DB::table('user_achievements')
            ->select(DB::raw('COUNT(DISTINCT user_id) as total'))
            ->where('is_completed', true)
            ->first()
            ->total ?? 0;

        $totalPages = ceil($totalUsers / $limit);

        $leaderboard = [];

        foreach ($achievementCounts as $index => $item) {
            $user = User::find($item->user_id);
            
            if (!$user) {
                continue;
            }

            $leaderboard[] = [
                'rank' => $offset + $index + 1,
                'user_id' => $user->id,
                'user_name' => $user->name,
                'profile_photo_path' => $user->profile_photo_path,
                'achievement_count' => $item->achievement_count,
                'level' => $user->level ?? 1,
            ];
        }

        return [
            'leaderboard' => $leaderboard,
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_users' => $totalUsers,
            'per_page' => $limit,
        ];
    }

    /**
     * Get level leaderboard.
     */
    public function getLevelLeaderboard(int $limit = 10, int $page = 1): array
    {
        $offset = ($page - 1) * $limit;

        $users = User::select('id', 'name', 'profile_photo_path', 'level', 'total_points_earned')
            ->orderBy('level', 'desc')
            ->orderBy('total_points_earned', 'desc')
            ->offset($offset)
            ->limit($limit)
            ->get();

        $totalUsers = User::count();
        $totalPages = ceil($totalUsers / $limit);

        $leaderboard = [];

        foreach ($users as $index => $user) {
            $leaderboard[] = [
                'rank' => $offset + $index + 1,
                'user_id' => $user->id,
                'user_name' => $user->name,
                'profile_photo_path' => $user->profile_photo_path,
                'level' => $user->level ?? 1,
                'total_points' => $user->total_points_earned ?? 0,
            ];
        }

        return [
            'leaderboard' => $leaderboard,
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_users' => $totalUsers,
            'per_page' => $limit,
        ];
    }

    /**
     * Get user's rank on points leaderboard.
     */
    public function getUserPointsRank(int $userId): int
    {
        $rank = User::where('total_points_earned', '>', function ($query) use ($userId) {
            $query->select('total_points_earned')
                ->from('users')
                ->where('id', $userId);
        })->count();

        return $rank + 1;
    }

    /**
     * Get user's rank on streak leaderboard.
     */
    public function getUserStreakRank(int $userId): int
    {
        $userStreak = PointStreak::where('user_id', $userId)->first();

        if (!$userStreak) {
            return 0;
        }

        $rank = PointStreak::where('current_streak', '>', $userStreak->current_streak)
            ->count();

        return $rank + 1;
    }

    /**
     * Get user's rank on achievement leaderboard.
     */
    public function getUserAchievementRank(int $userId): int
    {
        $userAchievementCount = UserAchievement::where('user_id', $userId)
            ->where('is_completed', true)
            ->count();

        $rank = DB::table('user_achievements')
            ->select('user_id', DB::raw('COUNT(*) as achievement_count'))
            ->where('is_completed', true)
            ->groupBy('user_id')
            ->having('achievement_count', '>', $userAchievementCount)
            ->count();

        return $rank + 1;
    }

    /**
     * Get user's rank on level leaderboard.
     */
    public function getUserLevelRank(int $userId): int
    {
        $user = User::find($userId);

        if (!$user) {
            return 0;
        }

        $rank = User::where('level', '>', $user->level)
            ->orWhere(function ($query) use ($user) {
                $query->where('level', $user->level)
                    ->where('total_points_earned', '>', $user->total_points_earned ?? 0);
            })
            ->count();

        return $rank + 1;
    }

    /**
     * Get combined leaderboard summary for a user.
     */
    public function getUserLeaderboardSummary(int $userId): array
    {
        return [
            'points' => [
                'rank' => $this->getUserPointsRank($userId),
                'total_users' => User::count(),
            ],
            'streak' => [
                'rank' => $this->getUserStreakRank($userId),
                'total_users' => PointStreak::count(),
            ],
            'achievements' => [
                'rank' => $this->getUserAchievementRank($userId),
                'total_users' => DB::table('user_achievements')
                    ->select(DB::raw('COUNT(DISTINCT user_id) as total'))
                    ->where('is_completed', true)
                    ->first()
                    ->total ?? 0,
            ],
            'level' => [
                'rank' => $this->getUserLevelRank($userId),
                'total_users' => User::count(),
            ],
        ];
    }
}
