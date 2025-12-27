<?php

namespace App\Models;

use App\Models\CoursePostComment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

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
        return asset('storage/images/courses/posts/comments/' . $this->filename);
    }

}
