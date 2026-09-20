<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LessonAnswerQuestion extends Model
{
    //
    protected $guarded = [];

    protected $casts = [
        'points' => 'float',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function lesson()
    {
        return $this->belongsTo(Lesson::class);
    }

    public function question()
    {
        return $this->belongsTo(Question::class);
    }
}
