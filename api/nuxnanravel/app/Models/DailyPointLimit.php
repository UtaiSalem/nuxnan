<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyPointLimit extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'daily_point_limits';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'date',
        'points_earned',
        'points_spent',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date' => 'date',
        'points_earned' => 'integer',
        'points_spent' => 'integer',
    ];

    /**
     * Get the user that owns the daily point limit.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get or create daily point limit for a user and date.
     */
    public static function getOrCreateForDate(int $userId, string $date): self
    {
        return self::firstOrCreate(
            [
                'user_id' => $userId,
                'date' => $date,
            ],
            [
                'points_earned' => 0,
                'points_spent' => 0,
            ]
        );
    }

    /**
     * Get or create daily point limit for today.
     */
    public static function getOrCreateForToday(int $userId): self
    {
        return self::getOrCreateForDate($userId, now()->toDateString());
    }

    /**
     * Increment points earned.
     */
    public function incrementPointsEarned(int $amount): bool
    {
        $this->points_earned += $amount;
        return $this->save();
    }

    /**
     * Increment points spent.
     */
    public function incrementPointsSpent(int $amount): bool
    {
        $this->points_spent += $amount;
        return $this->save();
    }

    /**
     * Check if can earn more points today based on daily limit.
     */
    public function canEarnMore(int $amount, int $dailyLimit = 1000): bool
    {
        return ($this->points_earned + $amount) <= $dailyLimit;
    }

    /**
     * Check if can spend more points today based on daily limit.
     */
    public function canSpendMore(int $amount, int $dailyLimit = 5000): bool
    {
        return ($this->points_spent + $amount) <= $dailyLimit;
    }

    /**
     * Get remaining points that can be earned today.
     */
    public function getRemainingEarnable(int $dailyLimit = 1000): int
    {
        return max(0, $dailyLimit - $this->points_earned);
    }

    /**
     * Get remaining points that can be spent today.
     */
    public function getRemainingSpendable(int $dailyLimit = 5000): int
    {
        return max(0, $dailyLimit - $this->points_spent);
    }

    /**
     * Get the percentage of daily earnings limit used.
     */
    public function getEarningsPercentage(int $dailyLimit = 1000): float
    {
        if ($dailyLimit <= 0) {
            return 0;
        }

        return min(100, ($this->points_earned / $dailyLimit) * 100);
    }

    /**
     * Get the percentage of daily spending limit used.
     */
    public function getSpendingPercentage(int $dailyLimit = 5000): float
    {
        if ($dailyLimit <= 0) {
            return 0;
        }

        return min(100, ($this->points_spent / $dailyLimit) * 100);
    }

    /**
     * Get the formatted points earned attribute.
     */
    public function getFormattedPointsEarnedAttribute(): string
    {
        return number_format($this->points_earned) . ' แต้ม';
    }

    /**
     * Get the formatted points spent attribute.
     */
    public function getFormattedPointsSpentAttribute(): string
    {
        return number_format($this->points_spent) . ' แต้ม';
    }

    /**
     * Get the formatted date attribute.
     */
    public function getFormattedDateAttribute(): string
    {
        return $this->date->locale('th')->translatedFormat('j F Y');
    }
}
