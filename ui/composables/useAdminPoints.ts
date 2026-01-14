import { useFetch } from '#app'

export const useAdminPoints = () => {
  const apiBase = '/api/admin/points'

  // Get points statistics
  const getStats = async () => {
    const { data, error } = await useFetch(`${apiBase}/stats`)
    return { data: data.value?.data, error }
  }

  // Get all point rules
  const getRules = async () => {
    const { data, error } = await useFetch(`${apiBase}/rules`)
    return { data: data.value?.data, error }
  }

  // Create a new point rule
  const createRule = async (ruleData: any) => {
    const { data, error } = await useFetch(`${apiBase}/rules`, {
      method: 'POST',
      body: ruleData,
    })
    return { data: data.value?.data, error }
  }

  // Update a point rule
  const updateRule = async (id: number, ruleData: any) => {
    const { data, error } = await useFetch(`${apiBase}/rules/${id}`, {
      method: 'PUT',
      body: ruleData,
    })
    return { data: data.value?.data, error }
  }

  // Delete a point rule
  const deleteRule = async (id: number) => {
    const { data, error } = await useFetch(`${apiBase}/rules/${id}`, {
      method: 'DELETE',
    })
    return { data: data.value, error }
  }

  // Adjust user points
  const adjustPoints = async (userId: number, adjustmentData: any) => {
    const { data, error } = await useFetch(`${apiBase}/users/${userId}/adjust`, {
      method: 'POST',
      body: adjustmentData,
    })
    return { data: data.value?.data, error }
  }

  // Get user transactions
  const getUserTransactions = async (userId: number, page = 1, perPage = 20) => {
    const { data, error } = await useFetch(
      `${apiBase}/users/${userId}/transactions?page=${page}&per_page=${perPage}`
    )
    return { data: data.value?.data, error }
  }

  // Get leaderboard
  const getLeaderboard = async (type = 'points', limit = 50) => {
    const { data, error } = await useFetch(
      `${apiBase}/leaderboard?type=${type}&limit=${limit}`
    )
    return { data: data.value?.data?.leaderboard, error }
  }

  // Get analytics
  const getAnalytics = async (filters: any = {}) => {
    const params = new URLSearchParams(filters as Record<string, string>).toString()
    const { data, error } = await useFetch(`${apiBase}/analytics?${params}`)
    return { data: data.value?.data, error }
  }

  // Format points for display
  const formatPoints = (points: number) => {
    return new Intl.NumberFormat('th-TH').format(Math.round(points))
  }

  // Format percentage
  const formatPercentage = (value: number) => {
    return `${value.toFixed(2)}%`
  }

  return {
    getStats,
    getRules,
    createRule,
    updateRule,
    deleteRule,
    adjustPoints,
    getUserTransactions,
    getLeaderboard,
    getAnalytics,
    formatPoints,
    formatPercentage,
  }
}
