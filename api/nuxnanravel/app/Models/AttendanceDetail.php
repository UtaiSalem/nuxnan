<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceDetail extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function attendanceable()
    {
        return $this->morphTo();
    }

    public function attendance(): BelongsTo
    {
        return $this->belongsTo(CourseAttendance::class, 'course_attendance_id');
    }

    public function courseAttendance(): BelongsTo
    {
        return $this->belongsTo(CourseAttendance::class, 'course_attendance_id');
    }

    public function courseMember(): BelongsTo
    {
        return $this->belongsTo(CourseMember::class, 'course_member_id');
    }
}
