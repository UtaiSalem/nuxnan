<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseFinalizationLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'performed_by',
        'action',
        'snapshot',
        'notes',
        'total_students',
        'passed_count',
        'failed_count',
        'average_score',
    ];

    protected $casts = [
        'snapshot' => 'array',
        'average_score' => 'decimal:2',
    ];

    // Actions
    const ACTION_START_GRADING = 'start_grading';
    const ACTION_PUBLISH_DRAFT = 'publish_draft_grades';
    const ACTION_FINALIZE = 'finalize_grades';
    const ACTION_OPEN_REMEDIATION = 'open_remediation';
    const ACTION_CLOSE_REMEDIATION = 'close_remediation';
    const ACTION_REOPEN = 'reopen_grading';
    const ACTION_ARCHIVE = 'archive_course';

    /**
     * วิชาที่ดำเนินการ
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * ผู้ดำเนินการ
     */
    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    /**
     * Scope: Filter by action type
     */
    public function scopeOfAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Get action label in Thai
     */
    public function getActionLabelAttribute(): string
    {
        return match($this->action) {
            self::ACTION_START_GRADING => 'เริ่มช่วงให้เกรด',
            self::ACTION_PUBLISH_DRAFT => 'ประกาศเกรดเบื้องต้น',
            self::ACTION_FINALIZE => 'ยืนยันเกรดสุดท้าย',
            self::ACTION_OPEN_REMEDIATION => 'เปิดช่วงแก้ตัว',
            self::ACTION_CLOSE_REMEDIATION => 'ปิดช่วงแก้ตัว',
            self::ACTION_REOPEN => 'เปิดให้แก้เกรดใหม่',
            self::ACTION_ARCHIVE => 'เก็บวิชาเข้า Archive',
            default => $this->action,
        };
    }
}
