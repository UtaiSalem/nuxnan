<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TypingTournamentEntry extends Model
{
    protected $fillable = [
        'tournament_id',
        'user_id',
        'best_session_id',
        'best_wpm',
        'best_accuracy',
        'best_score',
        'attempts',
        'last_played_at',
        'rank',
        'prize_claimed',
        'prize_xp',
        'prize_pp',
    ];

    protected $casts = [
        'last_played_at' => 'datetime',
        'best_wpm' => 'integer',
        'best_accuracy' => 'decimal:2',
        'best_score' => 'integer',
        'attempts' => 'integer',
        'rank' => 'integer',
        'prize_claimed' => 'boolean',
        'prize_xp' => 'integer',
        'prize_pp' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(TypingTournament::class, 'tournament_id');
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(TypingSession::class, 'best_session_id');
    }
}
