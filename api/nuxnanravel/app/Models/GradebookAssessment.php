<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * GradebookAssessment Model - การประเมินในสมุดคะแนน
 */
class GradebookAssessment extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'semester_id',
        'category_id',
        'name',
        'description',
        'max_score',
        'weight',
        'due_date',
        'is_published',
        'is_included_in_grade',
        'assignment_id',
        'quiz_id',
        'created_by',
    ];

    protected $casts = [
        'max_score' => 'decimal:2',
        'weight' => 'decimal:2',
        'due_date' => 'date',
        'is_published' => 'boolean',
        'is_included_in_grade' => 'boolean',
    ];

    // Relationships
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(AssessmentCategory::class, 'category_id');
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class);
    }

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(CourseQuiz::class, 'quiz_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scores(): HasMany
    {
        return $this->hasMany(GradebookScore::class, 'assessment_id');
    }

    // Scopes
    public function scopeByCourse($query, $courseId)
    {
        return $query->where('course_id', $courseId);
    }

    public function scopeBySemester($query, $semesterId)
    {
        return $query->where('semester_id', $semesterId);
    }

    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function scopeIncludedInGrade($query)
    {
        return $query->where('is_included_in_grade', true);
    }

    // Accessors
    public function getGradedCountAttribute(): int
    {
        return $this->scores()->where('status', 'graded')->count();
    }

    public function getPendingCountAttribute(): int
    {
        return $this->scores()->where('status', 'pending')->count();
    }

    public function getAverageScoreAttribute(): ?float
    {
        $avg = $this->scores()->where('status', 'graded')->avg('score');
        return $avg ? round($avg, 2) : null;
    }

    public function getAveragePercentageAttribute(): ?float
    {
        if (!$this->max_score || $this->max_score == 0) return null;
        $avg = $this->average_score;
        return $avg ? round(($avg / $this->max_score) * 100, 2) : null;
    }

    // Methods
    /**
     * Sync scores from assignment or quiz
     */
    public function syncScoresFromSource(): void
    {
        if ($this->assignment_id) {
            $this->syncFromAssignment();
        } elseif ($this->quiz_id) {
            $this->syncFromQuiz();
        }
    }

    protected function syncFromAssignment(): void
    {
        $answers = AssignmentAnswer::where('assignment_id', $this->assignment_id)
            ->with('user.students')
            ->get();

        foreach ($answers as $answer) {
            $student = $answer->user?->students()->first();
            if (!$student) continue;

            $this->scores()->updateOrCreate(
                ['student_id' => $student->id],
                [
                    'user_id' => $answer->user_id,
                    'score' => $answer->points,
                    'feedback' => $answer->feedback,
                    'status' => $answer->status === 'graded' ? 'graded' : 'pending',
                    'submitted_at' => $answer->submission_date,
                    'is_late' => $answer->late_submission ?? false,
                ]
            );
        }
    }

    protected function syncFromQuiz(): void
    {
        $results = CourseQuizResult::where('quiz_id', $this->quiz_id)
            ->with('user.students')
            ->get();

        foreach ($results as $result) {
            $student = $result->user?->students()->first();
            if (!$student) continue;

            $this->scores()->updateOrCreate(
                ['student_id' => $student->id],
                [
                    'user_id' => $result->user_id,
                    'score' => $result->score,
                    'status' => 'graded',
                    'submitted_at' => $result->completed_at,
                ]
            );
        }
    }
}
