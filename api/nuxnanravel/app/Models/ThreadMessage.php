<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * ThreadMessage Model - ข้อความในกลุ่มสนทนา
 */
class ThreadMessage extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'thread_id',
        'sender_id',
        'content',
        'message_type',
        'attachments',
        'reply_to_id',
        'is_edited',
        'edited_at',
    ];

    protected $casts = [
        'attachments' => 'array',
        'is_edited' => 'boolean',
        'edited_at' => 'datetime',
    ];

    // Message type constants
    const TYPE_TEXT = 'text';
    const TYPE_FILE = 'file';
    const TYPE_IMAGE = 'image';
    const TYPE_SYSTEM = 'system';

    // Relationships
    public function thread(): BelongsTo
    {
        return $this->belongsTo(MessageThread::class, 'thread_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function replyTo(): BelongsTo
    {
        return $this->belongsTo(ThreadMessage::class, 'reply_to_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(ThreadMessage::class, 'reply_to_id');
    }

    public function readReceipts(): HasMany
    {
        return $this->hasMany(MessageReadReceipt::class, 'message_id');
    }

    // Methods
    public function edit(string $newContent): void
    {
        $this->update([
            'content' => $newContent,
            'is_edited' => true,
            'edited_at' => now(),
        ]);
    }

    public function markAsReadBy(int $userId): void
    {
        $this->readReceipts()->firstOrCreate([
            'user_id' => $userId,
        ], [
            'read_at' => now(),
        ]);
    }

    public function isReadBy(int $userId): bool
    {
        return $this->readReceipts()->where('user_id', $userId)->exists();
    }

    // Scopes
    public function scopeByThread($query, $threadId)
    {
        return $query->where('thread_id', $threadId);
    }

    public function scopeBySender($query, $senderId)
    {
        return $query->where('sender_id', $senderId);
    }

    // Boot
    protected static function boot()
    {
        parent::boot();

        static::created(function ($message) {
            $message->thread->updateLastMessageTime();
        });
    }
}
