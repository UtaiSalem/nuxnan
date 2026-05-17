import { defineStore } from 'pinia'
import { ref } from 'vue'
import { useAuthStore } from './auth'
import { useGamificationStore } from './gamification'

export const useDashboardStore = defineStore('dashboard', () => {
  const authStore = useAuthStore()
  const gamificationStore = useGamificationStore()
  const config = useRuntimeConfig()
  const apiBase = config.public.apiBase as string

  // State
  const recentTransactions = ref<any[]>([])
  const featuredRewards = ref<any[]>([])
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
  async function fetchRecentTransactions(force = false) {
    if (!force && !shouldFetch('transactions')) return
    if (inFlight.value['transactions']) return inFlight.value['transactions']
    
    inFlight.value['transactions'] = (async () => {
      try {
        const response = await $fetch<any>(`${apiBase}/api/points/transactions?per_page=5`, {
          headers: { Authorization: `Bearer ${authStore.token}` }
        })
        if (response.success) {
          recentTransactions.value = response.data.transactions || []
          lastFetched.value['transactions'] = Date.now()
        }
      } catch (error) {
        console.error('Failed to fetch recent transactions:', error)
      } finally {
        inFlight.value['transactions'] = null
      }
    })()

    return inFlight.value['transactions']
  }

  async function fetchFeaturedRewards(force = false) {
    if (!force && !shouldFetch('rewards')) return
    if (inFlight.value['rewards']) return inFlight.value['rewards']
    
    inFlight.value['rewards'] = (async () => {
      try {
        const response = await $fetch<any>(`${apiBase}/api/rewards?per_page=3`, {
          headers: { Authorization: `Bearer ${authStore.token}` }
        })
        if (response.success) {
          featuredRewards.value = response.data.rewards?.slice(0, 3) || []
          lastFetched.value['rewards'] = Date.now()
        }
      } catch (error) {
        console.error('Failed to fetch featured rewards:', error)
      } finally {
        inFlight.value['rewards'] = null
      }
    })()

    return inFlight.value['rewards']
  }

  async function loadAllData(force = false) {
    isLoading.value = true
    try {
      await Promise.allSettled([
        gamificationStore.fetchStreakInfo(force),
        gamificationStore.fetchAchievements(force),
        gamificationStore.fetchLeaderboardSummary(force),
        gamificationStore.fetchLeaderboard({ limit: 10, force }),
        fetchRecentTransactions(force),
        fetchFeaturedRewards(force)
      ])
    } finally {
      isLoading.value = false
    }
  }

  return {
    recentTransactions,
    featuredRewards,
    isLoading,
    fetchRecentTransactions,
    fetchFeaturedRewards,
    loadAllData
  }
})
