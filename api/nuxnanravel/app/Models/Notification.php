<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'metadata' => 'array',
        'read_status' => 'boolean',
    ];

    // ===========================================
    // Notification Types - Grading System
    // ===========================================
    public const TYPE_GRADE_PUBLISHED = 'grade_published';
    public const TYPE_GRADE_FINALIZED = 'grade_finalized';
    public const TYPE_APPEAL_SUBMITTED = 'appeal_submitted';
    public const TYPE_APPEAL_RESPONDED = 'appeal_responded';
    public const TYPE_CERTIFICATE_ISSUED = 'certificate_issued';
    public const TYPE_ELIGIBILITY_UNLOCKED = 'eligibility_unlocked';
    public const TYPE_REMEDIATION_OPENED = 'remediation_opened';
    public const TYPE_REMEDIATION_COMPLETED = 'remediation_completed';
    public const TYPE_COURSE_MEMBER_REMOVED = 'course_member_removed';
    public const TYPE_COURSE_SELF_LEFT = 'course_self_left';
    public const TYPE_COURSE_REMOVAL_REFUNDED = 'course_removal_refunded';
    
    // Group notification types
    public const TYPE_GROUP_MEMBER_ADDED = 'group_member_added';
    public const TYPE_GROUP_ADMIN_ADDED = 'group_admin_added';
    public const TYPE_GROUP_POST_CREATED = 'group_post_created';

    // Other types
    public const TYPE_GENERAL = 'general';
    public const TYPE_COURSE = 'course';
    public const TYPE_ASSIGNMENT = 'assignment';
    public const TYPE_QUIZ = 'quiz';

    // ===========================================
    // Relationships
    // ===========================================
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    // ===========================================
    // Scopes
    // ===========================================
    public function scopeUnread($query)
    {
        return $query->where('read_status', false);
    }

    public function scopeRead($query)
    {
        return $query->where('read_status', true);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    // ===========================================
    // Helpers
    // ===========================================
    public function markAsRead(): self
    {
        $this->update(['read_status' => true]);
        return $this;
    }

    public function markAsUnread(): self
    {
        $this->update(['read_status' => false]);
        return $this;
    }

    /**
     * Get notification icon based on type
     */
    public function getIconAttribute(): string
    {
        return match ($this->type) {
            self::TYPE_GRADE_PUBLISHED, self::TYPE_GRADE_FINALIZED => 'heroicons:academic-cap',
            self::TYPE_APPEAL_SUBMITTED, self::TYPE_APPEAL_RESPONDED => 'heroicons:scale',
            self::TYPE_CERTIFICATE_ISSUED => 'heroicons:document-check',
            self::TYPE_ELIGIBILITY_UNLOCKED => 'heroicons:lock-open',
            self::TYPE_REMEDIATION_OPENED, self::TYPE_REMEDIATION_COMPLETED => 'heroicons:arrow-path',
            self::TYPE_ASSIGNMENT => 'heroicons:clipboard-document-list',
            self::TYPE_QUIZ => 'heroicons:question-mark-circle',
            self::TYPE_COURSE => 'heroicons:book-open',
            self::TYPE_GROUP_MEMBER_ADDED => 'heroicons:user-group',
            self::TYPE_GROUP_ADMIN_ADDED => 'heroicons:star',
            self::TYPE_GROUP_POST_CREATED => 'heroicons:document-text',
            default => 'heroicons:bell',
        };
    }

    /**
     * Get notification color based on type
     */
    public function getColorAttribute(): string
    {
        return match ($this->type) {
            self::TYPE_GRADE_PUBLISHED => 'blue',
            self::TYPE_GRADE_FINALIZED => 'green',
            self::TYPE_APPEAL_SUBMITTED => 'yellow',
            self::TYPE_APPEAL_RESPONDED => 'purple',
            self::TYPE_CERTIFICATE_ISSUED => 'emerald',
            self::TYPE_ELIGIBILITY_UNLOCKED => 'cyan',
            self::TYPE_REMEDIATION_OPENED => 'orange',
            self::TYPE_REMEDIATION_COMPLETED => 'teal',
            self::TYPE_GROUP_MEMBER_ADDED => 'purple',
            self::TYPE_GROUP_ADMIN_ADDED => 'yellow',
            self::TYPE_GROUP_POST_CREATED => 'blue',
            default => 'gray',
        };
    }
}
