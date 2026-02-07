<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GradeEditLog extends Model
{
    use HasFactory;

    const UPDATED_AT = null; // Only created_at, no updated_at

    protected $fillable = [
        'course_member_id',
        'course_id',
        'student_id',
        'edited_by',
        'change_type',
        'old_score',
        'old_grade',
        'old_grade_point',
        'new_score',
        'new_grade',
        'new_grade_point',
        'reason',
        'metadata',
        'appeal_id',
        'remediation_enrollment_id',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'old_score' => 'decimal:2',
        'new_score' => 'decimal:2',
        'old_grade_point' => 'decimal:2',
        'new_grade_point' => 'decimal:2',
        'metadata' => 'array',
    ];

    // Change types
    const TYPE_SCORE_UPDATE = 'score_update';
    const TYPE_GRADE_OVERRIDE = 'grade_override';
    const TYPE_ATTENDANCE_CORRECTION = 'attendance_correction';
    const TYPE_APPEAL_RESULT = 'appeal_result';
    const TYPE_REMEDIATION_RESULT = 'remediation_result';
    const TYPE_ADMIN_ADJUSTMENT = 'admin_adjustment';
    const TYPE_BULK_UPDATE = 'bulk_update';
    const TYPE_SYSTEM_RECALCULATION = 'system_recalculation';

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
     * ผู้แก้ไข
     */
    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'edited_by');
    }

    /**
     * Related appeal (if any)
     */
    public function appeal(): BelongsTo
    {
        return $this->belongsTo(GradeAppeal::class, 'appeal_id');
    }

    /**
     * Related remediation enrollment (if any)
     */
    public function remediationEnrollment(): BelongsTo
    {
        return $this->belongsTo(CourseRemediationEnrollment::class, 'remediation_enrollment_id');
    }

    /**
     * Create a log entry
     */
    public static function log(
        CourseMember $courseMember,
        User $editor,
        string $changeType,
        ?float $oldScore,
        ?string $oldGrade,
        ?float $oldGradePoint,
        ?float $newScore,
        ?string $newGrade,
        ?float $newGradePoint,
        ?string $reason = null,
        ?array $metadata = null,
        ?int $appealId = null,
        ?int $remediationEnrollmentId = null
    ): self {
        return self::create([
            'course_member_id' => $courseMember->id,
            'course_id' => $courseMember->course_id,
            'student_id' => $courseMember->user_id,
            'edited_by' => $editor->id,
            'change_type' => $changeType,
            'old_score' => $oldScore,
            'old_grade' => $oldGrade,
            'old_grade_point' => $oldGradePoint,
            'new_score' => $newScore,
            'new_grade' => $newGrade,
            'new_grade_point' => $newGradePoint,
            'reason' => $reason,
            'metadata' => $metadata,
            'appeal_id' => $appealId,
            'remediation_enrollment_id' => $remediationEnrollmentId,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Scope: By change type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('change_type', $type);
    }

    /**
     * Scope: Recent changes
     */
    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Check if grade was changed
     */
    public function hasGradeChange(): bool
    {
        return $this->old_grade !== $this->new_grade;
    }

    /**
     * Check if score was changed
     */
    public function hasScoreChange(): bool
    {
        return $this->old_score !== $this->new_score;
    }

    /**
     * Get score difference
     */
    public function getScoreDifference(): ?float
    {
        if ($this->old_score === null || $this->new_score === null) {
            return null;
        }

        return $this->new_score - $this->old_score;
    }

    /**
     * Get change type label in Thai
     */
    public function getTypeLabelAttribute(): string
    {
        return match($this->change_type) {
            self::TYPE_SCORE_UPDATE => 'อัพเดทคะแนน',
            self::TYPE_GRADE_OVERRIDE => 'แก้ไขเกรด',
            self::TYPE_ATTENDANCE_CORRECTION => 'แก้ไขการเข้าเรียน',
            self::TYPE_APPEAL_RESULT => 'ผลอุทธรณ์',
            self::TYPE_REMEDIATION_RESULT => 'ผลการแก้ตัว',
            self::TYPE_ADMIN_ADJUSTMENT => 'Admin ปรับ',
            self::TYPE_BULK_UPDATE => 'อัพเดทเป็นกลุ่ม',
            self::TYPE_SYSTEM_RECALCULATION => 'ระบบคำนวณใหม่',
            default => $this->change_type,
        };
    }

    /**
     * Get formatted change summary
     */
    public function getChangeSummaryAttribute(): string
    {
        $parts = [];

        if ($this->hasScoreChange()) {
            $parts[] = sprintf('คะแนน: %s → %s', 
                $this->old_score ?? '-', 
                $this->new_score ?? '-'
            );
        }

        if ($this->hasGradeChange()) {
            $parts[] = sprintf('เกรด: %s → %s', 
                $this->old_grade ?? '-', 
                $this->new_grade ?? '-'
            );
        }

        return implode(', ', $parts) ?: 'ไม่มีการเปลี่ยนแปลง';
    }
}
