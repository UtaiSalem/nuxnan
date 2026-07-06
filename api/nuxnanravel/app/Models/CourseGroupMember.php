<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseGroupMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'group_id',
        'user_id',
        'status',
        'role',
        'request_status',
        'last_accessed_tab',
    ];

    public function course_group(): BelongsTo
    {
        return $this->belongsTo(CourseGroup::class, 'group_id');
    }
}
