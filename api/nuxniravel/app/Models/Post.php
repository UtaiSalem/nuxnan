<?php

namespace App\Models;

use App\Models\User;
use App\Models\Share;
use App\Models\Activity;
use App\Models\PostImage;
use App\Models\PostLocation;
use App\Models\PostMention;
use App\Models\PostTaggedUser;
use App\Models\PostLinkPreview;
use App\Models\PostBackground;
use App\Models\Poll;
use App\Http\Resources\UserResource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Post extends Model
{
    use HasFactory;
    use Notifiable;
    // use HasUlids;

    // protected $fillable = [
    //     'user_id',
    //     'content',
    //     'status',
    //     'public',
    //     'meta',
    // ];

    protected $guarded = [];

    protected $appends = ['post_url'];

    protected $casts = [
        'hashtags' => 'array',
        'tags' => 'array',
        'meta' => 'array',
        'link_preview' => 'array',
        'scheduled_at' => 'datetime',
        'edited_at' => 'datetime',
        'live_location_expires_at' => 'datetime',
        'is_scheduled' => 'boolean',
        'is_published' => 'boolean',
        'is_pinned' => 'boolean',
        'comments_disabled' => 'boolean',
        'is_edited' => 'boolean',
        'is_live_location' => 'boolean',
    ];

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class);
        // return new UserResource($this->user);
    }
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // public function activities(): MorphMany
    // {
    //     return $this->morphMany(Activity::class, 'activityable');
    // }
    // morphOne relationship
    public function activity(): MorphOne
    {
        return $this->morphOne(Activity::class, 'activityable');
    }

    public function likedPost(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'liked_posts', 'post_id', 'user_id');
    }

    /**
     * The disliked that belong to the Post
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function dislikedPost(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'disliked_posts', 'post_id', 'user_id');
    }

    public function postImages(): HasMany
    {
        return $this->hasMany(PostImage::class);
    }

    /**
     * Get shares of this post
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function postShares(): MorphMany
    {
        return $this->morphMany(Share::class, 'shareable');
    }

    public function getPostUrlAttribute(): string
    {   
        return route('posts.show', $this->id);
    }

    /**
     * Get all of the comments for the Post (excluding replies)
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function postComments(): HasMany
    {
        return $this->hasMany(PostComment::class)->whereNull('parent_post_comment_id');
    }

    /**
     * Get all comments including replies
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function allPostComments(): HasMany
    {
        return $this->hasMany(PostComment::class);
    }

    /**
     * Get the location for the post.
     */
    public function postLocation(): HasOne
    {
        return $this->hasOne(PostLocation::class);
    }

    /**
     * Get all mentions in this post.
     */
    public function postMentions(): HasMany
    {
        return $this->hasMany(PostMention::class);
    }

    /**
     * Get all tagged users in this post.
     */
    public function postTaggedUsers(): HasMany
    {
        return $this->hasMany(PostTaggedUser::class);
    }

    /**
     * Get mentioned users through mentions.
     */
    public function mentionedUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'post_mentions', 'post_id', 'user_id')
                    ->withTimestamps();
    }

    /**
     * Get tagged users through tags.
     */
    public function taggedUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'post_tagged_users', 'post_id', 'user_id')
                    ->withPivot('is_approved', 'is_notified')
                    ->withTimestamps();
    }

    /**
     * Get the link preview for the post.
     */
    public function postLinkPreview(): HasOne
    {
        return $this->hasOne(PostLinkPreview::class);
    }

    /**
     * Get the poll associated with the post.
     */
    public function poll(): BelongsTo
    {
        return $this->belongsTo(Poll::class);
    }

    /**
     * Scope for published posts.
     */
    public function scopePublished($query)
    {
        return $query->where('is_published', true)
                     ->where(function ($q) {
                         $q->where('is_scheduled', false)
                           ->orWhere('scheduled_at', '<=', now());
                     });
    }

    /**
     * Scope for scheduled posts.
     */
    public function scopeScheduled($query)
    {
        return $query->where('is_scheduled', true)
                     ->where('scheduled_at', '>', now());
    }

    /**
     * Scope for pinned posts.
     */
    public function scopePinned($query)
    {
        return $query->where('is_pinned', true);
    }

    /**
     * Scope posts by privacy level.
     * 1 = Only Me, 2 = Friends, 3 = Public
     */
    public function scopeByPrivacy($query, int $privacyLevel)
    {
        return $query->where('privacy_settings', '>=', $privacyLevel);
    }

    /**
     * Scope posts visible to a specific user.
     */
    public function scopeVisibleTo($query, User $user)
    {
        return $query->where(function ($q) use ($user) {
            // User's own posts (any privacy)
            $q->where('user_id', $user->id)
              // Public posts
              ->orWhere('privacy_settings', 3)
              // Friends' posts if they're friends
              ->orWhere(function ($subQ) use ($user) {
                  $friendIds = $user->friends()->pluck('id')->toArray();
                  $subQ->where('privacy_settings', 2)
                       ->whereIn('user_id', $friendIds);
              });
        });
    }

    /**
     * Check if comments are allowed on this post.
     */
    public function canComment(): bool
    {
        return !$this->comments_disabled;
    }

    /**
     * Check if the post has a location.
     */
    public function hasLocation(): bool
    {
        return $this->postLocation()->exists();
    }

    /**
     * Check if the post has a feeling/activity.
     */
    public function hasFeeling(): bool
    {
        return !empty($this->feeling) || !empty($this->activity_type);
    }

    /**
     * Check if the post has a custom background.
     */
    public function hasBackground(): bool
    {
        return !empty($this->background_color) || 
               !empty($this->background_gradient) || 
               !empty($this->background_image);
    }

    /**
     * Check if the post has media (images).
     */
    public function hasMedia(): bool
    {
        return $this->postImages()->exists();
    }

    /**
     * Get formatted feeling/activity text.
     */
    public function getFeelingTextAttribute(): ?string
    {
        if ($this->feeling && $this->activity_type) {
            return "is {$this->activity_type} {$this->activity_text} — feeling {$this->feeling}";
        } elseif ($this->feeling) {
            return "is feeling {$this->feeling}";
        } elseif ($this->activity_type) {
            return "is {$this->activity_type} {$this->activity_text}";
        }
        return null;
    }


    public function getComments()
    {
        // return $this->postComments()->latest()->paginate(3);
        return $this->postComments()->latest()->limit(3)->get();
    }


    public function likedByAuth(): bool
    {
        $like = $this->likedPost()->contains('user_id', auth()->user()->id)->first();

        return $like ? true : false;
    }

}
