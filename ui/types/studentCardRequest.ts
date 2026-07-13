export type StudentCardRequestStatus = 'pending' | 'approved' | 'rejected' | 'in_progress' | 'completed' | 'cancelled'
export type StudentCardRequestType = 'first_issue' | 'replacement' | 'renewal'
export type StudentCardRequestReason = 'lost' | 'damaged' | 'expired' | 'name_changed' | 'photo_outdated' | 'new_student' | 'other'

/** ตัวเลือกเหตุผลใน dropdown — ต้องตรงกับ App\Enums\StudentCardRequestReason ฝั่ง Laravel */
export const STUDENT_CARD_REQUEST_REASONS: { value: StudentCardRequestReason; label: string }[] = [
  { value: 'lost', label: 'บัตรหาย' },
  { value: 'damaged', label: 'บัตรชำรุด' },
  { value: 'expired', label: 'บัตรหมดอายุ' },
  { value: 'name_changed', label: 'เปลี่ยนชื่อ–สกุล' },
  { value: 'photo_outdated', label: 'รูปถ่ายไม่เป็นปัจจุบัน' },
  { value: 'new_student', label: 'นักเรียนใหม่ยังไม่มีบัตร' },
  { value: 'other', label: 'อื่นๆ (ระบุรายละเอียด)' },
]

/** สรุปคำร้องที่ยังค้างอยู่ของนักเรียน แนบมากับรายชื่อนักเรียนในห้อง */
export interface ActiveCardRequestSummary {
  id: number
  status: StudentCardRequestStatus
  request_type: StudentCardRequestType
  reason_code: StudentCardRequestReason | null
  requested_at: string | null
}

export interface HomeroomTeacher {
  id: number
  name: string
  profile_image_url: string | null
}

export interface ClassroomSummary {
  id: number
  name: string
  grade_level: string
  section: string
  academic_year: { id: number, name: string } | null
  student_count: number | null
  homeroom_teacher: HomeroomTeacher | null
}

export interface StudentCardRequest {
  id: number
  student_id: number | null
  classroom_id: number | null
  request_type: StudentCardRequestType
  status: StudentCardRequestStatus
  priority: 'normal' | 'urgent'
  full_name: string
  student_number: string | null
  grade_level: string | null
  section: string | null
  reason: string | null
  reason_code: StudentCardRequestReason | null
  reason_label: string | null
  requester_name: string | null
  requester_phone: string | null
  admin_notes: string | null
  rejection_reason: string | null
  result_card_id: number | null
  requested_at: string | null
  classroom?: ClassroomSummary | null
}
