<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * AlertAcknowledgement Model - การรับทราบการแจ้งเตือนฉุกเฉิน
 */
class AlertAcknowledgement extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'alert_id',
        'user_id',
        'channel',
        'acknowledged_at',
        'response',
    ];

    protected $casts = [
        'acknowledged_at' => 'datetime',
    ];

    // Channel constants
    const CHANNEL_APP = 'app';
    const CHANNEL_SMS = 'sms';
    const CHANNEL_EMAIL = 'email';

    // Response constants
    const RESPONSE_SAFE = 'safe';
    const RESPONSE_NEED_HELP = 'need_help';

    // Relationships
    public function alert(): BelongsTo
    {
        return $this->belongsTo(EmergencyAlert::class, 'alert_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
