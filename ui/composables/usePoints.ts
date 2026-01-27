import { ref, computed } from 'vue'
import { useAuthStore } from '~/stores/auth'

export const usePoints = () => {
  const authStore = useAuthStore()
  const config = useRuntimeConfig()
  const apiBase = computed(() => config.public.apiBase || '')
  
  // Reactive state
  const isLoading = ref(false)
  const error = ref<string | null>(null)
  
  // Computed properties
  const points = computed(() => authStore.points)
  const user = computed(() => authStore.user)
  
  /**
   * Get points balance
   */
  const getBalance = async () => {
    try {
      isLoading.value = true
      error.value = null
      
      const response = await $fetch(`${apiBase.value}/api/points/balance`, {
        method: 'GET',
        headers: {
          'Authorization': `Bearer ${authStore.token}`,
        },
      }) as any
      
      if (response.success) {
        // Update auth store with latest data
        if (response.data) {
          authStore.setPoints(response.data.current_points || 0)
        }
        return response.data
      } else {
        throw new Error(response.message || 'Failed to get points balance')
      }
    } catch (err: any) {
      error.value = err.message || 'Failed to get points balance'
      console.error('Get points balance error:', err)
      throw err
    } finally {
      isLoading.value = false
    }
  }
  
  /**
   * Earn points
   */
  const earn = async (data: {
    source_type: string
    source_id?: number
    amount: number
    description?: string
    metadata?: Record<string, any>
  }) => {
    try {
      isLoading.value = true
      error.value = null
      
      const response = await $fetch(`${apiBase.value}/api/points/earn`, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${authStore.token}`,
        },
        body: data,
      }) as any
      
      if (response.success) {
        // Update auth store
        if (response.data) {
          authStore.addPoints(response.data.points_earned || 0)
        }
        
        // Check for achievements
        if (response.data.achievements_unlocked && response.data.achievements_unlocked.length > 0) {
          // Show achievement notifications
          response.data.achievements_unlocked.forEach((achievement: any) => {
            showAchievementNotification(achievement)
          })
        }
        
        return response.data
      } else {
        throw new Error(response.message || 'Failed to earn points')
      }
    } catch (err: any) {
      error.value = err.message || 'Failed to earn points'
      console.error('Earn points error:', err)
      
      // Rollback points in auth store
      if (data.amount) {
        authStore.rollback(data.amount)
      }
      
      throw err
    } finally {
      isLoading.value = false
    }
  }
  
  /**
   * Spend points
   */
  const spend = async (data: {
    source_type: string
    source_id?: number
    amount: number
    description?: string
    metadata?: Record<string, any>
  }) => {
    try {
      isLoading.value = true
      error.value = null
      
      // Check if user has enough points
      if (points.value < data.amount) {
        throw new Error(`แต้มของคุณไม่เพียงพอ (ต้องการ ${data.amount} แต้ม, มีอยู่ ${points.value} แต้ม)`)
      }
      
      // Deduct points from auth store (optimistic update)
      const hasEnough = authStore.deductPoints(data.amount)
      if (!hasEnough) {
        throw new Error('แต้มของคุณไม่เพียงพอ')
      }
      
      const response = await $fetch(`${apiBase.value}/api/points/spend`, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${authStore.token}`,
        },
        body: data,
      }) as any
      
      if (response.success) {
        // Points already deducted, just verify
        return response.data
      } else {
        // Rollback points
        authStore.rollback(data.amount)
        throw new Error(response.message || 'Failed to spend points')
      }
    } catch (err: any) {
      error.value = err.message || 'Failed to spend points'
      console.error('Spend points error:', err)
      throw err
    } finally {
      isLoading.value = false
    }
  }
  
  /**
   * Convert points to wallet money
   */
  const convertToWallet = async (pointsAmount: number) => {
    try {
      isLoading.value = true
      error.value = null
      
      // Check if user has enough points
      if (points.value < pointsAmount) {
        throw new Error(`แต้มของคุณไม่เพียงพอ (ต้องการ ${pointsAmount} แต้ม, มีอยู่ ${points.value} แต้ม)`)
      }
      
      const exchangeRate = 1200 // 1 THB = 1200 points
      const walletAmount = pointsAmount / exchangeRate
      
      // Deduct points from auth store (optimistic update)
      const hasEnough = authStore.deductPoints(pointsAmount)
      if (!hasEnough) {
        throw new Error('แต้มของคุณไม่เพียงพอ')
      }
      
      const response = await $fetch(`${apiBase.value}/api/points/convert`, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${authStore.token}`,
        },
        body: {
          points: pointsAmount,
          target: 'wallet',
        },
      }) as any
      
      if (response.success) {
        // Update auth store with new points balance
        if (response.data) {
          authStore.setPoints(response.data.new_points_balance || 0)
        }
        
        return response.data
      } else {
        // Rollback points
        authStore.rollback(pointsAmount)
        throw new Error(response.message || 'Failed to convert points')
      }
    } catch (err: any) {
      error.value = err.message || 'Failed to convert points'
      console.error('Convert points error:', err)
      throw err
    } finally {
      isLoading.value = false
    }
  }
  
  /**
   * Transfer points to another user
   */
  const transfer = async (data: {
    recipient_id: number
    amount: number
    message?: string
  }) => {
    try {
      isLoading.value = true
      error.value = null
      
      // Validate amount
      if (data.amount <= 0) {
        throw new Error('จำนวนแต้มต้องมากกว่า 0')
      }
      
      // Check if user has enough points
      if (points.value < data.amount) {
        throw new Error(`แต้มของคุณไม่เพียงพอ (ต้องการ ${data.amount} แต้ม, มีอยู่ ${points.value} แต้ม)`)
      }
      
      // Check if recipient is not self
      if (data.recipient_id === authStore.user?.id) {
        throw new Error('ไม่สามารถโอนแต้มให้ตัวเองได้')
      }
      
      // Deduct points (optimistic update)
      const hasEnough = authStore.deductPoints(data.amount)
      if (!hasEnough) {
        throw new Error('แต้มของคุณไม่เพียงพอ')
      }
      
      const response = await $fetch(`${apiBase.value}/api/points/transfer`, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${authStore.token}`,
        },
        body: {
          recipient_id: data.recipient_id,
          amount: data.amount,
          message: data.message || '',
        },
      }) as any
      
      if (response.success) {
        // Update auth store with new points balance
        if (response.data) {
          authStore.setPoints(response.data.new_balance || points.value - data.amount)
        }
        return response.data
      } else {
        // Rollback points
        authStore.rollback(data.amount)
        throw new Error(response.message || 'ไม่สามารถโอนแต้มได้')
      }
    } catch (err: any) {
      error.value = err.message || 'ไม่สามารถโอนแต้มได้'
      console.error('Transfer points error:', err)
      throw err
    } finally {
      isLoading.value = false
    }
  }
  
  /**
   * Get points transactions
   */
  const getTransactions = async (params: {
    type?: string
    source_type?: string
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
      if (params.source_type) queryParams.append('source_type', params.source_type)
      if (params.date_from) queryParams.append('date_from', params.date_from)
      if (params.date_to) queryParams.append('date_to', params.date_to)
      if (params.page) queryParams.append('page', params.page.toString())
      if (params.per_page) queryParams.append('per_page', params.per_page.toString())
      
      const response = await $fetch(`${apiBase.value}/api/points/transactions?${queryParams.toString()}`, {
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
   * Format points number
   */
  const formatPoints = (value: number): string => {
    return new Intl.NumberFormat('th-TH').format(value)
  }
  
  /**
   * Show achievement notification
   */
  const showAchievementNotification = (achievement: any) => {
    // Use SweetAlert or similar notification system
    const SwalLib = (window as any).Swal
    if (typeof SwalLib !== 'undefined') {
      SwalLib.fire({
        icon: 'success',
        title: '🎉 บรรลุความสำเร็จ!',
        html: `
          <div style="text-align: center;">
            <img src="${achievement.icon || '/icons/achievement.png'}" style="width: 64px; height: 64px; margin-bottom: 10px;">
            <h3 style="margin: 0 0 10px 0;">${achievement.name}</h3>
            <p style="margin: 0 0 10px 0; color: #666;">${achievement.description || ''}</p>
            ${achievement.points_reward ? `<p style="margin: 0; font-weight: bold; color: #10b981;">+${achievement.points_reward} แต้ม</p>` : ''}
          </div>
        `,
        confirmButtonText: 'รับทราบ',
        confirmButtonColor: '#10b981',
      })
    }
  }
  
  /**
   * Check if user can spend points
   */
  const canSpend = (amount: number): boolean => {
    return points.value >= amount
  }
  
  /**
   * Get points needed for next level
   */
  const getPointsForNextLevel = (): number => {
    if (!user.value) return 0
    return (user.value.xp_for_next_level || 0) - (user.value.current_xp || 0)
  }
  
  /**
   * Get level progress percentage
   */
  const getLevelProgress = (): number => {
    if (!user.value) return 0
    const xpForNextLevel = user.value.xp_for_next_level || 100
    const currentXp = user.value.current_xp || 0
    return Math.round((currentXp / xpForNextLevel) * 100)
  }
  
  return {
    // State
    points,
    user,
    isLoading,
    error,
    
    // Methods
    getBalance,
    earn,
    spend,
    transfer,
    convertToWallet,
    getTransactions,
    
    // Helpers
    formatPoints,
    canSpend,
    getPointsForNextLevel,
    getLevelProgress,
  }
}
