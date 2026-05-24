<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CourseRemediationSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'quiz_id',
        'created_by',
        'title',
        'description',
        'start_at',
        'end_at',
        'registration_deadline',
        'type',
        'eligible_grades',
        'min_score_to_remediate',
        'max_grade_achievable',
        'max_participants',
        'passing_score',
        'status',
        'location',
        'is_online',
        'online_link',
        'instructions',
        'attachments',
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'registration_deadline' => 'datetime',
        'eligible_grades' => 'array',
        'min_score_to_remediate' => 'decimal:2',
        'passing_score' => 'decimal:2',
        'is_online' => 'boolean',
        'attachments' => 'array',
    ];

    // Remediation types
    const TYPE_EXAM_RETAKE = 'exam_retake';
    const TYPE_ASSIGNMENT = 'assignment';
    const TYPE_PROJECT = 'project';
    const TYPE_ATTENDANCE_MAKEUP = 'attendance_makeup';
    const TYPE_MIXED = 'mixed';

    // Status
    const STATUS_DRAFT = 'draft';
    const STATUS_OPEN = 'open';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_GRADING = 'grading';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';

    /**
     * วิชา
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Quiz ที่ผูกกับรอบแก้ตัวนี้ (สำหรับ type = exam_retake)
     */
    public function quiz(): BelongsTo
    {
        return $this->belongsTo(CourseQuiz::class);
    }

    /**
     * ผู้สร้าง
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * รายการลงทะเบียนแก้ตัว
     */
    public function enrollments(): HasMany
    {
        return $this->hasMany(CourseRemediationEnrollment::class, 'remediation_session_id');
    }

    /**
     * Check if student's grade is eligible for this session
     */
    public function isGradeEligible(string $grade): bool
    {
        if (empty($this->eligible_grades)) {
            return true; // If not specified, all grades are eligible
        }

        return in_array($grade, $this->eligible_grades);
    }

    /**
     * Check if registration is still open
     */
    public function isRegistrationOpen(): bool
    {
        if ($this->status !== self::STATUS_OPEN) {
            return false;
        }

        if ($this->registration_deadline && $this->registration_deadline->isPast()) {
            return false;
        }

        // Check max participants
        if ($this->max_participants) {
            $currentCount = $this->enrollments()
                ->whereNotIn('status', ['cancelled'])
                ->count();
            
            if ($currentCount >= $this->max_participants) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get remaining slots
     */
    public function getRemainingSlots(): ?int
    {
        if (!$this->max_participants) {
            return null;
        }

        $currentCount = $this->enrollments()
            ->whereNotIn('status', ['cancelled'])
            ->count();

        return max(0, $this->max_participants - $currentCount);
    }

    /**
     * Scope: Active sessions
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', [self::STATUS_OPEN, self::STATUS_IN_PROGRESS, self::STATUS_GRADING]);
    }

    /**
     * Scope: Open for registration
     */
    public function scopeOpenForRegistration($query)
    {
        return $query->where('status', self::STATUS_OPEN)
            ->where(function ($q) {
                $q->whereNull('registration_deadline')
                  ->orWhere('registration_deadline', '>', now());
            });
    }

    /**
     * Get type label in Thai
     */
    public function getTypeLabelAttribute(): string
    {
        return match($this->type) {
            self::TYPE_EXAM_RETAKE => 'สอบใหม่',
            self::TYPE_ASSIGNMENT => 'ส่งงานเพิ่ม',
            self::TYPE_PROJECT => 'ทำโปรเจค',
            self::TYPE_ATTENDANCE_MAKEUP => 'เข้าเรียนชดเชย',
            self::TYPE_MIXED => 'ผสมผสาน',
            default => $this->type,
        };
    }

    /**
     * Get status label in Thai
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            self::STATUS_DRAFT => 'ฉบับร่าง',
            self::STATUS_OPEN => 'เปิดรับสมัคร',
            self::STATUS_IN_PROGRESS => 'กำลังดำเนินการ',
            self::STATUS_GRADING => 'กำลังให้คะแนน',
            self::STATUS_COMPLETED => 'เสร็จสิ้น',
            self::STATUS_CANCELLED => 'ยกเลิก',
            default => $this->status,
        };
    }
}
