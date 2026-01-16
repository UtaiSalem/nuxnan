import { ref, computed } from 'vue'
import { useAuthStore } from '~/stores/auth'

export interface Reward {
  id: number
  name: string
  description: string
  type: 'wallet' | 'badge' | 'feature' | 'discount'
  points_required: number
  value: number
  icon?: string
  image?: string
  quantity_available: number
  quantity_redeemed: number
  is_limited: boolean
  is_active: boolean
  start_date?: string
  end_date?: string
  created_at: string
  updated_at: string
}

export interface UserReward {
  id: number
  user_id: number
  reward_id: number
  status: 'pending' | 'claimed' | 'used' | 'expired' | 'cancelled'
  points_spent: number
  redeemed_at: string
  claimed_at?: string
  used_at?: string
  expires_at?: string
  reward: Reward
}

export interface RewardStats {
  total_rewards: number
  total_redeemed: number
  total_points_spent: number
  rewards_by_type: Record<string, number>
  most_popular: Reward[]
}

export const useRewards = () => {
  const authStore = useAuthStore()
  const config = useRuntimeConfig()
  const apiBase = config.public.apiBase as string
  
  // Reactive state
  const isLoading = ref(false)
  const error = ref<string | null>(null)
  const rewards = ref<Reward[]>([])
  const userRewards = ref<UserReward[]>([])
  
  // Computed properties
  const points = computed(() => authStore.points)
  const user = computed(() => authStore.user)
  
  /**
   * Get all available rewards
   */
  const getRewards = async (params: {
    type?: string
    is_active?: boolean
    page?: number
    per_page?: number
  } = {}) => {
    try {
      isLoading.value = true
      error.value = null
      
      const queryParams = new URLSearchParams()
      if (params.type) queryParams.append('type', params.type)
      if (params.is_active !== undefined) queryParams.append('is_active', params.is_active.toString())
      if (params.page) queryParams.append('page', params.page.toString())
      if (params.per_page) queryParams.append('per_page', params.per_page.toString())
      
      const response = await $fetch(`${apiBase}/api/rewards?${queryParams.toString()}`, {
        method: 'GET',
        headers: {
          'Authorization': `Bearer ${authStore.token}`,
        },
      }) as any
      
      if (response.success) {
        rewards.value = response.data.rewards || response.data
        return response.data
      } else {
        throw new Error(response.message || 'Failed to get rewards')
      }
    } catch (err: any) {
      error.value = err.message || 'Failed to get rewards'
      console.error('Get rewards error:', err)
      throw err
    } finally {
      isLoading.value = false
    }
  }
  
  /**
   * Get reward by ID
   */
  const getReward = async (rewardId: number) => {
    try {
      isLoading.value = true
      error.value = null
      
      const response = await $fetch(`${apiBase}/api/rewards/${rewardId}`, {
        method: 'GET',
        headers: {
          'Authorization': `Bearer ${authStore.token}`,
        },
      }) as any
      
      if (response.success) {
        return response.data
      } else {
        throw new Error(response.message || 'Failed to get reward')
      }
    } catch (err: any) {
      error.value = err.message || 'Failed to get reward'
      console.error('Get reward error:', err)
      throw err
    } finally {
      isLoading.value = false
    }
  }
  
  /**
   * Get user's redeemed rewards
   */
  const getMyRewards = async (params: {
    status?: string
    page?: number
    per_page?: number
  } = {}) => {
    try {
      isLoading.value = true
      error.value = null
      
      const queryParams = new URLSearchParams()
      if (params.status) queryParams.append('status', params.status)
      if (params.page) queryParams.append('page', params.page.toString())
      if (params.per_page) queryParams.append('per_page', params.per_page.toString())
      
      const response = await $fetch(`${apiBase}/api/rewards/my?${queryParams.toString()}`, {
        method: 'GET',
        headers: {
          'Authorization': `Bearer ${authStore.token}`,
        },
      }) as any
      
      if (response.success) {
        userRewards.value = response.data.rewards || response.data
        return response.data
      } else {
        throw new Error(response.message || 'Failed to get user rewards')
      }
    } catch (err: any) {
      error.value = err.message || 'Failed to get user rewards'
      console.error('Get user rewards error:', err)
      throw err
    } finally {
      isLoading.value = false
    }
  }
  
  /**
   * Redeem a reward
   */
  const redeemReward = async (rewardId: number) => {
    try {
      isLoading.value = true
      error.value = null
      
      const response = await $fetch(`${apiBase}/api/rewards/redeem`, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${authStore.token}`,
        },
        body: {
          reward_id: rewardId,
        },
      }) as any
      
      if (response.success) {
        // Update points in auth store
        if (response.data.new_points_balance !== undefined) {
          authStore.setPoints(response.data.new_points_balance)
        }
        
        // Refresh user rewards
        await getMyRewards()
        
        return response.data
      } else {
        throw new Error(response.message || 'Failed to redeem reward')
      }
    } catch (err: any) {
      error.value = err.message || 'Failed to redeem reward'
      console.error('Redeem reward error:', err)
      throw err
    } finally {
      isLoading.value = false
    }
  }
  
  /**
   * Claim a redeemed reward
   */
  const claimReward = async (userRewardId: number) => {
    try {
      isLoading.value = true
      error.value = null
      
      const response = await $fetch(`${apiBase}/api/rewards/${userRewardId}/claim`, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${authStore.token}`,
        },
      }) as any
      
      if (response.success) {
        // Refresh user rewards
        await getMyRewards()
        
        return response.data
      } else {
        throw new Error(response.message || 'Failed to claim reward')
      }
    } catch (err: any) {
      error.value = err.message || 'Failed to claim reward'
      console.error('Claim reward error:', err)
      throw err
    } finally {
      isLoading.value = false
    }
  }
  
  /**
   * Cancel a pending reward
   */
  const cancelReward = async (userRewardId: number) => {
    try {
      isLoading.value = true
      error.value = null
      
      const response = await $fetch(`${apiBase}/api/rewards/${userRewardId}/cancel`, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${authStore.token}`,
        },
      }) as any
      
      if (response.success) {
        // Update points in auth store (refunded)
        if (response.data.new_points_balance !== undefined) {
          authStore.setPoints(response.data.new_points_balance)
        }
        
        // Refresh user rewards
        await getMyRewards()
        
        return response.data
      } else {
        throw new Error(response.message || 'Failed to cancel reward')
      }
    } catch (err: any) {
      error.value = err.message || 'Failed to cancel reward'
      console.error('Cancel reward error:', err)
      throw err
    } finally {
      isLoading.value = false
    }
  }
  
  /**
   * Get reward statistics
   */
  const getStats = async (): Promise<RewardStats | null> => {
    try {
      isLoading.value = true
      error.value = null
      
      const response = await $fetch(`${apiBase}/api/rewards/stats`, {
        method: 'GET',
        headers: {
          'Authorization': `Bearer ${authStore.token}`,
        },
      }) as any
      
      if (response.success) {
        return response.data
      } else {
        throw new Error(response.message || 'Failed to get reward stats')
      }
    } catch (err: any) {
      error.value = err.message || 'Failed to get reward stats'
      console.error('Get reward stats error:', err)
      throw err
    } finally {
      isLoading.value = false
    }
  }
  
  /**
   * Check if user can redeem a reward
   */
  const canRedeem = (reward: Reward): boolean => {
    // Check if user has enough points
    if (points.value < reward.points_required) {
      return false
    }
    
    // Check if reward is active
    if (!reward.is_active) {
      return false
    }
    
    // Check if reward is still available (quantity)
    if (reward.is_limited && reward.quantity_redeemed >= reward.quantity_available) {
      return false
    }
    
    // Check if within date range
    const now = new Date()
    if (reward.start_date && new Date(reward.start_date) > now) {
      return false
    }
    if (reward.end_date && new Date(reward.end_date) < now) {
      return false
    }
    
    return true
  }
  
  /**
   * Get reward type label
   */
  const getTypeLabel = (type: string): string => {
    const labels: Record<string, string> = {
      wallet: 'เงินเข้ากระเป๋า',
      badge: 'เหรียญตรา',
      feature: 'ฟีเจอร์พิเศษ',
      discount: 'ส่วนลด',
    }
    return labels[type] || type
  }
  
  /**
   * Get reward type icon
   */
  const getTypeIcon = (type: string): string => {
    const icons: Record<string, string> = {
      wallet: 'mdi:wallet',
      badge: 'mdi:medal',
      feature: 'mdi:star-shooting',
      discount: 'mdi:percent',
    }
    return icons[type] || 'mdi:gift'
  }
  
  /**
   * Get status label
   */
  const getStatusLabel = (status: string): string => {
    const labels: Record<string, string> = {
      pending: 'รอรับ',
      claimed: 'รับแล้ว',
      used: 'ใช้แล้ว',
      expired: 'หมดอายุ',
      cancelled: 'ยกเลิก',
    }
    return labels[status] || status
  }
  
  /**
   * Get status color class
   */
  const getStatusColor = (status: string): string => {
    const colors: Record<string, string> = {
      pending: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
      claimed: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
      used: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
      expired: 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300',
      cancelled: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
    }
    return colors[status] || 'bg-gray-100 text-gray-800'
  }
  
  /**
   * Format points number
   */
  const formatPoints = (value: number): string => {
    return new Intl.NumberFormat('th-TH').format(value)
  }
  
  return {
    // State
    rewards,
    userRewards,
    points,
    user,
    isLoading,
    error,
    
    // Methods
    getRewards,
    getReward,
    getMyRewards,
    redeemReward,
    claimReward,
    cancelReward,
    getStats,
    
    // Helpers
    canRedeem,
    getTypeLabel,
    getTypeIcon,
    getStatusLabel,
    getStatusColor,
    formatPoints,
  }
}
