<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnalyticsSnapshot extends Model
{
    use HasFactory;

    protected $fillable = [
        'academy_id',
        'snapshot_type',
        'category',
        'snapshot_date',
        'period_start',
        'period_end',
        'metrics',
        'breakdown',
    ];

    protected $casts = [
        'snapshot_date' => 'date',
        'metrics' => 'array',
        'breakdown' => 'array',
    ];

    // Snapshot Types
    const TYPE_DAILY = 'daily';

    const TYPE_WEEKLY = 'weekly';

    const TYPE_MONTHLY = 'monthly';

    const TYPE_ACADEMIC_YEAR = 'academic_year';

    const TYPES = [
        self::TYPE_DAILY,
        self::TYPE_WEEKLY,
        self::TYPE_MONTHLY,
        self::TYPE_ACADEMIC_YEAR,
    ];

    // Categories
    const CATEGORY_ENROLLMENT = 'enrollment';

    const CATEGORY_ATTENDANCE = 'attendance';

    const CATEGORY_ACADEMIC = 'academic';

    const CATEGORY_FINANCIAL = 'financial';

    const CATEGORIES = [
        self::CATEGORY_ENROLLMENT,
        self::CATEGORY_ATTENDANCE,
        self::CATEGORY_ACADEMIC,
        self::CATEGORY_FINANCIAL,
    ];

    // Relationships
    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
    }

    // Scopes
    public function scopeByType($query, string $type)
    {
        return $query->where('snapshot_type', $type);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeForDate($query, $date)
    {
        return $query->where('snapshot_date', $date);
    }

    public function scopeBetweenDates($query, $start, $end)
    {
        return $query->whereBetween('snapshot_date', [$start, $end]);
    }

    // Methods
    public function getMetric(string $key, $default = null)
    {
        return $this->metrics[$key] ?? $default;
    }

    public function getBreakdownBy(string $key): array
    {
        return $this->breakdown[$key] ?? [];
    }

    public static function createOrUpdateSnapshot(
        int $academyId,
        string $snapshotType,
        string $category,
        $date,
        array $metrics,
        ?array $breakdown = null
    ): self {
        return self::updateOrCreate(
            [
                'academy_id' => $academyId,
                'snapshot_type' => $snapshotType,
                'category' => $category,
                'snapshot_date' => $date,
            ],
            [
                'metrics' => $metrics,
                'breakdown' => $breakdown,
            ]
        );
    }
}
