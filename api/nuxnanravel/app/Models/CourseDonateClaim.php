<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourseDonateClaim extends Model
{
    protected $guarded = [];

    protected $casts = ['claimed_at' => 'datetime'];

    public function donation()
    {
        return $this->belongsTo(CourseDonate::class, 'course_donate_id');
    }

    public function claimer()
    {
        return $this->belongsTo(User::class, 'claimer_id');
    }
}
