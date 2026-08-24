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

    public const ACTION_INVITE_LINK_CREATE = 'invite_link_create';

    public const ACTION_INVITE_LINK_UPDATE = 'invite_link_update';

    public const ACTION_INVITE_LINK_DELETE = 'invite_link_delete';

    public const ACTION_INVITE_LINK_TOGGLE = 'invite_link_toggle';

    // Role CRUD actions
    public const ACTION_ROLE_CREATE = 'role_create';

    public const ACTION_ROLE_UPDATE = 'role_update';

    public const ACTION_ROLE_DELETE = 'role_delete';

    public const ACTION_ROLE_ASSIGN = 'role_assign';

    public const ACTION_ROLE_BULK_ASSIGN = 'role_bulk_assign';

    public const ACTION_TAG_CREATE = 'tag_create';

    public const ACTION_TAG_UPDATE = 'tag_update';

    public const ACTION_TAG_DELETE = 'tag_delete';

    public const ACTION_TAG_ASSIGN = 'tag_assign';

    public const ACTION_TAG_REMOVE = 'tag_remove';

    public const ACTION_DEPARTMENT_CREATE = 'department_create';

    public const ACTION_DEPARTMENT_UPDATE = 'department_update';

    public const ACTION_DEPARTMENT_DELETE = 'department_delete';

    public const ACTION_DEPARTMENT_SETUP = 'department_setup';

    public const ACTION_DEPARTMENT_MEMBER_ADD = 'department_member_add';

    public const ACTION_DEPARTMENT_MEMBER_REMOVE = 'department_member_remove';

    public const ACTION_DEPARTMENT_MEMBER_ROLE_CHANGE = 'department_member_role_change';

    public const ACTION_DEPARTMENT_PERMISSION_UPDATE = 'department_permission_update';

    public const ACTION_ELECTION_CREATE = 'election_create';

    public const ACTION_ELECTION_UPDATE = 'election_update';

    public const ACTION_ELECTION_DELETE = 'election_delete';

    public const ACTION_ELECTION_STATUS_CHANGE = 'election_status_change';

    public const ACTION_ELECTION_PARTY_APPLY = 'election_party_apply';

    public const ACTION_ELECTION_PARTY_UPDATE = 'election_party_update';

    public const ACTION_ELECTION_PARTY_APPROVE = 'election_party_approve';

    public const ACTION_ELECTION_PARTY_REJECT = 'election_party_reject';

    public const ACTION_ELECTION_PARTY_WITHDRAW = 'election_party_withdraw';

    public const ACTION_ELECTION_VOTER_ROLL_LOCK = 'election_voter_roll_lock';

    public const ACTION_ELECTION_STATION_OPEN = 'election_station_open';

    public const ACTION_ELECTION_STATION_CLOSE = 'election_station_close';

    public const ACTION_ELECTION_BALLOT_ISSUE = 'election_ballot_issue';

    public const ACTION_ELECTION_BALLOT_VOID = 'election_ballot_void';

    public const ACTION_ELECTION_CLOSE_COUNT = 'election_close_count';

    public const ACTION_ELECTION_PUBLISH = 'election_publish';

    public const ACTION_ELECTION_COUNCIL_CREATE = 'election_council_create';

    public static function electionActions(): array
    {
        return [
            self::ACTION_ELECTION_CREATE, self::ACTION_ELECTION_UPDATE, self::ACTION_ELECTION_DELETE,
            self::ACTION_ELECTION_STATUS_CHANGE, self::ACTION_ELECTION_PARTY_APPLY,
            self::ACTION_ELECTION_PARTY_UPDATE, self::ACTION_ELECTION_PARTY_APPROVE,
            self::ACTION_ELECTION_PARTY_REJECT, self::ACTION_ELECTION_PARTY_WITHDRAW,
            self::ACTION_ELECTION_VOTER_ROLL_LOCK, self::ACTION_ELECTION_STATION_OPEN,
            self::ACTION_ELECTION_STATION_CLOSE, self::ACTION_ELECTION_BALLOT_ISSUE,
            self::ACTION_ELECTION_BALLOT_VOID, self::ACTION_ELECTION_CLOSE_COUNT,
            self::ACTION_ELECTION_PUBLISH, self::ACTION_ELECTION_COUNCIL_CREATE,
        ];
    }

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
        $roleDescriptions = [
            self::ACTION_ROLE_CREATE => 'สร้างบทบาทใหม่', self::ACTION_ROLE_UPDATE => 'แก้ไขบทบาท',
            self::ACTION_ROLE_DELETE => 'ลบบทบาท', self::ACTION_ROLE_ASSIGN => 'กำหนดบทบาทให้สมาชิก',
            self::ACTION_ROLE_BULK_ASSIGN => 'กำหนดบทบาทแบบกลุ่ม',
        ];
        if (isset($roleDescriptions[$action])) {
            return $roleDescriptions[$action];
        }
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
            self::ACTION_INVITE_LINK_CREATE => 'สร้างลิงก์เชิญ',
            self::ACTION_INVITE_LINK_UPDATE => 'แก้ไขลิงก์เชิญ',
            self::ACTION_INVITE_LINK_DELETE => 'ลบลิงก์เชิญ',
            self::ACTION_INVITE_LINK_TOGGLE => 'เปลี่ยนสถานะลิงก์เชิญ',
            self::ACTION_TAG_CREATE => 'สร้างแท็ก', self::ACTION_TAG_UPDATE => 'แก้ไขแท็ก', self::ACTION_TAG_DELETE => 'ลบแท็ก', self::ACTION_TAG_ASSIGN => 'เพิ่มแท็กให้สมาชิก', self::ACTION_TAG_REMOVE => 'นำแท็กออกจากสมาชิก',
        ];

        return $descriptions[$action] ?? $action;
    }

    /**
     * Get action icon
     */
    public function getIconAttribute(): string
    {
        $roleIcons = [self::ACTION_ROLE_CREATE => 'mdi:shield-plus', self::ACTION_ROLE_UPDATE => 'mdi:shield-edit', self::ACTION_ROLE_DELETE => 'mdi:shield-remove', self::ACTION_ROLE_ASSIGN => 'mdi:badge-account-outline', self::ACTION_ROLE_BULK_ASSIGN => 'mdi:account-group-outline'];
        if (isset($roleIcons[$this->action])) {
            return $roleIcons[$this->action];
        }
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
            self::ACTION_INVITE_LINK_CREATE => 'mdi:link-plus',
            self::ACTION_INVITE_LINK_UPDATE => 'mdi:link-edit',
            self::ACTION_INVITE_LINK_DELETE => 'mdi:link-off',
            self::ACTION_INVITE_LINK_TOGGLE => 'mdi:toggle-switch',
            self::ACTION_TAG_CREATE => 'mdi:tag-plus', self::ACTION_TAG_UPDATE => 'mdi:tag-edit', self::ACTION_TAG_DELETE => 'mdi:tag-remove', self::ACTION_TAG_ASSIGN => 'mdi:tag-plus', self::ACTION_TAG_REMOVE => 'mdi:tag-minus',
        ];

        return $icons[$this->action] ?? 'mdi:circle';
    }

    /**
     * Get action color
     */
    public function getColorAttribute(): string
    {
        $roleColors = [self::ACTION_ROLE_CREATE => 'green', self::ACTION_ROLE_UPDATE => 'purple', self::ACTION_ROLE_DELETE => 'red', self::ACTION_ROLE_ASSIGN => 'purple', self::ACTION_ROLE_BULK_ASSIGN => 'violet'];
        if (isset($roleColors[$this->action])) {
            return $roleColors[$this->action];
        }
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
            self::ACTION_INVITE_LINK_CREATE => 'green',
            self::ACTION_INVITE_LINK_UPDATE => 'purple',
            self::ACTION_INVITE_LINK_DELETE => 'red',
            self::ACTION_INVITE_LINK_TOGGLE => 'orange',
            self::ACTION_TAG_CREATE => 'green', self::ACTION_TAG_UPDATE => 'purple', self::ACTION_TAG_DELETE => 'red', self::ACTION_TAG_ASSIGN => 'blue', self::ACTION_TAG_REMOVE => 'orange',
        ];

        return $colors[$this->action] ?? 'gray';
    }
}
