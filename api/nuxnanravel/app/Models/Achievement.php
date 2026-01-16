<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Achievement extends Model
{
    use HasFactory;

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

    protected $casts = [
        'criteria' => 'array',
        'points_reward' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Get user achievements for this achievement.
     */
    public function userAchievements(): HasMany
    {
        return $this->hasMany(UserAchievement::class);
    }

    /**
     * Scope for active achievements
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for achievement type
     */
    public function scopeType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Get type label in Thai
     */
    public function getTypeLabelAttribute(): string
    {
        return match($this->type) {
            'points' => 'ความสำเร็จด้านแต้ม',
            'actions' => 'ความสำเร็จด้านการกระทำ',
            'streak' => 'ความสำเร็จด้านการเข้าต่อเนื่อง',
            'social' => 'ความสำเร็จด้านสังคม',
            'learning' => 'ความสำเร็จด้านการเรียนรู้',
            default => $this->type,
        };
    }
}
