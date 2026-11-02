<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TypingUserStage extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'stage_id',
        'is_unlocked',
        'is_passed',
        'stars',
        'best_accuracy',
        'best_wpm',
        'best_cpm',
        'attempts_count',
        'recent_attempts',
        'passed_at',
    ];

    protected $casts = [
        'is_unlocked' => 'boolean',
        'is_passed' => 'boolean',
        'stars' => 'integer',
        'best_accuracy' => 'float',
        'best_wpm' => 'integer',
        'best_cpm' => 'integer',
        'attempts_count' => 'integer',
        'recent_attempts' => 'array',
        'passed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(TypingStage::class, 'stage_id');
    }
}
