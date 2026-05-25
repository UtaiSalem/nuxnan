<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TypingSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'session_token',
        'game_mode',
        'language',
        'difficulty',
        'wpm',
        'raw_wpm',
        'accuracy',
        'score',
        'max_combo',
        'correct_chars',
        'total_chars',
        'correct_words',
        'total_words',
        'mistakes',
        'time_elapsed',
        'time_limit',
        'speed_bonus',
        'combo_bonus',
        'accuracy_bonus',
        'xp_earned',
        'classroom_id',
        'challenge_id',
        'completed',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'accuracy' => 'decimal:2',
        'completed' => 'boolean',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Academy::class, 'classroom_id');
    }
}
