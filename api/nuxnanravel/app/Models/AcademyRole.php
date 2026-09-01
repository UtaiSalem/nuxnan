<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcademyRole extends Model
{
    use HasFactory;

    protected $fillable = [
        'academy_id',
        'name',
        'display_name_th',
        'display_name_en',
        'description',
        'permissions',
        'is_system',
        'is_active',
        'sort_order',
        'color',
        'icon',
    ];

    protected $casts = [
        'permissions' => 'array',
        'is_system' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Default system roles with their permissions
     */
    public const SYSTEM_ROLES = [
        'owner' => [
            'display_name_th' => 'เจ้าของโรงเรียน',
            'display_name_en' => 'Owner',
            'color' => 'purple',
            'icon' => 'fluent:crown-24-filled',
            'sort_order' => 1,
            'permissions' => ['*'], // All permissions
        ],
        'director' => [
            'display_name_th' => 'ผู้อำนวยการ',
            'display_name_en' => 'Director',
            'color' => 'indigo',
            'icon' => 'fluent:building-24-filled',
            'sort_order' => 2,
            'permissions' => [
                'academy.view',
                'members.view', 'members.manage', 'members.roles.manage',
                'courses.view', 'courses.create', 'courses.edit', 'courses.delete',
                'students.view', 'students.manage',
                'students.cards.produce',
                'teachers.view', 'teachers.manage',
                'finance.view', 'finance.reports',
                'reports.view', 'reports.export',
                'announcements.view', 'announcements.create', 'announcements.manage',
                'home_visits.view', 'home_visits.manage',
                'behavior.view', 'behavior.record', 'behavior.approve', 'behavior.manage',
                'roles.view', 'roles.manage', 'groups.view', 'groups.manage', 'schedule.view',
                'grades.view', 'grades.manage', 'staff.view', 'staff.manage',
                'settings.view', 'settings.manage', 'events.view', 'events.manage',
                'school_attendance.view', 'school_attendance.manage', 'courses.manage',
                'elections.view', 'elections.manage', 'elections.station',
                'guardians.view', 'guardians.manage', 'guardians.sensitive.view', 'guardians.sensitive.manage', 'guardians.appoint',
                'emergency.view', 'emergency.manage',
            ],
        ],
        'admin' => [
            'display_name_th' => 'ผู้ดูแลระบบ',
            'display_name_en' => 'Administrator',
            'color' => 'blue',
            'icon' => 'fluent:settings-24-filled',
            'sort_order' => 3,
            'permissions' => [
                'academy.view',
                'members.view', 'members.manage',
                'courses.view', 'courses.create', 'courses.edit',
                'students.view', 'students.manage',
                'students.cards.produce',
                'teachers.view', 'teachers.manage',
                'finance.view',
                'reports.view',
                'announcements.view', 'announcements.create', 'announcements.manage',
                'home_visits.view', 'home_visits.manage',
                'behavior.view', 'behavior.record', 'behavior.approve', 'behavior.manage',
                'roles.view', 'roles.manage', 'groups.view', 'groups.manage', 'schedule.view',
                'grades.view', 'grades.manage', 'staff.view', 'staff.manage',
                'settings.view', 'settings.manage', 'events.view', 'events.manage',
                'school_attendance.view', 'school_attendance.manage', 'courses.manage',
                'elections.view', 'elections.manage', 'elections.station',
                'guardians.view', 'guardians.manage', 'guardians.sensitive.view', 'guardians.sensitive.manage', 'guardians.appoint',
                'emergency.view', 'emergency.manage',
            ],
        ],
        'teacher' => [
            'display_name_th' => 'ครู/อาจารย์',
            'display_name_en' => 'Teacher',
            'color' => 'green',
            'icon' => 'fluent:person-board-24-filled',
            'sort_order' => 4,
            'permissions' => [
                'academy.view',
                'members.view',
                'courses.view', 'courses.create', 'courses.edit.own',
                'students.view',
                'students.cards.request',
                'attendance.manage', 'gradebook.manage',
                'announcements.view', 'announcements.create.own',
                'home_visits.view', 'home_visits.create',
                'behavior.view', 'behavior.record',
                'grades.view', 'schedule.view', 'groups.view', 'staff.view', 'events.view',
                'school_attendance.view', 'school_attendance.manage',
                'elections.view', 'elections.station',
            ],
        ],
        'registrar' => [
            'display_name_th' => 'นายทะเบียน',
            'display_name_en' => 'Registrar',
            'color' => 'teal',
            'icon' => 'fluent:clipboard-text-24-filled',
            'sort_order' => 5,
            'permissions' => [
                'academy.view',
                'members.view',
                'students.view', 'students.create', 'students.import',
                'students.lifecycle', 'students.activate_account', 'students.export',
                'reports.view',
                'announcements.view',
                'behavior.view',
                'groups.view', 'schedule.view', 'staff.view', 'events.view',
                'elections.view', 'guardians.view', 'guardians.manage', 'guardians.sensitive.view', 'guardians.sensitive.manage', 'guardians.appoint',
            ],
        ],
        'staff' => [
            'display_name_th' => 'เจ้าหน้าที่',
            'display_name_en' => 'Staff',
            'color' => 'cyan',
            'icon' => 'fluent:person-24-filled',
            'sort_order' => 6,
            'permissions' => [
                'academy.view',
                'members.view',
                'students.view',
                'announcements.view',
                'behavior.view',
                'schedule.view', 'events.view',
                'elections.view',
            ],
        ],
        'card_admin' => [
            'display_name_th' => 'เจ้าหน้าที่จัดทำบัตร',
            'display_name_en' => 'Card Administrator',
            'color' => 'sky',
            'icon' => 'fluent:card-ui-24-filled',
            'sort_order' => 7,
            'permissions' => [
                'academy.view',
                'members.view',
                'students.view', 'students.cards.produce',
            ],
        ],
        'finance_staff' => [
            'display_name_th' => 'เจ้าหน้าที่การเงิน',
            'display_name_en' => 'Finance Staff',
            'color' => 'amber',
            'icon' => 'fluent:money-24-filled',
            'sort_order' => 7,
            'permissions' => [
                'academy.view',
                'members.view',
                'students.view',
                'finance.view', 'finance.manage', 'finance.reports',
                'announcements.view',
                'events.view', 'settings.view',
                'elections.view',
            ],
        ],
        'student' => [
            'display_name_th' => 'นักเรียน/นักศึกษา',
            'display_name_en' => 'Student',
            'color' => 'emerald',
            'icon' => 'fluent:hat-graduation-24-filled',
            'sort_order' => 8,
            'permissions' => [
                'academy.view',
                'courses.view.enrolled',
                'assignments.view.own', 'assignments.submit',
                'grades.view.own',
                'schedule.view.own',
                'announcements.view',
                'behavior.view.own',
                'elections.view',
            ],
        ],
        'parent' => [
            'display_name_th' => 'ผู้ปกครอง',
            'display_name_en' => 'Parent/Guardian',
            'color' => 'rose',
            'icon' => 'fluent:people-24-filled',
            'sort_order' => 9,
            'permissions' => [
                'academy.view',
                'children.view',
                'children.grades.view',
                'children.attendance.view',
                'children.schedule.view',
                'payments.view', 'payments.pay',
                'announcements.view',
                'messages.teacher',
                'children.behavior.view',
            ],
        ],
        'guest' => [
            'display_name_th' => 'บุคคลทั่วไป',
            'display_name_en' => 'Guest',
            'color' => 'gray',
            'icon' => 'fluent:person-question-mark-24-regular',
            'sort_order' => 10,
            'permissions' => [
                'academy.view.public',
            ],
        ],
    ];

    /**
     * Academy relationship
     */
    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
    }

    /**
     * Members with this role
     */
    public function members(): HasMany
    {
        return $this->hasMany(AcademyMember::class, 'academy_role_id');
    }

    /**
     * Check if role has a specific permission
     */
    public function hasPermission(string $permission): bool
    {
        $permissions = $this->permissions ?? [];

        // Wildcard check (owner has all permissions)
        if (in_array('*', $permissions)) {
            return true;
        }

        // Direct permission check
        if (in_array($permission, $permissions)) {
            return true;
        }

        // Hierarchical permission check (e.g., 'courses.edit' grants 'courses.edit.own')
        foreach ($permissions as $perm) {
            if (str_starts_with($permission, $perm.'.')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if role has any of the given permissions
     */
    public function hasAnyPermission(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if role has all of the given permissions
     */
    public function hasAllPermissions(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (! $this->hasPermission($permission)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get display name based on locale
     */
    public function getDisplayNameAttribute(): string
    {
        $locale = app()->getLocale();
        if ($locale === 'th' || empty($this->display_name_en)) {
            return $this->display_name_th;
        }

        return $this->display_name_en;
    }

    /**
     * Scope for active roles
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for system roles
     */
    public function scopeSystem($query)
    {
        return $query->where('is_system', true);
    }

    /**
     * Scope for a specific academy (including system roles)
     */
    public function scopeForAcademy($query, $academyId)
    {
        return $query->where(function ($q) use ($academyId) {
            $q->whereNull('academy_id') // System roles
                ->orWhere('academy_id', $academyId); // Academy-specific roles
        });
    }

    /**
     * Check if this is an admin-level role
     */
    public function isAdminRole(): bool
    {
        return in_array($this->name, ['owner', 'director', 'admin']);
    }

    /**
     * Check if this is a teaching role
     */
    public function isTeacherRole(): bool
    {
        return $this->name === 'teacher';
    }

    /**
     * Check if this is a student role
     */
    public function isStudentRole(): bool
    {
        return $this->name === 'student';
    }

    /**
     * Check if this is a parent role
     */
    public function isParentRole(): bool
    {
        return $this->name === 'parent';
    }
}
