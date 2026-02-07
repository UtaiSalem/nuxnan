<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrendAnalysis extends Model
{
    use HasFactory;

    protected $table = 'trend_analyses';

    protected $fillable = [
        'academy_id',
        'metric_name',
        'category',
        'time_granularity',
        'start_date',
        'end_date',
        'data_points',
        'trend_info',
        'forecast',
        'generated_at',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'data_points' => 'array',
        'trend_info' => 'array',
        'forecast' => 'array',
        'generated_at' => 'datetime',
    ];

    // Time Granularities
    const GRANULARITY_DAILY = 'daily';
    const GRANULARITY_WEEKLY = 'weekly';
    const GRANULARITY_MONTHLY = 'monthly';

    const GRANULARITIES = [
        self::GRANULARITY_DAILY,
        self::GRANULARITY_WEEKLY,
        self::GRANULARITY_MONTHLY,
    ];

    // Relationships
    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
    }

    // Scopes
    public function scopeByMetric($query, string $metric)
    {
        return $query->where('metric_name', $metric);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeByGranularity($query, string $granularity)
    {
        return $query->where('time_granularity', $granularity);
    }

    public function scopeRecent($query)
    {
        return $query->orderByDesc('generated_at');
    }

    // Methods
    public function getTrendDirection(): string
    {
        return $this->trend_info['direction'] ?? 'stable';
    }

    public function getSlope(): ?float
    {
        return $this->trend_info['slope'] ?? null;
    }

    public function getRSquared(): ?float
    {
        return $this->trend_info['r_squared'] ?? null;
    }

    public function isUpward(): bool
    {
        return $this->getTrendDirection() === 'upward';
    }

    public function isDownward(): bool
    {
        return $this->getTrendDirection() === 'downward';
    }

    public function isStable(): bool
    {
        return $this->getTrendDirection() === 'stable';
    }

    public function getDataPointCount(): int
    {
        return count($this->data_points ?? []);
    }

    public function getLatestValue(): ?float
    {
        $points = $this->data_points ?? [];
        if (empty($points)) {
            return null;
        }
        $last = end($points);
        return $last['value'] ?? null;
    }

    public function getForecastValue(int $periodsAhead = 1): ?float
    {
        $forecast = $this->forecast ?? [];
        return $forecast[$periodsAhead - 1]['value'] ?? null;
    }

    public static function generate(
        int $academyId,
        string $metricName,
        string $category,
        string $granularity,
        $startDate,
        $endDate,
        array $dataPoints,
        ?array $trendInfo = null,
        ?array $forecast = null
    ): self {
        return self::create([
            'academy_id' => $academyId,
            'metric_name' => $metricName,
            'category' => $category,
            'time_granularity' => $granularity,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'data_points' => $dataPoints,
            'trend_info' => $trendInfo,
            'forecast' => $forecast,
            'generated_at' => now(),
        ]);
    }
}
