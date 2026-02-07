<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * TeacherAssignment Model - การมอบหมายครูผู้สอน
 */
class TeacherAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'academy_id',
        'academic_year_id',
        'semester_id',
        'teacher_id',
        'subject_id',
        'classroom_id',
        'role',
        'is_homeroom',
        'weekly_hours',
        'total_periods',
        'notes',
    ];

    protected $casts = [
        'is_homeroom' => 'boolean',
        'weekly_hours' => 'integer',
        'total_periods' => 'integer',
    ];

    // Role constants
    const ROLE_PRIMARY = 'primary';
    const ROLE_SECONDARY = 'secondary';
    const ROLE_SUBSTITUTE = 'substitute';

    // Relationships
    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    // Scopes
    public function scopeByAcademy($query, $academyId)
    {
        return $query->where('academy_id', $academyId);
    }

    public function scopeBySemester($query, $semesterId)
    {
        return $query->where('semester_id', $semesterId);
    }

    public function scopeByTeacher($query, $teacherId)
    {
        return $query->where('teacher_id', $teacherId);
    }

    public function scopeByClassroom($query, $classroomId)
    {
        return $query->where('classroom_id', $classroomId);
    }

    public function scopePrimary($query)
    {
        return $query->where('role', self::ROLE_PRIMARY);
    }

    public function scopeHomeroom($query)
    {
        return $query->where('is_homeroom', true);
    }
}
