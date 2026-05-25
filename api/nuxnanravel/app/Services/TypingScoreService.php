<?php

namespace App\Services;

use App\Models\User;
use App\Models\TypingSession;
use App\Models\TypingAchievement;
use App\Models\TypingUserAchievement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TypingScoreService
{
    /**
     * Calculate WPM (Words Per Minute)
     * Standard: 1 word = 5 characters
     */
    public function calculateWpm(int $correctChars, float $elapsedSeconds): int
    {
        if ($elapsedSeconds <= 0) return 0;
        return (int) round(($correctChars / 5) / ($elapsedSeconds / 60));
    }

    /**
     * Calculate Accuracy percentage
     */
    public function calculateAccuracy(int $correctChars, int $totalChars): float
    {
        if ($totalChars <= 0) return 0.0;
        return round(($correctChars / $totalChars) * 100, 2);
    }

    /**
     * Calculate Score based on performance and difficulty
     * Formula: (CorrectWords × 10) + SpeedBonus + ComboBonus + AccBonus - MistakePenalty
     */
    public function calculateScore(array $data): array
    {
        $correctWords  = $data['correct_words'] ?? 0;
        $wpm           = $data['wpm'] ?? 0;
        $accuracy      = $data['accuracy'] ?? 0; // 0-100
        $maxCombo      = $data['max_combo'] ?? 0;
        $mistakes       = $data['mistakes'] ?? 0;
        $difficulty    = $data['difficulty'] ?? 'normal';

        // Base Multiplier
        $diffMultiplier = match($difficulty) {
            'beginner' => 1.0,
            'easy'     => 1.2,
            'normal'   => 1.5,
            'hard'     => 2.0,
            'expert'   => 3.0,
            default    => 1.0,
        };
        
        $base = $correctWords * 10 * $diffMultiplier;

        // Speed Bonus: +2 pts per WPM over 20
        $speedBonus = max(0, ($wpm - 20) * 2);

        // Combo Bonus: quadratically increasing
        $comboBonus = $maxCombo >= 5 ? (int) ($maxCombo * $maxCombo * 0.5) : 0;

        // Accuracy Bonus
        $accuracyBonus = match(true) {
            $accuracy >= 100 => 200,
            $accuracy >= 95  => 100,
            $accuracy >= 90  => 50,
            $accuracy >= 80  => 20,
            default          => 0,
        };

        // Mistake Penalty
        $penalty = $mistakes * 3;

        $total = (int) max(0, $base + $speedBonus + $comboBonus + $accuracyBonus - $penalty);

        return [
            'score'          => $total,
            'speed_bonus'    => (int) $speedBonus,
            'combo_bonus'    => $comboBonus,
            'accuracy_bonus' => $accuracyBonus,
            'penalty'        => $penalty,
        ];
    }

    /**
     * Calculate XP earned
     */
    public function calculateXp(int $score, int $wpm, float $accuracy, string $difficulty): int
    {
        $base = match($difficulty) {
            'beginner' => 5,
            'easy'     => 10,
            'normal'   => 20,
            'hard'     => 35,
            'expert'   => 50,
            default    => 10,
        };
        
        // Multiplier based on accuracy and speed
        $multiplier = min(($accuracy / 100) * max(1, $wpm / 30), 3.0);
        
        return max(3, (int) ($base * $multiplier));
    }

    /**
     * Check and award achievements
     */
    public function checkAchievements(User $user, TypingSession $session): array
    {
        $earned = [];
        $achievements = TypingAchievement::where('is_active', true)->get();

        foreach ($achievements as $achievement) {
            // ตรวจว่าได้ไปแล้วหรือยัง
            $alreadyEarned = TypingUserAchievement::where([
                'user_id'        => $user->id,
                'achievement_id' => $achievement->id,
            ])->exists();

            if ($alreadyEarned) continue;

            $condition = $achievement->condition; // assuming it's casted to array in Model
            if (is_string($condition)) $condition = json_decode($condition, true);

            $met = match($condition['type'] ?? '') {
                'wpm'         => $session->wpm >= $condition['value'],
                'accuracy'    => $session->accuracy >= $condition['value'],
                'combo'       => $session->max_combo >= $condition['value'],
                'score'       => $session->score >= $condition['value'],
                'first_game'  => true,
                'session_count' => TypingSession::where('user_id', $user->id)->count() >= ($condition['value'] ?? 1),
                default       => false,
            };

            if ($met) {
                TypingUserAchievement::create([
                    'user_id'        => $user->id,
                    'achievement_id' => $achievement->id,
                    'session_id'     => $session->id,
                    'earned_at'      => now(),
                ]);

                if ($achievement->xp_reward > 0) {
                    app(PointsService::class)->addXp($user, $achievement->xp_reward);
                }

                $earned[] = $achievement;
            }
        }

        return $earned;
    }
}
