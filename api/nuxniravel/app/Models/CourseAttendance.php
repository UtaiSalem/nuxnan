<?php

namespace App\Models;

use App\Models\User;
use App\Models\Course;
use App\Models\CourseGroup;
use App\Models\AttendanceDetail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CourseAttendance extends Model
{
    use HasFactory;

    protected $guarded = [];

    //belongsto user
    public function instructor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(CourseGroup::class);
    }

    public function attendanceDetails(): HasMany
    {
        return $this->hasMany(AttendanceDetail::class);
    }

    public function details(): MorphMany
    {
        return $this->morphMany(AttendanceDetail::class, 'attendanceable');
    }
}
