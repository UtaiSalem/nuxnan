<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class CourseQuiz extends Model
{
    use HasFactory;

    // Table name
    protected $table = 'course_quizzes';

    protected $guarded = [];
    // Mass assignable fields
    // protected $fillable = [
    //     'title',
    //     'description',
    //     'start_date',
    //     'end_date',
    //     'is_active',
    //     'shuffle_questions',
    //     'passing_score'
    // ];

    // Timestamps
    public $timestamps = true;

    // Relationships
    /**
     * Get the user that owns the Quiz
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // A quiz belongs to a course
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Retrieve the questions associated with this quiz.
     *
     * @return HasMany
     */
    // public function questions(): HasMany
    // {
    //     return $this->hasMany(Question::class);
    // }
    public function questions(): MorphMany
    {
        return $this->morphMany(Question::class, 'questionable');
    }

    /**
     * Get all of the userResults for the CourseQuiz
     */
    public function userResults(): HasMany
    {
        return $this->hasMany(CourseQuizResult::class, 'quiz_id');
    }

    /**
     * The real maximum score of this quiz.
     *
     * `total_score` is a denormalized counter that historically drifted to 0 whenever
     * questions were created outside CourseQuizQuestionController (seeders, clones,
     * bulk import). Dividing by it produced 0% and a FAILED status on perfect papers,
     * so every score calculation must go through here instead.
     */
    public function effectiveTotalScore(): float
    {
        $stored = (float) ($this->total_score ?? 0);
        if ($stored > 0) {
            return $stored;
        }

        return (float) $this->questions()->sum('points');
    }

    /**
     * Recompute the denormalized counters from the questions that actually exist.
     * Returns the new total score.
     */
    public function syncQuestionCounters(): float
    {
        $totalScore = (float) $this->questions()->sum('points');
        $totalQuestions = (int) $this->questions()->count();

        if ((float) $this->total_score !== $totalScore || (int) $this->total_questions !== $totalQuestions) {
            $this->forceFill([
                'total_score' => $totalScore,
                'total_questions' => $totalQuestions,
            ])->save();
        }

        return $totalScore;
    }
}
