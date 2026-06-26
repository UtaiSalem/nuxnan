<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Auditable;

/**
 * SchoolAnnouncement Model - ประกาศโรงเรียน
 */
class SchoolAnnouncement extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    protected $fillable = [
        'academy_id',
        'created_by',
        'title',
        'content',
        'announcement_type',
        'priority',
        'target_audience',
        'attachments',
        'is_pinned',
        'is_published',
        'published_at',
        'expires_at',
        'view_count',
    ];

    protected $casts = [
        'target_audience' => 'array',
        'attachments' => 'array',
        'is_pinned' => 'boolean',
        'is_published' => 'boolean',
        'published_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    // Type constants
    const TYPE_GENERAL = 'general';
    const TYPE_ACADEMIC = 'academic';
    const TYPE_FINANCIAL = 'financial';
    const TYPE_EVENT = 'event';
    const TYPE_EMERGENCY = 'emergency';

    const TYPES = [
        self::TYPE_GENERAL => 'ทั่วไป',
        self::TYPE_ACADEMIC => 'วิชาการ',
        self::TYPE_FINANCIAL => 'การเงิน',
        self::TYPE_EVENT => 'กิจกรรม',
        self::TYPE_EMERGENCY => 'ฉุกเฉิน',
    ];

    // Priority constants
    const PRIORITY_LOW = 'low';
    const PRIORITY_NORMAL = 'normal';
    const PRIORITY_HIGH = 'high';
    const PRIORITY_URGENT = 'urgent';

    const PRIORITIES = [
        self::PRIORITY_LOW => 'ต่ำ',
        self::PRIORITY_NORMAL => 'ปกติ',
        self::PRIORITY_HIGH => 'สูง',
        self::PRIORITY_URGENT => 'ด่วนมาก',
    ];

    // Relationships
    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reads(): HasMany
    {
        return $this->hasMany(AnnouncementRead::class, 'announcement_id');
    }

    // Accessors
    public function getTypeNameAttribute(): string
    {
        return self::TYPES[$this->announcement_type] ?? '';
    }

    public function getPriorityNameAttribute(): string
    {
        return self::PRIORITIES[$this->priority] ?? '';
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->expires_at && $this->expires_at < now();
    }

    // Methods
    public function publish(): void
    {
        $this->update([
            'is_published' => true,
            'published_at' => now(),
        ]);
    }

    public function unpublish(): void
    {
        $this->update([
            'is_published' => false,
        ]);
    }

    public function incrementViewCount(): void
    {
        $this->increment('view_count');
    }

    public function markAsRead(int $userId): void
    {
        $this->reads()->firstOrCreate([
            'user_id' => $userId,
        ], [
            'read_at' => now(),
        ]);
    }

    public function isReadBy(int $userId): bool
    {
        return $this->reads()->where('user_id', $userId)->exists();
    }

    // Scopes
    public function scopeByAcademy($query, $academyId)
    {
        return $query->where('academy_id', $academyId);
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function scopeActive($query)
    {
        return $query->published()
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    public function scopePinned($query)
    {
        return $query->where('is_pinned', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('announcement_type', $type);
    }

    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }
}
