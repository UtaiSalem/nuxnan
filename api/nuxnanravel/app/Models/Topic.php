<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Topic extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected static function booted()
    {
        static::creating(function ($topic) {
            if (! $topic->sort_order) {
                $topic->sort_order = static::where('lesson_id', $topic->lesson_id)->max('sort_order') + 1;
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(TopicImage::class);
    }

    public function assignments(): MorphMany
    {
        return $this->morphMany(Assignment::class, 'assignmentable');
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(LessonAttachment::class, 'attachable')->orderBy('order')->orderBy('id');
    }

    public function questions(): MorphMany
    {
        return $this->morphMany(Question::class, 'questionable');
    }

    public function readProgress(): HasMany
    {
        return $this->hasMany(TopicReadProgress::class);
    }

    public function userReadProgress(User $user): ?TopicReadProgress
    {
        return $this->readProgress()->where('user_id', $user->id)->first();
    }

    public function getRequiredReadSeconds(): int
    {
        return $this->min_read > 0 ? (int) $this->min_read * 60 : 30;
    }
}
