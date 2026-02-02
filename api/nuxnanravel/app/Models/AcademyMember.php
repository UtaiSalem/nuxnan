<?php

namespace App\Models;


use App\Models\User;
use App\Models\Academy;
use App\Models\AcademyRole;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AcademyMember extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Student::class);
    }

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    /**
     * Get the role assigned to this member
     */
    public function academyRole(): BelongsTo
    {
        return $this->belongsTo(AcademyRole::class, 'academy_role_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function academies(): HasMany
    {
        return $this->hasMany(Academy::class, 'id');
    }

    /**
     * Check if member has a specific permission
     */
    public function hasPermission(string $permission): bool
    {
        if (!$this->academyRole) {
            return false;
        }
        return $this->academyRole->hasPermission($permission);
    }

    /**
     * Check if member has any of the given permissions
     */
    public function hasAnyPermission(array $permissions): bool
    {
        if (!$this->academyRole) {
            return false;
        }
        return $this->academyRole->hasAnyPermission($permissions);
    }

    /**
     * Check if member is an admin-level role
     */
    public function isAdmin(): bool
    {
        if (!$this->academyRole) {
            return false;
        }
        return $this->academyRole->isAdminRole();
    }

    /**
     * Check if member is a teacher
     */
    public function isTeacher(): bool
    {
        if (!$this->academyRole) {
            return false;
        }
        return $this->academyRole->isTeacherRole();
    }

    /**
     * Check if member is a student
     */
    public function isStudent(): bool
    {
        if (!$this->academyRole) {
            // Default to student if no role assigned
            return true;
        }
        return $this->academyRole->isStudentRole();
    }

    /**
     * Check if member is a parent
     */
    public function isParent(): bool
    {
        if (!$this->academyRole) {
            return false;
        }
        return $this->academyRole->isParentRole();
    }

    public function getMemberNameAttribute()
    {
        if ($this->user_id && $this->user) {
            return $this->user->name;
        }
        if ($this->student_id && $this->student) {
            return $this->student->first_name_th . ' ' . $this->student->last_name_th;
        }
        return 'Unknown Member';
    }

    public function getMemberAvatarAttribute()
    {
        if ($this->user_id && $this->user) {
            return $this->user->profile_photo_url;
        }
        if ($this->student_id && $this->student && $this->student->profile_image) {
            return '/storage/images/students/profiles/' . $this->student->profile_image;
        }
        return 'https://ui-avatars.com/api/?name=' . urlencode($this->member_name) . '&color=7F9CF5&background=EBF4FF';
    }

    /**
     * Get role display name
     */
    public function getRoleDisplayNameAttribute(): string
    {
        if ($this->academyRole) {
            return $this->academyRole->display_name;
        }
        // Fallback to legacy role column
        if ($this->role) {
            $roleNames = [
                'owner' => 'เจ้าของ',
                'admin' => 'ผู้ดูแล',
                'teacher' => 'ครู',
                'student' => 'นักเรียน',
                'parent' => 'ผู้ปกครอง',
            ];
            return $roleNames[$this->role] ?? $this->role;
        }
        return 'สมาชิก';
    }
}
