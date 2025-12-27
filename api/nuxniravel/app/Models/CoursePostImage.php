<?php

namespace App\Models;

use App\Models\CoursePost;
use App\Models\CoursePostImageComment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class CoursePostImage extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $appends = ['full_url'];

    public function post(): BelongsTo
    {
        return $this->belongsTo(CoursePost::class);
    }

    public function getFullUrlAttribute(): string
    {
        return asset('storage/images/courses/posts/' . $this->filename);
    }

    /**
     * Get all of the comments for the CoursePostImage
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function image_comments(): HasMany
    {
        return $this->hasMany(CoursePostImageComment::class);
    }

    /**
     * The postImageLikes that belong to the CoursePostImage
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function postImageLikes(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'course_post_image_likes', 'course_post_image_id', 'user_id');
    }

    /**
     * The postImageDislikes that belong to the CoursePostImage
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */ 
    public function postImageDislikes(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'course_post_image_dislikes', 'course_post_image_id', 'user_id');
    }
    
}
