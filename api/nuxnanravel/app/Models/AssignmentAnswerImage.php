<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssignmentAnswerImage extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $appends = ['full_url'];

    public function answer(): BelongsTo
    {
        return $this->belongsTo(AssignmentAnswer::class);
    }

    public function getFullUrlAttribute(): string
    {
        return asset('storage/images/courses/assignments/answers/'.$this->filename);
    }
}
