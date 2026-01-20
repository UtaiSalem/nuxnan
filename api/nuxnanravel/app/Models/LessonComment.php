<?php

namespace App\Models;

use App\Models\User;
use App\Models\Lesson;

use App\Models\LessonCommentImage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class LessonComment extends Model
{
    use HasFactory;

    protected $fillable = [
        'lesson_id',
        'user_id',
        'content',
        'likes',
        'dislikes',
        'replies',
        'parent_id',
        'sentiment',
        'privacy_settings',
    ];

    /**
     * The "booted" method of the model.
     * Handle cascade delete for related records
     */
    protected static function booted(): void
    {
        static::deleting(function (LessonComment $comment) {
            // Delete all likes
            $comment->likeComment()->detach();

            // Delete all dislikes
            $comment->dislikeComment()->detach();

            // Delete all images
            $comment->lessonCommentImages()->delete();

            // Delete all replies (child comments)
            $comment->replies()->each(function ($reply) {
                $reply->delete(); // This will trigger cascade for each reply
            });
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function parentComment(): BelongsTo
    {
        return $this->belongsTo(LessonComment::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(LessonComment::class, 'parent_id');
    }

    public function lessonCommentImages(): HasMany
    {
        return $this->hasMany(LessonCommentImage::class, 'comment_id');
    }

    public function likeComment(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'lesson_comment_likes', 'comment_id', 'user_id');
    }

    public function dislikeComment(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'lesson_comment_dislikes', 'comment_id', 'user_id');
    }
}

