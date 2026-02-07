<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KpiValue extends Model
{
    use HasFactory;

    protected $fillable = [
        'kpi_id',
        'period_date',
        'period_type',
        'value',
        'previous_value',
        'change_percent',
        'status',
        'breakdown',
    ];

    protected $casts = [
        'period_date' => 'date',
        'value' => 'decimal:4',
        'previous_value' => 'decimal:4',
        'change_percent' => 'decimal:2',
        'breakdown' => 'array',
    ];

    // Period Types
    const PERIOD_DAILY = 'daily';
    const PERIOD_WEEKLY = 'weekly';
    const PERIOD_MONTHLY = 'monthly';
    const PERIOD_QUARTERLY = 'quarterly';
    const PERIOD_YEARLY = 'yearly';

    const PERIOD_TYPES = [
        self::PERIOD_DAILY,
        self::PERIOD_WEEKLY,
        self::PERIOD_MONTHLY,
        self::PERIOD_QUARTERLY,
        self::PERIOD_YEARLY,
    ];

    // Statuses
    const STATUS_ON_TRACK = 'on_track';
    const STATUS_WARNING = 'warning';
    const STATUS_CRITICAL = 'critical';

    // Relationships
    public function kpi(): BelongsTo
    {
        return $this->belongsTo(KpiDefinition::class, 'kpi_id');
    }

    // Scopes
    public function scopeByPeriodType($query, string $type)
    {
        return $query->where('period_type', $type);
    }

    public function scopeForDate($query, $date)
    {
        return $query->where('period_date', $date);
    }

    public function scopeBetweenDates($query, $start, $end)
    {
        return $query->whereBetween('period_date', [$start, $end]);
    }

    public function scopeOnTrack($query)
    {
        return $query->where('status', self::STATUS_ON_TRACK);
    }

    public function scopeWarning($query)
    {
        return $query->where('status', self::STATUS_WARNING);
    }

    public function scopeCritical($query)
    {
        return $query->where('status', self::STATUS_CRITICAL);
    }

    // Methods
    public function calculateChange(): void
    {
        if ($this->previous_value && $this->previous_value != 0) {
            $change = (($this->value - $this->previous_value) / $this->previous_value) * 100;
            $this->update(['change_percent' => $change]);
        }
    }

    public function updateStatus(): void
    {
        $status = $this->kpi->getStatus($this->value);
        $this->update(['status' => $status]);
    }

    public function isImproved(): bool
    {
        if ($this->change_percent === null) {
            return false;
        }

        $direction = $this->kpi->comparison_direction;
        
        if ($direction === KpiDefinition::DIRECTION_HIGHER_IS_BETTER) {
            return $this->change_percent > 0;
        }
        
        return $this->change_percent < 0;
    }

    public function getTrendIcon(): string
    {
        if ($this->change_percent === null) {
            return '→';
        }

        if ($this->isImproved()) {
            return '↑';
        }
        
        return '↓';
    }

    public static function recordValue(
        int $kpiId,
        $date,
        string $periodType,
        float $value,
        ?float $previousValue = null,
        ?array $breakdown = null
    ): self {
        $kpiValue = self::updateOrCreate(
            [
                'kpi_id' => $kpiId,
                'period_date' => $date,
                'period_type' => $periodType,
            ],
            [
                'value' => $value,
                'previous_value' => $previousValue,
                'breakdown' => $breakdown,
            ]
        );

        $kpiValue->calculateChange();
        $kpiValue->updateStatus();

        return $kpiValue->fresh();
    }
}
