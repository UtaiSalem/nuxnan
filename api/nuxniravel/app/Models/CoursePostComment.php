<?php

namespace App\Models;

use App\Models\User;
use App\Models\CoursePost;
use App\Models\CoursePostCommentImage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CoursePostComment extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_post_id',
        'user_id',
        'content',
        'likes',
        'dislikes',
        'replies',
        'parent_post_comment_id',
        'sentiment',
        'hashtags',
    ];

    protected $casts = [
        'hashtags' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }   

    public function coursePost()
    {
        return $this->belongsTo(CoursePost::class);
    }

    public function parentComment()
    {
        return $this->belongsTo(CoursePostComment::class, 'parent_post_comment_id');
    }

    public function replies()
    {
        return $this->hasMany(CoursePostComment::class, 'parent_post_comment_id');
    }

    public function comment_likes()
    {
        return $this->belongsToMany(User::class, 'course_post_comment_likes', 'course_post_comment_id', 'user_id');
    }

    public function comment_dislikes()
    {
        return $this->belongsToMany(User::class, 'course_post_comment_dislikes', 'course_post_comment_id', 'user_id');
    }

    public function postCommentImages(): HasMany
    {
        return $this->hasMany(CoursePostCommentImage::class, 'post_comment_id',);
    }
}
