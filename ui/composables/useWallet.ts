import { ref, computed } from 'vue'
import { useAuthStore } from '~/stores/auth'

export const useWallet = () => {
  const authStore = useAuthStore()
  const config = useRuntimeConfig()
  const apiBase = computed(() => config.public.apiBase || '')

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

      const response = await $fetch(`${apiBase.value}/api/wallet/balance`, {
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
      const msg = err.data?.message || err.response?._data?.message || err.message || 'Failed to get wallet balance'
      error.value = msg
      console.error('Get wallet balance error:', err)
      throw new Error(msg)
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

      const response = await $fetch(`${apiBase.value}/api/wallet/deposit`, {
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
      const msg = err.data?.message || err.response?._data?.message || err.message || 'Failed to deposit money'
      error.value = msg
      console.error('Deposit error:', err)
      throw new Error(msg)
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

      const response = await $fetch(`${apiBase.value}/api/wallet/withdraw`, {
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
      const msg = err.data?.message || err.response?._data?.message || err.message || 'Failed to withdraw money'
      error.value = msg
      console.error('Withdraw error:', err)
      throw new Error(msg)
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

      const response = await $fetch(`${apiBase.value}/api/wallet/transfer`, {
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
      const msg = err.data?.message || err.response?._data?.message || err.message || 'Failed to transfer money'
      error.value = msg
      console.error('Transfer error:', err)
      throw new Error(msg)
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

      const response = await $fetch(`${apiBase.value}/api/wallet/transactions?${queryParams.toString()}`, {
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
      const msg = err.data?.message || err.response?._data?.message || err.message || 'Failed to get transactions'
      error.value = msg
      console.error('Get transactions error:', err)
      throw new Error(msg)
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
   * Check if user can withdraw (minimum 25 THB)
   */
  const canWithdraw = (amount: number): boolean => {
    return amount >= 25 && wallet.value >= amount
  }

  /**
   * Calculate withdrawal fee
   */
  const calculateFee = (amount: number): number => {
    return amount * 0.13 // 13%
  }

  /**
   * Create deposit request with transfer slip
   */
  const createDepositRequest = async (formData: FormData) => {
    try {
      isLoading.value = true
      error.value = null

      const response = await $fetch(`${apiBase.value}/api/wallet/deposit-request`, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${authStore.token}`,
        },
        body: formData,
      }) as any

      if (response.success) {
        return response.data
      } else {
        throw new Error(response.message || 'ไม่สามารถสร้างคำขอเติมเงินได้')
      }
    } catch (err: any) {
      const msg = err.data?.message || err.response?._data?.message || err.message || 'ไม่สามารถสร้างคำขอเติมเงินได้'
      error.value = msg
      console.error('Create deposit request error:', err)
      throw new Error(msg)
    } finally {
      isLoading.value = false
    }
  }

  /**
   * Get user's deposit requests
   */
  const getDepositRequests = async (params: { status?: string; page?: number; per_page?: number } = {}) => {
    try {
      isLoading.value = true
      error.value = null

      const queryParams = new URLSearchParams()
      if (params.status) queryParams.append('status', params.status)
      if (params.page) queryParams.append('page', params.page.toString())
      if (params.per_page) queryParams.append('per_page', params.per_page.toString())

      const response = await $fetch(`${apiBase.value}/api/wallet/deposit-requests?${queryParams}`, {
        method: 'GET',
        headers: {
          'Authorization': `Bearer ${authStore.token}`,
        },
      }) as any

      if (response.success) {
        return response.data
      } else {
        throw new Error(response.message || 'ไม่สามารถดึงข้อมูลคำขอเติมเงินได้')
      }
    } catch (err: any) {
      const msg = err.data?.message || err.response?._data?.message || err.message || 'ไม่สามารถดึงข้อมูลคำขอเติมเงินได้'
      error.value = msg
      console.error('Get deposit requests error:', err)
      throw new Error(msg)
    } finally {
      isLoading.value = false
    }
  }

  /**
   * Cancel a pending deposit request
   */
  const cancelDepositRequest = async (requestId: number) => {
    try {
      isLoading.value = true
      error.value = null

      const response = await $fetch(`${apiBase.value}/api/wallet/deposit-requests/${requestId}`, {
        method: 'DELETE',
        headers: {
          'Authorization': `Bearer ${authStore.token}`,
        },
      }) as any

      if (response.success) {
        return response.data
      } else {
        throw new Error(response.message || 'ไม่สามารถยกเลิกคำขอได้')
      }
    } catch (err: any) {
      const msg = err.data?.message || err.response?._data?.message || err.message || 'ไม่สามารถยกเลิกคำขอได้'
      error.value = msg
      console.error('Cancel deposit request error:', err)
      throw new Error(msg)
    } finally {
      isLoading.value = false
    }
  }

  /**
   * Get net amount after fee
   */
  const getNetAmount = (amount: number): number => {
    const fee = calculateFee(amount)
    return amount - fee
  }

  /**
   * Convert wallet to points (for advertising support)
   */
  const convertToPoints = async (amount: number) => {
    try {
      isLoading.value = true
      error.value = null

      // Check if user has enough wallet balance
      if (wallet.value < amount) {
        throw new Error(`ยอดเงินของคุณไม่เพียงพอ (ต้องการ ${formatMoney(amount)}, มีอยู่ ${formatMoney(wallet.value)})`)
      }

      const response = await $fetch(`${apiBase.value}/api/wallet/convert-to-points`, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${authStore.token}`,
        },
        body: {
          amount: amount,
        },
      }) as any

      if (response.success) {
        // Update auth store with new wallet balance
        if (response.data) {
          authStore.setWallet(response.data.new_wallet_balance || 0)
        }
        return response.data
      } else {
        throw new Error(response.message || 'Failed to convert wallet to points')
      }
    } catch (err: any) {
      const msg = err.data?.message || err.response?._data?.message || err.message || 'Failed to convert wallet to points'
      error.value = msg
      console.error('Convert wallet to points error:', err)
      throw new Error(msg)
    } finally {
      isLoading.value = false
    }
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
    convertToPoints,
    getTransactions,

    // Deposit Request Methods
    createDepositRequest,
    getDepositRequests,
    cancelDepositRequest,

    // Helpers
    formatMoney,
    canWithdraw,
    calculateFee,
    getNetAmount,
  }
}
