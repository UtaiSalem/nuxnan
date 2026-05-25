<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TypingRaceRoom extends Model
{
    protected $fillable = [
        'room_code',
        'host_user_id',
        'academy_id',
        'status',
        'game_mode',
        'language',
        'difficulty',
        'time_limit',
        'content_ids',
        'max_players',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'content_ids' => 'json',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function host(): BelongsTo
    {
        return $this->belongsTo(User::class, 'host_user_id');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(TypingRaceParticipant::class, 'room_id');
    }

    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
    }
}
