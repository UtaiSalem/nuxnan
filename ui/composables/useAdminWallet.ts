import { useFetch } from '#app'

export const useAdminWallet = () => {
  const apiBase = '/api/admin/wallet'

  // Get wallet statistics
  const getStats = async () => {
    const { data, error } = await useFetch(`${apiBase}/stats`)
    return { data: data.value?.data, error }
  }

  // Get pending withdrawals
  const getPendingWithdrawals = async (page = 1, perPage = 20) => {
    const { data, error } = await useFetch(
      `${apiBase}/withdrawals/pending?page=${page}&per_page=${perPage}`
    )
    return { data: data.value?.data, error }
  }

  // Approve a withdrawal
  const approveWithdrawal = async (transactionId: number, adminNotes = '') => {
    const { data, error } = await useFetch(
      `${apiBase}/withdrawals/${transactionId}/approve`,
      {
        method: 'POST',
        body: { admin_notes: adminNotes },
      }
    )
    return { data: data.value?.data, error }
  }

  // Reject a withdrawal
  const rejectWithdrawal = async (transactionId: number, reason: string) => {
    const { data, error } = await useFetch(
      `${apiBase}/withdrawals/${transactionId}/reject`,
      {
        method: 'POST',
        body: { reason },
      }
    )
    return { data: data.value?.data, error }
  }

  // Adjust user wallet
  const adjustWallet = async (userId: number, adjustmentData: any) => {
    const { data, error } = await useFetch(`${apiBase}/users/${userId}/adjust`, {
      method: 'POST',
      body: adjustmentData,
    })
    return { data: data.value?.data, error }
  }

  // Get user wallet transactions
  const getUserTransactions = async (userId: number, page = 1, perPage = 20) => {
    const { data, error } = await useFetch(
      `${apiBase}/users/${userId}/transactions?page=${page}&per_page=${perPage}`
    )
    return { data: data.value?.data, error }
  }

  // Get analytics
  const getAnalytics = async (filters: any = {}) => {
    const params = new URLSearchParams(filters as Record<string, string>).toString()
    const { data, error } = await useFetch(`${apiBase}/analytics?${params}`)
    return { data: data.value?.data, error }
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
