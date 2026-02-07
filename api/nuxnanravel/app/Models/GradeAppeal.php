<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GradeAppeal extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_member_id',
        'course_id',
        'student_id',
        'appeal_type',
        'reason',
        'evidence',
        'original_grade',
        'original_score',
        'requested_grade',
        'status',
        'reviewed_by',
        'reviewed_at',
        'new_grade',
        'new_score',
        'reviewer_notes',
        'rejection_reason',
        'deadline_at',
        'is_late_appeal',
    ];

    protected $casts = [
        'evidence' => 'array',
        'reviewed_at' => 'datetime',
        'deadline_at' => 'datetime',
        'original_score' => 'decimal:2',
        'new_score' => 'decimal:2',
        'is_late_appeal' => 'boolean',
    ];

    // Appeal types
    const TYPE_SCORE_ERROR = 'score_error';
    const TYPE_ATTENDANCE_ERROR = 'attendance_error';
    const TYPE_GRADING_CRITERIA = 'grading_criteria';
    const TYPE_SPECIAL_CIRCUMSTANCE = 'special_circumstance';
    const TYPE_OTHER = 'other';

    // Status
    const STATUS_PENDING = 'pending';
    const STATUS_REVIEWING = 'reviewing';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_WITHDRAWN = 'withdrawn';

    /**
     * Course member
     */
    public function courseMember(): BelongsTo
    {
        return $this->belongsTo(CourseMember::class);
    }

    /**
     * วิชา
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * นักเรียน
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    /**
     * ผู้พิจารณา
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Grade edit log
     */
    public function gradeEditLog()
    {
        return $this->hasOne(GradeEditLog::class, 'appeal_id');
    }

    /**
     * Scope: Pending appeals
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope: Needs review
     */
    public function scopeNeedsReview($query)
    {
        return $query->whereIn('status', [self::STATUS_PENDING, self::STATUS_REVIEWING]);
    }

    /**
     * Check if appeal is still within deadline
     */
    public function isWithinDeadline(): bool
    {
        if (!$this->deadline_at) {
            return true;
        }
        return $this->deadline_at->isFuture();
    }

    /**
     * Approve the appeal
     */
    public function approve(User $reviewer, ?string $newGrade = null, ?float $newScore = null, ?string $notes = null): self
    {
        $this->update([
            'status' => self::STATUS_APPROVED,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'new_grade' => $newGrade ?? $this->requested_grade,
            'new_score' => $newScore,
            'reviewer_notes' => $notes,
        ]);

        return $this;
    }

    /**
     * Reject the appeal
     */
    public function reject(User $reviewer, string $reason, ?string $notes = null): self
    {
        $this->update([
            'status' => self::STATUS_REJECTED,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'rejection_reason' => $reason,
            'reviewer_notes' => $notes,
        ]);

        return $this;
    }

    /**
     * Get appeal type label in Thai
     */
    public function getTypeLabelAttribute(): string
    {
        return match($this->appeal_type) {
            self::TYPE_SCORE_ERROR => 'คะแนนผิดพลาด',
            self::TYPE_ATTENDANCE_ERROR => 'การเข้าเรียนผิดพลาด',
            self::TYPE_GRADING_CRITERIA => 'เกณฑ์การให้เกรด',
            self::TYPE_SPECIAL_CIRCUMSTANCE => 'กรณีพิเศษ',
            self::TYPE_OTHER => 'อื่นๆ',
            default => $this->appeal_type,
        };
    }

    /**
     * Get status label in Thai
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            self::STATUS_PENDING => 'รอพิจารณา',
            self::STATUS_REVIEWING => 'กำลังพิจารณา',
            self::STATUS_APPROVED => 'อนุมัติ',
            self::STATUS_REJECTED => 'ปฏิเสธ',
            self::STATUS_WITHDRAWN => 'ถอน',
            default => $this->status,
        };
    }
}
