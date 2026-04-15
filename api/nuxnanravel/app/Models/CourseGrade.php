<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * CourseGrade Model - เกรดรายวิชา
 */
class CourseGrade extends Model
{
    use HasFactory;

    protected $fillable = [
        'academy_id',
        'course_id',
        'student_id',
        'user_id',
        'semester_id',
        'total_score',
        'percentage',
        'letter_grade',
        'grade_points',
        'status',
        'remarks',
        'is_published',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'total_score' => 'decimal:2',
        'percentage' => 'decimal:2',
        'grade_points' => 'decimal:2',
        'is_published' => 'boolean',
        'approved_at' => 'datetime',
    ];

    // Status Constants
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED = 'completed';
    const STATUS_WITHDRAWN = 'withdrawn';
    const STATUS_INCOMPLETE = 'incomplete';

    // Relationships
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // Scopes
    public function scopeByCourse($query, $courseId)
    {
        return $query->where('course_id', $courseId);
    }

    public function scopeByStudent($query, $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    public function scopeBySemester($query, $semesterId)
    {
        return $query->where('semester_id', $semesterId);
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    // Accessors
    public function getStatusTextAttribute(): string
    {
        return match($this->status) {
            self::STATUS_IN_PROGRESS => 'กำลังเรียน',
            self::STATUS_COMPLETED => 'เสร็จสิ้น',
            self::STATUS_WITHDRAWN => 'ถอนวิชา',
            self::STATUS_INCOMPLETE => 'ไม่สมบูรณ์',
            default => 'ไม่ระบุ'
        };
    }

    public function getDisplayGradeAttribute(): string
    {
        if ($this->status === self::STATUS_WITHDRAWN) {
            return 'W';
        }
        if ($this->status === self::STATUS_INCOMPLETE) {
            return 'I';
        }
        return $this->letter_grade ?? '-';
    }

    public function getIsPassed(): bool
    {
        return $this->grade_points !== null && $this->grade_points >= 1.0;
    }

    // Methods
    /**
     * Calculate grade from gradebook scores
     */
    public function calculateFromScores(): self
    {
        $assessments = GradebookAssessment::where('course_id', $this->course_id)
            ->when($this->semester_id, function($q) {
                $q->where('semester_id', $this->semester_id);
            })
            ->where('is_included_in_grade', true)
            ->with(['scores' => function($q) {
                $q->where('student_id', $this->student_id)
                    ->whereIn('status', ['graded', 'missing']);
            }])
            ->get();

        $totalWeightedScore = 0;
        $totalWeight = 0;

        foreach ($assessments as $assessment) {
            $score = $assessment->scores->first();
            if ($score && $assessment->max_score > 0) {
                $percentage = ($score->score ?? 0) / $assessment->max_score * 100;
                $weightedScore = $percentage * ($assessment->weight / 100);
                $totalWeightedScore += $weightedScore;
                $totalWeight += $assessment->weight;
            }
        }

        // Calculate final percentage
        $finalPercentage = $totalWeight > 0 
            ? ($totalWeightedScore / $totalWeight) * 100 
            : 0;

        $this->update([
            'total_score' => $totalWeightedScore,
            'percentage' => $finalPercentage,
        ]);

        return $this;
    }

    /**
     * Assign letter grade based on percentage and grade scale
     */
    public function assignLetterGrade(?GradeScale $gradeScale = null): self
    {
        if ($this->percentage === null) {
            return $this;
        }

        // Get grade scale
        if (!$gradeScale) {
            $academyId = $this->course?->academy_id;
            $gradeScale = GradeScale::where('academy_id', $academyId)
                ->where('is_default', true)
                ->first();
        }

        if (!$gradeScale) {
            return $this;
        }

        $gradeItem = $gradeScale->getGradeFromScore($this->percentage);

        if ($gradeItem) {
            $this->update([
                'letter_grade' => $gradeItem->letter_grade,
                'grade_points' => $gradeItem->grade_points,
            ]);
        }

        return $this;
    }

    /**
     * Mark as completed
     */
    public function markAsCompleted(): self
    {
        $this->calculateFromScores();
        $this->assignLetterGrade();
        $this->update(['status' => self::STATUS_COMPLETED]);
        
        return $this;
    }

    /**
     * Publish the grade
     */
    public function publish(?int $approverId = null): self
    {
        $this->update([
            'is_published' => true,
            'approved_by' => $approverId ?? auth()->id(),
            'approved_at' => now(),
        ]);

        return $this;
    }
}
