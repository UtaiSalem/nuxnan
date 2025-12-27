<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class QuestionImage extends Model
{
    use HasFactory;
    
    protected $guarded = [];

    protected $appends = ['url'];

    public function imageable(): MorphTo
    {
        return $this->morphTo();
    }

    public function getUrlAttribute(): string
    {
        return asset('storage/images/courses/quizzes/questions/' . $this->filename);
    }
}
