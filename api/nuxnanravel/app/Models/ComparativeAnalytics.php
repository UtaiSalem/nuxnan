<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComparativeAnalytics extends Model
{
    use HasFactory;

    protected $table = 'comparative_analytics';

    protected $fillable = [
        'academy_id',
        'comparison_type',
        'category',
        'period_1',
        'period_2',
        'metrics',
        'breakdown',
        'generated_at',
    ];

    protected $casts = [
        'period_1' => 'array',
        'period_2' => 'array',
        'metrics' => 'array',
        'breakdown' => 'array',
        'generated_at' => 'datetime',
    ];

    // Comparison Types
    const TYPE_YEAR_OVER_YEAR = 'year_over_year';

    const TYPE_PERIOD_COMPARISON = 'period_comparison';

    const TYPE_COHORT = 'cohort';

    const TYPES = [
        self::TYPE_YEAR_OVER_YEAR,
        self::TYPE_PERIOD_COMPARISON,
        self::TYPE_COHORT,
    ];

    // Categories
    const CATEGORY_ENROLLMENT = 'enrollment';

    const CATEGORY_ACADEMIC = 'academic';

    const CATEGORY_FINANCIAL = 'financial';

    const CATEGORY_ATTENDANCE = 'attendance';

    const CATEGORIES = [
        self::CATEGORY_ENROLLMENT,
        self::CATEGORY_ACADEMIC,
        self::CATEGORY_FINANCIAL,
        self::CATEGORY_ATTENDANCE,
    ];

    // Relationships
    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
    }

    // Scopes
    public function scopeByType($query, string $type)
    {
        return $query->where('comparison_type', $type);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeRecent($query)
    {
        return $query->orderByDesc('generated_at');
    }

    // Methods
    public function getMetricComparison(string $metricName): ?array
    {
        return $this->metrics[$metricName] ?? null;
    }

    public function getChangePercent(string $metricName): ?float
    {
        $metric = $this->getMetricComparison($metricName);

        return $metric['change'] ?? null;
    }

    public function isImproved(string $metricName, bool $higherIsBetter = true): bool
    {
        $change = $this->getChangePercent($metricName);
        if ($change === null) {
            return false;
        }

        return $higherIsBetter ? $change > 0 : $change < 0;
    }

    public function getPeriod1Label(): string
    {
        return $this->period_1['label'] ?? 'Period 1';
    }

    public function getPeriod2Label(): string
    {
        return $this->period_2['label'] ?? 'Period 2';
    }

    public static function generate(
        int $academyId,
        string $comparisonType,
        string $category,
        array $period1,
        array $period2,
        array $metrics,
        ?array $breakdown = null
    ): self {
        return self::create([
            'academy_id' => $academyId,
            'comparison_type' => $comparisonType,
            'category' => $category,
            'period_1' => $period1,
            'period_2' => $period2,
            'metrics' => $metrics,
            'breakdown' => $breakdown,
            'generated_at' => now(),
        ]);
    }
}
