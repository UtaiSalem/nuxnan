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

    public static bool $skipQuizCounterSync = false;

    /**
     * Counter drift caused 0% / FAILED on perfect papers.
     * This observer ensures the counter stays in sync by recomputing the authoritative value
     * whenever questions are created, updated or deleted.
     */
    protected static function booted(): void
    {
        $sync = function (Question $question) {
            if (self::$skipQuizCounterSync) {
                return;
            }

            $quizIds = array_unique(array_filter([
                $question->getOriginal('questionable_id'),
                $question->questionable_id,
            ]));

            foreach ($quizIds as $quizId) {
                $type = $quizId === $question->getOriginal('questionable_id')
                    ? $question->getOriginal('questionable_type')
                    : $question->questionable_type;

                if ($type === CourseQuiz::class || $type === 'App\Models\CourseQuiz') {
                    CourseQuiz::find($quizId)?->syncQuestionCounters();
                }
            }
        };

        static::created($sync);
        static::updated($sync);
        static::deleted($sync);
    }

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
