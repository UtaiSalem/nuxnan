<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * ClassroomMember Model - สมาชิกในห้องเรียน (multi-role)
 */
class ClassroomMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'classroom_id',
        'user_id',
        'role',
        'student_no',
        'join_method',
        'is_active',
        'joined_at',
        'left_at',
        'added_by',
    ];

    protected $casts = [
        'student_no' => 'integer',
        'is_active' => 'boolean',
        'joined_at' => 'datetime',
        'left_at' => 'datetime',
    ];

    // Role Constants
    const ROLE_TEACHER = 'teacher';

    const ROLE_CO_TEACHER = 'co_teacher';

    const ROLE_STUDENT = 'student';

    const ROLE_OBSERVER = 'observer';

    // Join Method Constants
    const JOIN_ADMIN = 'admin_assigned';

    const JOIN_INVITATION = 'invitation';

    const JOIN_CODE = 'classroom_code';

    // ───── Relationships ─────

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function addedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by');
    }

    public function groupMemberships(): HasMany
    {
        return $this->hasMany(GroupMember::class, 'member_id');
    }

    // ───── Scopes ─────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByRole($query, string $role)
    {
        return $query->where('role', $role);
    }

    public function scopeStudents($query)
    {
        return $query->where('role', self::ROLE_STUDENT);
    }

    public function scopeTeachers($query)
    {
        return $query->whereIn('role', [self::ROLE_TEACHER, self::ROLE_CO_TEACHER]);
    }

    // ───── Accessors ─────

    public function getRoleTextAttribute(): string
    {
        return match ($this->role) {
            self::ROLE_TEACHER => 'ครูประจำชั้น',
            self::ROLE_CO_TEACHER => 'ครูผู้ช่วย',
            self::ROLE_STUDENT => 'นักเรียน',
            self::ROLE_OBSERVER => 'ผู้สังเกตการณ์',
            default => 'ไม่ระบุ',
        };
    }

    /**
     * Check if member is a teacher or co-teacher.
     */
    public function isTeacher(): bool
    {
        return in_array($this->role, [self::ROLE_TEACHER, self::ROLE_CO_TEACHER]);
    }

    /**
     * Soft-remove: mark inactive and set left_at.
     */
    public function deactivate(): bool
    {
        return $this->update([
            'is_active' => false,
            'left_at' => now(),
        ]);
    }
}
