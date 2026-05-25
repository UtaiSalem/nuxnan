<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TypingRaceParticipant extends Model
{
    protected $fillable = [
        'room_id',
        'user_id',
        'session_id',
        'player_name',
        'status',
        'progress',
        'wpm',
        'accuracy',
        'score',
        'rank',
        'finished_at',
    ];

    protected $casts = [
        'finished_at' => 'datetime',
        'accuracy' => 'decimal:2',
    ];

    public function room(): BelongsTo
    {
        return $this->belongsTo(TypingRaceRoom::class, 'room_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(TypingSession::class, 'session_id');
    }
}
