<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CoursePostCommentImage extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $appends = ['url'];

    public function postComment(): BelongsTo
    {
        return $this->belongsTo(CoursePostComment::class);
    }

    public function getUrlAttribute(): string
    {
        return asset('storage/images/courses/posts/comments/'.$this->filename);
    }
}
