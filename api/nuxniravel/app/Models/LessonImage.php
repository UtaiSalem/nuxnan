<?php

namespace App\Models;

use App\Models\Lesson;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LessonImage extends Model
{
    use HasFactory;
    protected $guarded = [];

    protected $appends = ['full_url'];

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function getFullUrlAttribute()
    {
        return asset('storage/images/courses/lessons/' . $this->filename);
    }
}
