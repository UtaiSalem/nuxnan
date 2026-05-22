<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserUsageEvent extends Model
{
    protected $fillable = [
        'user_id', 'event_type', 'source_type', 'source_id',
        'context', 'occurred_at', 'idempotency_key', 'ip_hash', 'processed_at'
    ];

    protected $casts = [
        'context' => 'array',
        'occurred_at' => 'datetime',
        'processed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
