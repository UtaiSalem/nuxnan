import { ref } from 'vue'
import { useAuthStore } from '~/stores/auth'

export const useFollow = () => {
  const authStore = useAuthStore()
  const config = useRuntimeConfig()
  const apiBase = config.public.apiBase as string

  // Reactive state
  const isLoading = ref(false)
  const error = ref<string | null>(null)

  /**
   * Toggle follow status for a user
   */
  const toggleFollow = async (userId: number) => {
    try {
      isLoading.value = true
      error.value = null

      const response = await $fetch(`${apiBase}/api/follow/toggle`, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${authStore.token}`,
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          user_id: userId,
        }),
      }) as any

      if (response.success) {
        return response.data
      } else {
        throw new Error(response.message || 'Failed to toggle follow')
      }
    } catch (err: any) {
      error.value = err.message || 'Failed to toggle follow'
      console.error('Toggle follow error:', err)
      throw err
    } finally {
      isLoading.value = false
    }
  }

  /**
   * Check if current user is following another user
   */
  const isFollowing = async (userId: number) => {
    try {
      isLoading.value = true
      error.value = null

      const response = await $fetch(`${apiBase}/api/follow/is-following`, {
        method: 'GET',
        headers: {
          'Authorization': `Bearer ${authStore.token}`,
        },
        query: {
          user_id: userId,
        },
      }) as any

      if (response.success) {
        return response.data
      } else {
        throw new Error(response.message || 'Failed to check follow status')
      }
    } catch (err: any) {
      error.value = err.message || 'Failed to check follow status'
      console.error('Check follow status error:', err)
      throw err
    } finally {
      isLoading.value = false
    }
  }

  /**
   * Get followers of a user
   */
  const getFollowers = async (userId: number, params: { per_page?: number; page?: number } = {}) => {
    try {
      isLoading.value = true
      error.value = null

      const queryParams = new URLSearchParams()
      if (params.per_page) queryParams.append('per_page', params.per_page.toString())
      if (params.page) queryParams.append('page', params.page.toString())

      const response = await $fetch(`${apiBase}/api/follow/users/${userId}/followers?${queryParams.toString()}`, {
        method: 'GET',
        headers: {
          'Authorization': `Bearer ${authStore.token}`,
        },
      }) as any

      if (response.success) {
        return response.data
      } else {
        throw new Error(response.message || 'Failed to get followers')
      }
    } catch (err: any) {
      error.value = err.message || 'Failed to get followers'
      console.error('Get followers error:', err)
      throw err
    } finally {
      isLoading.value = false
    }
  }

  /**
   * Get users that a user is following
   */
  const getFollowing = async (userId: number, params: { per_page?: number; page?: number } = {}) => {
    try {
      isLoading.value = true
      error.value = null

      const queryParams = new URLSearchParams()
      if (params.per_page) queryParams.append('per_page', params.per_page.toString())
      if (params.page) queryParams.append('page', params.page.toString())

      const response = await $fetch(`${apiBase}/api/follow/users/${userId}/following?${queryParams.toString()}`, {
        method: 'GET',
        headers: {
          'Authorization': `Bearer ${authStore.token}`,
        },
      }) as any

      if (response.success) {
        return response.data
      } else {
        throw new Error(response.message || 'Failed to get following')
      }
    } catch (err: any) {
      error.value = err.message || 'Failed to get following'
      console.error('Get following error:', err)
      throw err
    } finally {
      isLoading.value = false
    }
  }

  /**
   * Get follow statistics for a user
   */
  const getFollowStats = async (userId: number) => {
    try {
      isLoading.value = true
      error.value = null

      const response = await $fetch(`${apiBase}/api/follow/users/${userId}/stats`, {
        method: 'GET',
        headers: {
          'Authorization': `Bearer ${authStore.token}`,
        },
      }) as any

      if (response.success) {
        return response.data
      } else {
        throw new Error(response.message || 'Failed to get follow stats')
      }
    } catch (err: any) {
      error.value = err.message || 'Failed to get follow stats'
      console.error('Get follow stats error:', err)
      throw err
    } finally {
      isLoading.value = false
    }
  }

  return {
    // State
    isLoading,
    error,

    // Methods
    toggleFollow,
    isFollowing,
    getFollowers,
    getFollowing,
    getFollowStats,
  }
}