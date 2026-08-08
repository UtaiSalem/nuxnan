<?php

namespace App\Observers;

use App\Models\ClassroomStudent;
use App\Models\StudentCard;

class ClassroomStudentObserver
{
    /**
     * Handle the ClassroomStudent "created" event.
     *
     * Re-enrolling a student whose card was expired by a previous
     * close (transfer/remove) must bring the card back, otherwise the
     * student disappears from every room endpoint.
     */
    public function created(ClassroomStudent $classroomStudent): void
    {
        if ($classroomStudent->status === ClassroomStudent::STATUS_ACTIVE) {
            $this->reactivateCard($classroomStudent->student_id, $classroomStudent->academy_id);
        }
    }

    /**
     * Handle the ClassroomStudent "updated" event.
     */
    public function updated(ClassroomStudent $classroomStudent): void
    {
        if ($classroomStudent->isDirty('status')) {
            $status = $classroomStudent->status;

            if ($status === 'graduated') {
                $this->cardsOf($classroomStudent->student_id, $classroomStudent->academy_id)
                    ->where('student_status', 'active')
                    ->update(['student_status' => 'graduated']);
            } elseif ($status === ClassroomStudent::STATUS_ACTIVE) {
                $this->reactivateCard($classroomStudent->student_id, $classroomStudent->academy_id);
            } else {
                // ปิดห้องหนึ่งไม่ได้แปลว่านักเรียนออกจากโรงเรียน — ถ้ายังมีห้อง active อยู่แถวอื่น
                // บัตรต้องอยู่ต่อ ไม่งั้นโฟลว์ที่ "เปิดแถวใหม่ก่อนปิดแถวเก่า" จะดับบัตรที่เพิ่งปลุก
                if ($this->hasOtherActiveEnrollment($classroomStudent)) {
                    return;
                }

                $this->cardsOf($classroomStudent->student_id, $classroomStudent->academy_id)
                    ->where('student_status', 'active')
                    ->update(['student_status' => 'expired']);
            }
        }
    }

    /**
     * บัตรของนักเรียนคนนี้เฉพาะใน academy ที่เกี่ยวข้อง — ห้องของโรงเรียนหนึ่ง
     * ต้องไม่ไปแตะบัตรที่นักเรียนถืออยู่ในอีกโรงเรียนหนึ่ง
     */
    private function cardsOf(int $studentId, ?int $academyId)
    {
        return StudentCard::where('student_id', $studentId)
            ->when($academyId !== null, fn ($query) => $query->where('academy_id', $academyId));
    }

    /**
     * ปลุกบัตรที่หมดอายุกลับมาใบเดียวเท่านั้น
     *
     * uq_student_card_active (student_id, academy_id, is_active_flag) ยอมให้มีบัตร
     * active ได้ใบเดียวต่อ academy การ update ทั้งชุดจึงชนกันเองเมื่อนักเรียนมีบัตร
     * expired หลายใบ (เก็บไว้ใบละปีการศึกษา) และชนกับใบที่ active อยู่แล้ว
     */
    private function reactivateCard(int $studentId, ?int $academyId): void
    {
        if ($this->cardsOf($studentId, $academyId)->where('student_status', 'active')->exists()) {
            return;
        }

        $card = $this->cardsOf($studentId, $academyId)
            ->where('student_status', 'expired')
            ->orderByDesc('academic_year_id')
            ->orderByDesc('card_issue_date')
            ->orderByDesc('id')
            ->first();

        $card?->update(['student_status' => 'active']);
    }

    /**
     * นักเรียนคนนี้ยังมีห้องเรียน active อยู่แถวอื่นไหม (ไม่นับแถวที่กำลังถูกปิดอยู่)
     *
     * academy_id บน classroom_students เป็น nullable — ถ้าเป็น null จะไม่กรองตามโรงเรียน
     * ซึ่งเป็นฝั่งที่ปลอดภัยกว่า (เลือกไม่ expire ดีกว่าเผลอ expire)
     */
    private function hasOtherActiveEnrollment(ClassroomStudent $classroomStudent): bool
    {
        return ClassroomStudent::query()
            ->where('student_id', $classroomStudent->student_id)
            ->whereKeyNot($classroomStudent->getKey())
            ->where('status', ClassroomStudent::STATUS_ACTIVE)
            ->when(
                $classroomStudent->academy_id !== null,
                fn ($query) => $query->where('academy_id', $classroomStudent->academy_id)
            )
            ->exists();
    }
}
