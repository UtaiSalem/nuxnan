<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    /**
     * Disable timestamps as we only use created_at
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'user_id',
        'action',
        'entity_type',
        'entity_id',
        'module',
        'old_values',
        'new_values',
        'metadata',
        'ip_address',
        'user_agent',
        'url',
        'method',
        'status_code',
        'created_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * Action constants
     */
    public const ACTION_CREATED = 'created';

    public const ACTION_UPDATED = 'updated';

    public const ACTION_DELETED = 'deleted';

    public const ACTION_VIEWED = 'viewed';

    public const ACTION_LOGIN = 'login';

    public const ACTION_LOGOUT = 'logout';

    public const ACTION_LOGIN_FAILED = 'login_failed';

    public const ACTION_PASSWORD_CHANGED = 'password_changed';

    public const ACTION_PASSWORD_RESET = 'password_reset';

    public const ACTION_EXPORTED = 'exported';

    public const ACTION_IMPORTED = 'imported';

    public const ACTION_APPROVED = 'approved';

    public const ACTION_REJECTED = 'rejected';

    /**
     * Get the user who performed this action.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the entity being audited.
     */
    public function entity()
    {
        if ($this->entity_type && $this->entity_id) {
            return $this->entity_type::find($this->entity_id);
        }

        return null;
    }

    /**
     * Scope to filter by user.
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to filter by action.
     */
    public function scopeByAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope to filter by module.
     */
    public function scopeByModule($query, string $module)
    {
        return $query->where('module', $module);
    }

    /**
     * Scope to filter by entity.
     */
    public function scopeByEntity($query, string $entityType, ?int $entityId = null)
    {
        $query->where('entity_type', $entityType);
        if ($entityId) {
            $query->where('entity_id', $entityId);
        }

        return $query;
    }

    /**
     * Scope to filter by date range.
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Scope to get recent logs.
     */
    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Get a human-readable description of the action.
     */
    public function getDescriptionAttribute(): string
    {
        $entityName = $this->entity_type ? class_basename($this->entity_type) : 'Item';

        $descriptions = [
            self::ACTION_CREATED => "สร้าง {$entityName} ใหม่",
            self::ACTION_UPDATED => "แก้ไข {$entityName}",
            self::ACTION_DELETED => "ลบ {$entityName}",
            self::ACTION_VIEWED => "ดู {$entityName}",
            self::ACTION_LOGIN => 'เข้าสู่ระบบ',
            self::ACTION_LOGOUT => 'ออกจากระบบ',
            self::ACTION_LOGIN_FAILED => 'เข้าสู่ระบบล้มเหลว',
            self::ACTION_PASSWORD_CHANGED => 'เปลี่ยนรหัสผ่าน',
            self::ACTION_PASSWORD_RESET => 'รีเซ็ตรหัสผ่าน',
            self::ACTION_EXPORTED => 'ส่งออกข้อมูล',
            self::ACTION_IMPORTED => 'นำเข้าข้อมูล',
            self::ACTION_APPROVED => "อนุมัติ {$entityName}",
            self::ACTION_REJECTED => "ปฏิเสธ {$entityName}",
        ];

        return $descriptions[$this->action] ?? $this->action;
    }

    /**
     * Get the changed fields between old and new values.
     */
    public function getChangedFieldsAttribute(): array
    {
        if (! $this->old_values || ! $this->new_values) {
            return [];
        }

        $changes = [];
        foreach ($this->new_values as $key => $newValue) {
            $oldValue = $this->old_values[$key] ?? null;
            if ($oldValue !== $newValue) {
                $changes[$key] = [
                    'old' => $oldValue,
                    'new' => $newValue,
                ];
            }
        }

        return $changes;
    }
}
