<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CampaignDeliveryEvent extends Model
{
    public const STATUS_STARTED = 'started';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_REPLAYED = 'replayed';

    public const STATUS_INSUFFICIENT_WATCH = 'insufficient_watch';

    public const STATUS_INSUFFICIENT_VISIBILITY = 'insufficient_visibility';

    public $timestamps = false;

    public const CREATED_AT = 'created_at';

    public const UPDATED_AT = null;

    protected $fillable = [
        'advert_id', 'user_id', 'event_type', 'ip_hash', 'user_agent',
        'placement', 'idempotency_key', 'metadata',
        'session_id', 'delivery_token_hash', 'started_at', 'last_heartbeat_at', 'completed_at',
        'required_duration', 'page_visibility_ratio', 'device_fingerprint_hash', 'status', 'fraud_reason',
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
        'started_at' => 'datetime', 'last_heartbeat_at' => 'datetime', 'completed_at' => 'datetime',
        'required_duration' => 'integer', 'page_visibility_ratio' => 'decimal:4',
    ];

    public function advert(): BelongsTo
    {
        return $this->belongsTo(Advert::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
