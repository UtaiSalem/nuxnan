<?php

namespace App\Models;

use App\Models\User;
use App\Models\CoursePostImage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CoursePostImageComment extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function post_image(): BelongsTo
    {
        return $this->belongsTo(CoursePostImage::class);
    }

    public function liked()
    {
        return $this->belongsToMany(User::class, 'course_post_image_comment_likes', 'comment_id', 'user_id');
    }

    public function disliked()
    {
        return $this->belongsToMany(User::class, 'course_post_image_comment_dislikes', 'comment_id', 'user_id');
    }
}
