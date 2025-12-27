<?php

namespace App\Models;

use App\Models\User;
use App\Models\Share;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ShareComment extends Model
{
    use HasFactory;

    protected $guarded = [];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * The accessors to append to the model's array form.
     */
    protected $appends = [
        'diff_humans_created_at',
        'diff_humans_updated_at',
        'is_liked_by_auth',
        'is_disliked_by_auth',
    ];

    /**
     * Get human-readable created_at
     */
    public function getDiffHumansCreatedAtAttribute(): string
    {
        return $this->created_at ? $this->created_at->diffForHumans() : '';
    }

    /**
     * Get human-readable updated_at
     */
    public function getDiffHumansUpdatedAtAttribute(): string
    {
        return $this->updated_at ? $this->updated_at->diffForHumans() : '';
    }

    /**
     * Check if comment is liked by authenticated user
     */
    public function getIsLikedByAuthAttribute(): bool
    {
        if (!auth()->check()) {
            return false;
        }
        
        return \DB::table('share_comment_likes')
            ->where('share_comment_id', $this->id)
            ->where('user_id', auth()->id())
            ->exists();
    }

    /**
     * Check if comment is disliked by authenticated user
     */
    public function getIsDislikedByAuthAttribute(): bool
    {
        if (!auth()->check()) {
            return false;
        }
        
        return \DB::table('share_comment_dislikes')
            ->where('share_comment_id', $this->id)
            ->where('user_id', auth()->id())
            ->exists();
    }

    /**
     * Get the share this comment belongs to
     */
    public function share(): BelongsTo
    {
        return $this->belongsTo(Share::class);
    }

    /**
     * Get the user who created the comment
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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
}
