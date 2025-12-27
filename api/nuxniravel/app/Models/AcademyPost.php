<?php

namespace App\Models;

use App\Models\User;
use App\Models\Academy;
use App\Models\Activity;
use App\Models\AcademyPostImage;
use App\Models\AcademyPostComment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class AcademyPost extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $appends = ['academy_post_url'];

    public function academy()
    {
        return $this->belongsTo(Academy::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
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
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
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
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
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
