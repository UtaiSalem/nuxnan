<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PostImageCommentDislike extends Model
{
    use HasFactory;

    protected $table = 'post_image_comment_dislikes';

    protected $fillable = [
        'user_id',
        'post_image_comment_id'
    ];

    protected $casts = [
        'user_id' => 'integer',
        'post_image_comment_id' => 'integer'
    ];
}
