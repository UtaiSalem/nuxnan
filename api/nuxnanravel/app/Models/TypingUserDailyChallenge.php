<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent.Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TypingUserDailyChallenge extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'challenge_id',
        'session_id',
        'completed',
        'score',
        'wpm',
        'accuracy',
        'completed_at',
    ];

    protected $casts = [
        'completed' => 'boolean',
        'accuracy' => 'decimal:2',
        'completed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function challenge(): BelongsTo
    {
        return $this->belongsTo(TypingDailyChallenge::class, 'challenge_id');
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(TypingSession::class, 'session_id');
    }
}
