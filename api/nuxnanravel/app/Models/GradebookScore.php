<?php

namespace App\Models;

use App\Services\GamificationService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * GradebookScore Model - คะแนนนักเรียน
 */
class GradebookScore extends Model
{
    use HasFactory;

    protected $fillable = [
        'academy_id',
        'assessment_id',
        'student_id',
        'user_id',
        'score',
        'feedback',
        'status',
        'is_late',
        'submitted_at',
        'graded_by',
        'graded_at',
        'points_awarded',
    ];

    protected $casts = [
        'score' => 'decimal:2',
        'is_late' => 'boolean',
        'submitted_at' => 'date',
        'graded_at' => 'datetime',
        'points_awarded' => 'integer',
    ];

    // Status Constants
    const STATUS_PENDING = 'pending';

    const STATUS_GRADED = 'graded';

    const STATUS_EXCUSED = 'excused';

    const STATUS_MISSING = 'missing';

    // Relationships
    public function assessment(): BelongsTo
    {
        return $this->belongsTo(GradebookAssessment::class, 'assessment_id');
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

    public function grader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'graded_by');
    }

    // Scopes
    public function scopeByAssessment($query, $assessmentId)
    {
        return $query->where('assessment_id', $assessmentId);
    }

    public function scopeByStudent($query, $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    public function scopeGraded($query)
    {
        return $query->where('status', self::STATUS_GRADED);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    // Accessors
    public function getPercentageAttribute(): ?float
    {
        $maxScore = $this->assessment?->max_score;
        if (! $maxScore || $maxScore == 0 || $this->score === null) {
            return null;
        }

        return round(($this->score / $maxScore) * 100, 2);
    }

    public function getStatusTextAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'รอตรวจ',
            self::STATUS_GRADED => 'ตรวจแล้ว',
            self::STATUS_EXCUSED => 'ได้รับการยกเว้น',
            self::STATUS_MISSING => 'ไม่ส่ง',
            default => 'ไม่ระบุ'
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'yellow',
            self::STATUS_GRADED => 'green',
            self::STATUS_EXCUSED => 'blue',
            self::STATUS_MISSING => 'red',
            default => 'gray'
        };
    }

    // Methods
    /**
     * Grade the score and optionally award gamification points
     */
    public function grade(float $score, ?string $feedback = null, ?int $graderId = null): self
    {
        $this->update([
            'score' => $score,
            'feedback' => $feedback,
            'status' => self::STATUS_GRADED,
            'graded_by' => $graderId ?? auth()->id(),
            'graded_at' => now(),
        ]);

        // Award gamification points based on performance
        $this->awardGamificationPoints();

        return $this;
    }

    /**
     * Award gamification points based on score percentage
     */
    protected function awardGamificationPoints(): void
    {
        if (! $this->user_id || ! $this->percentage) {
            return;
        }

        $points = 0;
        $percentage = $this->percentage;

        // Points based on percentage
        if ($percentage >= 90) {
            $points = 20; // Excellent
        } elseif ($percentage >= 80) {
            $points = 15; // Very Good
        } elseif ($percentage >= 70) {
            $points = 10; // Good
        } elseif ($percentage >= 60) {
            $points = 5; // Pass
        }

        // Bonus for not being late
        if (! $this->is_late && $points > 0) {
            $points += 5;
        }

        if ($points > 0) {
            try {
                $gamificationService = app(GamificationService::class);
                $gamificationService->awardPoints(
                    $this->user_id,
                    $points,
                    'gradebook_score',
                    "ได้รับคะแนน {$this->score}/{$this->assessment->max_score} ในการประเมิน {$this->assessment->name}"
                );

                $this->update(['points_awarded' => $points]);
            } catch (\Exception $e) {
                // Log error but don't fail the grading
                \Log::error('Failed to award gamification points: '.$e->getMessage());
            }
        }
    }

    /**
     * Mark as missing
     */
    public function markAsMissing(?int $graderId = null): self
    {
        $this->update([
            'score' => 0,
            'status' => self::STATUS_MISSING,
            'graded_by' => $graderId ?? auth()->id(),
            'graded_at' => now(),
        ]);

        return $this;
    }

    /**
     * Mark as excused
     */
    public function markAsExcused(?string $reason = null, ?int $graderId = null): self
    {
        $this->update([
            'status' => self::STATUS_EXCUSED,
            'feedback' => $reason,
            'graded_by' => $graderId ?? auth()->id(),
            'graded_at' => now(),
        ]);

        return $this;
    }
}
