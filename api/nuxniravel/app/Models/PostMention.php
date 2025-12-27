<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostMention extends Model
{
    use HasFactory;

    protected $fillable = [
        'post_id',
        'user_id',
        'mentioned_by',
        'username',
        'position',
        'is_notified',
    ];

    protected $casts = [
        'is_notified' => 'boolean',
        'position' => 'integer',
    ];

    /**
     * Get the post that this mention belongs to.
     */
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    /**
     * Get the user who was mentioned.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the user who created the mention.
     */
    public function mentionedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mentioned_by');
    }

    /**
     * Scope for unnotified mentions.
     */
    public function scopeUnnotified($query)
    {
        return $query->where('is_notified', false);
    }

    /**
     * Mark the mention as notified.
     */
    public function markAsNotified(): bool
    {
        return $this->update(['is_notified' => true]);
    }
}
