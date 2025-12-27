<?php

namespace App\Models;

use App\Models\User;
use App\Models\Course;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CourseMember extends Model
{
    use HasFactory;

    protected $guarded = [];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'edited_grade' => 'integer',
            'achieved_score' => 'integer',
            'bonus_points' => 'integer',
            'efficiency' => 'integer',
            'grade_progress' => 'integer',
            'order_number' => 'integer',
            'member_code' => 'integer',
            'enrollment_date' => 'datetime',
            'completion_date' => 'datetime',
            'access_expiry_date' => 'datetime',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(CourseGroup::class, 'group_id');
    }

    public function members():HasMany
    {
        return $this->hasMany(User::class);
    }

    public function courses(): HasMany
    {
        return $this->hasMany(Course::class, 'id');
    }

    //members accessors sort by order_number
    public function getMembersAttribute()
    {
        return $this->members()->orderBy('order_number')->get();
    }

    /**
     * Calculate the total achieved score including bonus points
     */
    public function getTotalAchievedScore(): int
    {
        return ($this->achieved_score ?? 0) + ($this->bonus_points ?? 0);
    }

    /**
     * Calculate the percentage score capped at 100%
     */
    public function getPercentageScore(): ?float
    {
        if (!$this->course || $this->course->total_score <= 0) {
            return null;
        }

        $totalAchieved = $this->getTotalAchievedScore();
        $percentage = ($totalAchieved / $this->course->total_score) * 100;
        
        return min(100, max(0, $percentage));
    }

    /**
     * Calculate Thai grade based on percentage score
     * 
     * Thai Grading System (Integer-based):
     * - 80-100% = 4 (A)
     * - 75-79% = 3 (B+/B)
     * - 70-74% = 3 (B)
     * - 65-69% = 2 (C+/C)
     * - 60-64% = 2 (C)
     * - 55-59% = 1 (D+/D)
     * - 50-54% = 1 (D)
     * - 0-49% = 0 (F)
     * - Incomplete/No score = null
     */
    public function getCalculatedGrade(): ?int
    {
        $percentage = $this->getPercentageScore();
        
        if ($percentage === null) {
            return null;
        }

        if ($percentage >= 80) {
            return 4; // A (80-100)
        } elseif ($percentage >= 75) {
            return 3; // B+ (75-79)
        } elseif ($percentage >= 70) {
            return 3; // B (70-74)
        } elseif ($percentage >= 65) {
            return 2; // C+ (65-69)
        } elseif ($percentage >= 60) {
            return 2; // C (60-64)
        } elseif ($percentage >= 55) {
            return 1; // D+ (55-59)
        } elseif ($percentage >= 50) {
            return 1; // D (50-54)
        } else {
            return 0; // F (0-49)
        }
    }

    /**
     * Get the grade name (A, B, C, D, F) from numeric grade
     */
    public function getGradeName(): ?string
    {
        $grade = $this->getCalculatedGrade();
        
        $gradeNames = [
            4 => 'A',
            3 => 'B+/B',
            2 => 'C+/C',
            1 => 'D+/D',
            0 => 'F'
        ];

        return $gradeNames[$grade] ?? null;
    }

    /**
     * Check if the current grade_progress matches the calculated grade
     */
    public function hasCorrectGrade(): bool
    {
        $calculatedGrade = $this->getCalculatedGrade();
        $currentGrade = $this->grade_progress;
        
        // Both null or both same value
        return $calculatedGrade === $currentGrade;
    }
}
