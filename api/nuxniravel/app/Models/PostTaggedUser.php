<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostTaggedUser extends Model
{
    use HasFactory;

    protected $fillable = [
        'post_id',
        'user_id',
        'tagged_by',
        'is_approved',
        'is_notified',
    ];

    protected $casts = [
        'is_approved' => 'boolean',
        'is_notified' => 'boolean',
    ];

    /**
     * Get the post that this tag belongs to.
     */
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    /**
     * Get the tagged user.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the user who created the tag.
     */
    public function taggedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tagged_by');
    }

    /**
     * Scope for approved tags.
     */
    public function scopeApproved($query)
    {
        return $query->where('is_approved', true);
    }

    /**
     * Scope for pending approval.
     */
    public function scopePending($query)
    {
        return $query->where('is_approved', false);
    }

    /**
     * Scope for unnotified tags.
     */
    public function scopeUnnotified($query)
    {
        return $query->where('is_notified', false);
    }

    /**
     * Approve the tag.
     */
    public function approve(): bool
    {
        return $this->update(['is_approved' => true]);
    }

    /**
     * Mark the tag as notified.
     */
    public function markAsNotified(): bool
    {
        return $this->update(['is_notified' => true]);
    }
}
