import type { StudentCardRequestReason } from '~/types/studentCardRequest'

/**
 * สัญญา (type) ของการส่งคำร้องทำบัตร — ใช้ร่วมกันโดยโมดัลขอทำบัตร
 *
 * หมายเหตุ: เส้นทางสาธารณะเดิม (/api/student-card/{level}/{room}/requests) ถูก
 * ปลดระวางแล้ว 2026-10-11 · ตัวจัดการจริงถูกส่งเข้ามาเป็น prop จากหน้า academy
 * (ยิง /api/academies/{academy}/student-card-requests ผ่าน useStudentCardRequests)
 */
export interface PublicCardRequestPayload {
    student_id: number
    reason_code: StudentCardRequestReason
    reason?: string | null
    requester_name?: string | null
    requester_phone?: string | null
}

export interface PublicBulkCardRequestPayload {
    student_ids: number[]
    reason_code: StudentCardRequestReason
    reason?: string | null
    requester_name?: string | null
    requester_phone?: string | null
}

export interface PublicBulkCardRequestResult {
    success: boolean
    message: string
    results: { student_id: number; success: boolean; request_id?: number; message?: string }[]
}
