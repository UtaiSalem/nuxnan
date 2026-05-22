<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserActivitySummary extends Model
{
    protected $fillable = [
        'user_id', 'summary_date', 'period_type', 'event_counts',
        'points_earned', 'xp_earned', 'active_minutes',
        'lessons_completed', 'quizzes_completed', 'streak_day'
    ];

    protected $casts = [
        'summary_date' => 'date',
        'event_counts' => 'array',
        'points_earned' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
