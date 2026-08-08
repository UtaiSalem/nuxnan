<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * นักเรียนหนึ่งคนในห้อง มองจาก classroom_students (source of truth) แล้วค่อยแนบบัตรถ้ามี
 *
 * ต่างจาก StudentCardResource ตรงที่ resource ตัวนั้นรับ StudentCard เป็นราก
 * จึงแสดงคนที่ยังไม่มีบัตรไม่ได้เลย ที่นี่ $this คือ ClassroomStudent
 *
 * ห้ามอ่านห้องจาก $card->class_level / class_section — เป็น snapshot ที่ค้างได้
 */
class RoomStudentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $student = $this->student;
        $classroom = $this->classroom;
        $card = $student?->activeCard;
        $birthDate = $student?->date_of_birth ?: $card?->birth_date;

        return [
            // key ของแถว — ต้องไม่เป็น null เพราะคนที่ยังไม่มีบัตรจะ key ชนกันหมด
            'uid' => 'cs-'.$this->id,
            // id = รหัสบัตร (null ได้) endpoint แก้บัตร/อัพโหลดรูป ใช้ค่านี้เป็น path param
            'id' => $card?->id,
            'has_card' => (bool) $card,

            'student_id' => $this->student_id,
            'academy_id' => $classroom?->academy_id,
            'student_number' => $student?->student_id,
            'national_id' => $student?->citizen_id,
            'title_name' => $student?->title_prefix_th,
            'first_name_thai' => $student?->first_name_th,
            'last_name_thai' => $student?->last_name_th,
            'full_name_thai' => trim("{$student?->title_prefix_th} {$student?->first_name_th} {$student?->last_name_th}"),
            'first_name_english' => $student?->first_name_en,
            'last_name_english' => $student?->last_name_en,
            'full_name_english' => trim($student?->first_name_en.' '.$student?->last_name_en),
            'birth_date' => $birthDate ? Carbon::parse($birthDate)->format('Y-m-d') : null,
            'birth_date_string' => $birthDate ? Carbon::parse($birthDate)->format('d/m/Y') : null,

            // ห้องมาจาก enrollment เสมอ
            'class_level' => $this->numericGradeLevel($classroom?->grade_level),
            'class_section' => (int) $classroom?->section,
            'level_and_room' => $this->numericGradeLevel($classroom?->grade_level).'/'.$classroom?->section,
            'order_no' => $this->student_number,

            'profile_image' => $student?->profile_image,
            'profile_image_url' => $student?->profile_image_url,

            // ฟิลด์ที่มีเฉพาะเมื่อมีบัตรจริง
            'card_issue_date' => $card?->card_issue_date,
            'card_expiry_date' => $card?->card_expiry_date,
            'student_status' => $card?->student_status,
            'has_physical_card' => (bool) $card?->card_issue_date,
            'qr_content' => ($classroom && $student) ? "STUDENT:{$classroom->academy_id}:{$student->student_id}" : null,
            'qr_url' => ($classroom && $student) ? url("/academies/{$classroom->academy_id}/members/{$student->student_id}") : null,

            // คำร้องผูกกับ "นักเรียน" ไม่ใช่บัตร ไม่งั้นคนที่ยังไม่มีบัตรจะส่งคำร้องซ้ำได้เรื่อย ๆ
            'active_card_request' => $student?->activeCardRequest ? [
                'id' => $student->activeCardRequest->id,
                'status' => $student->activeCardRequest->status?->value,
                'request_type' => $student->activeCardRequest->request_type?->value,
                'reason_code' => $student->activeCardRequest->reason_code,
                'requested_at' => $student->activeCardRequest->requested_at,
            ] : null,
        ];
    }

    private function numericGradeLevel(?string $grade): int
    {
        if (! $grade) {
            return 0;
        }
        if (! preg_match('/(\d+)\s*$/u', trim($grade), $matches)) {
            return 0;
        }

        return (int) $matches[1];
    }
}
