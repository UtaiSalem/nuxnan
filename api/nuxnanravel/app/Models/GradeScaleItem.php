<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * GradeScaleItem Model - รายละเอียดเกรด
 */
class GradeScaleItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'grade_scale_id',
        'grade',
        'letter_grade',
        'min_score',
        'max_score',
        'grade_points',
        'description',
        'sort_order',
    ];

    protected $casts = [
        'min_score' => 'decimal:2',
        'max_score' => 'decimal:2',
        'grade_points' => 'decimal:2',
        'sort_order' => 'integer',
    ];

    // Relationships
    public function gradeScale(): BelongsTo
    {
        return $this->belongsTo(GradeScale::class);
    }

    // Accessors
    public function getDisplayNameAttribute(): string
    {
        if ($this->letter_grade) {
            return "{$this->letter_grade} ({$this->grade})";
        }
        return $this->grade;
    }

    public function getScoreRangeAttribute(): string
    {
        return number_format($this->min_score, 2) . ' - ' . number_format($this->max_score, 2);
    }

    /**
     * Check if score falls within this grade item's range
     */
    public function matchesScore(float $score): bool
    {
        return $score >= $this->min_score && $score <= $this->max_score;
    }
}
