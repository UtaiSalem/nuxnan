<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PointStreak extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'point_streaks';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'current_streak',
        'longest_streak',
        'last_activity_date',
        'bonus_points_earned',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'current_streak' => 'integer',
        'longest_streak' => 'integer',
        'last_activity_date' => 'datetime',
        'bonus_points_earned' => 'integer',
    ];

    /**
     * Get the user that owns the point streak.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Increment the current streak.
     */
    public function incrementStreak(): bool
    {
        $this->current_streak += 1;
        
        // Update longest streak if needed
        if ($this->current_streak > $this->longest_streak) {
            $this->longest_streak = $this->current_streak;
        }
        
        $this->last_activity_date = now();
        
        return $this->save();
    }

    /**
     * Reset the current streak.
     */
    public function resetStreak(): bool
    {
        $this->current_streak = 1;
        $this->last_activity_date = now();
        
        return $this->save();
    }

    /**
     * Check if the streak is active today.
     */
    public function isStreakActiveToday(): bool
    {
        if (!$this->last_activity_date) {
            return false;
        }

        return $this->last_activity_date->isToday();
    }

    /**
     * Check if the streak was active yesterday.
     */
    public function wasStreakActiveYesterday(): bool
    {
        if (!$this->last_activity_date) {
            return false;
        }

        return $this->last_activity_date->isYesterday();
    }

    /**
     * Calculate bonus points for current streak.
     */
    public function calculateBonusPoints(): int
    {
        // Bonus points formula: 10 points * (current_streak / 5), max 100 points
        $bonus = 10 * floor($this->current_streak / 5);
        
        return min($bonus, 100);
    }

    /**
     * Get the next streak bonus milestone.
     */
    public function getNextBonusMilestone(): int
    {
        $currentMilestone = floor($this->current_streak / 5) * 5;
        $nextMilestone = $currentMilestone + 5;
        
        return $nextMilestone;
    }

    /**
     * Get the days until next bonus milestone.
     */
    public function getDaysUntilNextBonus(): int
    {
        $nextMilestone = $this->getNextBonusMilestone();
        
        return $nextMilestone - $this->current_streak;
    }

    /**
     * Get the formatted current streak attribute.
     */
    public function getFormattedCurrentStreakAttribute(): string
    {
        return number_format($this->current_streak) . ' วัน';
    }

    /**
     * Get the formatted longest streak attribute.
     */
    public function getFormattedLongestStreakAttribute(): string
    {
        return number_format($this->longest_streak) . ' วัน';
    }

    /**
     * Get the formatted bonus points earned attribute.
     */
    public function getFormattedBonusPointsEarnedAttribute(): string
    {
        return number_format($this->bonus_points_earned) . ' แต้ม';
    }

    /**
     * Get streak level based on current streak.
     */
    public function getStreakLevel(): string
    {
        return match(true) {
            $this->current_streak >= 30 => 'Diamond',
            $this->current_streak >= 21 => 'Platinum',
            $this->current_streak >= 14 => 'Gold',
            $this->current_streak >= 7 => 'Silver',
            $this->current_streak >= 3 => 'Bronze',
            default => 'Newbie',
        };
    }

    /**
     * Get streak level color.
     */
    public function getStreakLevelColor(): string
    {
        return match($this->getStreakLevel()) {
            'Diamond' => '#b9f2ff',
            'Platinum' => '#e5e4e2',
            'Gold' => '#ffd700',
            'Silver' => '#c0c0c0',
            'Bronze' => '#cd7f32',
            default => '#9ca3af',
        };
    }

    /**
     * Get streak icon.
     */
    public function getStreakIcon(): string
    {
        return match($this->getStreakLevel()) {
            'Diamond' => '💎',
            'Platinum' => '🏆',
            'Gold' => '🥇',
            'Silver' => '🥈',
            'Bronze' => '🥉',
            default => '🔥',
        };
    }
}
