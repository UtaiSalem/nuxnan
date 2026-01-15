<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Photo extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'album_id',
        'url',
        'thumbnail_url',
        'caption',
        'is_public',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_public' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'likes_count',
        'comments_count',
        'is_liked',
    ];

    /**
     * Get the user that owns the photo.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the album that contains the photo.
     */
    public function album(): BelongsTo
    {
        return $this->belongsTo(Album::class);
    }

    /**
     * Get all of the photo's likes.
     */
    public function likes(): MorphMany
    {
        return $this->morphMany(Like::class, 'likeable');
    }

    /**
     * Get all of the photo's comments.
     */
    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    /**
     * Get likes count attribute.
     */
    public function getLikesCountAttribute(): int
    {
        return $this->likes()->count();
    }

    /**
     * Get comments count attribute.
     */
    public function getCommentsCountAttribute(): int
    {
        return $this->comments()->count();
    }

    /**
     * Get is_liked attribute.
     */
    public function getIsLikedAttribute(): bool
    {
        if (!auth()->check()) {
            return false;
        }

        return $this->likes()
            ->where('user_id', auth()->id())
            ->exists();
    }

    /**
     * Check if the authenticated user has liked the photo.
     */
    public function isLikedBy(int $userId): bool
    {
        return $this->likes()
            ->where('user_id', $userId)
            ->exists();
    }

    /**
     * Scope a query to only include public photos.
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    /**
     * Scope a query to only include private photos.
     */
    public function scopePrivate($query)
    {
        return $query->where('is_public', false);
    }

    /**
     * Scope a query to only include photos for a specific user.
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope a query to only include photos in a specific album.
     */
    public function scopeInAlbum($query, ?int $albumId)
    {
        if ($albumId === null) {
            return $query->whereNull('album_id');
        }

        return $query->where('album_id', $albumId);
    }
}
