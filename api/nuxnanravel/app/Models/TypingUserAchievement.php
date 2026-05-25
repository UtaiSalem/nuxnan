<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent.Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TypingUserAchievement extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'achievement_id',
        'earned_at',
        'session_id',
    ];

    protected $casts = [
        'earned_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function achievement(): BelongsTo
    {
        return $this->belongsTo(TypingAchievement::class, 'achievement_id');
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(TypingSession::class, 'session_id');
    }
}
