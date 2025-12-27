<?php

namespace App\Models;

use App\Models\User;
use App\Models\Course;
use App\Models\Academy;
use App\Models\Activity;
use App\Models\CourseGroup;
use App\Models\CoursePostLike;
use App\Models\CoursePostImage;
use App\Models\CoursePostShare;
use App\Models\CoursePostComment;
use App\Models\CoursePostDislike;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class CoursePost extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'academy_id',
        'course_id',
        'group_id',
        'content',
        'status',
        'likes',
        'dislikes',
        'comments',
        'shares',
        'views',
        'hashtags',
        'privacy_settings',
        'location',
        'tags',
        'sentiment',
        'engagement_rate',
        'post_type',
        'source_platform',
        'interacted_at',
        'meta',
    ];

    protected $casts = [
        'hashtags' => 'array',
        'tags' => 'array',
        'meta' => 'array',
    ];

    protected $appends = [
        'post_url',
    ];

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

    public function courseGroup()
    {
        return $this->belongsTo(CourseGroup::class);
    }

    public function post_images()
    {
        return $this->hasMany(CoursePostImage::class);
    }

    public function post_comments()
    {
        return $this->hasMany(CoursePostComment::class);
    }

    public function getComments()
    {
        // return $this->postComments()->latest()->paginate(3);
        return $this->post_comments()->latest()->limit(3)->get();
    }

    public function post_likes()
    {
        return $this->hasMany(CoursePostLike::class);
    }

    public function likedPost(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'course_post_likes', 'course_post_id', 'user_id');
    }

    public function dislikedPost(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'course_post_dislikes', 'course_post_id', 'user_id');
    }

    public function post_dislikes()
    {
        return $this->hasMany(CoursePostDislike::class);
    }


    public function activity(): MorphOne
    {
        return $this->morphOne(Activity::class, 'activityable');
    }

    public function getPostUrlAttribute(): string
    {
        // return route('course.posts.show', $this->course_id, $this->id);
        return '/courses/'. $this->course_id . '/posts/' . $this->id;
    }

}
