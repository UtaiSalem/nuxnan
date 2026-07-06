<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamEligibilityOverride extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_member_id',
        'course_id',
        'student_id',
        'unlock_method',
        'points_spent',
        'points_transaction_id',
        'reading_minutes_completed',
        'reading_proof',
        'approved_by',
        'admin_reason',
        'appeal_reason',
        'appeal_evidence',
        'reading_completed_lesson_ids',
        'reading_required_lesson_ids',
        'remediation_session_id',
        'status',
        'approved_at',
        'expires_at',
        'absence_percent_at_unlock',
        'total_sessions_at_unlock',
        'absent_sessions_at_unlock',
        'notes',
    ];

    protected $casts = [
        'reading_proof' => 'array',
        'appeal_evidence' => 'array',
        'reading_completed_lesson_ids' => 'array',
        'reading_required_lesson_ids' => 'array',
        'approved_at' => 'datetime',
        'expires_at' => 'datetime',
        'absence_percent_at_unlock' => 'decimal:2',
    ];

    // Unlock methods
    const METHOD_POINTS = 'points';

    const METHOD_READING = 'reading';

    const METHOD_ADMIN = 'admin';

    const METHOD_APPEAL = 'appeal';

    const METHOD_SELF = 'self';

    // Status
    const STATUS_PENDING = 'pending';

    const STATUS_APPROVED = 'approved';

    const STATUS_REJECTED = 'rejected';

    const STATUS_EXPIRED = 'expired';

    /**
     * Course member record
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
     * ผู้อนุมัติ
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Check if override is still valid
     */
    public function isValid(): bool
    {
        if ($this->status !== self::STATUS_APPROVED) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Scope: Only approved
     */
    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    /**
     * Scope: Only pending
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope: Valid (approved and not expired)
     */
    public function scopeValid($query)
    {
        return $query->where('status', self::STATUS_APPROVED)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    /**
     * Get method label in Thai
     */
    public function getMethodLabelAttribute(): string
    {
        return match ($this->unlock_method) {
            self::METHOD_POINTS => 'ใช้ Points',
            self::METHOD_READING => 'อ่านเพิ่มเติม',
            self::METHOD_ADMIN => 'Admin อนุมัติ',
            self::METHOD_APPEAL => 'อุทธรณ์',
            self::METHOD_SELF => 'ปลดล็อคด้วยตนเอง',
            default => $this->unlock_method,
        };
    }
}
