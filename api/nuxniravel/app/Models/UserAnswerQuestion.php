<?php

namespace App\Models;

use App\Models\User;
use App\Models\Course;
use App\Models\Question;
use App\Models\CourseQuiz;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class UserAnswerQuestion extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(CourseQuiz::class);
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }


}
