import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { useAuthStore } from './auth'

export const useGamificationStore = defineStore('gamification', () => {
  const authStore = useAuthStore()
  const config = useRuntimeConfig()
  const apiBase = config.public.apiBase as string

  // State
  const leaderboard = ref<any[]>([])
  const leaderboardSummary = ref<any>(null)
  const streakInfo = ref<any>(null)
  const achievements = ref<any[]>([])
  const isLoading = ref(false)
  
  // TTL / Caching logic
  const lastFetched = ref<Record<string, number>>({})
  const inFlight = ref<Record<string, Promise<any> | null>>({})
  const TTL = 60_000 // 1 minute

  const shouldFetch = (key: string) => {
    const now = Date.now()
    const last = lastFetched.value[key] || 0
    return now - last > TTL
  }

  // Actions
  async function fetchLeaderboard(params: { limit?: number; force?: boolean } = {}) {
    if (!params.force && !shouldFetch('leaderboard')) return
    if (inFlight.value['leaderboard']) return inFlight.value['leaderboard']
    
    isLoading.value = true
    inFlight.value['leaderboard'] = (async () => {
      try {
        const limit = params.limit || 10
        const response = await $fetch<any>(`${apiBase}/api/gamification/leaderboard/points?limit=${limit}`, {
          headers: { Authorization: `Bearer ${authStore.token}` }
        })
        if (response.success) {
          leaderboard.value = response.data.leaderboard || response.data.users || []
          lastFetched.value['leaderboard'] = Date.now()
        }
      } catch (error) {
        console.error('Failed to fetch leaderboard:', error)
      } finally {
        isLoading.value = false
        inFlight.value['leaderboard'] = null
      }
    })()

    return inFlight.value['leaderboard']
  }

  async function fetchLeaderboardSummary(force = false) {
    if (!force && !shouldFetch('summary')) return
    if (inFlight.value['summary']) return inFlight.value['summary']

    inFlight.value['summary'] = (async () => {
      try {
        const response = await $fetch<any>(`${apiBase}/api/gamification/leaderboard/summary`, {
          headers: { Authorization: `Bearer ${authStore.token}` }
        })
        if (response.success) {
          leaderboardSummary.value = response.data
          lastFetched.value['summary'] = Date.now()
        }
      } catch (error) {
        console.error('Failed to fetch leaderboard summary:', error)
      } finally {
        inFlight.value['summary'] = null
      }
    })()

    return inFlight.value['summary']
  }

  async function fetchStreakInfo(force = false) {
    if (!force && !shouldFetch('streak')) return
    if (inFlight.value['streak']) return inFlight.value['streak']

    inFlight.value['streak'] = (async () => {
      try {
        const response = await $fetch<any>(`${apiBase}/api/gamification/streak`, {
          headers: { Authorization: `Bearer ${authStore.token}` }
        })
        if (response.success) {
          streakInfo.value = response.data
          lastFetched.value['streak'] = Date.now()
        }
      } catch (error) {
        console.error('Failed to fetch streak info:', error)
      } finally {
        inFlight.value['streak'] = null
      }
    })()

    return inFlight.value['streak']
  }

  async function fetchAchievements(force = false) {
    if (!force && !shouldFetch('achievements')) return
    if (inFlight.value['achievements']) return inFlight.value['achievements']

    inFlight.value['achievements'] = (async () => {
      try {
        const response = await $fetch<any>(`${apiBase}/api/gamification/achievements`, {
          headers: { Authorization: `Bearer ${authStore.token}` }
        })
        if (response.success) {
          achievements.value = response.data.achievements || []
          lastFetched.value['achievements'] = Date.now()
        }
      } catch (error) {
        console.error('Failed to fetch achievements:', error)
      } finally {
        inFlight.value['achievements'] = null
      }
    })()

    return inFlight.value['achievements']
  }

  return {
    leaderboard,
    leaderboardSummary,
    streakInfo,
    achievements,
    isLoading,
    fetchLeaderboard,
    fetchLeaderboardSummary,
    fetchStreakInfo,
    fetchAchievements
  }
})
