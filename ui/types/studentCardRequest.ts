export type StudentCardRequestStatus = 'pending' | 'approved' | 'rejected' | 'in_progress' | 'completed' | 'cancelled'
export type StudentCardRequestType = 'first_issue' | 'replacement' | 'renewal'

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
  admin_notes: string | null
  rejection_reason: string | null
  result_card_id: number | null
  requested_at: string | null
}
