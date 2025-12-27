<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PostImageCommentLike extends Model
{
    use HasFactory;

    protected $table = 'post_image_comment_likes';

    protected $fillable = [
        'user_id',
        'post_image_comment_id'
    ];

    protected $casts = [
        'user_id' => 'integer',
        'post_image_comment_id' => 'integer'
    ];
}
