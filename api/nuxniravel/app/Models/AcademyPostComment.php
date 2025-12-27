<?php

namespace App\Models;

use App\Models\User;
use App\Models\AcademyPost;
use App\Models\AcademyPostComment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AcademyPostComment extends Model
{
    use HasFactory;

    protected $fillable = [
        'academy_post_id',
        'user_id',
        'content',
        'likes',
        'dislikes',
        'replies',
        'parent_comment_id',
        'sentiment',
        'privacy_settings',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function post()
    {
        return $this->belongsTo(AcademyPost::class);
    }

    public function parentComment()
    {
        return $this->belongsTo(AcademyPostComment::class, 'parent_comment_id');
    }

    public function replies()
    {
        return $this->hasMany(AcademyPostComment::class, 'parent_comment_id');
    }

    public function likes()
    {
        return $this->belongsToMany(User::class, 'academy_post_comment_likes', 'academy_post_comment_id', 'user_id');
    }

    public function dislikes()
    {
        return $this->belongsToMany(User::class, 'academy_post_comment_dislikes', 'academy_post_comment_id', 'user_id');
    }

    public function images()
    {
        return $this->hasMany(AcademyPostCommentImage::class);
    }

    
    
}
