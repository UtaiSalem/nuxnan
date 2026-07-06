<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AnalyticsAlertRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'academy_id',
        'kpi_id',
        'name',
        'description',
        'condition_type',
        'operator',
        'threshold_value',
        'alert_level',
        'notification_channels',
        'recipients',
        'is_active',
        'last_triggered_at',
    ];

    protected $casts = [
        'threshold_value' => 'decimal:4',
        'notification_channels' => 'array',
        'recipients' => 'array',
        'is_active' => 'boolean',
        'last_triggered_at' => 'datetime',
    ];

    // Condition Types
    const CONDITION_THRESHOLD = 'threshold';

    const CONDITION_CHANGE_PERCENT = 'change_percent';

    const CONDITION_ANOMALY = 'anomaly';

    const CONDITION_TYPES = [
        self::CONDITION_THRESHOLD,
        self::CONDITION_CHANGE_PERCENT,
        self::CONDITION_ANOMALY,
    ];

    // Operators
    const OPERATOR_GT = 'gt';

    const OPERATOR_LT = 'lt';

    const OPERATOR_EQ = 'eq';

    const OPERATOR_GTE = 'gte';

    const OPERATOR_LTE = 'lte';

    const OPERATORS = [
        self::OPERATOR_GT,
        self::OPERATOR_LT,
        self::OPERATOR_EQ,
        self::OPERATOR_GTE,
        self::OPERATOR_LTE,
    ];

    // Alert Levels
    const LEVEL_INFO = 'info';

    const LEVEL_WARNING = 'warning';

    const LEVEL_CRITICAL = 'critical';

    const LEVELS = [
        self::LEVEL_INFO,
        self::LEVEL_WARNING,
        self::LEVEL_CRITICAL,
    ];

    // Relationships
    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
    }

    public function kpi(): BelongsTo
    {
        return $this->belongsTo(KpiDefinition::class, 'kpi_id');
    }

    public function history(): HasMany
    {
        return $this->hasMany(AnalyticsAlertHistory::class, 'alert_rule_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByLevel($query, string $level)
    {
        return $query->where('alert_level', $level);
    }

    // Methods
    public function activate(): void
    {
        $this->update(['is_active' => true]);
    }

    public function deactivate(): void
    {
        $this->update(['is_active' => false]);
    }

    public function checkCondition(float $value): bool
    {
        switch ($this->operator) {
            case self::OPERATOR_GT:
                return $value > $this->threshold_value;
            case self::OPERATOR_LT:
                return $value < $this->threshold_value;
            case self::OPERATOR_EQ:
                return $value == $this->threshold_value;
            case self::OPERATOR_GTE:
                return $value >= $this->threshold_value;
            case self::OPERATOR_LTE:
                return $value <= $this->threshold_value;
            default:
                return false;
        }
    }

    public function trigger(float $triggerValue): AnalyticsAlertHistory
    {
        $this->update(['last_triggered_at' => now()]);

        return AnalyticsAlertHistory::create([
            'alert_rule_id' => $this->id,
            'trigger_value' => $triggerValue,
            'threshold_value' => $this->threshold_value,
            'alert_level' => $this->alert_level,
            'message' => $this->generateMessage($triggerValue),
        ]);
    }

    public function generateMessage(float $triggerValue): string
    {
        $kpiName = $this->kpi ? $this->kpi->name : 'Unknown KPI';
        $operatorText = $this->getOperatorText();

        return sprintf(
            '%s: %s is %s %s (current: %s)',
            strtoupper($this->alert_level),
            $kpiName,
            $operatorText,
            $this->threshold_value,
            $triggerValue
        );
    }

    private function getOperatorText(): string
    {
        return match ($this->operator) {
            self::OPERATOR_GT => 'greater than',
            self::OPERATOR_LT => 'less than',
            self::OPERATOR_EQ => 'equal to',
            self::OPERATOR_GTE => 'greater than or equal to',
            self::OPERATOR_LTE => 'less than or equal to',
            default => 'compared to',
        };
    }
}
