<?php

namespace App\Models;

use App\Models\User;
use App\Models\Activity;
use App\Models\ShareComment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Share extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $appends = ['share_url'];

    /**
     * Get the user that created the share
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the shared item (Post, CoursePost, etc.)
     */
    public function shareable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the activity for this share
     */
    public function activity(): MorphOne
    {
        return $this->morphOne(Activity::class, 'activityable');
    }

    /**
     * Users who liked this share
     */
    public function likedShare(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'share_likes', 'share_id', 'user_id')->withTimestamps();
    }

    /**
     * Users who disliked this share
     */
    public function dislikedShare(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'share_dislikes', 'share_id', 'user_id')->withTimestamps();
    }

    /**
     * Comments on this share
     */
    public function shareComments(): HasMany
    {
        return $this->hasMany(ShareComment::class);
    }

    /**
     * Get 3 latest comments for pre-loading
     */
    public function getComments()
    {
        return $this->shareComments()->latest()->limit(3)->get();
    }

    /**
     * Get the share URL
     */
    public function getShareUrlAttribute(): string
    {
        return route('shares.show', $this->id);
    }

    /**
     * Check if authenticated user liked this share
     */
    public function isLikedByAuth(): bool
    {
        if (!auth()->check()) {
            return false;
        }
        return $this->likedShare()->where('user_id', auth()->id())->exists();
    }

    /**
     * Check if authenticated user disliked this share
     */
    public function isDislikedByAuth(): bool
    {
        if (!auth()->check()) {
            return false;
        }
        return $this->dislikedShare()->where('user_id', auth()->id())->exists();
    }

    /**
     * Increment likes count
     */
    public function incrementLikes(): void
    {
        $this->increment('likes');
    }

    /**
     * Decrement likes count
     */
    public function decrementLikes(): void
    {
        $this->decrement('likes');
    }

    /**
     * Increment dislikes count
     */
    public function incrementDislikes(): void
    {
        $this->increment('dislikes');
    }

    /**
     * Decrement dislikes count
     */
    public function decrementDislikes(): void
    {
        $this->decrement('dislikes');
    }

    /**
     * Increment comments count
     */
    public function incrementComments(): void
    {
        $this->increment('comments');
    }

    /**
     * Decrement comments count
     */
    public function decrementComments(): void
    {
        $this->decrement('comments');
    }

    /**
     * Increment views count
     */
    public function incrementViews(): void
    {
        $this->increment('views');
    }
}
