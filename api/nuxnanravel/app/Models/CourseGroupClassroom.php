<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseGroupClassroom extends Model
{
    use HasFactory;

    protected $table = 'course_group_classrooms';

    protected $fillable = [
        'course_group_id',
        'classroom_id',
        'academic_year_id',
        'created_by_user_id',
    ];

    public function courseGroup(): BelongsTo
    {
        return $this->belongsTo(CourseGroup::class, 'course_group_id');
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class, 'classroom_id');
    }
}
