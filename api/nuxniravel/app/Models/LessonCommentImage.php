<?php

namespace App\Models;

use App\Models\LessonComment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

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
