<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserAchievement extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'achievement_id',
        'progress',
        'is_completed',
        'completed_at',
        'metadata',
    ];

    protected $casts = [
        'progress' => 'integer',
        'is_completed' => 'boolean',
        'completed_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Get user that owns this achievement.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get achievement for this user achievement.
     */
    public function achievement(): BelongsTo
    {
        return $this->belongsTo(Achievement::class);
    }

    /**
     * Scope for completed achievements
     */
    public function scopeCompleted($query)
    {
        return $query->where('is_completed', true);
    }

    /**
     * Scope for pending achievements
     */
    public function scopePending($query)
    {
        return $query->where('is_completed', false);
    }

    /**
     * Get progress percentage
     */
    public function getProgressPercentageAttribute(): float
    {
        if (!$this->achievement) {
            return 0;
        }

        $criteria = $this->achievement->criteria ?? [];
        $target = $criteria['target'] ?? 100;

        if ($target <= 0) {
            return 0;
        }

        return min(100, ($this->progress / $target) * 100);
    }

    /**
     * Get formatted progress
     */
    public function getFormattedProgressAttribute(): string
    {
        if (!$this->achievement) {
            return '0/0';
        }

        $criteria = $this->achievement->criteria ?? [];
        $target = $criteria['target'] ?? 100;

        return "{$this->progress}/{$target}";
    }

    /**
     * Check if achievement is completed
     */
    public function isCompleted(): bool
    {
        return $this->is_completed;
    }
}
