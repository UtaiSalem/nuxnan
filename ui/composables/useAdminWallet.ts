import { ref } from 'vue'
import { useAuthStore } from '~/stores/auth'

export const useAdminWallet = () => {
  const authStore = useAuthStore()
  const config = useRuntimeConfig()
  const apiBase = config.public.apiBase as string
  
  const isLoading = ref(false)
  const error = ref<string | null>(null)

  // Get wallet statistics
  const getStats = async () => {
    try {
      isLoading.value = true
      const response = await $fetch(`${apiBase}/api/admin/wallet/stats`, {
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

  // Get pending withdrawals
  const getPendingWithdrawals = async (params: any = {}) => {
    try {
      isLoading.value = true
      const queryParams = new URLSearchParams(params).toString()
      const response = await $fetch(`${apiBase}/api/admin/wallet/withdrawals/pending?${queryParams}`, {
        headers: { 'Authorization': `Bearer ${authStore.token}` }
      }) as any
      return response.success ? response.data : null
    } catch (err) {
      console.error('Failed to get pending withdrawals:', err)
      return null
    } finally {
      isLoading.value = false
    }
  }

  // Approve a withdrawal
  const approveWithdrawal = async (transactionId: number, adminNotes = '') => {
    try {
      isLoading.value = true
      const response = await $fetch(`${apiBase}/api/admin/wallet/withdrawals/${transactionId}/approve`, {
        method: 'POST',
        headers: { 'Authorization': `Bearer ${authStore.token}` },
        body: { admin_notes: adminNotes }
      }) as any
      return response.success ? response.data : null
    } catch (err) {
      console.error('Failed to approve withdrawal:', err)
      throw err
    } finally {
      isLoading.value = false
    }
  }

  // Reject a withdrawal
  const rejectWithdrawal = async (transactionId: number, reason: string) => {
    try {
      isLoading.value = true
      const response = await $fetch(`${apiBase}/api/admin/wallet/withdrawals/${transactionId}/reject`, {
        method: 'POST',
        headers: { 'Authorization': `Bearer ${authStore.token}` },
        body: { reason }
      }) as any
      return response.success ? response.data : null
    } catch (err) {
      console.error('Failed to reject withdrawal:', err)
      throw err
    } finally {
      isLoading.value = false
    }
  }

  // Adjust user wallet
  const adjustWallet = async (userId: number, adjustmentData: any) => {
    try {
      isLoading.value = true
      const response = await $fetch(`${apiBase}/api/admin/wallet/users/${userId}/adjust`, {
        method: 'POST',
        headers: { 'Authorization': `Bearer ${authStore.token}` },
        body: adjustmentData
      }) as any
      return response.success ? response.data : null
    } catch (err) {
      console.error('Failed to adjust wallet:', err)
      throw err
    } finally {
      isLoading.value = false
    }
  }

  // Get user wallet transactions
  const getUserTransactions = async (userId: number, params: any = {}) => {
    try {
      isLoading.value = true
      const queryParams = new URLSearchParams(params).toString()
      const response = await $fetch(`${apiBase}/api/admin/wallet/users/${userId}/transactions?${queryParams}`, {
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

  // Get analytics
  const getAnalytics = async (filters: any = {}) => {
    try {
      isLoading.value = true
      const queryParams = new URLSearchParams(filters).toString()
      const response = await $fetch(`${apiBase}/api/admin/wallet/analytics?${queryParams}`, {
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

  // Format money for display
  const formatMoney = (amount: number) => {
    return new Intl.NumberFormat('th-TH', {
      style: 'currency',
      currency: 'THB',
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    }).format(amount)
  }

  // Format percentage
  const formatPercentage = (value: number) => {
    return `${value.toFixed(2)}%`
  }

  // Calculate withdrawal fee
  const calculateFee = (amount: number) => {
    const feePercentage = 0.005 // 0.5%
    const minFee = 10 // 10 THB
    const calculatedFee = amount * feePercentage
    return Math.max(calculatedFee, minFee)
  }

  // Calculate net amount after fee
  const calculateNetAmount = (amount: number) => {
    const fee = calculateFee(amount)
    return amount - fee
  }

  return {
    isLoading,
    error,
    getStats,
    getPendingWithdrawals,
    approveWithdrawal,
    rejectWithdrawal,
    adjustWallet,
    getUserTransactions,
    getAnalytics,
    formatMoney,
    formatPercentage,
    calculateFee,
    calculateNetAmount,
  }
}
