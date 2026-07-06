<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PostCommentImage extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $appends = ['url'];

    public function postComment()
    {
        return $this->belongsTo(PostComment::class);
    }

    public function getUrlAttribute(): string
    {
        return asset('storage/images/posts/comments/'.$this->filename);
    }
}
