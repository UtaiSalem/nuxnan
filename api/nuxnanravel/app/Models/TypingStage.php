<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TypingStage extends Model
{
    use HasFactory;

    protected $fillable = [
        'language',
        'level',
        'stage_number',
        'title',
        'description',
        'stage_type',
        'target_keys',
        'exercise_pool',
        'min_accuracy',
        'min_wpm',
        'min_duration_sec',
        'pass_count_needed',
        'pass_window_size',
        'xp_reward',
        'sort_order',
    ];

    protected $casts = [
        'level' => 'integer',
        'stage_number' => 'integer',
        'target_keys' => 'array',
        'exercise_pool' => 'array',
        'min_accuracy' => 'float',
        'min_wpm' => 'integer',
        'min_duration_sec' => 'integer',
        'pass_count_needed' => 'integer',
        'pass_window_size' => 'integer',
        'xp_reward' => 'integer',
        'sort_order' => 'integer',
    ];

    public function userStages(): HasMany
    {
        return $this->hasMany(TypingUserStage::class, 'stage_id');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(TypingSession::class, 'stage_id');
    }
}
