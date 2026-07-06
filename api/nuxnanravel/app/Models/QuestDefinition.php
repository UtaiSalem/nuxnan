<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuestDefinition extends Model
{
    protected $fillable = [
        'quest_key', 'title', 'title_th', 'description', 'quest_type',
        'event_type', 'target_count', 'points_reward', 'xp_reward',
        'icon', 'is_active', 'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'points_reward' => 'decimal:2',
    ];

    public function userProgress(): HasMany
    {
        return $this->hasMany(UserQuestProgress::class, 'quest_id');
    }
}
