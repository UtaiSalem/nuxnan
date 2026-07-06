<?php

namespace App\Services;

use App\Models\GamificationRuleLog;
use App\Models\User;
use App\Models\UserActivitySummary;
use App\Models\UserUsageEvent;
use Illuminate\Support\Carbon;
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
                'summary_date' => Carbon::parse($date)->toDateString(),
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

    /**
     * Get summary between two dates.
     */
    public function getSummaryBetween(User $user, string $start, string $end): array
    {
        $summaries = UserActivitySummary::where('user_id', $user->id)
            ->whereBetween('summary_date', [$start, $end])
            ->where('period_type', 'daily')
            ->get();

        $eventCounts = [];
        foreach ($summaries as $summary) {
            $counts = $summary->event_counts ?? [];
            foreach ($counts as $type => $count) {
                $eventCounts[$type] = ($eventCounts[$type] ?? 0) + $count;
            }
        }

        return [
            'points_earned' => (float) $summaries->sum('points_earned'),
            'xp_earned' => (int) $summaries->sum('xp_earned'),
            'lessons_completed' => (int) $summaries->sum('lessons_completed'),
            'quizzes_completed' => (int) $summaries->sum('quizzes_completed'),
            'event_counts' => $eventCounts,
            'days_active' => $summaries->count(),
        ];
    }

    /**
     * Get weekly summary for a user.
     */
    public function getWeeklySummary(User $user): array
    {
        $start = now()->startOfWeek()->toDateString();
        $end = now()->endOfWeek()->toDateString();

        return $this->getSummaryBetween($user, $start, $end);
    }

    /**
     * Get monthly summary for a user.
     */
    public function getMonthlySummary(User $user): array
    {
        $start = now()->startOfMonth()->toDateString();
        $end = now()->endOfMonth()->toDateString();

        return $this->getSummaryBetween($user, $start, $end);
    }
}
