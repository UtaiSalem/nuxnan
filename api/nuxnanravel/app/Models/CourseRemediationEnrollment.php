<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseRemediationEnrollment extends Model
{
    use HasFactory;

    protected $fillable = [
        'remediation_session_id',
        'course_member_id',
        'student_id',
        'original_grade',
        'original_score',
        'status',
        'enrolled_at',
        'confirmed_at',
        'attended_at',
        'submitted_at',
        'remediation_score',
        'remediation_grade',
        'grader_notes',
        'graded_by',
        'graded_at',
        'submission',
        'student_notes',
        'attempt_number',
    ];

    protected $casts = [
        'enrolled_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'attended_at' => 'datetime',
        'submitted_at' => 'datetime',
        'graded_at' => 'datetime',
        'original_score' => 'decimal:2',
        'remediation_score' => 'decimal:2',
        'submission' => 'array',
    ];

    // Status
    const STATUS_ENROLLED = 'enrolled';
    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_ATTENDED = 'attended';
    const STATUS_SUBMITTED = 'submitted';
    const STATUS_GRADED = 'graded';
    const STATUS_PASSED = 'passed';
    const STATUS_FAILED = 'failed';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_NO_SHOW = 'no_show';

    /**
     * Remediation session
     */
    public function remediationSession(): BelongsTo
    {
        return $this->belongsTo(CourseRemediationSession::class, 'remediation_session_id');
    }

    /**
     * Course member
     */
    public function courseMember(): BelongsTo
    {
        return $this->belongsTo(CourseMember::class);
    }

    /**
     * นักเรียน
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    /**
     * ผู้ให้คะแนน
     */
    public function grader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'graded_by');
    }

    /**
     * Grade edit log
     */
    public function gradeEditLog()
    {
        return $this->hasOne(GradeEditLog::class, 'remediation_enrollment_id');
    }

    /**
     * Confirm enrollment
     */
    public function confirm(): self
    {
        $this->update([
            'status' => self::STATUS_CONFIRMED,
            'confirmed_at' => now(),
        ]);

        return $this;
    }

    /**
     * Mark as attended
     */
    public function markAttended(): self
    {
        $this->update([
            'status' => self::STATUS_ATTENDED,
            'attended_at' => now(),
        ]);

        return $this;
    }

    /**
     * Submit work
     */
    public function submit(array $submission, ?string $notes = null): self
    {
        $this->update([
            'status' => self::STATUS_SUBMITTED,
            'submitted_at' => now(),
            'submission' => $submission,
            'student_notes' => $notes,
        ]);

        return $this;
    }

    /**
     * Grade the remediation
     */
    public function grade(float $score, string $grade, User $grader, ?string $notes = null): self
    {
        $session = $this->remediationSession;
        $passed = $session->passing_score ? $score >= $session->passing_score : true;

        $this->update([
            'status' => $passed ? self::STATUS_PASSED : self::STATUS_FAILED,
            'remediation_score' => $score,
            'remediation_grade' => $grade,
            'graded_by' => $grader->id,
            'graded_at' => now(),
            'grader_notes' => $notes,
        ]);

        return $this;
    }

    /**
     * Cancel enrollment
     */
    public function cancel(): self
    {
        $this->update([
            'status' => self::STATUS_CANCELLED,
        ]);

        return $this;
    }

    /**
     * Mark as no-show
     */
    public function markNoShow(): self
    {
        $this->update([
            'status' => self::STATUS_NO_SHOW,
        ]);

        return $this;
    }

    /**
     * Scope: Active enrollments (not cancelled)
     */
    public function scopeActive($query)
    {
        return $query->whereNotIn('status', [self::STATUS_CANCELLED]);
    }

    /**
     * Scope: Completed (passed or failed)
     */
    public function scopeCompleted($query)
    {
        return $query->whereIn('status', [self::STATUS_PASSED, self::STATUS_FAILED]);
    }

    /**
     * Get status label in Thai
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            self::STATUS_ENROLLED => 'ลงทะเบียนแล้ว',
            self::STATUS_CONFIRMED => 'ยืนยันแล้ว',
            self::STATUS_ATTENDED => 'เข้าร่วมแล้ว',
            self::STATUS_SUBMITTED => 'ส่งงานแล้ว',
            self::STATUS_GRADED => 'ให้คะแนนแล้ว',
            self::STATUS_PASSED => 'ผ่าน',
            self::STATUS_FAILED => 'ไม่ผ่าน',
            self::STATUS_CANCELLED => 'ยกเลิก',
            self::STATUS_NO_SHOW => 'ไม่มา',
            default => $this->status,
        };
    }
}
