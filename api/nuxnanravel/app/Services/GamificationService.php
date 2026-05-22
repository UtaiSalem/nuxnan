<?php

namespace App\Services;

use App\Models\PointStreak;
use App\Models\User;
use App\Services\PointsService;
use App\Services\AchievementService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
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
     * Get user progress summary (unified level, xp, streak).
     */
    public function getUserProgressSummary(User $user): array
    {
        $level = $this->getUserLevel($user);
        $streak = $this->getUserStreak($user);

        // Find next milestone (next level)
        $nextLevel = \App\Models\LevelDefinition::where('level', $user->level + 1)->first();

        return [
            'level' => $level['level'],
            'current_xp' => $level['current_xp'],
            'xp_for_next_level' => $level['xp_for_next_level'],
            'progress_percentage' => $level['progress_percentage'],
            'total_points' => (float) $user->pp,
            'streak' => [
                'count' => $streak['current_streak'],
                'has_today' => $streak['has_logged_in_today'],
            ],
            'next_milestone' => $nextLevel ? [
                'level' => $nextLevel->level,
                'name' => $nextLevel->name_th ?? $nextLevel->name,
                'xp_required' => $nextLevel->xp_required,
            ] : null,
        ];
    }

    /**
     * Get dashboard summary (today activity, weekly XP, rank).
     */
    public function getDashboardSummary(User $user): array
    {
        $today = now()->toDateString();
        
        $todaySummary = \App\Models\UserActivitySummary::where('user_id', $user->id)
            ->where('summary_date', $today)
            ->first();

        $weeklySummary = \App\Models\UserActivitySummary::where('user_id', $user->id)
            ->whereBetween('summary_date', [now()->startOfWeek()->toDateString(), now()->endOfWeek()->toDateString()])
            ->selectRaw('SUM(points_earned) as points, SUM(xp_earned) as xp')
            ->first();

        return [
            'today' => [
                'points' => (float) ($todaySummary->points_earned ?? 0),
                'xp' => (int) ($todaySummary->xp_earned ?? 0),
                'lessons' => (int) ($todaySummary->lessons_completed ?? 0),
                'quizzes' => (int) ($todaySummary->quizzes_completed ?? 0),
                'events' => $todaySummary->event_counts ?? [],
            ],
            'weekly' => [
                'points' => (float) ($weeklySummary->points ?? 0),
                'xp' => (int) ($weeklySummary->xp ?? 0),
            ],
            'rank' => [
                'points' => $this->getUserRank($user, 'points'),
                'weekly' => $this->getUserRank($user, 'weekly'),
            ],
        ];
    }

    /**
     * Get user quests with progress.
     */
    public function getUserQuests(User $user): array
    {
        $today = now()->toDateString();
        
        $quests = \App\Models\QuestDefinition::where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return $quests->map(function ($quest) use ($user, $today) {
            $progress = \App\Models\UserQuestProgress::where('user_id', $user->id)
                ->where('quest_id', $quest->id)
                ->where('quest_date', $today)
                ->first();

            return [
                'id' => $quest->id,
                'key' => $quest->quest_key,
                'title' => $quest->title_th ?? $quest->title,
                'description' => $quest->description,
                'type' => $quest->quest_type,
                'target' => $quest->target_count,
                'progress' => $progress->progress ?? 0,
                'is_completed' => $progress->is_completed ?? false,
                'reward_claimed' => $progress->reward_claimed ?? false,
                'reward' => [
                    'points' => (float) $quest->points_reward,
                    'xp' => (int) $quest->xp_reward,
                ],
                'icon' => $quest->icon,
            ];
        })->toArray();
    }

    /**
     * Claim quest reward.
     */
    public function claimQuestReward(User $user, int $questId): array
    {
        $today = now()->toDateString();
        
        $progress = \App\Models\UserQuestProgress::where('user_id', $user->id)
            ->where('quest_id', $questId)
            ->where('quest_date', $today)
            ->first();

        if (!$progress) {
            return ['success' => false, 'message' => 'ไม่พบข้อมูลภารกิจ'];
        }

        if (!$progress->is_completed) {
            return ['success' => false, 'message' => 'คุณยังทำภารกิจไม่สำเร็จ'];
        }

        if ($progress->reward_claimed) {
            return ['success' => false, 'message' => 'คุณรับรางวัลไปแล้ว'];
        }

        $progress->update(['reward_claimed' => true]);

        // Rewards are already given when quest is completed in GamificationRuleEngine
        // But if we want a "claim" step, we should move the award logic here.
        // For now, I'll follow the RuleEngine award logic and just mark as claimed.
        
        return ['success' => true, 'message' => 'รับรางวัลสำเร็จ'];
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
                    $query->selectRaw('users.*, COALESCE(SUM(points_transactions.amount), 0) as weekly_points')
                    ->leftJoin('points_transactions', function ($join) {
                        $join->on('users.id', '=', 'points_transactions.user_id')
                            ->where('points_transactions.transaction_type', '=', 'earn')
                            ->where('points_transactions.created_at', '>=', now()->startOfWeek())
                            ->where('points_transactions.created_at', '<=', now()->endOfWeek());
                    })
                    ->groupBy('users.id')
                    ->orderByDesc('weekly_points');
                    break;
                case 'monthly':
                    $query->selectRaw('users.*, COALESCE(SUM(points_transactions.amount), 0) as monthly_points')
                    ->leftJoin('points_transactions', function ($join) {
                        $join->on('users.id', '=', 'points_transactions.user_id')
                            ->where('points_transactions.transaction_type', '=', 'earn')
                            ->where('points_transactions.created_at', '>=', now()->startOfMonth())
                            ->where('points_transactions.created_at', '<=', now()->endOfMonth());
                    })
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
        switch ($type) {
            case 'points':
                return User::where('pp', '>', $user->pp)->count() + 1;

            case 'weekly':
                $startOfWeek = now()->startOfWeek();
                $endOfWeek = now()->endOfWeek();
                
                $userPoints = \App\Models\PointsTransaction::where('user_id', $user->id)
                    ->where('transaction_type', 'earn')
                    ->whereBetween('created_at', [$startOfWeek, $endOfWeek])
                    ->sum('amount');

                return DB::table('points_transactions')
                    ->select('user_id')
                    ->where('transaction_type', 'earn')
                    ->whereBetween('created_at', [$startOfWeek, $endOfWeek])
                    ->groupBy('user_id')
                    ->havingRaw('SUM(amount) > ?', [$userPoints])
                    ->get()
                    ->count() + 1;

            case 'monthly':
                $startOfMonth = now()->startOfMonth();
                $endOfMonth = now()->endOfMonth();
                
                $userPoints = \App\Models\PointsTransaction::where('user_id', $user->id)
                    ->where('transaction_type', 'earn')
                    ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
                    ->sum('amount');

                return DB::table('points_transactions')
                    ->select('user_id')
                    ->where('transaction_type', 'earn')
                    ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
                    ->groupBy('user_id')
                    ->havingRaw('SUM(amount) > ?', [$userPoints])
                    ->get()
                    ->count() + 1;

            case 'streak':
                $userStreak = $user->pointStreak ? $user->pointStreak->current_streak : 0;
                return \App\Models\PointStreak::where('current_streak', '>', $userStreak)->count() + 1;

            case 'achievements':
                $userAchievementsCount = $user->userAchievements()->where('is_completed', true)->count();
                
                return User::whereHas('userAchievements', function($query) {
                        $query->where('is_completed', true);
                    }, '>', $userAchievementsCount)
                    ->count() + 1;

            default:
                return User::where('pp', '>', $user->pp)->count() + 1;
        }
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
