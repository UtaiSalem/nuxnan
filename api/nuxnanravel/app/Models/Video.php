<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Video extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'title',
        'description',
        'thumbnail_url',
        'video_url',
        'duration',
        'views_count',
        'likes_count',
        'privacy_settings',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'duration' => 'integer',
        'views_count' => 'integer',
        'likes_count' => 'integer',
        'privacy_settings' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user that owns the video.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope a query to only include public videos.
     */
    public function scopePublic($query)
    {
        return $query->where('privacy_settings', 3);
    }

    /**
     * Scope a query to only include videos visible to friends.
     */
    public function scopeFriendsOrPublic($query)
    {
        return $query->whereIn('privacy_settings', [2, 3]);
    }

    /**
     * Scope posts visible to a specific user.
     */
    public function scopeVisibleTo($query, User $viewer, int $ownerId)
    {
        return $query->where(function ($q) use ($viewer, $ownerId) {
            // Owner can see all their own videos
            if ($viewer->id === $ownerId) {
                return $q;
            }
            
            // Check if viewer is a friend of the owner
            $isFriend = $viewer->friends()->where('users.id', $ownerId)->exists();
            
            if ($isFriend) {
                // Friends can see friends-only and public videos
                $q->whereIn('privacy_settings', [2, 3]);
            } else {
                // Others can only see public videos
                $q->where('privacy_settings', 3);
            }
        });
    }

    /**
     * Get privacy label.
     */
    public function getPrivacyLabelAttribute(): string
    {
        return match ($this->privacy_settings) {
            1 => 'private',
            2 => 'friends',
            3 => 'public',
            default => 'public',
        };
    }

    /**
     * Increment view count.
     */
    public function recordView(): void
    {
        $this->increment('views_count');
    }
}
