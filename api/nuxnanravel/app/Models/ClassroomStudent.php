<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ClassroomStudent Model - นักเรียนในห้องเรียน
 */
class ClassroomStudent extends Model
{
    use HasFactory;

    protected $fillable = [
        'classroom_id',
        'student_id',
        'student_number',
        'status',
    ];

    protected $casts = [
        'student_number' => 'integer',
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

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeByClassroom($query, $classroomId)
    {
        return $query->where('classroom_id', $classroomId);
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
