export interface AcademySupportSummary {
  approved_points_total: number
  approved_cash_total: number
  point_balance: number
  available_point_balance: number
  total_distributed: number
  supporter_count: number
  ad_revenue_points: number
  campaign_count: number
  recent_donations: AcademyPublicDonation[]
}

export interface AcademyPublicDonation {
  id: number
  academy_id: number
  academy: { id: number; name: string }
  donation_type: 'point' | 'cash'
  points_amount: number | null
  cash_amount: number | null
  currency: string | null
  status: string
  purpose: string | null
  anonymous: boolean
  donor_display_name: string
  donor: null
  slip_path: null
  created_at: string
  reviewed_at: string | null
}

export interface AcademyAdminDonation {
  id: number
  academy_id: number
  academy: { id: number; name: string }
  donation_type: 'point' | 'cash'
  points_amount: number
  cash_amount: number
  currency: string
  status: string
  purpose: string | null
  anonymous: boolean
  donor_display_name: string
  donor: {
    id: number
    name: string
    email: string
    avatar: string | null
  }
  slip_path: string | null
  payment_method: string | null
  payment_reference: string | null
  created_at: string
  reviewed_at: string | null
  reviewer: { id: number; name: string } | null
  rejection_reason: string | null
  academy_point_transaction_id: number | null
  metadata: Record<string, any>
}

export interface AcademyRevenueDashboard {
  points: {
    balance: number
    available_balance: number
    reserved_balance: number
    total_earned: number
    total_withdrawn: number
    total_distributed: number
    platform_earned: number
  }
  donations: {
    pending_count: number
    pending_points: number
    pending_cash: number
    approved_count: number
    approved_points: number
    approved_cash: number
    rejected_count: number
  }
  ad_revenue: {
    total_points: number
    campaign_count: number
    active_campaigns: number
  }
  recent_donations: AcademyAdminDonation[]
  recent_transactions: Array<{
    id: number
    type: string
    amount: number
    balance_after: number
    created_at: string
    metadata: Record<string, any>
  }>
}

export interface AcademyRevenueActivity {
  id: number
  type: string
  description: string
  amount: number | null
  amount_type: string | null
  created_at: string
  user: { id: number; name: string } | null
}

export interface AcademyCampaign {
  id: number
  campaign_type: 'advertisement' | 'support'
  scope_type: string
  title: string
  description: string | null
  budget_amount: number
  total_views: number
  remaining_views: number
  review_status: string
  payment_status: string
  status: number
  created_at: string
}

const API_BASE = '/api'

import { useAcademyRole } from './useAcademyRole'

export const useAcademyRevenue = (academyId: MaybeRef<number | null>) => {
  const api = useApi()
  const toast = useToast()
  const resolvedAcademyId = computed(() => toValue(academyId))

  const roleAcademyId = computed(() => resolvedAcademyId.value)
  const { can } = useAcademyRole(roleAcademyId)
  const canViewRevenue = computed(() => can('finance.view'))
  const canManageRevenue = computed(() => can('finance.manage'))

  const supportSummary = ref<AcademySupportSummary | null>(null)
  const donations = ref<AcademyPublicDonation[]>([])
  const revenueDashboard = ref<AcademyRevenueDashboard | null>(null)
  const activities = ref<AcademyRevenueActivity[]>([])
  const campaigns = ref<AcademyCampaign[]>([])
  const isLoading = ref(false)
  const error = ref<string | null>(null)

  const fetchSupportSummary = async () => {
    if (!resolvedAcademyId.value) return
    isLoading.value = true
    error.value = null
    try {
      const res = await api.get(`${API_BASE}/academies/${resolvedAcademyId.value}/revenue/support-summary`)
      supportSummary.value = res.data
    } catch (e: any) {
      error.value = e?.data?.message || 'ไม่สามารถโหลดข้อมูลสรุปรายได้ได้'
    } finally {
      isLoading.value = false
    }
  }

  const fetchDonations = async (params?: { status?: string; donation_type?: string; page?: number }) => {
    if (!resolvedAcademyId.value) return
    isLoading.value = true
    error.value = null
    try {
      const query = new URLSearchParams()
      if (params?.status) query.set('status', params.status)
      if (params?.donation_type) query.set('donation_type', params.donation_type)
      if (params?.page) query.set('page', String(params.page))
      const res = await api.get(`${API_BASE}/academies/${resolvedAcademyId.value}/revenue/donations?${query.toString()}`)
      donations.value = res.donations || []
      return res
    } catch (e: any) {
      error.value = e?.data?.message || 'ไม่สามารถโหลดรายการสนับสนุนได้'
    } finally {
      isLoading.value = false
    }
  }

  const fetchRevenueDashboard = async () => {
    if (!resolvedAcademyId.value) return
    isLoading.value = true
    error.value = null
    try {
      const res = await api.get(`${API_BASE}/academies/${resolvedAcademyId.value}/revenue/revenue`)
      revenueDashboard.value = res.data
    } catch (e: any) {
      error.value = e?.data?.message || 'ไม่สามารถโหลดแดชบอร์ดรายได้ได้'
    } finally {
      isLoading.value = false
    }
  }

  const fetchRevenueActivity = async (page = 1) => {
    if (!resolvedAcademyId.value) return
    isLoading.value = true
    error.value = null
    try {
      const res = await api.get(`${API_BASE}/academies/${resolvedAcademyId.value}/revenue/revenue/activity?page=${page}`)
      activities.value = res.activities || []
      return res
    } catch (e: any) {
      error.value = e?.data?.message || 'ไม่สามารถโหลดประวัติรายได้ได้'
    } finally {
      isLoading.value = false
    }
  }

  const fetchCampaigns = async (params?: { review_status?: string; campaign_type?: string; page?: number }) => {
    if (!resolvedAcademyId.value) return
    isLoading.value = true
    error.value = null
    try {
      const query = new URLSearchParams()
      if (params?.review_status) query.set('review_status', params.review_status)
      if (params?.campaign_type) query.set('campaign_type', params.campaign_type)
      if (params?.page) query.set('page', String(params.page))
      const res = await api.get(`${API_BASE}/academies/${resolvedAcademyId.value}/revenue/campaigns?${query.toString()}`)
      campaigns.value = res.campaigns?.data || res.campaigns || []
      return res
    } catch (e: any) {
      error.value = e?.data?.message || 'ไม่สามารถโหลดแคมเปญได้'
    } finally {
      isLoading.value = false
    }
  }

  const approveDonation = async (donationId: number, note?: string) => {
    if (!resolvedAcademyId.value) return
    const res = await api.post(`${API_BASE}/academies/${resolvedAcademyId.value}/revenue/donations/${donationId}/approve`, { note })
    toast.add({ severity: 'success', summary: 'สำเร็จ', detail: 'อนุมัติการบริจาคแล้ว', life: 3000 })
    await fetchDonations()
    await fetchRevenueDashboard()
    return res
  }

  const rejectDonation = async (donationId: number, reason: string) => {
    if (!resolvedAcademyId.value) return
    const res = await api.post(`${API_BASE}/academies/${resolvedAcademyId.value}/revenue/donations/${donationId}/reject`, { reason })
    toast.add({ severity: 'success', summary: 'สำเร็จ', detail: 'ปฏิเสธการบริจาคแล้ว', life: 3000 })
    await fetchDonations()
    await fetchRevenueDashboard()
    return res
  }

  const createCampaign = async (data: any) => {
    if (!resolvedAcademyId.value) return
    const res = await api.post(`${API_BASE}/academies/${resolvedAcademyId.value}/revenue/campaigns`, data)
    toast.add({ severity: 'success', summary: 'สำเร็จ', detail: 'สร้างแคมเปญแล้ว', life: 3000 })
    await fetchCampaigns()
    return res
  }

  const updateCampaign = async (campaignId: number, data: any) => {
    if (!resolvedAcademyId.value) return
    const res = await api.patch(`${API_BASE}/academies/${resolvedAcademyId.value}/revenue/campaigns/${campaignId}`, data)
    toast.add({ severity: 'success', summary: 'สำเร็จ', detail: 'บันทึกการเปลี่ยนแปลงแล้ว', life: 3000 })
    await fetchCampaigns()
    return res
  }

  return {
    supportSummary,
    donations,
    revenueDashboard,
    activities,
    campaigns,
    isLoading,
    error,
    canViewRevenue,
    canManageRevenue,
    fetchSupportSummary,
    fetchDonations,
    fetchRevenueDashboard,
    fetchRevenueActivity,
    fetchCampaigns,
    approveDonation,
    rejectDonation,
    createCampaign,
    updateCampaign,
  }
}
