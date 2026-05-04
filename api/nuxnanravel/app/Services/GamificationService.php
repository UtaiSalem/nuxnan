<?php

namespace App\Services;

use App\Models\PointStreak;
use App\Models\User;
use App\Services\PointsService;
use App\Services\AchievementService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class GamificationService
{
    protected PointsService $pointsService;
    protected AchievementService $achievementService;

    public function __construct(
        PointsService $pointsService,
        AchievementService $achievementService
    ) {
        $this->pointsService = $pointsService;
        $this->achievementService = $achievementService;
    }

    /**
     * Record user activity and update streak.
     */
    public function recordActivity(User $user): array
    {
        $streak = PointStreak::getOrCreateForUser($user->id);
        $result = $streak->updateStreak();

        // Award streak bonus points
        if ($result['bonus_points'] > 0) {
            $this->pointsService->earn(
                $user,
                $result['bonus_points'],
                'streak_bonus',
                null,
                "โบนัสการเข้าต่อเนื่อง {$result['current_streak']} วัน",
                [
                    'streak_days' => $result['current_streak'],
                    'bonus_tier' => $streak->streak_tier,
                ]
            );
        }

        // Check and update achievements
        $unlockedAchievements = $this->achievementService->checkAndUpdateAchievements(
            $user,
            'streak',
            [
                'streak_days' => $result['current_streak'],
            ]
        );

        return [
            'current_streak' => $result['current_streak'],
            'longest_streak' => $result['longest_streak'],
            'bonus_points' => $result['bonus_points'],
            'streak_increased' => $result['streak_increased'],
            'next_streak_bonus' => $streak->getNextStreakBonus(),
            'days_to_next_bonus' => $streak->getDaysToNextBonus(),
            'streak_tier' => $streak->streak_tier,
            'has_logged_in_today' => $streak->hasLoggedInToday(),
            'unlocked_achievements' => $unlockedAchievements,
        ];
    }

    /**
     * Get user level information.
     */
    public function getUserLevel(User $user): array
    {
        $balance = $this->pointsService->getBalance($user);

        return [
            'level' => $balance['level'],
            'current_xp' => $balance['current_xp'],
            'xp_for_next_level' => $balance['xp_for_next_level'],
            'progress_percentage' => $balance['progress_percentage'],
            'total_points' => $balance['current_points'],
        ];
    }

    /**
     * Get leaderboard.
     */
    public function getLeaderboard(string $type = 'points', int $page = 1, int $perPage = 20): array
    {
        $cacheKey = "leaderboard_{$type}_{$page}_{$perPage}";

        return Cache::remember($cacheKey, now()->addMinutes(10), function () use ($type, $page, $perPage) {
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
                    ->leftJoin('points_transactions', 'users.id', '=', 'points_transactions.user_id') // Added join for summation
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
                    ->leftJoin('points_transactions', 'users.id', '=', 'points_transactions.user_id') // Added join
                    ->groupBy('users.id')
                    ->orderByDesc('monthly_points');
                    break;
                case 'streak':
                    $query->with('pointStreak')
                        ->orderByDesc('point_streaks.current_streak');
                    break;
                case 'achievements':
                    $query->withCount(['userAchievements' => function ($query) {
                        $query->where('is_completed', true);
                    }])
                    ->orderByDesc('user_achievements_count');
                    break;
                default:
                    $query->orderByDesc('pp');
                    break;
            }

            $users = $query->paginate($perPage, ['id', 'username', 'name', 'pp', 'level', 'profile_photo_path'], 'page', $page);

            $leaderboard = [];
            $rank = ($page - 1) * $perPage + 1;

            foreach ($users->items() as $userItem) {
                $leaderboard[] = [
                    'rank' => $rank++,
                    'user_id' => $userItem->id,
                    'username' => $userItem->username,
                    'avatar' => $userItem->avatar,
                    'score' => match($type) {
                        'points' => (float) $userItem->pp,
                        'weekly' => (float) ($userItem->weekly_points ?? 0),
                        'monthly' => (float) ($userItem->monthly_points ?? 0),
                        'streak' => (int) ($userItem->pointStreak->current_streak ?? 0),
                        'achievements' => (int) ($userItem->user_achievements_count ?? 0),
                        default => (float) $userItem->pp,
                    },
                    'level' => $userItem->level ?? 1,
                ];
            }

            return [
                'leaderboard' => $leaderboard,
                'pagination' => [
                    'total' => $users->total(),
                    'per_page' => $users->perPage(),
                    'current_page' => $users->currentPage(),
                    'last_page' => $users->lastPage(),
                ],
            ];
        });
    }

    /**
     * Get user rank in leaderboard.
     */
    public function getUserRank(User $user, string $type = 'points'): ?int
    {
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
            case 'streak':
                $query->with('pointStreak')
                    ->orderByDesc('point_streaks.current_streak');
                break;
            case 'achievements':
                $query->withCount(['userAchievements' => function ($query) {
                    $query->where('is_completed', true);
                }])
                ->orderByDesc('user_achievements_count');
                break;
            default:
                $query->orderByDesc('pp');
                break;
        }

        $users = $query->get();
        $rank = null;

        foreach ($users as $index => $userItem) {
            if ($userItem->id === $user->id) {
                $rank = $index + 1;
                break;
            }
        }

        return $rank;
    }

    /**
     * Get user streak information.
     */
    public function getUserStreak(User $user): array
    {
        $streak = PointStreak::getOrCreateForUser($user->id);

        return [
            'current_streak' => $streak->current_streak,
            'longest_streak' => $streak->longest_streak,
            'last_activity_date' => $streak->last_activity_date ? $streak->formatted_last_activity_date : null,
            'bonus_points_earned' => $streak->bonus_points_earned,
            'streak_tier' => $streak->streak_tier,
            'next_streak_bonus' => $streak->getNextStreakBonus(),
            'days_to_next_bonus' => $streak->getDaysToNextBonus(),
            'has_logged_in_today' => $streak->hasLoggedInToday(),
        ];
    }

    /**
     * Initialize default data.
     */
    public function initializeDefaultData(): void
    {
        // Create default achievements
        $this->achievementService->createDefaultAchievements();

        // Create default rewards
        $rewardService = new \App\Services\RewardService($this->pointsService);
        $rewardService->createDefaultRewards();

        Log::info('Default gamification data initialized');
    }
}
