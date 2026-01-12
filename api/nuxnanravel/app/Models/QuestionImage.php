<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

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
             return asset('storage/' . $this->attributes['image_url']);
        }

        // Define all potential base paths where images might be stored
        $candidatePaths = [];

        // 1. Quizzes (Preferred for this context)
        // Note: User confirmed options are also here: images/courses/quizzes/questions/
        $candidatePaths[] = "storage/images/courses/quizzes/questions/" . $this->filename;
        $candidatePaths[] = "storage/images/courses/quizzes/questions/options/" . $this->filename;

        // 2. Lessons (Legacy/Other context)
        $candidatePaths[] = "storage/images/courses/lessons/questions/" . $this->filename;
        $candidatePaths[] = "storage/images/courses/lessons/questions/options/" . $this->filename;

        foreach ($candidatePaths as $path) {
            if (file_exists(public_path($path))) {
                return asset($path);
            }
        }
        
        // Fallback based on type if not found (to maintain old behavior preference or just default)
        if ($this->imageable_type === 'App\Models\QuestionOption' || $this->imageable_type === 'QuestionOption') {
             return asset("storage/images/courses/lessons/questions/options/" . $this->filename);
        }
        
        return asset("storage/images/courses/lessons/questions/" . $this->filename);
    }



    public function getFullUrlAttribute(): string
    {
        return $this->url;
    }
}
