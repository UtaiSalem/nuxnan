<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\Auditable;

/**
 * EmergencyAlert Model - การแจ้งเตือนฉุกเฉิน
 */
class EmergencyAlert extends Model
{
    use HasFactory, Auditable;

    protected $fillable = [
        'academy_id',
        'created_by',
        'title',
        'message',
        'alert_type',
        'severity',
        'target_audience',
        'send_sms',
        'send_email',
        'send_push',
        'is_active',
        'expires_at',
        'delivery_stats',
    ];

    protected $casts = [
        'target_audience' => 'array',
        'delivery_stats' => 'array',
        'send_sms' => 'boolean',
        'send_email' => 'boolean',
        'send_push' => 'boolean',
        'is_active' => 'boolean',
        'expires_at' => 'datetime',
    ];

    // Alert type constants
    const TYPE_FIRE = 'fire';
    const TYPE_EARTHQUAKE = 'earthquake';
    const TYPE_FLOOD = 'flood';
    const TYPE_LOCKDOWN = 'lockdown';
    const TYPE_MEDICAL = 'medical';
    const TYPE_WEATHER = 'weather';
    const TYPE_OTHER = 'other';

    const TYPES = [
        self::TYPE_FIRE => 'ไฟไหม้',
        self::TYPE_EARTHQUAKE => 'แผ่นดินไหว',
        self::TYPE_FLOOD => 'น้ำท่วม',
        self::TYPE_LOCKDOWN => 'ล็อคดาวน์',
        self::TYPE_MEDICAL => 'ฉุกเฉินทางการแพทย์',
        self::TYPE_WEATHER => 'สภาพอากาศ',
        self::TYPE_OTHER => 'อื่นๆ',
    ];

    // Severity constants
    const SEVERITY_LOW = 'low';
    const SEVERITY_MEDIUM = 'medium';
    const SEVERITY_HIGH = 'high';
    const SEVERITY_CRITICAL = 'critical';

    const SEVERITIES = [
        self::SEVERITY_LOW => 'ต่ำ',
        self::SEVERITY_MEDIUM => 'ปานกลาง',
        self::SEVERITY_HIGH => 'สูง',
        self::SEVERITY_CRITICAL => 'วิกฤต',
    ];

    // Relationships
    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function acknowledgements(): HasMany
    {
        return $this->hasMany(AlertAcknowledgement::class, 'alert_id');
    }

    // Accessors
    public function getTypeNameAttribute(): string
    {
        return self::TYPES[$this->alert_type] ?? '';
    }

    public function getSeverityNameAttribute(): string
    {
        return self::SEVERITIES[$this->severity] ?? '';
    }

    public function getAcknowledgementCountAttribute(): int
    {
        return $this->acknowledgements()->count();
    }

    // Methods
    public function deactivate(): void
    {
        $this->update(['is_active' => false]);
    }

    public function updateDeliveryStats(array $stats): void
    {
        $this->update(['delivery_stats' => $stats]);
    }

    public function acknowledge(int $userId, string $channel = 'app', ?string $response = null): void
    {
        $this->acknowledgements()->firstOrCreate([
            'user_id' => $userId,
        ], [
            'channel' => $channel,
            'acknowledged_at' => now(),
            'response' => $response,
        ]);
    }

    // Scopes
    public function scopeByAcademy($query, $academyId)
    {
        return $query->where('academy_id', $academyId);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    public function scopeByType($query, $type)
    {
        return $query->where('alert_type', $type);
    }

    public function scopeBySeverity($query, $severity)
    {
        return $query->where('severity', $severity);
    }
}
