<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PointStreak extends Model
{
    use HasFactory;

    /**
     * The table associated with model.
     *
     * @var string
     */
    protected $table = 'point_streaks';

    protected $fillable = [
        'user_id',
        'current_streak',
        'longest_streak',
        'last_activity_date',
        'bonus_points_earned',
    ];

    protected $casts = [
        'current_streak' => 'integer',
        'longest_streak' => 'integer',
        'last_activity_date' => 'date',
        'bonus_points_earned' => 'integer',
    ];

    /**
     * Get user that owns this streak.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get or create streak for a user.
     */
    public static function getOrCreateForUser(int $userId): self
    {
        return self::firstOrCreate(
            ['user_id' => $userId],
            [
                'current_streak' => 0,
                'longest_streak' => 0,
                'last_activity_date' => null,
                'bonus_points_earned' => 0,
            ]
        );
    }

    /**
     * Update streak based on activity.
     */
    public function updateStreak(): array
    {
        $today = now()->toDateString();
        $yesterday = now()->subDay()->toDateString();

        $bonusPoints = 0;
        $streakIncreased = false;

        if ($this->last_activity_date === $today) {
            // Already logged in today, no change
            return [
                'current_streak' => $this->current_streak,
                'bonus_points' => 0,
                'streak_increased' => false,
            ];
        }

        if ($this->last_activity_date === $yesterday) {
            // Logged in yesterday, increment streak
            $this->current_streak++;
            $streakIncreased = true;

            // Calculate bonus points based on streak
            $bonusPoints = $this->calculateStreakBonus($this->current_streak);

            // Update longest streak if needed
            if ($this->current_streak > $this->longest_streak) {
                $this->longest_streak = $this->current_streak;
            }

            $this->bonus_points_earned += $bonusPoints;
        } else {
            // Streak broken, reset to 1
            $this->current_streak = 1;
            $streakIncreased = false;
            $bonusPoints = 10; // Base bonus for new streak
            $this->bonus_points_earned += $bonusPoints;
        }

        $this->last_activity_date = $today;
        $this->save();

        return [
            'current_streak' => $this->current_streak,
            'longest_streak' => $this->longest_streak,
            'bonus_points' => $bonusPoints,
            'streak_increased' => $streakIncreased,
        ];
    }

    /**
     * Calculate streak bonus points.
     */
    protected function calculateStreakBonus(int $streak): int
    {
        return match(true) {
            $streak >= 1 && $streak <= 3 => 10,
            $streak >= 4 && $streak <= 7 => 20,
            $streak >= 8 && $streak <= 14 => 30,
            $streak >= 15 && $streak <= 30 => 50,
            $streak >= 31 => 100,
            default => 10,
        };
    }

    /**
     * Get next streak bonus.
     */
    public function getNextStreakBonus(): int
    {
        $nextStreak = $this->current_streak + 1;
        return $this->calculateStreakBonus($nextStreak);
    }

    /**
     * Get days to next bonus tier.
     */
    public function getDaysToNextBonus(): int
    {
        return match(true) {
            $this->current_streak < 3 => 3 - $this->current_streak,
            $this->current_streak < 7 => 7 - $this->current_streak,
            $this->current_streak < 14 => 14 - $this->current_streak,
            $this->current_streak < 30 => 30 - $this->current_streak,
            default => 0, // Already at max bonus
        };
    }

    /**
     * Check if user has logged in today.
     */
    public function hasLoggedInToday(): bool
    {
        return $this->last_activity_date === now()->toDateString();
    }

    /**
     * Check if streak is active (logged in yesterday or today).
     */
    public function isActive(): bool
    {
        if (!$this->last_activity_date) {
            return false;
        }

        $yesterday = now()->subDay()->toDateString();
        $today = now()->toDateString();

        return $this->last_activity_date === $yesterday || $this->last_activity_date === $today;
    }

    /**
     * Get formatted last activity date.
     */
    public function getFormattedLastActivityDateAttribute(): string
    {
        if (!$this->last_activity_date) {
            return '-';
        }

        return \Carbon\Carbon::parse($this->last_activity_date)
            ->locale('th')
            ->translatedFormat('j F Y');
    }

    /**
     * Get streak tier label.
     */
    public function getStreakTierAttribute(): string
    {
        return match(true) {
            $this->current_streak >= 31 => 'ตำนาน (31+ วัน)',
            $this->current_streak >= 15 => 'มหาศาล (15-30 วัน)',
            $this->current_streak >= 8 => 'หายาก (8-14 วัน)',
            $this->current_streak >= 4 => 'ไม่สามัญ (4-7 วัน)',
            $this->current_streak >= 1 => 'สามัญ (1-3 วัน)',
            default => 'ไม่มีสตรีก',
        };
    }
}
