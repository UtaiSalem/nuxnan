<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AcademyPermission extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'display_name',
        'group',
        'description',
    ];

    /** Permission families that departments may delegate. New families are denied by default. */
    private const DEPARTMENT_DELEGABLE_FAMILIES = [
        'students', 'behavior', 'home_visits', 'attendance', 'school_attendance',
        'grades', 'gradebook', 'schedule', 'courses', 'assignments', 'announcements',
        'events', 'reports', 'messages', 'teachers', 'staff', 'children', 'elections', 'sports',
    ];

    /**
     * roles.* lets department members self-escalate privileges, breaking the permission system.
     * groups.manage lets a department edit group permissions and indirectly escalate privileges.
     * members.manage and related members actions add/remove or re-role school members (admin-only).
     * settings.* and academy.* change school/institution settings.
     * finance.* and payments.* control money.
     */
    public static function departmentDelegableKeys(): array
    {
        $keys = array_values(array_filter(
            array_column(self::getAllPermissions(), 'name'),
            static fn (string $key): bool => in_array(strtok($key, '.'), self::DEPARTMENT_DELEGABLE_FAMILIES, true)
        ));

        return array_values(array_unique(array_merge($keys, ['groups.view', 'members.view'])));
    }

    public static function nonDelegableDepartmentKeys(array $keys): array
    {
        return array_values(array_diff($keys, self::departmentDelegableKeys()));
    }

    /**
     * All available academy-level permissions grouped by category
     */
    public const PERMISSIONS = [
        'roles' => [
            ['name' => 'roles.view', 'display_name' => 'ดูบทบาท'],
            ['name' => 'roles.manage', 'display_name' => 'จัดการบทบาท: สร้าง/แก้ไข/ลบ'],
        ],
        'groups' => [
            ['name' => 'groups.view', 'display_name' => 'ดูกลุ่มเรียน/ฝ่าย/แผนก'],
            ['name' => 'groups.manage', 'display_name' => 'จัดการกลุ่มเรียน/ฝ่าย/แผนก'],
        ],
        'academy' => [
            ['name' => 'academy.view', 'display_name' => 'ดูข้อมูลโรงเรียน'],
            ['name' => 'academy.view.public', 'display_name' => 'ดูข้อมูลสาธารณะของโรงเรียน'],
            ['name' => 'academy.settings.view', 'display_name' => 'ดูการตั้งค่าโรงเรียน'],
            ['name' => 'academy.settings.edit', 'display_name' => 'แก้ไขการตั้งค่าโรงเรียน'],
        ],
        'members' => [
            ['name' => 'members.view', 'display_name' => 'ดูรายชื่อสมาชิก'],
            ['name' => 'members.manage', 'display_name' => 'จัดการสมาชิก (เพิ่ม/ลบ/แก้ไข)'],
            ['name' => 'members.invite', 'display_name' => 'เชิญสมาชิกใหม่'],
            ['name' => 'members.roles.manage', 'display_name' => 'จัดการบทบาทสมาชิก'],
        ],
        'courses' => [
            ['name' => 'courses.manage', 'display_name' => 'จัดการรายวิชา'],
            ['name' => 'courses.view', 'display_name' => 'ดูรายวิชาทั้งหมด'],
            ['name' => 'courses.view.enrolled', 'display_name' => 'ดูรายวิชาที่ลงทะเบียน'],
            ['name' => 'courses.create', 'display_name' => 'สร้างรายวิชา'],
            ['name' => 'courses.edit', 'display_name' => 'แก้ไขรายวิชาทั้งหมด'],
            ['name' => 'courses.edit.own', 'display_name' => 'แก้ไขรายวิชาของตนเอง'],
            ['name' => 'courses.delete', 'display_name' => 'ลบรายวิชา'],
        ],
        'students' => [
            ['name' => 'students.view', 'display_name' => 'ดูข้อมูลนักเรียน'],
            ['name' => 'students.manage', 'display_name' => 'จัดการข้อมูลนักเรียน'],
            ['name' => 'students.create', 'display_name' => 'เพิ่มนักเรียน'],
            ['name' => 'students.import', 'display_name' => 'นำเข้าข้อมูลนักเรียน'],
            ['name' => 'students.lifecycle', 'display_name' => 'จัดการสถานะนักเรียน'],
            ['name' => 'students.activate_account', 'display_name' => 'เปิดใช้งานบัญชีนักเรียน'],
            ['name' => 'students.export', 'display_name' => 'ส่งออกข้อมูลนักเรียน'],
            ['name' => 'students.delete', 'display_name' => 'ลบนักเรียน'],
            ['name' => 'students.cards.request', 'display_name' => 'ส่งคำร้องขอทำบัตรนักเรียน'],
            ['name' => 'students.cards.produce', 'display_name' => 'อนุมัติและจัดทำบัตรนักเรียน'],
        ],
        'teachers' => [
            ['name' => 'teachers.view', 'display_name' => 'ดูข้อมูลครู'],
            ['name' => 'teachers.manage', 'display_name' => 'จัดการข้อมูลครู'],
        ],
        'attendance' => [
            ['name' => 'attendance.view', 'display_name' => 'ดูการเช็คชื่อ'],
            ['name' => 'attendance.manage', 'display_name' => 'เช็คชื่อนักเรียน'],
        ],
        'gradebook' => [
            ['name' => 'gradebook.view', 'display_name' => 'ดูคะแนน'],
            ['name' => 'gradebook.manage', 'display_name' => 'จัดการคะแนน'],
        ],
        'grades' => [
            ['name' => 'grades.view', 'display_name' => 'ดูผลการเรียนของโรงเรียน'],
            ['name' => 'grades.manage', 'display_name' => 'จัดการผลการเรียน'],
            ['name' => 'grades.view.own', 'display_name' => 'ดูผลการเรียนของตนเอง'],
            ['name' => 'grades.view.all', 'display_name' => 'ดูผลการเรียนทั้งหมด'],
        ],
        'assignments' => [
            ['name' => 'assignments.view.own', 'display_name' => 'ดูงานที่ได้รับมอบหมาย'],
            ['name' => 'assignments.submit', 'display_name' => 'ส่งงาน'],
            ['name' => 'assignments.manage', 'display_name' => 'จัดการงานที่มอบหมาย'],
        ],
        'schedule' => [
            ['name' => 'schedule.view', 'display_name' => 'ดูตารางเรียนของโรงเรียน'],
            ['name' => 'schedule.view.own', 'display_name' => 'ดูตารางเรียนของตนเอง'],
            ['name' => 'schedule.view.all', 'display_name' => 'ดูตารางเรียนทั้งหมด'],
            ['name' => 'schedule.manage', 'display_name' => 'จัดการตารางเรียน'],
        ],
        'finance' => [
            ['name' => 'finance.view', 'display_name' => 'ดูข้อมูลการเงิน'],
            ['name' => 'finance.manage', 'display_name' => 'จัดการการเงิน'],
            ['name' => 'finance.reports', 'display_name' => 'ดูรายงานการเงิน'],
        ],
        'payments' => [
            ['name' => 'payments.view', 'display_name' => 'ดูประวัติการชำระเงิน'],
            ['name' => 'payments.pay', 'display_name' => 'ชำระเงิน'],
        ],
        'reports' => [
            ['name' => 'reports.view', 'display_name' => 'ดูรายงาน'],
            ['name' => 'reports.export', 'display_name' => 'ส่งออกรายงาน'],
            ['name' => 'reports.manage', 'display_name' => 'จัดการรายงาน'],
        ],
        'announcements' => [
            ['name' => 'announcements.view', 'display_name' => 'ดูประกาศ'],
            ['name' => 'announcements.create', 'display_name' => 'สร้างประกาศ'],
            ['name' => 'announcements.create.own', 'display_name' => 'สร้างประกาศสำหรับคลาสของตน'],
            ['name' => 'announcements.manage', 'display_name' => 'จัดการประกาศทั้งหมด'],
        ],
        'home_visits' => [
            ['name' => 'home_visits.view', 'display_name' => 'ดูข้อมูลเยี่ยมบ้าน'],
            ['name' => 'home_visits.create', 'display_name' => 'สร้างรายการเยี่ยมบ้าน'],
            ['name' => 'home_visits.manage', 'display_name' => 'จัดการเยี่ยมบ้าน'],
        ],
        'messages' => [
            ['name' => 'messages.view', 'display_name' => 'ดูข้อความ'],
            ['name' => 'messages.send', 'display_name' => 'ส่งข้อความ'],
            ['name' => 'messages.teacher', 'display_name' => 'ส่งข้อความหาครู'],
        ],
        'children' => [
            ['name' => 'children.behavior.view', 'display_name' => 'ดูพฤติกรรมบุตรหลาน'],
            ['name' => 'children.view', 'display_name' => 'ดูข้อมูลบุตรหลาน'],
            ['name' => 'children.grades.view', 'display_name' => 'ดูผลการเรียนบุตรหลาน'],
            ['name' => 'children.attendance.view', 'display_name' => 'ดูการเข้าเรียนบุตรหลาน'],
            ['name' => 'children.schedule.view', 'display_name' => 'ดูตารางเรียนบุตรหลาน'],
        ],
        'staff' => [
            ['name' => 'staff.view', 'display_name' => 'ดูบุคลากร'],
            ['name' => 'staff.manage', 'display_name' => 'จัดการบุคลากร'],
        ],
        'settings' => [
            ['name' => 'settings.view', 'display_name' => 'ดูการตั้งค่า'],
            ['name' => 'settings.manage', 'display_name' => 'จัดการการตั้งค่า'],
        ],
        'behavior' => [
            ['name' => 'behavior.view', 'display_name' => 'ดูพฤติกรรม'],
            ['name' => 'behavior.record', 'display_name' => 'บันทึกพฤติกรรม'],
            ['name' => 'behavior.approve', 'display_name' => 'อนุมัติพฤติกรรม'],
            ['name' => 'behavior.manage', 'display_name' => 'จัดการพฤติกรรม'],
            ['name' => 'behavior.view.own', 'display_name' => 'ดูพฤติกรรมของตนเอง'],
        ],
        'events' => [
            ['name' => 'events.view', 'display_name' => 'ดูกิจกรรม'],
            ['name' => 'events.manage', 'display_name' => 'จัดการกิจกรรม'],
        ],
        'elections' => [
            ['name' => 'elections.view', 'display_name' => 'ดูการเลือกตั้งและผลคะแนน'],
            ['name' => 'elections.manage', 'display_name' => 'จัดการการเลือกตั้ง: สร้าง/รับสมัคร/อนุมัติพรรค/เปิด-ปิดหีบ/ประกาศผล'],
            ['name' => 'elections.station', 'display_name' => 'ประจำหน่วยเลือกตั้ง: เปิดหน่วยและออกบัตรเลือกตั้ง'],
        ],
        'sports' => [
            ['name' => 'sports.view', 'display_name' => 'ดูคณะสีและคะแนนกีฬาสี'],
            ['name' => 'sports.manage', 'display_name' => 'จัดการกีฬาสี: คณะสี/การจัดกลุ่มนักเรียน/บันทึกผล'],
        ],
        'school_attendance' => [
            ['name' => 'school_attendance.view', 'display_name' => 'ดูการเข้าเรียนของโรงเรียน'],
            ['name' => 'school_attendance.manage', 'display_name' => 'จัดการการเข้าเรียนของโรงเรียน'],
        ],
    ];

    /**
     * Get all permissions as flat array
     */
    public static function getAllPermissions(): array
    {
        $all = [];
        foreach (self::PERMISSIONS as $group => $permissions) {
            foreach ($permissions as $permission) {
                $all[] = array_merge($permission, ['group' => $group]);
            }
        }

        return $all;
    }

    /**
     * Get permissions grouped
     */
    public static function getGroupedPermissions(): array
    {
        return self::PERMISSIONS;
    }
}
