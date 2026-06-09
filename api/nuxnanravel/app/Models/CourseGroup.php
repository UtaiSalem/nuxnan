<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CourseGroup extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'course_id',
        'name',
        'description',
        'image_url',
        'status',
        'auto_accept_member',
        'privacy',
        'color',
        'max_members',
        'cover',
        'sort_order',
    ];

    protected $casts = [
        'auto_accept_member' => 'boolean',
        'max_members' => 'integer',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function members(): HasMany
    {
        return $this->hasMany(CourseMember::class, 'group_id');
    }

    public function course_group_members(): HasMany
    {
        return $this->hasMany(CourseGroupMember::class, 'group_id');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(CourseAttendance::class, 'group_id');
    }
}
