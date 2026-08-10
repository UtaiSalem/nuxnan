<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssignmentAnswerAttachment extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function answer(): BelongsTo
    {
        return $this->belongsTo(AssignmentAnswer::class, 'assignment_answer_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function getDownloadUrlAttribute(): string
    {
        return route('assignments.answers.attachments.download', [
            'assignment' => $this->assignment_id,
            'answer' => $this->assignment_answer_id,
            'attachment' => $this->id,
        ]);
    }

    public function getHumanSizeAttribute(): string
    {
        $bytes = $this->size;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        if ($bytes == 0) {
            return '0 B';
        }
        $pow = floor(log($bytes) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= (1 << (10 * $pow));

        return round($bytes, 1).' '.$units[$pow];
    }
}
