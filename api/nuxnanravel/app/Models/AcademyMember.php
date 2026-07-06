<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        return $this->belongsTo(Student::class);
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
        if (! $this->academyRole) {
            return false;
        }

        return $this->academyRole->hasPermission($permission);
    }

    /**
     * Check if member has any of the given permissions
     */
    public function hasAnyPermission(array $permissions): bool
    {
        if (! $this->academyRole) {
            return false;
        }

        return $this->academyRole->hasAnyPermission($permissions);
    }

    /**
     * Check if member is an admin-level role
     */
    public function isAdmin(): bool
    {
        if (! $this->academyRole) {
            return false;
        }

        return $this->academyRole->isAdminRole();
    }

    /**
     * Check if member is a teacher
     */
    public function isTeacher(): bool
    {
        if (! $this->academyRole) {
            return false;
        }

        return $this->academyRole->isTeacherRole();
    }

    /**
     * Check if member is a student
     */
    public function isStudent(): bool
    {
        if (! $this->academyRole) {
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
        if (! $this->academyRole) {
            return false;
        }

        return $this->academyRole->isParentRole();
    }

    /**
     * Get tags assigned to this member
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(MemberTag::class, 'academy_member_tag')
            ->withPivot('assigned_by')
            ->withTimestamps();
    }

    public function getMemberNameAttribute()
    {
        // Priority: User name > Student Thai name > Student English name > Unknown
        if ($this->user_id && $this->relationLoaded('user') && $this->user) {
            return $this->user->name;
        }
        if ($this->student_id && $this->relationLoaded('student') && $this->student) {
            $name = trim($this->student->first_name_th.' '.$this->student->last_name_th);
            if (! empty($name)) {
                return $name;
            }

            $nameEn = trim($this->student->first_name_en.' '.$this->student->last_name_en);
            if (! empty($nameEn)) {
                return $nameEn;
            }
        }

        return 'สมาชิก #'.$this->id;
    }

    public function getMemberAvatarAttribute()
    {
        // Priority: User avatar > Student profile_image > UI Avatars
        if ($this->user_id && $this->relationLoaded('user') && $this->user && $this->user->profile_photo_url) {
            return $this->user->profile_photo_url;
        }
        if ($this->student_id && $this->student && $this->student->profile_image_url) {
            return $this->student->profile_image_url;
        }

        // Generate UI Avatar with initials
        $name = $this->member_name;
        $initials = $this->getInitials($name);

        return 'https://ui-avatars.com/api/?name='.urlencode($initials).'&color=FFFFFF&background=6366F1&font-size=0.4&bold=true';
    }

    /**
     * Get initials from name (supports Thai)
     */
    protected function getInitials(string $name): string
    {
        $words = explode(' ', trim($name));
        if (count($words) >= 2) {
            // Get first character of first and last word
            return mb_substr($words[0], 0, 1).mb_substr($words[count($words) - 1], 0, 1);
        }

        // Just use first 2 characters
        return mb_substr($name, 0, 2);
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
