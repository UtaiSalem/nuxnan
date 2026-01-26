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
            'edited_grade' => 'float',
            'achieved_score' => 'integer',
            'bonus_points' => 'integer',
            'efficiency' => 'integer',
            'grade_progress' => 'float',
            'order_number' => 'integer',
            'member_code' => 'integer',
            'enrollment_date' => 'datetime',
            'completion_date' => 'datetime',
            'access_expiry_date' => 'datetime',
            'last_accessed_at' => 'datetime',
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

    public function permissions(): HasMany
    {
        return $this->hasMany(CoursePermission::class);
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
     /**
     * Calculate Thai grade based on percentage score (Static helper)
     */
    public static function calculateGradeFromPercentage($percentage)
    {
        if ($percentage >= 80) {
            return 4.0;
        } elseif ($percentage >= 75) {
            return 3.5;
        } elseif ($percentage >= 70) {
            return 3.0;
        } elseif ($percentage >= 65) {
            return 2.5;
        } elseif ($percentage >= 60) {
            return 2.0;
        } elseif ($percentage >= 55) {
            return 1.5;
        } elseif ($percentage >= 50) {
            return 1.0;
        } else {
            return 0.0;
        }
    }

    /**
     * Calculate Thai grade based on percentage score (Instance method)
     */
    public function getCalculatedGrade(): ?float
    {
        $percentage = $this->getPercentageScore();
        
        if ($percentage === null) {
            return null;
        }

        return self::calculateGradeFromPercentage($percentage);
    }

    /**
     * Get grade name from numeric grade (Static helper)
     */
    public static function getGradeNameFromGrade($grade)
    {
        $gradeStr = (string)$grade;
        $gradeNames = [
            '4' => 'A',
            '3.5' => 'B+',
            '3' => 'B',
            '2.5' => 'C+',
            '2' => 'C',
            '1.5' => 'D+',
            '1' => 'D',
            '0' => 'F'
        ];

        return $gradeNames[$gradeStr] ?? null;
    }

    /**
     * Get the grade name (A, B, C, D, F) from numeric grade
     */
    public function getGradeName(): ?string
    {
        // Prioritize edited_grade, then grade_progress, then calculation
        $grade = $this->edited_grade ?? $this->grade_progress ?? $this->getCalculatedGrade();
        return self::getGradeNameFromGrade($grade);
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

    /**
     * Check if this member has a specific permission
     */
    public function hasPermission(string $permission): bool
    {
        // Course owner has all permissions
        if ($this->course->user_id === $this->user_id) {
            return true;
        }

        return $this->permissions()->where('permission', $permission)->exists();
    }

    /**
     * Get all permissions for this member
     */
    public function getPermissions(): array
    {
        return $this->permissions()->pluck('permission')->toArray();
    }

    /**
     * Grant default permissions based on role
     */
    public function grantDefaultPermissions(): void
    {
        CoursePermission::grantDefaultPermissions($this);
    }
}
