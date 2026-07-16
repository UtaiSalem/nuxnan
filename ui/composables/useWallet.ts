import { ref, computed } from 'vue'
import { useAuthStore } from '~/stores/auth'

/**
 * Withdrawal rules — must mirror api/nuxnanravel/config/wallet.php.
 * Fee = max(amount * WITHDRAW_FEE_RATE, WITHDRAW_FEE_MIN), rounded to 2 dp.
 */
/** Minimum withdrawal amount in THB */
export const WITHDRAW_MIN_AMOUNT = 25

/** Percentage fee rate (0.01 = 1%) */
export const WITHDRAW_FEE_RATE = 0.01

/** Minimum fee floor in THB */
export const WITHDRAW_FEE_MIN = 5
export const WITHDRAW_DAILY_LIMIT = 100000
export const WITHDRAW_MONTHLY_LIMIT = 500000

/** Minimum transfer amount in THB — must mirror WalletController::transfer (min:10) */
export const TRANSFER_MIN_AMOUNT = 10

export type WithdrawalStatus = 'pending' | 'under_review' | 'approved' | 'processing' | 'completed' | 'paid' | 'rejected' | 'failed' | 'cancelled'

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

      if (!response.success) {
        throw new Error(response.message || 'Failed to get wallet balance')
      }

      const data = response.data || {}
      
      // Determine next balance check order: total_balance, cash_balance, balance, wallet, current_wallet, current_balance
      const hasBalanceKey = 
        'total_balance' in data || 
        'cash_balance' in data || 
        'balance' in data || 
        'wallet' in data || 
        'current_wallet' in data || 
        'current_balance' in data

      if (!hasBalanceKey) {
        throw new Error('API response does not contain any valid balance keys')
      }

      const nextWallet =
        data.total_balance ??
        data.cash_balance ??
        data.balance ??
        data.wallet ??
        data.current_wallet ??
        data.current_balance ??
        0

      // Sync to store so dashboard card can use cached value immediately
      authStore.setWallet(Number(nextWallet) || 0)

      return response.data
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
    method: 'bank_transfer' | 'promptpay'
    bank_account: {
      bank_name: string
      account_number: string
      account_name: string
    }
    description?: string
    idempotency_key?: string
  }) => {
    try {
      isLoading.value = true
      error.value = null

      const response = await $fetch(`${apiBase.value}/api/wallet/withdraw`, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${authStore.token}`,
        },
        body: { ...data, idempotency_key: data.idempotency_key || crypto.randomUUID() },
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
   * List my withdrawal requests with statuses
   */
  const getMyWithdrawals = async (params: { status?: string, page?: number, per_page?: number } = {}) => {
    try {
      isLoading.value = true
      error.value = null

      const query = new URLSearchParams()
      if (params.status) query.set('status', params.status)
      if (params.page) query.set('page', String(params.page))
      if (params.per_page) query.set('per_page', String(params.per_page))

      const response = await $fetch(`${apiBase.value}/api/wallet/withdrawals?${query.toString()}`, {
        headers: {
          'Authorization': `Bearer ${authStore.token}`,
        },
      }) as any

      if (response.success) {
        return response.data
      } else {
        throw new Error(response.message || 'Failed to load withdrawals')
      }
    } catch (err: any) {
      const msg = err.data?.message || err.response?._data?.message || err.message || 'Failed to load withdrawals'
      error.value = msg
      console.error('Get withdrawals error:', err)
      throw new Error(msg)
    } finally {
      isLoading.value = false
    }
  }

  /**
   * Cancel my own pending withdrawal (refunds money back to wallet)
   */
  const cancelWithdrawal = async (transactionId: number) => {
    try {
      isLoading.value = true
      error.value = null

      const response = await $fetch(`${apiBase.value}/api/wallet/withdrawals/${transactionId}/cancel`, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${authStore.token}`,
        },
      }) as any

      if (response.success) {
        return response.data
      } else {
        throw new Error(response.message || 'Failed to cancel withdrawal')
      }
    } catch (err: any) {
      const msg = err.data?.message || err.response?._data?.message || err.message || 'Failed to cancel withdrawal'
      error.value = msg
      console.error('Cancel withdrawal error:', err)
      throw new Error(msg)
    } finally {
      isLoading.value = false
    }
  }

  /**
   * Download/view the payout slip for my paid withdrawal
   */
  const getWithdrawalProof = async (transactionId: number): Promise<Blob> => {
    const response = await $fetch(`${apiBase.value}/api/wallet/withdrawals/${transactionId}/proof`, {
      headers: {
        'Authorization': `Bearer ${authStore.token}`,
      },
      responseType: 'blob',
    }) as Blob
    return response
  }

  /**
   * Deduct money from wallet (for internal purchases/exams)
   */
  const deduct = async (data: {
    amount: number
    reason: string
    metadata?: Record<string, any>
  }) => {
    try {
      isLoading.value = true
      error.value = null

      const response = await $fetch(`${apiBase.value}/api/wallet/deduct`, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${authStore.token}`,
        },
        body: data,
      }) as any

      if (response.success) {
        // Update wallet balance in store
        if (response.data && response.data.new_balance !== undefined) {
          authStore.setWallet(response.data.new_balance)
        }
        return response.data
      } else {
        throw new Error(response.message || 'Failed to deduct money')
      }
    } catch (err: any) {
      const msg = err.data?.message || err.response?._data?.message || err.message || 'Failed to deduct money'
      error.value = msg
      console.error('Deduct error:', err)
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
    return amount >= WITHDRAW_MIN_AMOUNT && wallet.value >= amount
  }

  /**
   * Calculate withdrawal fee: max(amount * rate, min floor), rounded to 2 dp.
   * Mirrors WalletService::withdraw() in the backend.
   */
  const calculateFee = (amount: number): number => {
    return Math.round(Math.max(amount * WITHDRAW_FEE_RATE, WITHDRAW_FEE_MIN) * 100) / 100
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
    return Math.round((amount - fee) * 100) / 100
  }

  /**
   * Strip everything but digits from a PromptPay number
   */
  const normalizePromptPay = (raw: string): string => (raw || '').replace(/\D/g, '')

  /**
   * Validate a PromptPay number: Thai mobile (10 digits, 0[689]xxxxxxxx) or
   * national ID (13 digits)
   */
  const validatePromptPay = (digits: string): boolean => /^(0[689]\d{8}|\d{13})$/.test(digits)

  /**
   * Format a PromptPay number for display (081-234-5678 / 1-2345-67890-12-3)
   */
  const formatPromptPay = (raw: string): string => {
    const digits = normalizePromptPay(raw)
    if (digits.length === 10) return digits.replace(/(\d{3})(\d{3})(\d{4})/, '$1-$2-$3')
    if (digits.length === 13) return digits.replace(/(\d{1})(\d{4})(\d{5})(\d{2})(\d{1})/, '$1-$2-$3-$4-$5')
    return digits
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
    getMyWithdrawals,
    cancelWithdrawal,
    getWithdrawalProof,
    deduct,
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
    normalizePromptPay,
    validatePromptPay,
    formatPromptPay,

    // Constants
    WITHDRAW_MIN_AMOUNT,
    WITHDRAW_FEE_RATE,
    WITHDRAW_FEE_MIN,
    TRANSFER_MIN_AMOUNT,
  }
}
