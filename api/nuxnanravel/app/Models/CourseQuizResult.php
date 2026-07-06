<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseQuizResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'course_id',
        'quiz_id',
        'score',
        'percentage',
        'started_at',
        'completed_at',
        'duration',
        'attempted_questions',
        'correct_answers',
        'incorrect_answers',
        'skipped_questions',
        'passed',
        'status',
        'efficiency',
        'retake_unlocked_at',
        'retake_used_at',
        'retake_granted_by_enrollment_id',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'retake_unlocked_at' => 'datetime',
        'retake_used_at' => 'datetime',
    ];

    /**
     * Check if the student has an active retake grant (unlocked but not used).
     */
    public function hasActiveRetakeGrant(): bool
    {
        return $this->retake_unlocked_at !== null && $this->retake_used_at === null;
    }

    // Define relationships
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

    // Add any other custom methods or relationships
}
