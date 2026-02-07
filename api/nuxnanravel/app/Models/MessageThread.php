<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * MessageThread Model - กลุ่มสนทนา
 */
class MessageThread extends Model
{
    use HasFactory;

    protected $fillable = [
        'academy_id',
        'subject',
        'thread_type',
        'related_student_id',
        'class_id',
        'is_archived',
        'last_message_at',
    ];

    protected $casts = [
        'is_archived' => 'boolean',
        'last_message_at' => 'datetime',
    ];

    // Thread type constants
    const TYPE_DIRECT = 'direct';
    const TYPE_GROUP = 'group';
    const TYPE_PARENT_TEACHER = 'parent_teacher';
    const TYPE_CLASS = 'class';

    const TYPES = [
        self::TYPE_DIRECT => 'สนทนาตรง',
        self::TYPE_GROUP => 'กลุ่ม',
        self::TYPE_PARENT_TEACHER => 'ผู้ปกครอง-ครู',
        self::TYPE_CLASS => 'ห้องเรียน',
    ];

    // Relationships
    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
    }

    public function relatedStudent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'related_student_id');
    }

    public function classGroup(): BelongsTo
    {
        return $this->belongsTo(AcademyGroup::class, 'class_id');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(ThreadParticipant::class, 'thread_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ThreadMessage::class, 'thread_id');
    }

    // Accessors
    public function getTypeNameAttribute(): string
    {
        return self::TYPES[$this->thread_type] ?? '';
    }

    public function getLastMessageAttribute()
    {
        return $this->messages()->latest()->first();
    }

    public function getParticipantCountAttribute(): int
    {
        return $this->participants()->whereNull('left_at')->count();
    }

    public function getUnreadCountAttribute(): int
    {
        $userId = auth()->id();
        if (!$userId) return 0;

        $participant = $this->participants()->where('user_id', $userId)->first();
        if (!$participant) return 0;

        return $this->messages()
            ->where('created_at', '>', $participant->last_read_at ?? $participant->joined_at)
            ->where('sender_id', '!=', $userId)
            ->count();
    }

    // Methods
    public function archive(): void
    {
        $this->update(['is_archived' => true]);
    }

    public function unarchive(): void
    {
        $this->update(['is_archived' => false]);
    }

    public function updateLastMessageTime(): void
    {
        $this->update(['last_message_at' => now()]);
    }

    public function addParticipant(int $userId, string $role = 'member'): ThreadParticipant
    {
        return $this->participants()->firstOrCreate([
            'user_id' => $userId,
        ], [
            'role' => $role,
            'joined_at' => now(),
        ]);
    }

    public function removeParticipant(int $userId): void
    {
        $this->participants()
            ->where('user_id', $userId)
            ->update(['left_at' => now()]);
    }

    public function isParticipant(int $userId): bool
    {
        return $this->participants()
            ->where('user_id', $userId)
            ->whereNull('left_at')
            ->exists();
    }

    // Scopes
    public function scopeByAcademy($query, $academyId)
    {
        return $query->where('academy_id', $academyId);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('thread_type', $type);
    }

    public function scopeActive($query)
    {
        return $query->where('is_archived', false);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->whereHas('participants', function ($q) use ($userId) {
            $q->where('user_id', $userId)->whereNull('left_at');
        });
    }
}
