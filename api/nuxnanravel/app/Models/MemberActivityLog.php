<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberActivityLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'academy_id',
        'user_id',
        'target_user_id',
        'academy_member_id',
        'action',
        'action_category',
        'old_values',
        'new_values',
        'description',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Action types constants
    public const ACTION_JOIN = 'join';

    public const ACTION_LEAVE = 'leave';

    public const ACTION_APPROVE = 'approve';

    public const ACTION_REJECT = 'reject';

    public const ACTION_SUSPEND = 'suspend';

    public const ACTION_UNSUSPEND = 'unsuspend';

    public const ACTION_REMOVE = 'remove';

    public const ACTION_ROLE_CHANGE = 'role_change';

    public const ACTION_INVITE = 'invite';

    public const ACTION_ACCEPT_INVITE = 'accept_invite';

    public const ACTION_DECLINE_INVITE = 'decline_invite';

    public const ACTION_PROFILE_UPDATE = 'profile_update';

    public const ACTION_BULK_ACTION = 'bulk_action';

    // Categories
    public const CATEGORY_MEMBER = 'member';

    public const CATEGORY_ROLE = 'role';

    public const CATEGORY_COURSE = 'course';

    public const CATEGORY_ATTENDANCE = 'attendance';

    public const CATEGORY_SYSTEM = 'system';

    /**
     * Relationships
     */
    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function targetUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(AcademyMember::class, 'academy_member_id');
    }

    /**
     * Scopes
     */
    public function scopeForAcademy($query, int $academyId)
    {
        return $query->where('academy_id', $academyId);
    }

    public function scopeByAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('action_category', $category);
    }

    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByTargetUser($query, int $userId)
    {
        return $query->where('target_user_id', $userId);
    }

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Helper to log activity
     */
    public static function logActivity(array $data): self
    {
        $request = request();

        return self::create([
            'academy_id' => $data['academy_id'],
            'user_id' => $data['user_id'] ?? $request->user()?->id,
            'target_user_id' => $data['target_user_id'] ?? null,
            'academy_member_id' => $data['academy_member_id'] ?? null,
            'action' => $data['action'],
            'action_category' => $data['action_category'] ?? self::CATEGORY_MEMBER,
            'old_values' => $data['old_values'] ?? null,
            'new_values' => $data['new_values'] ?? null,
            'description' => $data['description'] ?? self::generateDescription($data['action'], $data),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }

    /**
     * Generate human-readable description
     */
    public static function generateDescription(string $action, array $data = []): string
    {
        $descriptions = [
            self::ACTION_JOIN => 'ส่งคำขอเข้าร่วม',
            self::ACTION_LEAVE => 'ออกจากสถาบัน',
            self::ACTION_APPROVE => 'ได้รับการอนุมัติเป็นสมาชิก',
            self::ACTION_REJECT => 'ถูกปฏิเสธการเข้าร่วม',
            self::ACTION_SUSPEND => 'ถูกระงับสถานะสมาชิก',
            self::ACTION_UNSUSPEND => 'ได้รับการปลดระงับ',
            self::ACTION_REMOVE => 'ถูกนำออกจากสถาบัน',
            self::ACTION_ROLE_CHANGE => 'มีการเปลี่ยนแปลงบทบาท',
            self::ACTION_INVITE => 'ได้รับคำเชิญ',
            self::ACTION_ACCEPT_INVITE => 'ยอมรับคำเชิญ',
            self::ACTION_DECLINE_INVITE => 'ปฏิเสธคำเชิญ',
            self::ACTION_PROFILE_UPDATE => 'อัพเดทข้อมูลโปรไฟล์',
            self::ACTION_BULK_ACTION => 'ดำเนินการแบบกลุ่ม',
        ];

        return $descriptions[$action] ?? $action;
    }

    /**
     * Get action icon
     */
    public function getIconAttribute(): string
    {
        $icons = [
            self::ACTION_JOIN => 'mdi:account-plus',
            self::ACTION_LEAVE => 'mdi:account-remove',
            self::ACTION_APPROVE => 'mdi:check-circle',
            self::ACTION_REJECT => 'mdi:close-circle',
            self::ACTION_SUSPEND => 'mdi:account-lock',
            self::ACTION_UNSUSPEND => 'mdi:account-lock-open',
            self::ACTION_REMOVE => 'mdi:account-minus',
            self::ACTION_ROLE_CHANGE => 'mdi:badge-account',
            self::ACTION_INVITE => 'mdi:email-send',
            self::ACTION_ACCEPT_INVITE => 'mdi:email-check',
            self::ACTION_DECLINE_INVITE => 'mdi:email-remove',
            self::ACTION_PROFILE_UPDATE => 'mdi:account-edit',
            self::ACTION_BULK_ACTION => 'mdi:account-group',
        ];

        return $icons[$this->action] ?? 'mdi:circle';
    }

    /**
     * Get action color
     */
    public function getColorAttribute(): string
    {
        $colors = [
            self::ACTION_JOIN => 'blue',
            self::ACTION_LEAVE => 'gray',
            self::ACTION_APPROVE => 'green',
            self::ACTION_REJECT => 'red',
            self::ACTION_SUSPEND => 'orange',
            self::ACTION_UNSUSPEND => 'teal',
            self::ACTION_REMOVE => 'red',
            self::ACTION_ROLE_CHANGE => 'purple',
            self::ACTION_INVITE => 'indigo',
            self::ACTION_ACCEPT_INVITE => 'green',
            self::ACTION_DECLINE_INVITE => 'gray',
            self::ACTION_PROFILE_UPDATE => 'blue',
            self::ACTION_BULK_ACTION => 'violet',
        ];

        return $colors[$this->action] ?? 'gray';
    }
}
