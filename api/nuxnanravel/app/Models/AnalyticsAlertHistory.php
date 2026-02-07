<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnalyticsAlertHistory extends Model
{
    use HasFactory;

    protected $table = 'analytics_alert_history';

    protected $fillable = [
        'alert_rule_id',
        'trigger_value',
        'threshold_value',
        'alert_level',
        'message',
        'notification_sent',
        'is_acknowledged',
        'acknowledged_by',
        'acknowledged_at',
    ];

    protected $casts = [
        'trigger_value' => 'decimal:4',
        'threshold_value' => 'decimal:4',
        'notification_sent' => 'array',
        'is_acknowledged' => 'boolean',
        'acknowledged_at' => 'datetime',
    ];

    // Relationships
    public function alertRule(): BelongsTo
    {
        return $this->belongsTo(AnalyticsAlertRule::class, 'alert_rule_id');
    }

    public function acknowledgedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acknowledged_by');
    }

    // Scopes
    public function scopeUnacknowledged($query)
    {
        return $query->where('is_acknowledged', false);
    }

    public function scopeAcknowledged($query)
    {
        return $query->where('is_acknowledged', true);
    }

    public function scopeByLevel($query, string $level)
    {
        return $query->where('alert_level', $level);
    }

    public function scopeCritical($query)
    {
        return $query->where('alert_level', AnalyticsAlertRule::LEVEL_CRITICAL);
    }

    public function scopeRecent($query, int $hours = 24)
    {
        return $query->where('created_at', '>=', now()->subHours($hours));
    }

    // Methods
    public function acknowledge(int $userId): void
    {
        $this->update([
            'is_acknowledged' => true,
            'acknowledged_by' => $userId,
            'acknowledged_at' => now(),
        ]);
    }

    public function markNotificationSent(string $channel): void
    {
        $sent = $this->notification_sent ?? [];
        $sent[$channel] = now()->toIso8601String();
        $this->update(['notification_sent' => $sent]);
    }

    public function wasNotificationSent(string $channel): bool
    {
        return isset($this->notification_sent[$channel]);
    }

    public function isCritical(): bool
    {
        return $this->alert_level === AnalyticsAlertRule::LEVEL_CRITICAL;
    }

    public function isWarning(): bool
    {
        return $this->alert_level === AnalyticsAlertRule::LEVEL_WARNING;
    }

    public function getTimeSinceCreated(): string
    {
        return $this->created_at->diffForHumans();
    }
}
