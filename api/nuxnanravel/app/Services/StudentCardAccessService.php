<?php

namespace App\Services;

use App\Models\Academy;
use App\Models\AcademyMember;
use App\Models\Classroom;
use App\Models\ClassroomStudent;
use App\Models\StudentCard;
use App\Models\User;

/**
 * ตัดสินว่าใครแก้ข้อมูลบัตรนักเรียน/รายชื่อในห้องได้บ้าง
 *
 * มีสองระดับ:
 *  - ระดับโรงเรียน = แอดมิน หรือผู้มีสิทธิ์ students.manage / students.cards.produce
 *  - ระดับห้อง     = ครูประจำชั้นของห้องนั้นในปีการศึกษาปัจจุบัน (แก้ได้เฉพาะห้องตัวเอง)
 *
 * รูปเดียวกับ StudentCardRequestController::ensureHomeroom() ที่ระบบคำร้องใช้อยู่แล้ว
 * ลำดับการตรวจล้อ CheckAcademyPermission ทุกขั้น (superadmin → academy admin →
 * academy role → group grant) เพื่อไม่ให้เกิดกรณีที่ผ่าน middleware แล้วมาตกที่นี่
 */
class StudentCardAccessService
{
    /**
     * สิทธิ์ที่ถือว่าจัดการบัตรได้ทั้งโรงเรียน
     */
    public const MANAGE_PERMISSIONS = ['students.manage', 'students.cards.produce'];

    public function __construct(private readonly AcademyGroupPermissionAccessService $groupPermissionAccess) {}

    /**
     * จัดการบัตรของนักเรียนคนไหนก็ได้ในโรงเรียนนี้
     */
    public function canManageAny(Academy $academy, ?User $user): bool
    {
        if (! $user) {
            return false;
        }

        if ($user->isSuperAdmin() || $academy->isAdmin($user)) {
            return true;
        }

        $member = AcademyMember::where('user_id', $user->id)
            ->where('academy_id', $academy->id)
            ->where('status', AcademyMember::STATUS_APPROVED)
            ->first();

        if (! $member) {
            return false;
        }

        if ($member->academyRole?->hasAnyPermission(self::MANAGE_PERMISSIONS)) {
            return true;
        }

        return $this->groupPermissionAccess->hasAnyPermission($user, $academy, self::MANAGE_PERMISSIONS);
    }

    /**
     * จัดการห้องนี้ได้ไหม — ผู้จัดการระดับโรงเรียน หรือครูประจำชั้นของห้องนี้เอง
     */
    public function canManageClassroom(Academy $academy, Classroom $classroom, ?User $user): bool
    {
        if (! $user || (int) $classroom->academy_id !== (int) $academy->id) {
            return false;
        }

        if ($this->canManageAny($academy, $user)) {
            return true;
        }

        return $classroom->homeroom_teacher_id !== null
            && (int) $classroom->homeroom_teacher_id === (int) $user->id;
    }

    /**
     * จัดการบัตรใบนี้ได้ไหม
     *
     * ครูประจำชั้นผ่านได้ก็ต่อเมื่อนักเรียนเจ้าของบัตร "กำลังเรียนอยู่" (enrollment
     * สถานะ active) ในห้องที่ตัวเองเป็นครูประจำชั้นของปีการศึกษาปัจจุบัน
     *
     * การเช็ค student_id ว่างเป็นแนวกันไว้เฉย ๆ — ปัจจุบัน student_cards.student_id
     * เป็น NOT NULL จึงไม่มีบัตรที่ไม่ผูกกับ student master ในระบบ
     */
    public function canManageCard(Academy $academy, StudentCard $card, ?User $user): bool
    {
        if (! $user || (int) $card->academy_id !== (int) $academy->id) {
            return false;
        }

        if ($this->canManageAny($academy, $user)) {
            return true;
        }

        if (! $card->student_id) {
            return false;
        }

        return ClassroomStudent::query()
            ->where('student_id', $card->student_id)
            ->where('status', ClassroomStudent::STATUS_ACTIVE)
            ->whereHas('classroom', function ($query) use ($academy, $user) {
                $query->where('academy_id', $academy->id)
                    ->where('homeroom_teacher_id', $user->id)
                    ->whereHas('academicYear', fn ($year) => $year->where('is_current', true));
            })
            ->exists();
    }

    public function ensureCanManageCard(Academy $academy, StudentCard $card, ?User $user): void
    {
        abort_unless(
            $this->canManageCard($academy, $card, $user),
            403,
            'คุณไม่มีสิทธิ์แก้ไขบัตรของนักเรียนคนนี้ (ครูประจำชั้นแก้ได้เฉพาะห้องของตนเอง)'
        );
    }

    public function ensureCanManageClassroom(Academy $academy, Classroom $classroom, ?User $user): void
    {
        abort_unless(
            $this->canManageClassroom($academy, $classroom, $user),
            403,
            'คุณไม่มีสิทธิ์จัดการรายชื่อนักเรียนของห้องนี้ (ครูประจำชั้นจัดการได้เฉพาะห้องของตนเอง)'
        );
    }
}
