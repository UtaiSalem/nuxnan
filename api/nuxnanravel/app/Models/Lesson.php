<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Lesson extends Model
{
    use HasFactory;

    // Publication status
    const STATUS_DRAFT = 'draft';

    const STATUS_PUBLISHED = 'published';

    const STATUS_ARCHIVED = 'archived';

    // Access type
    const ACCESS_FREE = 'free';

    const ACCESS_POINTS = 'points';

    const ACCESS_MONEY = 'money';

    protected $guarded = [];

    protected static function booted()
    {
        static::creating(function ($lesson) {
            if (! $lesson->order) {
                $lesson->order = static::where('course_id', $lesson->course_id)->max('order') + 1;
            }
        });
    }

    protected $casts = [
        'require_completion_before_exercises' => 'boolean',
    ];

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
        return $this->hasMany(Topic::class)->orderBy('sort_order')->orderBy('id');
    }

    public function images(): HasMany
    {
        return $this->hasMany(LessonImage::class);
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

    public function likes(): HasMany
    {
        return $this->hasMany(LessonLike::class);
    }

    public function dislikes(): HasMany
    {
        return $this->hasMany(LessonDislike::class);
    }

    public function bookmarks(): HasMany
    {
        return $this->hasMany(LessonBookmark::class);
    }

    public function isBookmarkedBy(User $user): bool
    {
        return $this->bookmarks()->where('user_id', $user->id)->exists();
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
        return $this->comments()->whereNull('parent_id')->latest()->limit(3)->get();
    }

    public function progress(): HasMany
    {
        return $this->hasMany(LessonProgress::class);
    }

    /**
     * Get progress for a specific user
     */
    public function userProgress(User $user): ?LessonProgress
    {
        return $this->progress()->where('user_id', $user->id)->first();
    }

    /**
     * Get or create progress for a user
     */
    public function getOrCreateProgress(User $user): LessonProgress
    {
        return $this->progress()->firstOrCreate(
            ['user_id' => $user->id],
            ['status' => LessonProgress::STATUS_NOT_STARTED]
        );
    }

    /**
     * Check if lesson is completed by user
     */
    public function isCompletedBy(User $user): bool
    {
        $progress = $this->userProgress($user);

        return $progress && $progress->isCompleted();
    }

    /**
     * Check if user is allowed to do exercises for this lesson
     */
    public function canUserDoExercises(?User $user, bool $isCourseAdmin = false): bool
    {
        if ($isCourseAdmin) {
            return true;
        }
        if (! $this->require_completion_before_exercises) {
            return true;
        }
        if (! $user) {
            return false;
        }

        return $this->isCompletedBy($user);
    }

    public function accesses(): HasMany
    {
        return $this->hasMany(LessonAccess::class);
    }

    /**
     * ตรวจว่า user คนนี้มีสิทธิ์อ่านบทเรียนนี้หรือไม่
     */
    public function isAccessibleByUser(User $user, bool $isCourseAdmin = false): bool
    {
        // admin เห็นทุกอย่างเสมอ
        if ($isCourseAdmin) {
            return true;
        }

        // draft/archived — ไม่อนุญาต
        if ($this->publication_status !== self::STATUS_PUBLISHED) {
            return false;
        }

        // free lesson — อนุญาตทันที
        if ($this->access_type === self::ACCESS_FREE) {
            return true;
        }

        // points/money — ตรวจ access record
        return $this->accesses()
            ->where('user_id', $user->id)
            ->where('status', LessonAccess::STATUS_ACTIVE)
            ->exists();
    }

    /**
     * ตรวจว่า user ปลดล็อกแล้วหรือยัง
     */
    public function hasActiveAccessFor(User $user): bool
    {
        return $this->accesses()
            ->where('user_id', $user->id)
            ->where('status', LessonAccess::STATUS_ACTIVE)
            ->exists();
    }

    /**
     * Check if all published topics in this lesson are completed by user
     */
    public function areAllTopicsCompletedBy(User $user): bool
    {
        $publishedTopicIds = $this->topics()
            ->where('status', 'published')
            ->pluck('id');

        if ($publishedTopicIds->isEmpty()) {
            return false;
        }

        $completedCount = TopicReadProgress::where('user_id', $user->id)
            ->where('lesson_id', $this->id)
            ->where('status', TopicReadProgress::STATUS_COMPLETED)
            ->whereIn('topic_id', $publishedTopicIds)
            ->count();

        return $completedCount >= $publishedTopicIds->count();
    }
}
