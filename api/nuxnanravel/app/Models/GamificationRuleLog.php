<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GamificationRuleLog extends Model
{
    protected $fillable = [
        'user_id', 'usage_event_id', 'rule_key', 'result',
        'points_awarded', 'xp_awarded', 'reason', 'evaluated_at',
    ];

    protected $casts = [
        'points_awarded' => 'decimal:2',
        'evaluated_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function usageEvent(): BelongsTo
    {
        return $this->belongsTo(UserUsageEvent::class);
    }
}
