import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { useAuthStore } from './auth'

export const useGamificationStore = defineStore('gamification', () => {
  const authStore = useAuthStore()
  const config = useRuntimeConfig()
  const apiBase = config.public.apiBase as string

  // State
  const progress = ref<any>(null)
  const dashboard = ref<any>(null)
  const quests = ref<any[]>([])
  const leaderboard = ref<any[]>([])
  const leaderboardSummary = ref<any>(null)
  const streakInfo = ref<any>(null)
  const achievements = ref<any[]>([])
  const isLoading = ref(false)
  
  // Computed
  const weeklySummary = computed(() => dashboard.value?.weekly || null)
  const monthlySummary = computed(() => dashboard.value?.monthly || null)
  const xpToday = computed(() => dashboard.value?.today?.xp || 0)
  const xpThisWeek = computed(() => weeklySummary.value?.xp_earned || 0)
  const levelProgress = computed(() => progress.value?.progress_percentage || 0)
  const recentXpLogs = computed(() => dashboard.value?.recent_xp || [])

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
  async function fetchProgress(force = false) {
    if (!force && !shouldFetch('progress')) return
    if (inFlight.value['progress']) return inFlight.value['progress']

    inFlight.value['progress'] = (async () => {
      try {
        const response = await $fetch<any>(`${apiBase}/api/gamification/progress`, {
          headers: { Authorization: `Bearer ${authStore.token}` }
        })
        if (response.success) {
          progress.value = response.data
          lastFetched.value['progress'] = Date.now()
        }
      } catch (error) {
        console.error('Failed to fetch progress:', error)
      } finally {
        inFlight.value['progress'] = null
      }
    })()

    return inFlight.value['progress']
  }

  async function fetchDashboard(force = false) {
    if (!force && !shouldFetch('dashboard')) return
    if (inFlight.value['dashboard']) return inFlight.value['dashboard']

    inFlight.value['dashboard'] = (async () => {
      try {
        const response = await $fetch<any>(`${apiBase}/api/gamification/dashboard`, {
          headers: { Authorization: `Bearer ${authStore.token}` }
        })
        if (response.success) {
          dashboard.value = response.data
          lastFetched.value['dashboard'] = Date.now()
        }
      } catch (error) {
        console.error('Failed to fetch dashboard:', error)
      } finally {
        inFlight.value['dashboard'] = null
      }
    })()

    return inFlight.value['dashboard']
  }

  async function fetchQuests(force = false) {
    if (!force && !shouldFetch('quests')) return
    if (inFlight.value['quests']) return inFlight.value['quests']

    inFlight.value['quests'] = (async () => {
      try {
        const response = await $fetch<any>(`${apiBase}/api/gamification/quests`, {
          headers: { Authorization: `Bearer ${authStore.token}` }
        })
        if (response.success) {
          quests.value = response.data.quests || []
          lastFetched.value['quests'] = Date.now()
        }
      } catch (error) {
        console.error('Failed to fetch quests:', error)
      } finally {
        inFlight.value['quests'] = null
      }
    })()

    return inFlight.value['quests']
  }

  async function claimQuestReward(questId: number) {
    try {
      const response = await $fetch<any>(`${apiBase}/api/gamification/quests/${questId}/claim`, {
        method: 'POST',
        headers: { Authorization: `Bearer ${authStore.token}` }
      })
      if (response.success) {
        // Refresh data
        await Promise.all([fetchProgress(true), fetchQuests(true), fetchDashboard(true)])
        return response
      }
      return response
    } catch (error) {
      console.error('Failed to claim quest reward:', error)
      throw error
    }
  }

  async function fetchLeaderboard(params: { limit?: number; force?: boolean; type?: string } = {}) {
    const type = params.type || 'points'
    const key = `leaderboard_${type}`
    if (!params.force && !shouldFetch(key)) return
    if (inFlight.value[key]) return inFlight.value[key]
    
    isLoading.value = true
    inFlight.value[key] = (async () => {
      try {
        const limit = params.limit || 10
        const response = await $fetch<any>(`${apiBase}/api/gamification/leaderboard/${type}?limit=${limit}`, {
          headers: { Authorization: `Bearer ${authStore.token}` }
        })
        if (response.success) {
          leaderboard.value = response.data.leaderboard || response.data.users || []
          lastFetched.value[key] = Date.now()
        }
      } catch (error) {
        console.error(`Failed to fetch ${type} leaderboard:`, error)
      } finally {
        isLoading.value = false
        inFlight.value[key] = null
      }
    })()

    return inFlight.value[key]
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
    progress,
    dashboard,
    quests,
    leaderboard,
    leaderboardSummary,
    streakInfo,
    achievements,
    isLoading,
    weeklySummary,
    monthlySummary,
    xpToday,
    xpThisWeek,
    levelProgress,
    recentXpLogs,
    fetchProgress,
    fetchDashboard,
    fetchQuests,
    claimQuestReward,
    fetchLeaderboard,
    fetchLeaderboardSummary,
    fetchStreakInfo,
    fetchAchievements
  }
})
