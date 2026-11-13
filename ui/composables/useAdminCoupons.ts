/**
 * useAdminCoupons — เรียก /api/admin/coupons สำหรับหน้า /nuxnan-admin/coupons
 *
 * คูปอง = บัตรกำนัลแต้ม/เงินใช้ครั้งเดียว ผู้รับแลกที่ /coupons/redeem
 * - สร้าง: มูลค่าหักจากแต้ม/เงินของแอดมินผู้สร้างเอง (ไม่ได้สร้างมูลค่าใหม่)
 * - ยกเลิก (DELETE): คืนยอดให้ผู้สร้างคูปอง · คูปองที่แลกแล้วยกเลิกไม่ได้
 * - แก้ไข: ได้เฉพาะคำอธิบายและวันหมดอายุ
 */

export type CouponType = 'points' | 'wallet'
export type CouponStatus = 'active' | 'redeemed' | 'expired' | 'cancelled'

export interface AdminCouponPerson {
  id: number
  name: string
  username?: string | null
  email?: string | null
  is_plearnd_admin?: boolean
}

export interface AdminCoupon {
  id: number
  coupon_code: string
  coupon_type: CouponType
  type_label: string
  amount: string
  formatted_amount: string
  status: CouponStatus
  /** สถานะที่ผู้ใช้เห็นจริง (active ที่เลยวันหมดอายุ = expired) */
  display_status: CouponStatus
  status_label: string
  description: string | null
  expires_at: string | null
  redeemed_at: string | null
  created_at: string | null
  updated_at: string | null
  is_expired: boolean
  can_redeem: boolean
  can_edit: boolean
  can_cancel: boolean
  qr_code_url: string
  creator: AdminCouponPerson | null
  redeemer: AdminCouponPerson | null
}

export interface AdminCouponSummary {
  active_count: number
  active_points: string
  active_wallet: string
  redeemed_count: number
}

export interface AdminCouponListParams {
  page?: number
  per_page?: number
  status?: CouponStatus | 'all'
  type?: CouponType | 'all'
  search?: string
}

export interface AdminCouponListResponse {
  success: boolean
  data: {
    data: AdminCoupon[]
    meta: { current_page: number; last_page: number; per_page: number; total: number }
  }
  summary: AdminCouponSummary
}

export interface CreateAdminCouponPayload {
  type: CouponType
  amount: number
  quantity?: number
  description?: string | null
  expires_in_days?: number | null
}

export interface UpdateAdminCouponPayload {
  description?: string | null
  /** ISO string หรือ null = ไม่มีวันหมดอายุ */
  expires_at?: string | null
}

/** ดึงข้อความ error ที่อ่านรู้เรื่องจาก error ของ useApi (422 = errors ราย field) */
export const couponErrorMessage = (error: any, fallback = 'เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง'): string => {
  const data = error?.data
  if (data?.errors && typeof data.errors === 'object') {
    const first = Object.values(data.errors as Record<string, string[]>)[0]
    if (Array.isArray(first) && first.length) return first[0]
  }
  if (error?.status === 403) return data?.message || 'บัญชีนี้ไม่มีสิทธิ์จัดการคูปอง (ต้องเป็นผู้ดูแลแพลตฟอร์มที่มีสิทธิ์คูปอง)'
  return data?.message || fallback
}

/** errors ราย field จาก 422 → { field: 'ข้อความแรก' } */
export const couponFieldErrors = (error: any): Record<string, string> => {
  const errors = error?.data?.errors
  if (!errors || typeof errors !== 'object') return {}
  return Object.fromEntries(
    Object.entries(errors as Record<string, string[]>).map(([key, messages]) => [key, messages?.[0] ?? ''])
  )
}

export const useAdminCoupons = () => {
  const api = useApi()
  const base = '/api/admin/coupons'

  const list = (params: AdminCouponListParams = {}) => {
    const query = new URLSearchParams()
    Object.entries(params).forEach(([key, value]) => {
      if (value === undefined || value === null || value === '' || value === 'all') return
      query.set(key, String(value))
    })
    const qs = query.toString()
    return api.get<AdminCouponListResponse>(qs ? `${base}?${qs}` : base)
  }

  const get = (id: number | string) =>
    api.get<{ success: boolean; data: AdminCoupon }>(`${base}/${id}`)

  const create = (payload: CreateAdminCouponPayload) =>
    api.post<{
      success: boolean
      message: string
      data: { coupons: AdminCoupon[]; count: number; total_amount: number }
    }>(base, payload)

  const update = (id: number | string, payload: UpdateAdminCouponPayload) =>
    api.put<{ success: boolean; message: string; data: AdminCoupon }>(`${base}/${id}`, payload)

  /** ยกเลิก + คืนยอดผู้สร้าง */
  const cancel = (id: number | string) =>
    api.delete<{ success: boolean; message: string; data: AdminCoupon }>(`${base}/${id}`)

  return { list, get, create, update, cancel }
}
