<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ClassroomStudent Model - นักเรียนในห้องเรียน (Pivot Table)
 *
 * เป็น Source of Truth สำหรับการสังกัดห้องเรียนของนักเรียน
 * รองรับ Historical Data — เก็บประวัติห้องเรียนข้ามปีการศึกษาได้
 */
class ClassroomStudent extends Model
{
    use Auditable, HasFactory;

    protected $fillable = [
        'academy_id',
        'classroom_id',
        'student_id',
        'academic_year_id',
        'student_number',
        'status',
        'enrolled_at',
        'left_at',
        'leave_reason',
        'rollover_batch_id',
        'created_by_user_id',
    ];

    protected $casts = [
        'student_number' => 'integer',
        'enrolled_at' => 'date',
        'left_at' => 'date',
    ];

    // Status Constants
    const STATUS_ACTIVE = 'active';

    const STATUS_TRANSFERRED = 'transferred';      // ย้ายห้องในปีเดียวกัน

    const STATUS_PROMOTED = 'promoted';            // เลื่อนชั้นข้ามปี (Phase 2.4)

    const STATUS_GRADUATED = 'graduated';          // จบการศึกษา

    const STATUS_DROPPED = 'dropped';              // ลาออก/พ้นสภาพ

    const STATUS_REPEATING = 'repeating';          // ซ้ำชั้น

    const STATUS_SUPERSEDED = 'superseded';        // ปิดโดย repair (active row ซ้อน)

    public static array $statuses = [
        self::STATUS_ACTIVE,
        self::STATUS_TRANSFERRED,
        self::STATUS_PROMOTED,
        self::STATUS_GRADUATED,
        self::STATUS_DROPPED,
        self::STATUS_REPEATING,
        self::STATUS_SUPERSEDED,
    ];

    // Relationships
    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeByClassroom($query, $classroomId)
    {
        return $query->where('classroom_id', $classroomId);
    }

    public function scopeByAcademicYear($query, $academicYearId)
    {
        return $query->where('academic_year_id', $academicYearId);
    }

    public function scopeByAcademy($query, $academyId)
    {
        return $query->where('academy_id', $academyId);
    }

    public function scopeCurrent($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    // Accessors
    public function getStatusTextAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_ACTIVE => 'กำลังศึกษา',
            self::STATUS_TRANSFERRED => 'ย้ายห้อง',
            self::STATUS_PROMOTED => 'เลื่อนชั้น',
            self::STATUS_GRADUATED => 'จบการศึกษา',
            self::STATUS_DROPPED => 'พ้นสภาพ',
            self::STATUS_REPEATING => 'ซ้ำชั้น',
            self::STATUS_SUPERSEDED => 'ปิดโดยระบบ',
            default => 'ไม่ระบุ'
        };
    }

    // Created by (audit)
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * Get the audit module name for this model.
     */
    public function getAuditModule(): ?string
    {
        return 'enrollment';
    }
}
