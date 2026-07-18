export type CoursePointWithdrawalStatus = 'pending' | 'reviewing' | 'approved' | 'paid' | 'rejected' | 'cancelled'
export interface CoursePointWithdrawal {
  id: number; course_id: number; course?: { name: string }; amount: number; purpose: string | null
  status: CoursePointWithdrawalStatus; requested_at: string; requester: { name: string }; reviewer?: { name: string } | null
  reviewed_at?: string | null; approver?: { name: string } | null; approved_at?: string | null; payer?: { name: string } | null
  paid_at?: string | null; payment_reference?: string | null; admin_note?: string | null; rejection_reason?: string | null
  has_proof: boolean; version: number
}
type Params = Record<string, string | number | undefined>
const query = (p?: Params) => p ? `?${new URLSearchParams(Object.entries(p).filter(([, v]) => v !== undefined).map(([k, v]) => [k, String(v)]))}` : ''
const idem = (key?: string) => key || (typeof crypto !== 'undefined' && crypto.randomUUID ? crypto.randomUUID() : `${Date.now()}-${Math.random()}`)
export const useCoursePointWithdrawals = () => {
  const api = useApi()
  const createRequest = (courseId: number, body: { amount: number; purpose?: string }, idempotencyKey?: string) => api.post<{ data: CoursePointWithdrawal }>(`/api/courses/${courseId}/withdrawals`, body, { headers: { 'Idempotency-Key': idem(idempotencyKey) } })
  const fetchCourseHistory = (courseId: number, params?: Params) => api.get<{ data: CoursePointWithdrawal[]; meta: any }>(`/api/courses/${courseId}/withdrawals${query(params)}`)
  const cancel = (id: number) => api.post<{ data: CoursePointWithdrawal }>(`/api/course-withdrawals/${id}/cancel`, {})
  const adminList = (params?: Params) => api.get<{ data: CoursePointWithdrawal[]; meta: any }>(`/api/plearnd-admin/course-withdrawals${query(params)}`)
  const adminShow = (id: number) => api.get<{ data: CoursePointWithdrawal }>(`/api/plearnd-admin/course-withdrawals/${id}`)
  const adminReview = (id: number) => api.patch<{ data: CoursePointWithdrawal }>(`/api/plearnd-admin/course-withdrawals/${id}/review`, {})
  const adminApprove = (id: number, note?: string) => api.patch<{ data: CoursePointWithdrawal }>(`/api/plearnd-admin/course-withdrawals/${id}/approve`, note ? { note } : {})
  const adminReject = (id: number, reason: string) => api.patch<{ data: CoursePointWithdrawal }>(`/api/plearnd-admin/course-withdrawals/${id}/reject`, { reason })
  const adminMarkPaid = (id: number, formData: FormData) => api.patch<{ data: CoursePointWithdrawal }>(`/api/plearnd-admin/course-withdrawals/${id}/mark-paid`, formData)
  return { createRequest, fetchCourseHistory, cancel, adminList, adminShow, adminReview, adminApprove, adminReject, adminMarkPaid }
}
