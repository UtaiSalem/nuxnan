<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KpiDefinition extends Model
{
    use HasFactory;

    protected $fillable = [
        'academy_id',
        'code',
        'name',
        'description',
        'category',
        'metric_type',
        'calculation',
        'target_value',
        'warning_threshold',
        'critical_threshold',
        'comparison_direction',
        'is_active',
    ];

    protected $casts = [
        'calculation' => 'array',
        'target_value' => 'decimal:4',
        'warning_threshold' => 'decimal:4',
        'critical_threshold' => 'decimal:4',
        'is_active' => 'boolean',
    ];

    // Categories
    const CATEGORY_ACADEMIC = 'academic';

    const CATEGORY_FINANCIAL = 'financial';

    const CATEGORY_OPERATIONAL = 'operational';

    const CATEGORY_STAFF = 'staff';

    const CATEGORIES = [
        self::CATEGORY_ACADEMIC,
        self::CATEGORY_FINANCIAL,
        self::CATEGORY_OPERATIONAL,
        self::CATEGORY_STAFF,
    ];

    // Metric Types
    const METRIC_PERCENTAGE = 'percentage';

    const METRIC_COUNT = 'count';

    const METRIC_CURRENCY = 'currency';

    const METRIC_RATIO = 'ratio';

    const METRIC_TYPES = [
        self::METRIC_PERCENTAGE,
        self::METRIC_COUNT,
        self::METRIC_CURRENCY,
        self::METRIC_RATIO,
    ];

    // Comparison Directions
    const DIRECTION_HIGHER_IS_BETTER = 'higher_is_better';

    const DIRECTION_LOWER_IS_BETTER = 'lower_is_better';

    // Relationships
    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
    }

    public function values(): HasMany
    {
        return $this->hasMany(KpiValue::class, 'kpi_id');
    }

    public function latestValue(): HasMany
    {
        return $this->hasMany(KpiValue::class, 'kpi_id')->latest('period_date');
    }

    public function alertRules(): HasMany
    {
        return $this->hasMany(AnalyticsAlertRule::class, 'kpi_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
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

    public function getStatus(float $value): string
    {
        if ($this->comparison_direction === self::DIRECTION_HIGHER_IS_BETTER) {
            if ($this->critical_threshold && $value < $this->critical_threshold) {
                return 'critical';
            }
            if ($this->warning_threshold && $value < $this->warning_threshold) {
                return 'warning';
            }
        } else {
            if ($this->critical_threshold && $value > $this->critical_threshold) {
                return 'critical';
            }
            if ($this->warning_threshold && $value > $this->warning_threshold) {
                return 'warning';
            }
        }

        return 'on_track';
    }

    public function isOnTrack(float $value): bool
    {
        return $this->getStatus($value) === 'on_track';
    }

    public function formatValue(float $value): string
    {
        switch ($this->metric_type) {
            case self::METRIC_PERCENTAGE:
                return number_format($value, 1).'%';
            case self::METRIC_CURRENCY:
                return '฿'.number_format($value, 2);
            case self::METRIC_RATIO:
                return number_format($value, 2);
            default:
                return number_format($value, 0);
        }
    }
}
