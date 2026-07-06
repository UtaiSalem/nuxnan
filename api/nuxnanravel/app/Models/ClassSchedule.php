<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ClassSchedule Model - ตารางเรียน
 */
class ClassSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'academy_id',
        'academic_year_id',
        'semester_id',
        'classroom_id',
        'subject_id',
        'teacher_id',
        'day_of_week',
        'start_time',
        'end_time',
        'period_number',
        'room',
        'status',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'day_of_week' => 'integer',
        'period_number' => 'integer',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
    ];

    // Day of week constants
    const MONDAY = 1;

    const TUESDAY = 2;

    const WEDNESDAY = 3;

    const THURSDAY = 4;

    const FRIDAY = 5;

    const SATURDAY = 6;

    const SUNDAY = 7;

    const DAYS = [
        1 => 'วันจันทร์',
        2 => 'วันอังคาร',
        3 => 'วันพุธ',
        4 => 'วันพฤหัสบดี',
        5 => 'วันศุกร์',
        6 => 'วันเสาร์',
        7 => 'วันอาทิตย์',
    ];

    const DAYS_SHORT = [
        1 => 'จ.',
        2 => 'อ.',
        3 => 'พ.',
        4 => 'พฤ.',
        5 => 'ศ.',
        6 => 'ส.',
        7 => 'อา.',
    ];

    const STATUS_ACTIVE = 'active';

    const STATUS_CANCELLED = 'cancelled';

    const STATUS_TEMPORARY = 'temporary';

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

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Accessors
    public function getDayNameAttribute(): string
    {
        return self::DAYS[$this->day_of_week] ?? '';
    }

    public function getDayNameShortAttribute(): string
    {
        return self::DAYS_SHORT[$this->day_of_week] ?? '';
    }

    public function getTimeRangeAttribute(): string
    {
        return $this->start_time->format('H:i').' - '.$this->end_time->format('H:i');
    }

    public function getDurationMinutesAttribute(): int
    {
        return $this->start_time->diffInMinutes($this->end_time);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeByAcademy($query, $academyId)
    {
        return $query->where('academy_id', $academyId);
    }

    public function scopeBySemester($query, $semesterId)
    {
        return $query->where('semester_id', $semesterId);
    }

    public function scopeByClassroom($query, $classroomId)
    {
        return $query->where('classroom_id', $classroomId);
    }

    public function scopeByTeacher($query, $teacherId)
    {
        return $query->where('teacher_id', $teacherId);
    }

    public function scopeByDay($query, $dayOfWeek)
    {
        return $query->where('day_of_week', $dayOfWeek);
    }

    public function scopeToday($query)
    {
        return $query->where('day_of_week', now()->dayOfWeek ?: 7);
    }

    // Check for conflicts
    public static function hasTeacherConflict(
        int $teacherId,
        int $semesterId,
        int $dayOfWeek,
        string $startTime,
        string $endTime,
        ?int $excludeId = null
    ): bool {
        $query = static::where('teacher_id', $teacherId)
            ->where('semester_id', $semesterId)
            ->where('day_of_week', $dayOfWeek)
            ->where('status', self::STATUS_ACTIVE)
            ->where(function ($q) use ($startTime, $endTime) {
                $q->whereBetween('start_time', [$startTime, $endTime])
                    ->orWhereBetween('end_time', [$startTime, $endTime])
                    ->orWhere(function ($q2) use ($startTime, $endTime) {
                        $q2->where('start_time', '<=', $startTime)
                            ->where('end_time', '>=', $endTime);
                    });
            });

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    public static function hasClassroomConflict(
        int $classroomId,
        int $semesterId,
        int $dayOfWeek,
        string $startTime,
        string $endTime,
        ?int $excludeId = null
    ): bool {
        $query = static::where('classroom_id', $classroomId)
            ->where('semester_id', $semesterId)
            ->where('day_of_week', $dayOfWeek)
            ->where('status', self::STATUS_ACTIVE)
            ->where(function ($q) use ($startTime, $endTime) {
                $q->whereBetween('start_time', [$startTime, $endTime])
                    ->orWhereBetween('end_time', [$startTime, $endTime])
                    ->orWhere(function ($q2) use ($startTime, $endTime) {
                        $q2->where('start_time', '<=', $startTime)
                            ->where('end_time', '>=', $endTime);
                    });
            });

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }
}
