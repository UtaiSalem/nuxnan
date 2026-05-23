import { computed } from 'vue'
import { useAuthStore } from '~/stores/auth'
import { useDashboardStore } from '~/stores/dashboard'
import { useGamificationStore } from '~/stores/gamification'
import { usePoints } from './usePoints'
import { useWallet } from './useWallet'

export const useDashboardData = () => {
  const authStore = useAuthStore()
  const dashboardStore = useDashboardStore()
  const gamificationStore = useGamificationStore()
  
  const { getLevelProgress } = usePoints()
  const { getBalance: getWalletBalance } = useWallet()
  const { getBalance: getPointsBalance } = usePoints()

  // Computed state from stores
  const points = computed(() => authStore.points)
  const wallet = computed(() => authStore.wallet)
  const user = computed(() => authStore.user)
  
  const streakInfo = computed(() => gamificationStore.streakInfo)
  const achievements = computed(() => gamificationStore.achievements)
  const leaderboardSummary = computed(() => gamificationStore.leaderboardSummary)
  const topUsers = computed(() => gamificationStore.leaderboard)
  
  const xpToday = computed(() => gamificationStore.xpToday)
  const xpThisWeek = computed(() => gamificationStore.xpThisWeek)
  const levelProgress = computed(() => gamificationStore.levelProgress)
  const recentXpLogs = computed(() => gamificationStore.recentXpLogs)

  const recentTransactions = computed(() => dashboardStore.recentTransactions)
  const featuredRewards = computed(() => dashboardStore.featuredRewards)
  
  const isLoading = computed(() => dashboardStore.isLoading)

  // Loading map for specific sections (derived from stores if needed, or simplified)
  const loadingMap = computed(() => ({
    points: false,
    wallet: false,
    streak: false,
    achievements: false,
    transactions: false,
    leaderboard: gamificationStore.isLoading,
    rewards: false
  }))

  const loadAllData = async (force = false) => {
    // Background refresh for points/wallet if authenticated
    if (authStore.isAuthenticated) {
      void getPointsBalance()
      void getWalletBalance()
    }
    
    // Load other dashboard data via centralized store
    await dashboardStore.loadAllData(force)
  }

  const refreshSection = async (section: string) => {
    switch (section) {
      case 'points': await getPointsBalance(); break
      case 'wallet': await getWalletBalance(); break
      case 'streak': await gamificationStore.fetchStreakInfo(true); break
      case 'achievements': await gamificationStore.fetchAchievements(true); break
      case 'transactions': await dashboardStore.fetchRecentTransactions(true); break
      case 'leaderboard': {
        await gamificationStore.fetchLeaderboardSummary(true)
        await gamificationStore.fetchLeaderboard({ limit: 10, force: true })
        break
      }
      case 'rewards': await dashboardStore.fetchFeaturedRewards(true); break
    }
  }

  return {
    // State
    points,
    wallet,
    user,
    isLoading,
    streakInfo,
    achievements,
    recentTransactions,
    leaderboardSummary,
    topUsers,
    featuredRewards,
    loadingMap,
    xpToday,
    xpThisWeek,
    recentXpLogs,
    
    // Level Helpers
    levelProgress: computed(() => getLevelProgress()),

    // Methods
    loadAllData,
    refreshSection
  }
}
