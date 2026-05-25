<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent.Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TypingDailyChallenge extends Model
{
    use HasFactory;

    protected $fillable = [
        'challenge_date',
        'game_mode',
        'language',
        'difficulty',
        'content_ids',
        'target_wpm',
        'target_acc',
        'xp_reward',
        'pp_reward',
    ];

    protected $casts = [
        'challenge_date' => 'date',
        'content_ids' => 'json',
        'target_acc' => 'decimal:2',
    ];

    public function userChallenges(): HasMany
    {
        return $this->hasMany(TypingUserDailyChallenge::class, 'challenge_id');
    }
}
