<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserQuestProgress extends Model
{
    protected $table = 'user_quest_progress';

    protected $fillable = [
        'user_id', 'quest_id', 'quest_date', 'progress',
        'is_completed', 'completed_at', 'reward_claimed'
    ];

    protected $casts = [
        'quest_date' => 'date',
        'is_completed' => 'boolean',
        'reward_claimed' => 'boolean',
        'completed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function quest(): BelongsTo
    {
        return $this->belongsTo(QuestDefinition::class, 'quest_id');
    }
}
