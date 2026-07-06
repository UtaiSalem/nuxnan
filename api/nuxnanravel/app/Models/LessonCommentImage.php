<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LessonCommentImage extends Model
{
    use HasFactory;

    protected $guarded = [];

    // protected $appends = ['url'];

    public function lessonComment()
    {
        return $this->belongsTo(LessonComment::class);
    }
}
