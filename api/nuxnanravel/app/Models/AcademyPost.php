<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class AcademyPost extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $appends = ['academy_post_url'];

    protected $casts = [
        'target_audience' => 'array',
        'embed_data' => 'array',
        'is_pinned' => 'boolean',
    ];

    public function academy()
    {
        return $this->belongsTo(Academy::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function postedAsGroup(): BelongsTo
    {
        return $this->belongsTo(AcademyGroup::class, 'posted_as_group_id');
    }

    public function activity(): MorphOne
    {
        return $this->morphOne(Activity::class, 'activityable');
    }

    public function likedPost(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'academy_post_likes', 'academy_post_id', 'user_id');
    }

    /**
     * The disliked that belong to the Post
     */
    public function dislikedPost(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'academy_post_dislikes', 'academy_post_id', 'user_id');
    }

    public function images()
    {
        return $this->hasMany(AcademyPostImage::class);
    }

    public function getAcademyPostUrlAttribute()
    {
        return route('academy_post.show', ['academy' => $this->academy, 'post' => $this]);
    }

    /**
     * Get all of the comments for the AcademyPost
     */
    public function comments(): HasMany
    {
        return $this->hasMany(AcademyPostComment::class);
    }

    public function getComments()
    {
        // return $this->postComments()->latest()->paginate(3);
        return $this->comments()->latest()->limit(3)->get();
    }
}
