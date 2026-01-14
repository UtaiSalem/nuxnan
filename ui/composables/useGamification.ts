import { ref, computed } from 'vue'
import { useAuthStore } from '~/stores/auth'

export const useGamification = () => {
  const authStore = useAuthStore()
  
  // Reactive state
  const isLoading = ref(false)
  const error = ref<string | null>(null)
  
  // Computed properties
  const user = computed(() => authStore.user)
  
  /**
   * Record user login (for streak)
   */
  const recordLogin = async () => {
    try {
      isLoading.value = true
      error.value = null
      
      const response = await $fetch('/api/gamification/login', {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${authStore.token}`,
        },
      }) as any
      
      if (response.success) {
        return response.data
      } else {
        throw new Error(response.message || 'Failed to record login')
      }
    } catch (err: any) {
      error.value = err.message || 'Failed to record login'
      console.error('Record login error:', err)
      throw err
    } finally {
      isLoading.value = false
    }
  }
  
  /**
   * Get streak information
   */
  const getStreakInfo = async () => {
    try {
      isLoading.value = true
      error.value = null
      
      const response = await $fetch('/api/gamification/streak', {
        method: 'GET',
        headers: {
          'Authorization': `Bearer ${authStore.token}`,
        },
      }) as any
      
      if (response.success) {
        return response.data
      } else {
        throw new Error(response.message || 'Failed to get streak info')
      }
    } catch (err: any) {
      error.value = err.message || 'Failed to get streak info'
      console.error('Get streak info error:', err)
      throw err
    } finally {
      isLoading.value = false
    }
  }
  
  /**
   * Get user achievements
   */
  const getAchievements = async () => {
    try {
      isLoading.value = true
      error.value = null
      
      const response = await $fetch('/api/gamification/achievements', {
        method: 'GET',
        headers: {
          'Authorization': `Bearer ${authStore.token}`,
        },
      }) as any
      
      if (response.success) {
        return response.data.achievements
      } else {
        throw new Error(response.message || 'Failed to get achievements')
      }
    } catch (err: any) {
      error.value = err.message || 'Failed to get achievements'
      console.error('Get achievements error:', err)
      throw err
    } finally {
      isLoading.value = false
    }
  }
  
  /**
   * Get available achievements
   */
  const getAvailableAchievements = async () => {
    try {
      isLoading.value = true
      error.value = null
      
      const response = await $fetch('/api/gamification/achievements/available', {
        method: 'GET',
        headers: {
          'Authorization': `Bearer ${authStore.token}`,
        },
      }) as any
      
      if (response.success) {
        return response.data.achievements
      } else {
        throw new Error(response.message || 'Failed to get available achievements')
      }
    } catch (err: any) {
      error.value = err.message || 'Failed to get available achievements'
      console.error('Get available achievements error:', err)
      throw err
    } finally {
      isLoading.value = false
    }
  }
  
  /**
   * Get achievement statistics
   */
  const getAchievementStats = async () => {
    try {
      isLoading.value = true
      error.value = null
      
      const response = await $fetch('/api/gamification/achievements/stats', {
        method: 'GET',
        headers: {
          'Authorization': `Bearer ${authStore.token}`,
        },
      }) as any
      
      if (response.success) {
        return response.data
      } else {
        throw new Error(response.message || 'Failed to get achievement stats')
      }
    } catch (err: any) {
      error.value = err.message || 'Failed to get achievement stats'
      console.error('Get achievement stats error:', err)
      throw err
    } finally {
      isLoading.value = false
    }
  }
  
  /**
   * Get points leaderboard
   */
  const getPointsLeaderboard = async (params: { limit?: number; page?: number } = {}) => {
    try {
      isLoading.value = true
      error.value = null
      
      const queryParams = new URLSearchParams()
      if (params.limit) queryParams.append('limit', params.limit.toString())
      if (params.page) queryParams.append('page', params.page.toString())
      
      const response = await $fetch(`/api/gamification/leaderboard/points?${queryParams.toString()}`, {
        method: 'GET',
        headers: {
          'Authorization': `Bearer ${authStore.token}`,
        },
      }) as any
      
      if (response.success) {
        return response.data
      } else {
        throw new Error(response.message || 'Failed to get points leaderboard')
      }
    } catch (err: any) {
      error.value = err.message || 'Failed to get points leaderboard'
      console.error('Get points leaderboard error:', err)
      throw err
    } finally {
      isLoading.value = false
    }
  }
  
  /**
   * Get streak leaderboard
   */
  const getStreakLeaderboard = async (params: { limit?: number; page?: number } = {}) => {
    try {
      isLoading.value = true
      error.value = null
      
      const queryParams = new URLSearchParams()
      if (params.limit) queryParams.append('limit', params.limit.toString())
      if (params.page) queryParams.append('page', params.page.toString())
      
      const response = await $fetch(`/api/gamification/leaderboard/streak?${queryParams.toString()}`, {
        method: 'GET',
        headers: {
          'Authorization': `Bearer ${authStore.token}`,
        },
      }) as any
      
      if (response.success) {
        return response.data
      } else {
        throw new Error(response.message || 'Failed to get streak leaderboard')
      }
    } catch (err: any) {
      error.value = err.message || 'Failed to get streak leaderboard'
      console.error('Get streak leaderboard error:', err)
      throw err
    } finally {
      isLoading.value = false
    }
  }
  
  /**
   * Get achievement leaderboard
   */
  const getAchievementLeaderboard = async (params: { limit?: number; page?: number } = {}) => {
    try {
      isLoading.value = true
      error.value = null
      
      const queryParams = new URLSearchParams()
      if (params.limit) queryParams.append('limit', params.limit.toString())
      if (params.page) queryParams.append('page', params.page.toString())
      
      const response = await $fetch(`/api/gamification/leaderboard/achievements?${queryParams.toString()}`, {
        method: 'GET',
        headers: {
          'Authorization': `Bearer ${authStore.token}`,
        },
      }) as any
      
      if (response.success) {
        return response.data
      } else {
        throw new Error(response.message || 'Failed to get achievement leaderboard')
      }
    } catch (err: any) {
      error.value = err.message || 'Failed to get achievement leaderboard'
      console.error('Get achievement leaderboard error:', err)
      throw err
    } finally {
      isLoading.value = false
    }
  }
  
  /**
   * Get level leaderboard
   */
  const getLevelLeaderboard = async (params: { limit?: number; page?: number } = {}) => {
    try {
      isLoading.value = true
      error.value = null
      
      const queryParams = new URLSearchParams()
      if (params.limit) queryParams.append('limit', params.limit.toString())
      if (params.page) queryParams.append('page', params.page.toString())
      
      const response = await $fetch(`/api/gamification/leaderboard/level?${queryParams.toString()}`, {
        method: 'GET',
        headers: {
          'Authorization': `Bearer ${authStore.token}`,
        },
      }) as any
      
      if (response.success) {
        return response.data
      } else {
        throw new Error(response.message || 'Failed to get level leaderboard')
      }
    } catch (err: any) {
      error.value = err.message || 'Failed to get level leaderboard'
      console.error('Get level leaderboard error:', err)
      throw err
    } finally {
      isLoading.value = false
    }
  }
  
  /**
   * Get leaderboard summary for current user
   */
  const getLeaderboardSummary = async () => {
    try {
      isLoading.value = true
      error.value = null
      
      const response = await $fetch('/api/gamification/leaderboard/summary', {
        method: 'GET',
        headers: {
          'Authorization': `Bearer ${authStore.token}`,
        },
      }) as any
      
      if (response.success) {
        return response.data
      } else {
        throw new Error(response.message || 'Failed to get leaderboard summary')
      }
    } catch (err: any) {
      error.value = err.message || 'Failed to get leaderboard summary'
      console.error('Get leaderboard summary error:', err)
      throw err
    } finally {
      isLoading.value = false
    }
  }
  
  /**
   * Show streak notification
   */
  const showStreakNotification = (streakData: any) => {
    if (typeof Swal !== 'undefined') {
      Swal.fire({
        icon: streakData.bonus_milestone_reached ? 'success' : 'info',
        title: streakData.bonus_milestone_reached ? '🔥 Streak Bonus!' : '🔥 Streak Updated',
        html: `
          <div style="text-align: center;">
            <p style="font-size: 48px; margin: 0;">${streakData.streak_icon}</p>
            <h3 style="margin: 10px 0;">Streak: ${streakData.current_streak} วัน</h3>
            <p style="margin: 0;">${streakData.streak_level} Level</p>
            ${streakData.bonus_points_earned > 0 ? `<p style="margin: 10px 0; font-weight: bold; color: #10b981;">+${streakData.bonus_points_earned} แต้ม</p>` : ''}
            ${streakData.next_milestone ? `<p style="margin: 10px 0; color: #6b7280;">Bonus ถัดไปที่ ${streakData.next_milestone} วัน (${streakData.days_until_next_bonus} วัน)</p>` : ''}
          </div>
        `,
        confirmButtonText: 'รับทราบ',
        confirmButtonColor: '#10b981',
      })
    }
  }
  
  /**
   * Show achievement notification
   */
  const showAchievementNotification = (achievement: any) => {
    if (typeof Swal !== 'undefined') {
      Swal.fire({
        icon: 'success',
        title: '🏆 บรรลุความสำเร็จ!',
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
  
  return {
    // State
    user,
    isLoading,
    error,
    
    // Methods
    recordLogin,
    getStreakInfo,
    getAchievements,
    getAvailableAchievements,
    getAchievementStats,
    getPointsLeaderboard,
    getStreakLeaderboard,
    getAchievementLeaderboard,
    getLevelLeaderboard,
    getLeaderboardSummary,
    
    // Notifications
    showStreakNotification,
    showAchievementNotification,
  }
}
