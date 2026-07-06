<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Question extends Model
{
    use HasFactory;

    protected $guarded = [];

    /**
     * Get the user that owns the Question
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the course that owns the Question
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function questionable()
    {
        return $this->morphTo();
    }

    public function images(): MorphMany
    {
        return $this->morphMany(QuestionImage::class, 'imageable');
    }

    public function options(): MorphMany
    {
        return $this->morphMany(QuestionOption::class, 'optionable');
    }

    /**
     * Get all of the userAnsers for the Question
     */
    public function userAnswers(): HasMany
    {
        return $this->hasMany(UserAnswerQuestion::class, 'question_id');
    }
}
