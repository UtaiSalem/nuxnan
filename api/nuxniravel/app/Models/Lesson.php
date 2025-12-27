<?php

namespace App\Models;

use App\Models\User;
use App\Models\Topic;
use App\Models\Course;
use App\Models\Question;
use App\Models\Assignment;
use App\Models\LessonImage;
use App\Models\LessonComment;
use App\Models\LessonDislike;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Lesson extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $appends = [
        'lesson_url',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function topics(): HasMany
    {
        return $this->hasMany(Topic::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(LessonImage::class);
    }

    public function assignments(): MorphMany
    {
        return $this->morphMany(Assignment::class, 'assignmentable');
    }

    public function questions(): MorphMany
    {
        return $this->morphMany(Question::class, 'questionable');
    }

    public function likes(): HasMany
    { 
        return $this->hasMany(LessonLike::class); 
    }

    public function dislikes(): HasMany
    { 
        return $this->hasMany(LessonDislike::class); 
    }

    public function likeLesson(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'lesson_likes', 'lesson_id', 'user_id')->withTimestamps();
    }

    public function dislikeLesson(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'lesson_dislikes', 'lesson_id', 'user_id')->withTimestamps();
    }

    // get lesson url
    public function getLessonUrlAttribute()
    {
        return route('course.lessons.show', [$this->course_id, $this->id]);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(LessonComment::class);
    }
    
    public function getComments()
    {
        return $this->comments()->latest()->limit(3)->get();
    }
}
