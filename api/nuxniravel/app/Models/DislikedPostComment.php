<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DislikedPostComment extends Model
{
    use HasFactory;

    protected $table = 'disliked_post_comments';

    protected $fillable = [
        'user_id',
        'post_comment_id'
    ];

    protected $casts = [
        'user_id' => 'integer',
        'post_comment_id' => 'integer'
    ];
}
