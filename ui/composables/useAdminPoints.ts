import { ref } from 'vue'
import { useAuthStore } from '~/stores/auth'

export const useAdminPoints = () => {
  const authStore = useAuthStore()
  const config = useRuntimeConfig()
  const apiBase = config.public.apiBase as string
  
  const isLoading = ref(false)
  const error = ref<string | null>(null)

  // Get points statistics
  const getStats = async () => {
    try {
      isLoading.value = true
      const response = await $fetch(`${apiBase}/api/admin/points/stats`, {
        headers: { 'Authorization': `Bearer ${authStore.token}` }
      }) as any
      return response.success ? response.data : null
    } catch (err) {
      console.error('Failed to get stats:', err)
      return null
    } finally {
      isLoading.value = false
    }
  }

  // Get all point rules
  const getRules = async () => {
    try {
      isLoading.value = true
      const response = await $fetch(`${apiBase}/api/admin/points/rules`, {
        headers: { 'Authorization': `Bearer ${authStore.token}` }
      }) as any
      return response.success ? response.data : null
    } catch (err) {
      console.error('Failed to get rules:', err)
      return null
    } finally {
      isLoading.value = false
    }
  }

  // Create a new point rule
  const createRule = async (ruleData: any) => {
    try {
      isLoading.value = true
      const response = await $fetch(`${apiBase}/api/admin/points/rules`, {
        method: 'POST',
        headers: { 'Authorization': `Bearer ${authStore.token}` },
        body: ruleData
      }) as any
      return response.success ? response.data : null
    } catch (err) {
      console.error('Failed to create rule:', err)
      throw err
    } finally {
      isLoading.value = false
    }
  }

  // Update a point rule
  const updateRule = async (id: number, ruleData: any) => {
    try {
      isLoading.value = true
      const response = await $fetch(`${apiBase}/api/admin/points/rules/${id}`, {
        method: 'PUT',
        headers: { 'Authorization': `Bearer ${authStore.token}` },
        body: ruleData
      }) as any
      return response.success ? response.data : null
    } catch (err) {
      console.error('Failed to update rule:', err)
      throw err
    } finally {
      isLoading.value = false
    }
  }

  // Delete a point rule
  const deleteRule = async (id: number) => {
    try {
      isLoading.value = true
      const response = await $fetch(`${apiBase}/api/admin/points/rules/${id}`, {
        method: 'DELETE',
        headers: { 'Authorization': `Bearer ${authStore.token}` }
      }) as any
      return response.success
    } catch (err) {
      console.error('Failed to delete rule:', err)
      throw err
    } finally {
      isLoading.value = false
    }
  }

  // Adjust user points
  const adjustPoints = async (userId: number, adjustmentData: any) => {
    try {
      isLoading.value = true
      const response = await $fetch(`${apiBase}/api/admin/points/users/${userId}/adjust`, {
        method: 'POST',
        headers: { 'Authorization': `Bearer ${authStore.token}` },
        body: adjustmentData
      }) as any
      return response.success ? response.data : null
    } catch (err) {
      console.error('Failed to adjust points:', err)
      throw err
    } finally {
      isLoading.value = false
    }
  }

  // Get user transactions
  const getUserTransactions = async (userId: number, params: any = {}) => {
    try {
      isLoading.value = true
      const queryParams = new URLSearchParams(params).toString()
      const response = await $fetch(`${apiBase}/api/admin/points/users/${userId}/transactions?${queryParams}`, {
        headers: { 'Authorization': `Bearer ${authStore.token}` }
      }) as any
      return response.success ? response.data : null
    } catch (err) {
      console.error('Failed to get user transactions:', err)
      return null
    } finally {
      isLoading.value = false
    }
  }

  // Get leaderboard
  const getLeaderboard = async (params: { type?: string; limit?: number } = {}) => {
    try {
      isLoading.value = true
      const queryParams = new URLSearchParams(params as Record<string, string>).toString()
      const response = await $fetch(`${apiBase}/api/admin/points/leaderboard?${queryParams}`, {
        headers: { 'Authorization': `Bearer ${authStore.token}` }
      }) as any
      return response.success ? response.data : null
    } catch (err) {
      console.error('Failed to get leaderboard:', err)
      return null
    } finally {
      isLoading.value = false
    }
  }

  // Get analytics
  const getAnalytics = async (filters: any = {}) => {
    try {
      isLoading.value = true
      const queryParams = new URLSearchParams(filters).toString()
      const response = await $fetch(`${apiBase}/api/admin/points/analytics?${queryParams}`, {
        headers: { 'Authorization': `Bearer ${authStore.token}` }
      }) as any
      return response.success ? response.data : null
    } catch (err) {
      console.error('Failed to get analytics:', err)
      return null
    } finally {
      isLoading.value = false
    }
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
    isLoading,
    error,
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
