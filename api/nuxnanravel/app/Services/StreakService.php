<?php

namespace App\Services;

use App\Models\PointStreak;
use App\Models\User;
use App\Services\PointsService;
use Illuminate\Support\Facades\DB;

class StreakService
{
    protected PointsService $pointsService;

    public function __construct(PointsService $pointsService)
    {
        $this->pointsService = $pointsService;
    }

    /**
     * Record user login and update streak.
     */
    public function recordLogin(int $userId): array
    {
        return DB::transaction(function () use ($userId) {
            // Get or create point streak
            $streak = PointStreak::firstOrCreate(
                ['user_id' => $userId],
                [
                    'current_streak' => 0,
                    'longest_streak' => 0,
                    'last_activity_date' => null,
                    'bonus_points_earned' => 0,
                ]
            );

            $bonusPointsEarned = 0;
            $streakIncreased = false;
            $bonusMilestoneReached = false;

            // Check if streak is active today
            if ($streak->isStreakActiveToday()) {
                // Already logged in today, no update
                return [
                    'current_streak' => $streak->current_streak,
                    'longest_streak' => $streak->longest_streak,
                    'bonus_points_earned' => 0,
                    'streak_level' => $streak->getStreakLevel(),
                    'streak_icon' => $streak->getStreakIcon(),
                    'message' => 'คุณได้เข้าสู่ระบบไปแล้ววันนี้',
                ];
            }

            // Check if streak was active yesterday
            if ($streak->wasStreakActiveYesterday()) {
                // Increment streak
                $streak->incrementStreak();
                $streakIncreased = true;
            } else {
                // Reset streak
                $streak->resetStreak();
            }

            // Calculate bonus points
            $bonusPoints = $streak->calculateBonusPoints();
            
            // Check if bonus milestone reached
            $nextMilestone = $streak->getNextBonusMilestone();
            if ($streak->current_streak >= $nextMilestone && $bonusPoints > 0) {
                $bonusMilestoneReached = true;
                $streak->bonus_points_earned += $bonusPoints;
                $streak->save();

                // Award bonus points
                $this->pointsService->earn($userId, [
                    'source_type' => 'streak',
                    'source_id' => $streak->id,
                    'amount' => $bonusPoints,
                    'description' => "โบนัส Streak {$streak->current_streak} วัน",
                    'metadata' => [
                        'streak_days' => $streak->current_streak,
                        'streak_level' => $streak->getStreakLevel(),
                    ],
                ]);

                $bonusPointsEarned = $bonusPoints;
            }

            return [
                'current_streak' => $streak->current_streak,
                'longest_streak' => $streak->longest_streak,
                'bonus_points_earned' => $bonusPointsEarned,
                'streak_level' => $streak->getStreakLevel(),
                'streak_icon' => $streak->getStreakIcon(),
                'streak_increased' => $streakIncreased,
                'bonus_milestone_reached' => $bonusMilestoneReached,
                'next_milestone' => $nextMilestone,
                'days_until_next_bonus' => $streak->getDaysUntilNextBonus(),
                'message' => $this->getStreakMessage($streak->current_streak, $streakIncreased, $bonusPointsEarned),
            ];
        });
    }

    /**
     * Get streak information for a user.
     */
    public function getStreakInfo(int $userId): array
    {
        $streak = PointStreak::where('user_id', $userId)->first();

        if (!$streak) {
            return [
                'current_streak' => 0,
                'longest_streak' => 0,
                'bonus_points_earned' => 0,
                'streak_level' => 'Newbie',
                'streak_icon' => '🔥',
                'streak_level_color' => '#9ca3af',
                'is_active_today' => false,
                'next_milestone' => 5,
                'days_until_next_bonus' => 5,
            ];
        }

        return [
            'current_streak' => $streak->current_streak,
            'longest_streak' => $streak->longest_streak,
            'bonus_points_earned' => $streak->bonus_points_earned,
            'streak_level' => $streak->getStreakLevel(),
            'streak_icon' => $streak->getStreakIcon(),
            'streak_level_color' => $streak->getStreakLevelColor(),
            'is_active_today' => $streak->isStreakActiveToday(),
            'next_milestone' => $streak->getNextBonusMilestone(),
            'days_until_next_bonus' => $streak->getDaysUntilNextBonus(),
            'potential_bonus' => $streak->calculateBonusPoints(),
        ];
    }

    /**
     * Get streak leaderboard.
     */
    public function getStreakLeaderboard(int $limit = 10): array
    {
        $streaks = PointStreak::orderBy('current_streak', 'desc')
            ->with('user:id,name,profile_photo_path')
            ->limit($limit)
            ->get();

        $leaderboard = [];

        foreach ($streaks as $index => $streak) {
            $leaderboard[] = [
                'rank' => $index + 1,
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

        return $leaderboard;
    }

    /**
     * Get streak message based on streak count.
     */
    private function getStreakMessage(int $streakDays, bool $streakIncreased, int $bonusPoints): string
    {
        if (!$streakIncreased && $bonusPoints === 0) {
            return 'คุณได้เข้าสู่ระบบไปแล้ววันนี้';
        }

        if ($bonusPoints > 0) {
            return "🎉 ยอดเยี่ยม! คุณได้รับ {$bonusPoints} แต้ม จาก Streak {$streakDays} วัน!";
        }

        return match(true) {
            $streakDays >= 30 => "💎 สุดยอด! คุณมี Streak {$streakDays} วัน!",
            $streakDays >= 21 => "🏆 เยี่ยมมาก! คุณมี Streak {$streakDays} วัน!",
            $streakDays >= 14 => "🥇 ยอดเยี่ยม! คุณมี Streak {$streakDays} วัน!",
            $streakDays >= 7 => "🥈 ดีมาก! คุณมี Streak {$streakDays} วัน!",
            $streakDays >= 3 => "🥉 ดี! คุณมี Streak {$streakDays} วัน!",
            default => "คุณมี Streak {$streakDays} วัน ต่อไปเลย!",
        };
    }

    /**
     * Reset streak (for testing or admin purposes).
     */
    public function resetStreak(int $userId): bool
    {
        $streak = PointStreak::where('user_id', $userId)->first();

        if (!$streak) {
            return false;
        }

        return $streak->resetStreak();
    }
}
