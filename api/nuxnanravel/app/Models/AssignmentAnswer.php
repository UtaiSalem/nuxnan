<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class AssignmentAnswer extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $fillable = ['user_id', 'content', 'points', 'submission_date', 'attachment_path', 'status', 'late_submission', 'feedback'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class);
    }

    // public function images(): MorphMany
    // {
    //     return $this->morphMany(AnswerImage::class, 'imageable');
    // }

    public function images(): HasMany
    {
        return $this->hasMany(AssignmentAnswerImage::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(AssignmentAnswerAttachment::class);
    }
}
