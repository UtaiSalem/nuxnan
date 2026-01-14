<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Achievement extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'achievements';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'description',
        'icon',
        'type',
        'criteria',
        'points_reward',
        'badge_url',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'criteria' => 'array',
        'points_reward' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Get the user achievements for this achievement.
     */
    public function userAchievements(): HasMany
    {
        return $this->hasMany(UserAchievement::class);
    }

    /**
     * Scope a query to only include active achievements.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to filter by type.
     */
    public function scopeType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Check if the achievement is active.
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * Get the type label attribute.
     */
    public function getTypeLabelAttribute(): string
    {
        return match($this->type) {
            'points' => 'แต้ม',
            'actions' => 'การกระทำ',
            'streak' => 'Streak',
            'social' => 'สังคม',
            'learning' => 'การเรียนรู้',
            default => $this->type,
        };
    }

    /**
     * Get the formatted points reward attribute.
     */
    public function getFormattedPointsRewardAttribute(): string
    {
        return number_format($this->points_reward) . ' แต้ม';
    }

    /**
     * Check if criteria is met for a user.
     */
    public function isCriteriaMet(User $user): bool
    {
        $criteria = $this->criteria;
        $type = $this->type;

        return match($type) {
            'points' => $this->checkPointsCriteria($user, $criteria),
            'actions' => $this->checkActionsCriteria($user, $criteria),
            'streak' => $this->checkStreakCriteria($user, $criteria),
            'social' => $this->checkSocialCriteria($user, $criteria),
            'learning' => $this->checkLearningCriteria($user, $criteria),
            default => false,
        };
    }

    /**
     * Check points criteria.
     */
    private function checkPointsCriteria(User $user, array $criteria): bool
    {
        $totalPoints = $user->total_points_earned ?? 0;
        
        return match($criteria['condition'] ?? 'gte') {
            'gte' => $totalPoints >= ($criteria['points'] ?? 0),
            'eq' => $totalPoints === ($criteria['points'] ?? 0),
            'lte' => $totalPoints <= ($criteria['points'] ?? 0),
            default => false,
        };
    }

    /**
     * Check actions criteria.
     */
    private function checkActionsCriteria(User $user, array $criteria): bool
    {
        $actionType = $criteria['action_type'] ?? null;
        $requiredCount = $criteria['count'] ?? 1;

        if (!$actionType) {
            return false;
        }

        $count = PointsTransaction::where('user_id', $user->id)
            ->where('transaction_type', 'earn')
            ->where('source_type', $actionType)
            ->count();

        return $count >= $requiredCount;
    }

    /**
     * Check streak criteria.
     */
    private function checkStreakCriteria(User $user, array $criteria): bool
    {
        $streak = PointStreak::where('user_id', $user->id)->first();

        if (!$streak) {
            return false;
        }

        $requiredStreak = $criteria['streak'] ?? 1;
        $streakType = $criteria['streak_type'] ?? 'current';

        return match($streakType) {
            'current' => $streak->current_streak >= $requiredStreak,
            'longest' => $streak->longest_streak >= $requiredStreak,
            default => false,
        };
    }

    /**
     * Check social criteria.
     */
    private function checkSocialCriteria(User $user, array $criteria): bool
    {
        $socialType = $criteria['social_type'] ?? null;
        $requiredCount = $criteria['count'] ?? 1;

        if (!$socialType) {
            return false;
        }

        return match($socialType) {
            'friends' => $user->friends()->count() >= $requiredCount,
            'followers' => $user->followers()->count() >= $requiredCount,
            'following' => $user->following()->count() >= $requiredCount,
            default => false,
        };
    }

    /**
     * Check learning criteria.
     */
    private function checkLearningCriteria(User $user, array $criteria): bool
    {
        $learningType = $criteria['learning_type'] ?? null;
        $requiredCount = $criteria['count'] ?? 1;

        if (!$learningType) {
            return false;
        }

        return match($learningType) {
            'courses_completed' => $user->courses()->where('status', 'completed')->count() >= $requiredCount,
            'lessons_completed' => $user->lessons()->where('status', 'completed')->count() >= $requiredCount,
            'quizzes_passed' => $user->quizzes()->where('passed', true)->count() >= $requiredCount,
            'assignments_submitted' => $user->assignments()->where('submitted', true)->count() >= $requiredCount,
            default => false,
        };
    }
}
