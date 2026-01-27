<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CoursePermission extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_member_id',
        'permission',
        'granted_by',
    ];

    protected $casts = [
        'granted_at' => 'datetime',
    ];

    // Permission constants
    const PERMISSIONS = [
        // Member management
        'manage_members' => 'จัดการสมาชิก',
        'view_members' => 'ดูข้อมูลสมาชิก',
        'edit_member_details' => 'แก้ไขข้อมูลสมาชิก',
        'remove_members' => 'ลบสมาชิก',

        // Group management
        'manage_groups' => 'จัดการกลุ่ม',
        'create_groups' => 'สร้างกลุ่ม',
        'edit_groups' => 'แก้ไขกลุ่ม',
        'delete_groups' => 'ลบกลุ่ม',

        // Content management
        'manage_content' => 'จัดการเนื้อหา',
        'create_lessons' => 'สร้างบทเรียน',
        'edit_lessons' => 'แก้ไขบทเรียน',
        'delete_lessons' => 'ลบบทเรียน',

        // Assessment management
        'manage_assignments' => 'จัดการงาน',
        'create_assignments' => 'สร้างงาน',
        'grade_assignments' => 'ให้คะแนนงาน',
        'manage_quizzes' => 'จัดการแบบทดสอบ',
        'create_quizzes' => 'สร้างแบบทดสอบ',

        // Grade management
        'manage_grades' => 'จัดการผลการเรียน',
        'edit_grades' => 'แก้ไขผลการเรียน',
        'add_bonus_points' => 'เพิ่มคะแนนโบนัส',

        // Attendance management
        'manage_attendance' => 'จัดการการเข้าเรียน',
        'take_attendance' => 'บันทึกการเข้าเรียน',
        'view_attendance' => 'ดูการเข้าเรียน',

        // Reports and analytics
        'view_reports' => 'ดูรายงาน',
        'export_reports' => 'ส่งออกรายงาน',
        'view_analytics' => 'ดูสถิติ',

        // Course settings
        'manage_course_settings' => 'จัดการตั้งค่าหลักสูตร',
        'manage_admins' => 'จัดการผู้ดูแลระบบ',

        // Communication
        'send_announcements' => 'ส่งประกาศ',
        'manage_posts' => 'จัดการโพสต์',
    ];

    // Default permissions for each role
    const DEFAULT_PERMISSIONS = [
        'teacher' => [ // role 3
            'view_members',
            'edit_member_details',
            'manage_groups',
            'create_groups',
            'edit_groups',
            'manage_content',
            'create_lessons',
            'edit_lessons',
            'manage_assignments',
            'create_assignments',
            'grade_assignments',
            'manage_quizzes',
            'create_quizzes',
            'manage_grades',
            'edit_grades',
            'add_bonus_points',
            'manage_attendance',
            'take_attendance',
            'view_attendance',
            'view_reports',
            'send_announcements',
            'manage_posts',
        ],
        'admin' => [ // role 4
            'manage_members',
            'view_members',
            'edit_member_details',
            'remove_members',
            'manage_groups',
            'create_groups',
            'edit_groups',
            'delete_groups',
            'manage_content',
            'create_lessons',
            'edit_lessons',
            'delete_lessons',
            'manage_assignments',
            'create_assignments',
            'grade_assignments',
            'manage_quizzes',
            'create_quizzes',
            'manage_grades',
            'edit_grades',
            'add_bonus_points',
            'manage_attendance',
            'take_attendance',
            'view_attendance',
            'view_reports',
            'export_reports',
            'view_analytics',
            'manage_course_settings',
            'manage_admins',
            'send_announcements',
            'manage_posts',
        ],
    ];

    public function courseMember(): BelongsTo
    {
        return $this->belongsTo(CourseMember::class);
    }

    public function grantedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'granted_by');
    }

    /**
     * Check if a course member has a specific permission
     */
    public static function hasPermission(CourseMember $member, string $permission): bool
    {
        // Course owner has all permissions
        if ($member->course->user_id === $member->user_id) {
            return true;
        }

        return self::where('course_member_id', $member->id)
            ->where('permission', $permission)
            ->exists();
    }

    /**
     * Grant default permissions for a role
     */
    public static function grantDefaultPermissions(CourseMember $member): void
    {
        $roleName = $member->role === 3 ? 'teacher' : 'admin';
        $permissions = self::DEFAULT_PERMISSIONS[$roleName] ?? [];

        foreach ($permissions as $permission) {
            self::firstOrCreate([
                'course_member_id' => $member->id,
                'permission' => $permission,
            ], [
                'granted_by' => auth()->id(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Get all permissions for a course member
     */
    public static function getMemberPermissions(CourseMember $member): array
    {
        return self::where('course_member_id', $member->id)
            ->pluck('permission')
            ->toArray();
    }
}