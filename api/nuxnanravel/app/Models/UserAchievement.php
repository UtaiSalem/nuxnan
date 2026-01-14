<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserAchievement extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'user_achievements';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'achievement_id',
        'progress',
        'is_completed',
        'completed_at',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'progress' => 'integer',
        'is_completed' => 'boolean',
        'completed_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Get the user that owns the user achievement.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the achievement associated with the user achievement.
     */
    public function achievement(): BelongsTo
    {
        return $this->belongsTo(Achievement::class);
    }

    /**
     * Scope a query to only include completed achievements.
     */
    public function scopeCompleted($query)
    {
        return $query->where('is_completed', true);
    }

    /**
     * Scope a query to only include incomplete achievements.
     */
    public function scopeIncomplete($query)
    {
        return $query->where('is_completed', false);
    }

    /**
     * Check if the achievement is completed.
     */
    public function isCompleted(): bool
    {
        return $this->is_completed;
    }

    /**
     * Get the progress percentage.
     */
    public function getProgressPercentage(): float
    {
        $criteria = $this->achievement->criteria ?? [];
        $target = $criteria['count'] ?? $criteria['points'] ?? $criteria['streak'] ?? 100;
        
        if ($target <= 0) {
            return 0;
        }

        return min(100, ($this->progress / $target) * 100);
    }

    /**
     * Get the formatted progress attribute.
     */
    public function getFormattedProgressAttribute(): string
    {
        $percentage = $this->getProgressPercentage();
        return number_format($percentage, 1) . '%';
    }

    /**
     * Update progress.
     */
    public function updateProgress(int $progress): bool
    {
        $this->progress = $progress;
        
        // Check if achievement is completed
        $criteria = $this->achievement->criteria ?? [];
        $target = $criteria['count'] ?? $criteria['points'] ?? $criteria['streak'] ?? 100;
        
        if ($progress >= $target && !$this->is_completed) {
            $this->is_completed = true;
            $this->completed_at = now();
        }
        
        return $this->save();
    }

    /**
     * Mark as completed.
     */
    public function markAsCompleted(): bool
    {
        if ($this->is_completed) {
            return true;
        }

        $this->is_completed = true;
        $this->completed_at = now();
        $this->progress = 100; // Set to 100% progress

        return $this->save();
    }

    /**
     * Get the time since completed.
     */
    public function getTimeSinceCompleted(): string
    {
        if (!$this->completed_at) {
            return '';
        }

        return $this->completed_at->diffForHumans();
    }
}
