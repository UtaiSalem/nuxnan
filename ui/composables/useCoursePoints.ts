import { ref, computed, unref, type Ref } from 'vue'

export function useCoursePoints(courseId: Ref<string | number> | string | number) {
  const api = useApi()
  const id = computed(() => unref(courseId))

  // State
  const account = ref<{
    balance: number
    reserved_balance: number
    available_balance: number
    total_earned: number
    total_withdrawn: number
    total_distributed: number
    minimum_withdrawal: number
  } | null>(null)

  const transactions = ref<any[]>([])
  const isLoadingAccount = ref(false)
  const isWithdrawing = ref(false)
  const campaigns = ref<CoursePointCampaign[]>([])
  const isLoadingCampaigns = ref(false)
  const isClaiming = ref<number | null>(null)
  const ownerCampaigns = ref<CoursePointCampaign[]>([])
  const isLoadingOwnerCampaigns = ref(false)

  // Fetch account balance
  const fetchAccount = async () => {
    isLoadingAccount.value = true
    try {
      const res = await api.get(`/api/courses/${id.value}/points/account`) as any
      account.value = res.data
    } catch (e) {
      console.error('useCoursePoints.fetchAccount', e)
    } finally {
      isLoadingAccount.value = false
    }
  }

  const fetchAvailableCampaigns = async () => {
    isLoadingCampaigns.value = true
    try {
      const res = await api.get(`/api/courses/${id.value}/points/campaigns/available`) as { data: CoursePointCampaign[] }
      campaigns.value = res.data || []
      return campaigns.value
    } finally {
      isLoadingCampaigns.value = false
    }
  }

  const fetchOwnerCampaigns = async () => {
    isLoadingOwnerCampaigns.value = true
    try {
      const res = await api.get(`/api/courses/${id.value}/points/campaigns`) as { data: CoursePointCampaign[] }
      ownerCampaigns.value = res.data || []
      return ownerCampaigns.value
    } finally { isLoadingOwnerCampaigns.value = false }
  }

  const createManualCampaign = (data: Record<string, unknown>) => api.post(`/api/courses/${id.value}/points/campaigns`, data)
  const pauseCampaign = (campaignId: number) => api.patch(`/api/courses/${id.value}/points/campaigns/${campaignId}/pause`, {})
  const endCampaign = (campaignId: number) => api.patch(`/api/courses/${id.value}/points/campaigns/${campaignId}/end`, {})

  const viewCampaign = (campaignId: number) => api.post(`/api/courses/${id.value}/points/campaigns/${campaignId}/view`, {})

  const claimCampaign = async (campaignId: number, viewed?: { viewed_donor_id?: number, viewed_donation_id?: number }) => {
    isClaiming.value = campaignId
    try {
      const res = await api.post(`/api/courses/${id.value}/points/campaigns/${campaignId}/claim`, viewed || {}) as any
      if (res.success) {
        await Promise.all([fetchAvailableCampaigns(), fetchAccount()])
      }
      return res
    } finally {
      isClaiming.value = null
    }
  }

  // Fetch transaction history (paginated)
  const fetchTransactions = async (page = 1) => {
    const res = await api.get(
      `/api/courses/${id.value}/points/transactions?page=${page}`
    ) as any
    transactions.value = res.data || []
    return res
  }

  // Withdraw points
  const withdraw = async (amount: number) => {
    isWithdrawing.value = true
    try {
      const res = await api.post(
        `/api/courses/${id.value}/points/withdraw`,
        { amount }
      ) as any
      if (res.success) await fetchAccount()
      return res
    } finally {
      isWithdrawing.value = false
    }
  }

  // Fetch reward setting for a lesson
  const fetchLessonReward = async (lessonId: number) => {
    const res = await api.get(
      `/api/courses/${id.value}/lessons/${lessonId}/reward`
    ) as any
    return res.data ?? null
  }

  // Save lesson reward (create/replace)
  const saveLessonReward = async (lessonId: number, data: {
    points_per_claim: number
    max_claims?: number | null
    starts_at?: string | null
    ends_at?: string | null
  }) => {
    return api.post(
      `/api/courses/${id.value}/lessons/${lessonId}/reward`,
      data
    )
  }

  // Cancel lesson reward
  const cancelLessonReward = async (lessonId: number) => {
    return api.delete(
      `/api/courses/${id.value}/lessons/${lessonId}/reward`
    )
  }

  const fetchQuizReward = async (quizId: number) => (await api.get(`/api/courses/${id.value}/quizzes/${quizId}/reward`) as any).data ?? null
  const saveQuizReward = (quizId: number, data: Record<string, unknown>) => api.post(`/api/courses/${id.value}/quizzes/${quizId}/reward`, data)
  const cancelQuizReward = (quizId: number) => api.delete(`/api/courses/${id.value}/quizzes/${quizId}/reward`)

  return {
    account,
    transactions,
    isLoadingAccount,
    isWithdrawing,
    campaigns,
    isLoadingCampaigns,
    isClaiming,
    ownerCampaigns,
    isLoadingOwnerCampaigns,
    fetchAccount,
    fetchAvailableCampaigns,
    fetchOwnerCampaigns,
    createManualCampaign,
    pauseCampaign,
    endCampaign,
    claimCampaign,
    viewCampaign,
    fetchTransactions,
    withdraw,
    fetchLessonReward,
    saveLessonReward,
    cancelLessonReward,
    fetchQuizReward,
    saveQuizReward,
    cancelQuizReward,
  }
}

export interface CoursePointCampaign {
  id: number
  campaign_type?: string
  title: string
  description: string | null
  points_per_claim: number
  max_claims: number | null
  remaining: number | null
  total_claimed: number
  status: 'active' | 'paused' | 'ended' | 'depleted'
  starts_at: string | null
  ends_at: string | null
  claimed_by_auth: boolean
  can_claim: boolean
}
