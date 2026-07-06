<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ThreadParticipant Model - ผู้เข้าร่วมสนทนา
 */
class ThreadParticipant extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'thread_id',
        'user_id',
        'role',
        'is_muted',
        'last_read_at',
        'joined_at',
        'left_at',
    ];

    protected $casts = [
        'is_muted' => 'boolean',
        'last_read_at' => 'datetime',
        'joined_at' => 'datetime',
        'left_at' => 'datetime',
    ];

    // Role constants
    const ROLE_ADMIN = 'admin';

    const ROLE_MODERATOR = 'moderator';

    const ROLE_MEMBER = 'member';

    // Relationships
    public function thread(): BelongsTo
    {
        return $this->belongsTo(MessageThread::class, 'thread_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Methods
    public function markAsRead(): void
    {
        $this->update(['last_read_at' => now()]);
    }

    public function mute(): void
    {
        $this->update(['is_muted' => true]);
    }

    public function unmute(): void
    {
        $this->update(['is_muted' => false]);
    }

    public function leave(): void
    {
        $this->update(['left_at' => now()]);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->whereNull('left_at');
    }
}
