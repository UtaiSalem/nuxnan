import type { PointFundSource } from '~/composables/useCoursePointWithdrawals'

export type AcademyPointWithdrawalStatus = 'pending' | 'reviewing' | 'approved' | 'paid' | 'rejected' | 'cancelled'
export interface AcademyPointWithdrawal {
  id: number; academy_id: number; academy?: { name: string }; amount: number; purpose: string | null
  status: AcademyPointWithdrawalStatus; requested_at: string; requester: { name: string }; reviewer?: { name: string } | null
  reviewed_at?: string | null; approver?: { name: string } | null; approved_at?: string | null; payer?: { name: string } | null
  paid_at?: string | null; payment_reference?: string | null; admin_note?: string | null; rejection_reason?: string | null
  has_proof: boolean; version: number
}
type Params = Record<string, string | number | undefined>
const query = (p?: Params) => p ? `?${new URLSearchParams(Object.entries(p).filter(([, v]) => v !== undefined).map(([k, v]) => [k, String(v)]))}` : ''
const idem = (key?: string) => key || (typeof crypto !== 'undefined' && crypto.randomUUID ? crypto.randomUUID() : `${Date.now()}-${Math.random()}`)
export const useAcademyPointWithdrawals = () => {
  const api = useApi()
  const createRequest = (academyId: number, body: { amount: number; purpose?: string }, idempotencyKey?: string) => api.post<{ data: AcademyPointWithdrawal }>(`/api/academies/${academyId}/withdrawals`, body, { headers: { 'Idempotency-Key': idem(idempotencyKey) } })
  const fetchAcademyHistory = (academyId: number, params?: Params) => api.get<{ data: AcademyPointWithdrawal[]; meta: any }>(`/api/academies/${academyId}/withdrawals${query(params)}`)
  const cancel = (id: number) => api.post<{ data: AcademyPointWithdrawal }>(`/api/academy-withdrawals/${id}/cancel`, {})
  const adminList = (params?: Params) => api.get<{ data: AcademyPointWithdrawal[]; meta: any }>(`/api/plearnd-admin/academy-withdrawals${query(params)}`)
  const adminShow = (id: number) => api.get<{ data: AcademyPointWithdrawal }>(`/api/plearnd-admin/academy-withdrawals/${id}`)
  const adminReview = (id: number) => api.patch<{ data: AcademyPointWithdrawal }>(`/api/plearnd-admin/academy-withdrawals/${id}/review`, {})
  const adminApprove = (id: number, note?: string) => api.patch<{ data: AcademyPointWithdrawal }>(`/api/plearnd-admin/academy-withdrawals/${id}/approve`, note ? { note } : {})
  const adminReject = (id: number, reason: string) => api.patch<{ data: AcademyPointWithdrawal }>(`/api/plearnd-admin/academy-withdrawals/${id}/reject`, { reason })
  const adminMarkPaid = (id: number, paymentReference?: string) => api.patch<{ data: AcademyPointWithdrawal }>(`/api/plearnd-admin/academy-withdrawals/${id}/mark-paid`, paymentReference ? { payment_reference: paymentReference } : {})
  const adminSourceOfFunds = (id: number, params?: Params) => api.get<{ success: boolean; data: PointFundSource }>(`/api/plearnd-admin/academy-withdrawals/${id}/source-of-funds${query(params)}`)
  return { createRequest, fetchAcademyHistory, cancel, adminList, adminShow, adminReview, adminApprove, adminReject, adminMarkPaid, adminSourceOfFunds }
}
