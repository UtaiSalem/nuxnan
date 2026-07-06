<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Assignment extends Model
{
    use HasFactory;

    protected $guarded = [];

    // append is_published to the model
    protected $appends = ['is_published'];

    protected $casts = [
        'due_date' => 'datetime',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'is_group_assignment' => 'boolean',
        'target_groups' => 'array',
        'status' => 'integer',
        'increase_points' => 'integer',
        'decrease_points' => 'integer',
        'points' => 'integer',
        'passing_score' => 'integer',
        'graded_score' => 'integer',
    ];

    public function assignmentable()
    {
        return $this->morphTo();
    }

    public function images(): HasMany
    {
        return $this->hasMany(AssignmentImage::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(AssignmentAnswer::class);
    }

    public function getIsPublishedAttribute(): bool
    {
        return $this->status === 1;
    }

    /**
     * Resolve the lesson this assignment belongs to (directly or through topic)
     */
    public function getLesson(): ?Lesson
    {
        if ($this->assignmentable_type === Lesson::class) {
            return $this->assignmentable;
        }

        if ($this->assignmentable_type === Topic::class) {
            return $this->assignmentable->lesson;
        }

        return null;
    }
}
