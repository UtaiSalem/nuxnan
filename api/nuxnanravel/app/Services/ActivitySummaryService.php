<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserUsageEvent;
use App\Models\UserActivitySummary;
use App\Models\PointsTransaction;
use App\Models\GamificationRuleLog;
use Illuminate\Support\Facades\DB;

class ActivitySummaryService
{
    /**
     * Update daily summary for a user.
     */
    public function updateDailySummary(User $user, string $date): void
    {
        $startOfDay = "{$date} 00:00:00";
        $endOfDay = "{$date} 23:59:59";

        // Aggregate events
        $eventCounts = UserUsageEvent::where('user_id', $user->id)
            ->whereBetween('occurred_at', [$startOfDay, $endOfDay])
            ->select('event_type', DB::raw('count(*) as count'))
            ->groupBy('event_type')
            ->pluck('count', 'event_type')
            ->toArray();

        // Aggregate points and XP from logs
        $totals = GamificationRuleLog::where('user_id', $user->id)
            ->whereBetween('evaluated_at', [$startOfDay, $endOfDay])
            ->where('result', 'awarded')
            ->selectRaw('SUM(points_awarded) as points, SUM(xp_awarded) as xp')
            ->first();

        // Additional counts
        $lessonsCompleted = UserUsageEvent::where('user_id', $user->id)
            ->whereBetween('occurred_at', [$startOfDay, $endOfDay])
            ->where('event_type', 'lesson_complete')
            ->count();

        $quizzesCompleted = UserUsageEvent::where('user_id', $user->id)
            ->whereBetween('occurred_at', [$startOfDay, $endOfDay])
            ->where('event_type', 'quiz_pass')
            ->count();

        // Streak info
        $streak = $user->pointStreak ? $user->pointStreak->current_streak : 0;

        UserActivitySummary::updateOrCreate(
            [
                'user_id' => $user->id,
                'summary_date' => $date,
                'period_type' => 'daily',
            ],
            [
                'event_counts' => $eventCounts,
                'points_earned' => $totals->points ?? 0,
                'xp_earned' => (int) ($totals->xp ?? 0),
                'lessons_completed' => $lessonsCompleted,
                'quizzes_completed' => $quizzesCompleted,
                'streak_day' => $streak,
            ]
        );
    }
}
