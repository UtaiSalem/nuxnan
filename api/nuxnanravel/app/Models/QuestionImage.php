<?php

namespace App\Models;

use App\Services\CourseMediaService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class QuestionImage extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $appends = ['url', 'full_url'];

    public function imageable(): MorphTo
    {
        return $this->morphTo();
    }

    public function getUrlAttribute(): string
    {
        if (isset($this->attributes['image_url']) && $this->attributes['image_url']) {
            return asset('storage/'.$this->attributes['image_url']);
        }

        // Uploads are spread across several directories (quiz questions and their
        // options share one, lesson questions another), so the file has to be
        // looked up. CourseMediaService owns that list — keep it single-sourced.
        $path = app(CourseMediaService::class)
            ->locate($this->filename, CourseMediaService::QUESTION_IMAGE_SEARCH_PATHS);

        if ($path) {
            return asset('storage/'.$path.'/'.$this->filename);
        }

        // Fallback based on type if not found (to maintain old behavior preference or just default)
        if ($this->imageable_type === 'App\Models\QuestionOption' || $this->imageable_type === 'QuestionOption') {
            return asset('storage/images/courses/lessons/questions/options/'.$this->filename);
        }

        return asset('storage/images/courses/lessons/questions/'.$this->filename);
    }

    public function getFullUrlAttribute(): string
    {
        return $this->url;
    }
}
