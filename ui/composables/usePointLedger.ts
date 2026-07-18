export interface PointTransaction {
  id: number
  type: string
  amount: number
  source: string
  reference_id: number | null
  balance_before: number
  balance_after: number
  created_at: string
  metadata?: Record<string, any>
}

export interface DonationRecord {
  id: number
  donation_type: 'point' | 'cash'
  points_amount: number | null
  cash_amount: number | null
  currency: string
  status: string
  purpose: string | null
  anonymous: boolean
  created_at: string
  donor_display_name?: string | null
}

export interface PointAccount {
  id: number
  balance: number
  total_earned: number
  total_withdrawn: number
  total_distributed: number
  reserved_balance: number
  available_balance: number
  academy_id?: number
  course_id?: number
}

export const usePointLedger = () => {
  const api = useApi()

  const getMyTransactions = (params: { page?: number; per_page?: number; source?: string } = {}) =>
    api.get<{ data: PointTransaction[]; meta: any }>('/api/me/points/transactions', { params })

  const getCourseAccount = (courseId: number) =>
    api.get<PointAccount>(`/api/courses/${courseId}/point-account`)

  const getCourseTransactions = (courseId: number, params: { page?: number; per_page?: number } = {}) =>
    api.get<{ data: PointTransaction[]; meta: any }>(`/api/courses/${courseId}/point-transactions`, { params })

  const getAcademyAccount = (academyId: number) =>
    api.get<PointAccount>(`/api/academies/${academyId}/point-account`)

  const getAcademyTransactions = (academyId: number, params: { page?: number; per_page?: number } = {}) =>
    api.get<{ data: PointTransaction[]; meta: any }>(`/api/academies/${academyId}/point-transactions`, { params })

  const getMyCourseDonations = (params: { page?: number; per_page?: number } = {}) =>
    api.get<{ data: DonationRecord[]; meta: any }>('/api/me/course-donations', { params })

  const getMyAcademyDonations = (params: { page?: number; per_page?: number } = {}) =>
    api.get<{ data: DonationRecord[]; meta: any }>('/api/me/academy-donations', { params })

  return {
    getMyTransactions,
    getCourseAccount,
    getCourseTransactions,
    getAcademyAccount,
    getAcademyTransactions,
    getMyCourseDonations,
    getMyAcademyDonations,
  }
}
