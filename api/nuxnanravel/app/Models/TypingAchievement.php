<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class TypingAchievement extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'name',
        'description',
        'icon',
        'condition',
        'xp_reward',
        'is_active',
    ];

    protected $casts = [
        'condition' => 'json',
        'is_active' => 'boolean',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'typing_user_achievements')
            ->withPivot('earned_at', 'session_id')
            ->withTimestamps();
    }
}
