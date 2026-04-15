<?php

namespace App\Models;

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
    use HasFactory;

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
    ];

    protected $casts = [
        'student_number' => 'integer',
        'enrolled_at' => 'date',
        'left_at' => 'date',
    ];

    // Status Constants
    const STATUS_ACTIVE = 'active';
    const STATUS_TRANSFERRED = 'transferred';
    const STATUS_GRADUATED = 'graduated';
    const STATUS_DROPPED = 'dropped';

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
        return match($this->status) {
            self::STATUS_ACTIVE => 'กำลังศึกษา',
            self::STATUS_TRANSFERRED => 'ย้ายออก',
            self::STATUS_GRADUATED => 'จบการศึกษา',
            self::STATUS_DROPPED => 'พ้นสภาพ',
            default => 'ไม่ระบุ'
        };
    }
}
