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

  return {
    account,
    transactions,
    isLoadingAccount,
    isWithdrawing,
    fetchAccount,
    fetchTransactions,
    withdraw,
    fetchLessonReward,
    saveLessonReward,
    cancelLessonReward,
  }
}
