<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * PerformanceReview Model - การประเมินผลงาน
 */
class PerformanceReview extends Model
{
    use Auditable, HasFactory;

    protected $fillable = [
        'staff_profile_id',
        'reviewer_id',
        'review_period',
        'review_date',
        'criteria_scores',
        'overall_score',
        'rating',
        'strengths',
        'areas_for_improvement',
        'goals_for_next_period',
        'staff_comments',
        'reviewer_comments',
        'status',
        'acknowledged_at',
    ];

    protected $casts = [
        'review_date' => 'date',
        'criteria_scores' => 'array',
        'overall_score' => 'decimal:2',
        'acknowledged_at' => 'datetime',
    ];

    // Rating constants
    const RATING_EXCELLENT = 'excellent';

    const RATING_GOOD = 'good';

    const RATING_SATISFACTORY = 'satisfactory';

    const RATING_NEEDS_IMPROVEMENT = 'needs_improvement';

    const RATING_UNSATISFACTORY = 'unsatisfactory';

    const RATINGS = [
        self::RATING_EXCELLENT => 'ดีเยี่ยม',
        self::RATING_GOOD => 'ดี',
        self::RATING_SATISFACTORY => 'ปานกลาง',
        self::RATING_NEEDS_IMPROVEMENT => 'ต้องปรับปรุง',
        self::RATING_UNSATISFACTORY => 'ไม่น่าพอใจ',
    ];

    // Status constants
    const STATUS_DRAFT = 'draft';

    const STATUS_SUBMITTED = 'submitted';

    const STATUS_ACKNOWLEDGED = 'acknowledged';

    const STATUS_FINAL = 'final';

    const STATUSES = [
        self::STATUS_DRAFT => 'ร่าง',
        self::STATUS_SUBMITTED => 'ส่งแล้ว',
        self::STATUS_ACKNOWLEDGED => 'รับทราบแล้ว',
        self::STATUS_FINAL => 'สรุปผล',
    ];

    // Relationships
    public function staffProfile(): BelongsTo
    {
        return $this->belongsTo(StaffProfile::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    // Accessors
    public function getRatingNameAttribute(): string
    {
        return self::RATINGS[$this->rating] ?? '';
    }

    public function getStatusNameAttribute(): string
    {
        return self::STATUSES[$this->status] ?? '';
    }

    // Methods
    public function submit(): void
    {
        $this->update(['status' => self::STATUS_SUBMITTED]);
    }

    public function acknowledge(): void
    {
        $this->update([
            'status' => self::STATUS_ACKNOWLEDGED,
            'acknowledged_at' => now(),
        ]);
    }

    public function finalize(): void
    {
        $this->update(['status' => self::STATUS_FINAL]);
    }

    public function calculateOverallScore(): void
    {
        if (! $this->criteria_scores || empty($this->criteria_scores)) {
            return;
        }

        $scores = collect($this->criteria_scores);
        $totalWeight = $scores->sum('weight');

        if ($totalWeight <= 0) {
            $this->overall_score = $scores->avg('score');
        } else {
            $weightedSum = $scores->sum(function ($item) {
                return ($item['score'] ?? 0) * ($item['weight'] ?? 1);
            });
            $this->overall_score = round($weightedSum / $totalWeight, 2);
        }

        // Determine rating based on score
        if ($this->overall_score >= 90) {
            $this->rating = self::RATING_EXCELLENT;
        } elseif ($this->overall_score >= 75) {
            $this->rating = self::RATING_GOOD;
        } elseif ($this->overall_score >= 60) {
            $this->rating = self::RATING_SATISFACTORY;
        } elseif ($this->overall_score >= 40) {
            $this->rating = self::RATING_NEEDS_IMPROVEMENT;
        } else {
            $this->rating = self::RATING_UNSATISFACTORY;
        }

        $this->save();
    }

    // Scopes
    public function scopeByStaff($query, $staffProfileId)
    {
        return $query->where('staff_profile_id', $staffProfileId);
    }

    public function scopeByReviewer($query, $reviewerId)
    {
        return $query->where('reviewer_id', $reviewerId);
    }

    public function scopeByPeriod($query, $period)
    {
        return $query->where('review_period', $period);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByRating($query, $rating)
    {
        return $query->where('rating', $rating);
    }
}
