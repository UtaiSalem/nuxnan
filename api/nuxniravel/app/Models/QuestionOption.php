<?php

namespace App\Models;

use App\Models\Question;
use App\Models\QuestionImage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class QuestionOption extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function optionable()
    {
        return $this->morphTo();
    }
    // public function question(): BelongsTo
    // {
    //     return $this->belongsTo(Question::class);
    // }

    public function images(): MorphMany
    {
        return $this->morphMany(QuestionImage::class, 'imageable');
    }
}
