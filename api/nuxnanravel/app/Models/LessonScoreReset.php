<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LessonScoreReset extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'course_member_id',
        'lesson_id',
        'admin_id',
        'reset_type',
        'old_score_snapshot',
        'reason',
    ];

    protected $casts = [
        'old_score_snapshot' => 'array',
    ];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function courseMember()
    {
        return $this->belongsTo(CourseMember::class);
    }

    public function lesson()
    {
        return $this->belongsTo(Lesson::class);
    }

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
