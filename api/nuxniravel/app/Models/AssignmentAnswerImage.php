<?php

namespace App\Models;

use App\Models\AssignmentAnswer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

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
        return asset('storage/images/courses/assignments/answers/' . $this->filename);
    }
}
