import { ref, computed } from 'vue'
import { useAuthStore } from '~/stores/auth'

export const useWallet = () => {
  const authStore = useAuthStore()
  
  // Reactive state
  const isLoading = ref(false)
  const error = ref<string | null>(null)
  
  // Computed properties
  const wallet = computed(() => authStore.user?.wallet || 0)
  const user = computed(() => authStore.user)
  
  /**
   * Get wallet balance
   */
  const getBalance = async () => {
    try {
      isLoading.value = true
      error.value = null
      
      const response = await $fetch('/api/wallet/balance', {
        method: 'GET',
        headers: {
          'Authorization': `Bearer ${authStore.token}`,
        },
      }) as any
      
      if (response.success) {
        return response.data
      } else {
        throw new Error(response.message || 'Failed to get wallet balance')
      }
    } catch (err: any) {
      error.value = err.message || 'Failed to get wallet balance'
      console.error('Get wallet balance error:', err)
      throw err
    } finally {
      isLoading.value = false
    }
  }
  
  /**
   * Deposit money to wallet
   */
  const deposit = async (data: {
    amount: number
    method: string
    reference?: string
    description?: string
    metadata?: Record<string, any>
  }) => {
    try {
      isLoading.value = true
      error.value = null
      
      const response = await $fetch('/api/wallet/deposit', {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${authStore.token}`,
        },
        body: data,
      }) as any
      
      if (response.success) {
        return response.data
      } else {
        throw new Error(response.message || 'Failed to deposit money')
      }
    } catch (err: any) {
      error.value = err.message || 'Failed to deposit money'
      console.error('Deposit error:', err)
      throw err
    } finally {
      isLoading.value = false
    }
  }
  
  /**
   * Withdraw money from wallet
   */
  const withdraw = async (data: {
    amount: number
    method: string
    bank_account: {
      bank_name: string
      account_number: string
      account_name: string
    }
    description?: string
  }) => {
    try {
      isLoading.value = true
      error.value = null
      
      const response = await $fetch('/api/wallet/withdraw', {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${authStore.token}`,
        },
        body: data,
      }) as any
      
      if (response.success) {
        return response.data
      } else {
        throw new Error(response.message || 'Failed to withdraw money')
      }
    } catch (err: any) {
      error.value = err.message || 'Failed to withdraw money'
      console.error('Withdraw error:', err)
      throw err
    } finally {
      isLoading.value = false
    }
  }
  
  /**
   * Transfer money to another user
   */
  const transfer = async (data: {
    recipient_id: number
    amount: number
    message?: string
  }) => {
    try {
      isLoading.value = true
      error.value = null
      
      const response = await $fetch('/api/wallet/transfer', {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${authStore.token}`,
        },
        body: data,
      }) as any
      
      if (response.success) {
        return response.data
      } else {
        throw new Error(response.message || 'Failed to transfer money')
      }
    } catch (err: any) {
      error.value = err.message || 'Failed to transfer money'
      console.error('Transfer error:', err)
      throw err
    } finally {
      isLoading.value = false
    }
  }
  
  /**
   * Get wallet transactions
   */
  const getTransactions = async (params: {
    type?: string
    date_from?: string
    date_to?: string
    page?: number
    per_page?: number
  } = {}) => {
    try {
      isLoading.value = true
      error.value = null
      
      const queryParams = new URLSearchParams()
      if (params.type) queryParams.append('type', params.type)
      if (params.date_from) queryParams.append('date_from', params.date_from)
      if (params.date_to) queryParams.append('date_to', params.date_to)
      if (params.page) queryParams.append('page', params.page.toString())
      if (params.per_page) queryParams.append('per_page', params.per_page.toString())
      
      const response = await $fetch(`/api/wallet/transactions?${queryParams.toString()}`, {
        method: 'GET',
        headers: {
          'Authorization': `Bearer ${authStore.token}`,
        },
      }) as any
      
      if (response.success) {
        return response.data
      } else {
        throw new Error(response.message || 'Failed to get transactions')
      }
    } catch (err: any) {
      error.value = err.message || 'Failed to get transactions'
      console.error('Get transactions error:', err)
      throw err
    } finally {
      isLoading.value = false
    }
  }
  
  /**
   * Format money amount
   */
  const formatMoney = (value: number): string => {
    return new Intl.NumberFormat('th-TH', {
      style: 'currency',
      currency: 'THB',
    }).format(value)
  }
  
  /**
   * Check if user can withdraw
   */
  const canWithdraw = (amount: number): boolean => {
    return wallet.value >= amount
  }
  
  /**
   * Calculate withdrawal fee
   */
  const calculateFee = (amount: number): number => {
    return Math.max(amount * 0.005, 10) // 0.5% min 10 THB
  }
  
  /**
   * Get net amount after fee
   */
  const getNetAmount = (amount: number): number => {
    const fee = calculateFee(amount)
    return amount - fee
  }
  
  return {
    // State
    wallet,
    user,
    isLoading,
    error,
    
    // Methods
    getBalance,
    deposit,
    withdraw,
    transfer,
    getTransactions,
    
    // Helpers
    formatMoney,
    canWithdraw,
    calculateFee,
    getNetAmount,
  }
}
