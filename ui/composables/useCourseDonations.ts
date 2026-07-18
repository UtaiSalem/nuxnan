import type { ApiCallOptions } from './useApi'

export interface CourseDonation {
  id: number
  course_id: number
  donation_type: 'point' | 'cash'
  points_amount: number | null
  cash_amount: number | null
  currency: string | null
  status: 'pending' | 'approved' | 'rejected' | 'refunded' | 'completed'
  purpose: string | null
  anonymous: boolean
  donor_display_name: string | null
  created_at: string
  reviewed_at: string | null
  course_point_transaction_id: number | null
  course?: { id: number; name: string; slug?: string }
  slip_url?: string | null
}

type PageParams = Record<string, string | number | boolean | undefined>
const key = (value?: string) => value || (typeof crypto !== 'undefined' && crypto.randomUUID ? crypto.randomUUID() : `${Date.now()}-${Math.random()}`)
const query = (params?: PageParams) => params ? `?${new URLSearchParams(Object.entries(params).filter(([, v]) => v !== undefined).map(([k, v]) => [k, String(v)]))}` : ''

export const useCourseDonations = () => {
  const api = useApi()
  const sendPointDonation = (courseId: number, payload: Record<string, unknown>, idempotencyKey?: string) => api.post<{ data: CourseDonation }>(`/api/courses/${courseId}/donations/points`, payload, { headers: { 'Idempotency-Key': key(idempotencyKey) } })
  const sendCashDonation = (courseId: number, formData: FormData, idempotencyKey?: string) => api.post<{ data: CourseDonation }>(`/api/courses/${courseId}/donations/cash`, formData, { headers: { 'Idempotency-Key': key(idempotencyKey) } })
  const fetchMyDonations = (params?: PageParams) => api.get<{ data: CourseDonation[]; meta: any }>(`/api/me/course-donations${query(params)}`)
  const fetchCourseDonations = (courseId: number, params?: PageParams) => api.get<{ data: CourseDonation[]; meta: any }>(`/api/courses/${courseId}/donations${query(params)}`)
  const adminList = (params?: PageParams) => api.get<{ data: CourseDonation[]; meta: any }>(`/api/plearnd-admin/course-donations${query(params)}`)
  const adminApprove = (id: number, note?: string) => api.patch<{ data: CourseDonation }>(`/api/plearnd-admin/course-donations/${id}/approve`, note ? { note } : {})
  const adminReject = (id: number, reason: string) => api.patch<{ data: CourseDonation }>(`/api/plearnd-admin/course-donations/${id}/reject`, { reason })
  return { sendPointDonation, sendCashDonation, fetchMyDonations, fetchCourseDonations, adminList, adminApprove, adminReject }
}
