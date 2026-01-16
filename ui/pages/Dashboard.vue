<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { Icon } from '@iconify/vue'
import { usePoints } from '~/composables/usePoints'
import { useWallet } from '~/composables/useWallet'
import { useRewards } from '~/composables/useRewards'
import { useGamification } from '~/composables/useGamification'
import { useAuthStore } from '~/stores/auth'

definePageMeta({
  layout: 'main',
  middleware: 'auth',
})

useHead({
  title: 'Dashboard | Nuxnan',
})

// Composables
const { 
  points, 
  user, 
  getBalance, 
  getLevelProgress, 
  formatPoints,
  getTransactions 
} = usePoints()

const { 
  wallet, 
  getBalance: getWalletBalance, 
  formatMoney 
} = useWallet()

const { 
  getRewards, 
  rewards, 
  isLoading: rewardsLoading,
  formatPoints: formatRewardsPoints 
} = useRewards()

const { 
  getStreakInfo, 
  getAchievements, 
  getLeaderboardSummary,
  getPointsLeaderboard 
} = useGamification()

const authStore = useAuthStore()

// State
const isLoading = ref(true)
const streakInfo = ref<any>(null)
const achievements = ref<any[]>([])
const recentTransactions = ref<any[]>([])
const leaderboardSummary = ref<any>(null)
const topUsers = ref<any[]>([])
const featuredRewards = ref<any[]>([])

// Computed
const userName = computed(() => user.value?.name || authStore.user?.name || 'ผู้ใช้')
const currentLevel = computed(() => user.value?.level || 1)
const currentXP = computed(() => user.value?.current_xp || 0)
const xpForNextLevel = computed(() => user.value?.xp_for_next_level || 100)
const levelProgress = computed(() => getLevelProgress())

const greeting = computed(() => {
  const hour = new Date().getHours()
  if (hour < 12) return 'สวัสดีตอนเช้า'
  if (hour < 18) return 'สวัสดีตอนบ่าย'
  return 'สวัสดีตอนค่ำ'
})

const currentDate = computed(() => {
  return new Date().toLocaleDateString('th-TH', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
})

// Methods
const loadDashboardData = async () => {
  try {
    isLoading.value = true
    
    // Load all data in parallel
    const [balanceData, walletData, streakData, achievementsData, leaderboardData, pointsLeaderboardData, rewardsData] = await Promise.allSettled([
      getBalance(),
      getWalletBalance(),
      getStreakInfo(),
      getAchievements(),
      getLeaderboardSummary(),
      getPointsLeaderboard({ limit: 5 }),
      getRewards({ per_page: 3 })
    ])

    if (streakData.status === 'fulfilled') {
      streakInfo.value = streakData.value
    }

    if (achievementsData.status === 'fulfilled') {
      achievements.value = achievementsData.value || []
    }

    if (leaderboardData.status === 'fulfilled') {
      leaderboardSummary.value = leaderboardData.value
    }

    if (pointsLeaderboardData.status === 'fulfilled') {
      topUsers.value = pointsLeaderboardData.value?.users || []
    }

    if (rewardsData.status === 'fulfilled') {
      featuredRewards.value = rewardsData.value?.rewards?.slice(0, 3) || []
    }

    // Load recent transactions
    try {
      const transactionsData = await getTransactions({ per_page: 5 })
      recentTransactions.value = transactionsData?.transactions || []
    } catch (error) {
      console.error('Failed to load transactions:', error)
    }
  } catch (error) {
    console.error('Failed to load dashboard data:', error)
  } finally {
    isLoading.value = false
  }
}

const getTransactionIcon = (type: string): string => {
  const icons: Record<string, string> = {
    earn: 'mdi:arrow-up-circle',
    spend: 'mdi:arrow-down-circle',
    refund: 'mdi:refresh-circle',
    transfer: 'mdi:swap-horizontal',
    conversion: 'mdi:currency-exchange',
  }
  return icons[type] || 'mdi:star-circle'
}

const getTransactionColor = (type: string): string => {
  const colors: Record<string, string> = {
    earn: 'text-green-500',
    spend: 'text-red-500',
    refund: 'text-blue-500',
    transfer: 'text-orange-500',
    conversion: 'text-purple-500',
  }
  return colors[type] || 'text-gray-500'
}

const formatDate = (date: string): string => {
  return new Date(date).toLocaleDateString('th-TH', {
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const getStreakIcon = (streak: number): string => {
  if (streak >= 30) return '🔥🔥🔥'
  if (streak >= 14) return '🔥🔥'
  if (streak >= 7) return '🔥'
  return '⚡'
}

const getTypeLabel = (type: string): string => {
  const labels: Record<string, string> = {
    earn: 'รับแต้ม',
    spend: 'ใช้แต้ม',
    refund: 'คืนแต้ม',
    transfer: 'โอนแต้ม',
    conversion: 'แลกแต้ม',
  }
  return labels[type] || type
}

const getRewardTypeIcon = (type: string): string => {
  const icons: Record<string, string> = {
    wallet: 'mdi:wallet',
    badge: 'mdi:medal',
    feature: 'mdi:star-shooting',
    discount: 'mdi:percent',
  }
  return icons[type] || 'mdi:gift'
}

// Lifecycle
onMounted(() => {
  loadDashboardData()
})
</script>

<template>
  <div class="dashboard-page min-h-screen bg-gray-50 dark:bg-vikinger-dark">
    <!-- Header Section - Vikinger Style -->
    <div class="relative overflow-hidden">
      <!-- Background with animated gradient -->
      <div class="absolute inset-0 bg-gradient-to-r from-vikinger-dark via-vikinger-purple to-vikinger-dark"></div>
      <div class="absolute inset-0 bg-[url('/images/patterns/hexagon-pattern.png')] opacity-5"></div>
      
      <!-- Floating particles effect -->
      <div class="absolute inset-0 overflow-hidden">
        <div class="absolute top-10 left-10 w-2 h-2 bg-vikinger-cyan rounded-full animate-pulse"></div>
        <div class="absolute top-20 right-20 w-3 h-3 bg-vikinger-purple rounded-full animate-bounce"></div>
        <div class="absolute bottom-10 left-1/4 w-2 h-2 bg-pink-400 rounded-full animate-ping"></div>
        <div class="absolute top-1/2 right-1/3 w-1.5 h-1.5 bg-yellow-400 rounded-full animate-pulse"></div>
      </div>
      
      <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
          <!-- Left: Welcome Message -->
          <div class="flex items-center gap-6">
            <!-- Avatar with Level Badge -->
            <div class="relative">
              <div class="w-20 h-20 md:w-24 md:h-24 rounded-full bg-gradient-to-br from-vikinger-purple to-vikinger-cyan p-1 shadow-vikinger">
                <img 
                  :src="authStore.user?.avatar || authStore.user?.profile_photo_url || '/images/default-avatar.png'" 
                  :alt="userName"
                  class="w-full h-full rounded-full object-cover border-2 border-vikinger-dark"
                />
              </div>
              <!-- Level Badge -->
              <div class="absolute -bottom-2 -right-2 w-10 h-10 bg-gradient-vikinger rounded-full flex items-center justify-center text-white font-bold text-sm border-3 border-vikinger-dark shadow-lg">
                {{ currentLevel }}
              </div>
            </div>
            
            <div>
              <p class="text-vikinger-cyan text-sm font-medium mb-1">{{ currentDate }}</p>
              <h1 class="text-2xl md:text-3xl lg:text-4xl font-bold text-white mb-2">
                {{ greeting }}, <span class="bg-gradient-to-r from-vikinger-cyan to-vikinger-purple bg-clip-text text-transparent">{{ userName }}</span>! 
                <span class="inline-block animate-wave">👋</span>
              </h1>
              <p class="text-gray-400 text-sm md:text-base">ยินดีต้อนรับสู่การผจญภัยของคุณ</p>
            </div>
          </div>
          
          <!-- Right: Stats Badges -->
          <div class="flex flex-wrap items-center gap-3">
            <!-- Level Badge -->
            <div class="relative group">
              <div class="absolute -inset-0.5 bg-gradient-to-r from-vikinger-purple to-vikinger-cyan rounded-xl blur opacity-30 group-hover:opacity-60 transition duration-300"></div>
              <div class="relative bg-vikinger-dark-100 border border-vikinger-purple/30 rounded-xl px-5 py-3 flex items-center gap-3">
                <div class="w-10 h-10 bg-gradient-vikinger rounded-lg flex items-center justify-center shadow-vikinger">
                  <Icon icon="mdi:shield-star" class="w-5 h-5 text-white" />
                </div>
                <div>
                  <p class="text-xs text-gray-400 uppercase tracking-wider">Level</p>
                  <p class="text-xl font-bold text-white">Lv. {{ currentLevel }}</p>
                </div>
              </div>
            </div>
            
            <!-- XP Progress -->
            <div class="relative group">
              <div class="absolute -inset-0.5 bg-gradient-to-r from-vikinger-cyan to-green-400 rounded-xl blur opacity-30 group-hover:opacity-60 transition duration-300"></div>
              <div class="relative bg-vikinger-dark-100 border border-vikinger-cyan/30 rounded-xl px-5 py-3">
                <div class="flex items-center gap-3 mb-2">
                  <Icon icon="mdi:lightning-bolt" class="w-5 h-5 text-vikinger-cyan" />
                  <span class="text-xs text-gray-400 uppercase tracking-wider">Experience</span>
                </div>
                <div class="flex items-center gap-2">
                  <div class="w-24 h-2 bg-vikinger-dark rounded-full overflow-hidden">
                    <div 
                      class="h-full bg-gradient-to-r from-vikinger-cyan to-green-400 rounded-full transition-all duration-500"
                      :style="{ width: `${levelProgress}%` }"
                    ></div>
                  </div>
                  <span class="text-xs text-gray-400">{{ currentXP }}/{{ xpForNextLevel }}</span>
                </div>
              </div>
            </div>
            
            <!-- Streak Badge -->
            <div v-if="streakInfo" class="relative group">
              <div class="absolute -inset-0.5 bg-gradient-to-r from-orange-500 to-red-500 rounded-xl blur opacity-30 group-hover:opacity-60 transition duration-300"></div>
              <div class="relative bg-vikinger-dark-100 border border-orange-500/30 rounded-xl px-5 py-3 flex items-center gap-3">
                <div class="text-2xl">{{ getStreakIcon(streakInfo.current_streak) }}</div>
                <div>
                  <p class="text-xs text-gray-400 uppercase tracking-wider">Streak</p>
                  <p class="text-xl font-bold text-orange-400">{{ streakInfo.current_streak }} วัน</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Stats Cards -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Points Card -->
        <div class="bg-gradient-to-br from-purple-500 to-indigo-600 rounded-2xl p-6 text-white shadow-lg hover:shadow-xl transition-shadow overflow-hidden">
          <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center">
              <Icon icon="mdi:star" class="w-6 h-6" />
            </div>
            <NuxtLink to="/Earn/Points" class="text-white/80 hover:text-white text-sm whitespace-nowrap">
              ดูทั้งหมด →
            </NuxtLink>
          </div>
          <p class="text-white/80 text-sm mb-1">แต้มสะสม</p>
          <p class="text-xl sm:text-2xl lg:text-xl xl:text-2xl font-bold mb-2 truncate" :title="formatPoints(points)">{{ formatPoints(points) }}</p>
          <div class="bg-white/10 rounded-lg p-2">
            <div class="flex items-center justify-between text-xs mb-1">
              <span>XP Progress</span>
              <span>{{ currentXP }} / {{ xpForNextLevel }}</span>
            </div>
            <div class="h-2 bg-white/20 rounded-full overflow-hidden">
              <div 
                class="h-full bg-white rounded-full transition-all duration-500"
                :style="{ width: `${levelProgress}%` }"
              ></div>
            </div>
          </div>
        </div>

        <!-- Wallet Card -->
        <div class="bg-gradient-to-br from-emerald-500 to-teal-600 rounded-2xl p-6 text-white shadow-lg hover:shadow-xl transition-shadow overflow-hidden">
          <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center">
              <Icon icon="mdi:wallet" class="w-6 h-6" />
            </div>
            <NuxtLink to="/Earn/Wallet" class="text-white/80 hover:text-white text-sm whitespace-nowrap">
              จัดการ →
            </NuxtLink>
          </div>
          <p class="text-white/80 text-sm mb-1">ยอดเงินในกระเป๋า</p>
          <p class="text-xl sm:text-2xl lg:text-xl xl:text-2xl font-bold mb-2 truncate" :title="formatMoney(wallet)">{{ formatMoney(wallet) }}</p>
          <p class="text-white/70 text-xs">1,080 แต้ม = 1 บาท</p>
        </div>

        <!-- Achievements Card -->
        <div class="bg-gradient-to-br from-amber-500 to-orange-600 rounded-2xl p-6 text-white shadow-lg hover:shadow-xl transition-shadow">
          <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center">
              <Icon icon="mdi:trophy" class="w-6 h-6" />
            </div>
            <NuxtLink to="/badges" class="text-white/80 hover:text-white text-sm">
              ดูทั้งหมด →
            </NuxtLink>
          </div>
          <p class="text-white/80 text-sm mb-1">ความสำเร็จ</p>
          <p class="text-3xl font-bold mb-2">
            {{ achievements.filter(a => a.completed).length }} / {{ achievements.length }}
          </p>
          <p class="text-white/70 text-xs">
            {{ achievements.length > 0 ? Math.round((achievements.filter(a => a.completed).length / achievements.length) * 100) : 0 }}% บรรลุแล้ว
          </p>
        </div>

        <!-- Leaderboard Card -->
        <div class="bg-gradient-to-br from-rose-500 to-pink-600 rounded-2xl p-6 text-white shadow-lg hover:shadow-xl transition-shadow">
          <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center">
              <Icon icon="mdi:podium" class="w-6 h-6" />
            </div>
            <NuxtLink to="/Earn/Gamification" class="text-white/80 hover:text-white text-sm">
              อันดับ →
            </NuxtLink>
          </div>
          <p class="text-white/80 text-sm mb-1">อันดับของคุณ</p>
          <p class="text-3xl font-bold mb-2">
            #{{ leaderboardSummary?.points_rank || '-' }}
          </p>
          <p class="text-white/70 text-xs">
            จากทั้งหมด {{ leaderboardSummary?.total_users || 0 }} คน
          </p>
        </div>
      </div>

      <!-- Main Grid -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Left Column (2/3) -->
        <div class="lg:col-span-2 space-y-8">
          <!-- Quick Actions -->
          <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
              <Icon icon="mdi:lightning-bolt" class="w-5 h-5 text-yellow-500" />
              การกระทำด่วน
            </h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
              <NuxtLink 
                to="/Earn/Points"
                class="group bg-gradient-to-br from-purple-50 to-indigo-50 dark:from-purple-900/20 dark:to-indigo-900/20 rounded-xl p-4 text-center hover:shadow-md transition-all hover:scale-105"
              >
                <div class="w-12 h-12 bg-gradient-to-br from-purple-500 to-indigo-600 rounded-xl flex items-center justify-center mx-auto mb-3 group-hover:scale-110 transition-transform">
                  <Icon icon="mdi:star-plus" class="w-6 h-6 text-white" />
                </div>
                <p class="font-semibold text-gray-900 dark:text-white text-sm">รับแต้ม</p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">ทำภารกิจต่างๆ</p>
              </NuxtLink>

              <NuxtLink 
                to="/Earn/Wallet"
                class="group bg-gradient-to-br from-emerald-50 to-teal-50 dark:from-emerald-900/20 dark:to-teal-900/20 rounded-xl p-4 text-center hover:shadow-md transition-all hover:scale-105"
              >
                <div class="w-12 h-12 bg-gradient-to-br from-emerald-500 to-teal-600 rounded-xl flex items-center justify-center mx-auto mb-3 group-hover:scale-110 transition-transform">
                  <Icon icon="mdi:currency-exchange" class="w-6 h-6 text-white" />
                </div>
                <p class="font-semibold text-gray-900 dark:text-white text-sm">แลกแต้ม</p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">เป็นเงิน</p>
              </NuxtLink>

              <NuxtLink 
                to="/Earn/Rewards"
                class="group bg-gradient-to-br from-amber-50 to-orange-50 dark:from-amber-900/20 dark:to-orange-900/20 rounded-xl p-4 text-center hover:shadow-md transition-all hover:scale-105"
              >
                <div class="w-12 h-12 bg-gradient-to-br from-amber-500 to-orange-600 rounded-xl flex items-center justify-center mx-auto mb-3 group-hover:scale-110 transition-transform">
                  <Icon icon="mdi:gift" class="w-6 h-6 text-white" />
                </div>
                <p class="font-semibold text-gray-900 dark:text-white text-sm">รางวัล</p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">แลกรางวัล</p>
              </NuxtLink>

              <NuxtLink 
                to="/Earn/Gamification"
                class="group bg-gradient-to-br from-rose-50 to-pink-50 dark:from-rose-900/20 dark:to-pink-900/20 rounded-xl p-4 text-center hover:shadow-md transition-all hover:scale-105"
              >
                <div class="w-12 h-12 bg-gradient-to-br from-rose-500 to-pink-600 rounded-xl flex items-center justify-center mx-auto mb-3 group-hover:scale-110 transition-transform">
                  <Icon icon="mdi:trophy" class="w-6 h-6 text-white" />
                </div>
                <p class="font-semibold text-gray-900 dark:text-white text-sm">ความสำเร็จ</p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">ติดตามความสำเร็จ</p>
              </NuxtLink>
            </div>
          </div>

          <!-- Recent Transactions -->
          <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
            <div class="flex items-center justify-between mb-6">
              <h2 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                <Icon icon="mdi:history" class="w-5 h-5 text-blue-500" />
                กิจกรรมล่าสุด
              </h2>
              <NuxtLink to="/Earn/Points" class="text-primary-500 hover:text-primary-600 text-sm font-medium">
                ดูทั้งหมด →
              </NuxtLink>
            </div>

            <div v-if="recentTransactions.length > 0" class="space-y-3">
              <div 
                v-for="transaction in recentTransactions" 
                :key="transaction.id"
                class="flex items-center gap-4 p-3 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"
              >
                <div 
                  class="w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0"
                  :class="getTransactionColor(transaction.type)"
                >
                  <Icon :icon="getTransactionIcon(transaction.type)" class="w-5 h-5" />
                </div>
                <div class="flex-grow min-w-0">
                  <p class="font-medium text-gray-900 dark:text-white text-sm truncate">
                    {{ transaction.description || getTypeLabel(transaction.type) }}
                  </p>
                  <p class="text-xs text-gray-500 dark:text-gray-400">
                    {{ formatDate(transaction.created_at) }}
                  </p>
                </div>
                <div 
                  class="text-sm font-bold flex-shrink-0"
                  :class="getTransactionColor(transaction.type)"
                >
                  {{ ['earn', 'refund'].includes(transaction.type) ? '+' : '-' }}{{ formatPoints(transaction.amount) }}
                </div>
              </div>
            </div>

            <div v-else class="text-center py-8 text-gray-500 dark:text-gray-400">
              <Icon icon="mdi:history" class="w-12 h-12 mx-auto mb-3 text-gray-300 dark:text-gray-600" />
              <p>ยังไม่มีกิจกรรม</p>
              <p class="text-sm mt-1">เริ่มทำกิจกรรมเพื่อสะสมแต้ม!</p>
            </div>
          </div>

          <!-- Featured Rewards -->
          <div v-if="featuredRewards.length > 0" class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
            <div class="flex items-center justify-between mb-6">
              <h2 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                <Icon icon="mdi:gift-open" class="w-5 h-5 text-amber-500" />
                รางวัลแนะนำ
              </h2>
              <NuxtLink to="/Earn/Rewards" class="text-primary-500 hover:text-primary-600 text-sm font-medium">
                ดูทั้งหมด →
              </NuxtLink>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div 
                v-for="reward in featuredRewards" 
                :key="reward.id"
                class="bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-700 dark:to-gray-800 rounded-xl p-4 hover:shadow-md transition-all"
              >
                <div class="w-12 h-12 bg-gradient-to-br from-amber-400 to-orange-500 rounded-xl flex items-center justify-center mb-3">
                  <Icon :icon="getRewardTypeIcon(reward.type)" class="w-6 h-6 text-white" />
                </div>
                <h3 class="font-semibold text-gray-900 dark:text-white text-sm mb-1 line-clamp-2">
                  {{ reward.name }}
                </h3>
                <div class="flex items-center gap-1 text-amber-600 dark:text-amber-400 text-sm font-bold">
                  <Icon icon="mdi:star" class="w-4 h-4" />
                  {{ formatRewardsPoints(reward.points_required) }} แต้ม
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Right Column (1/3) -->
        <div class="space-y-8">
          <!-- Achievements Widget -->
          <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg overflow-hidden">
            <div class="bg-gradient-to-r from-indigo-500 to-purple-600 p-4 text-white">
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                  <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center backdrop-blur">
                    <Icon icon="mdi:trophy" class="w-6 h-6" />
                  </div>
                  <div>
                    <h3 class="font-semibold text-sm">ความสำเร็จล่าสุด</h3>
                    <p class="text-xs text-white/80">Achievements</p>
                  </div>
                </div>
                <NuxtLink to="/badges" class="text-xs bg-white/20 px-3 py-1 rounded-full hover:bg-white/30 transition-colors">
                  ดูทั้งหมด
                </NuxtLink>
              </div>
            </div>

            <div class="p-4">
              <div v-if="achievements.filter(a => a.completed).length > 0" class="space-y-3">
                <div 
                  v-for="achievement in achievements.filter(a => a.completed).slice(0, 3)" 
                  :key="achievement.id"
                  class="flex items-center gap-3 p-2 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"
                >
                  <div class="w-10 h-10 rounded-full flex items-center justify-center bg-gradient-to-br from-yellow-400 to-amber-500">
                    <img 
                      v-if="achievement.icon" 
                      :src="achievement.icon" 
                      :alt="achievement.name"
                      class="w-6 h-6"
                    >
                    <Icon v-else icon="mdi:trophy" class="w-5 h-5 text-white" />
                  </div>
                  <div class="flex-grow min-w-0">
                    <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                      {{ achievement.name }}
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                      {{ achievement.description }}
                    </p>
                  </div>
                  <Icon icon="mdi:check-circle" class="w-5 h-5 text-green-500 flex-shrink-0" />
                </div>
              </div>

              <div v-else class="text-center py-4 text-gray-500 dark:text-gray-400">
                <Icon icon="mdi:trophy-outline" class="w-8 h-8 mx-auto mb-2" />
                <p class="text-sm">ยังไม่มีความสำเร็จ</p>
              </div>

              <!-- In Progress -->
              <div v-if="achievements.filter(a => !a.completed && a.progress > 0).length > 0" class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700">
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">กำลังดำเนินการ</p>
                <div class="space-y-2">
                  <div 
                    v-for="achievement in achievements.filter(a => !a.completed && a.progress > 0).slice(0, 2)" 
                    :key="achievement.id"
                    class="bg-gray-50 dark:bg-gray-700 rounded-xl p-3"
                  >
                    <div class="flex items-center justify-between mb-2">
                      <span class="text-sm font-medium text-gray-900 dark:text-white">
                        {{ achievement.name }}
                      </span>
                      <span class="text-xs text-gray-500">{{ achievement.progress }}%</span>
                    </div>
                    <div class="h-2 bg-gray-200 dark:bg-gray-600 rounded-full overflow-hidden">
                      <div 
                        class="h-full bg-gradient-to-r from-indigo-500 to-purple-500 rounded-full transition-all duration-500"
                        :style="{ width: `${achievement.progress}%` }"
                      ></div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Leaderboard Preview -->
          <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
            <div class="flex items-center justify-between mb-4">
              <h2 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                <Icon icon="mdi:podium" class="w-5 h-5 text-rose-500" />
                อันดับแต้ม
              </h2>
              <NuxtLink to="/Earn/Gamification" class="text-primary-500 hover:text-primary-600 text-sm font-medium">
                ดูทั้งหมด →
              </NuxtLink>
            </div>

            <div v-if="topUsers.length > 0" class="space-y-3">
              <div 
                v-for="(user, index) in topUsers" 
                :key="user.id"
                class="flex items-center gap-3 p-2 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"
              >
                <div 
                  class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-sm flex-shrink-0"
                  :class="{
                    'bg-gradient-to-br from-yellow-400 to-amber-500 text-white': index === 0,
                    'bg-gradient-to-br from-gray-300 to-gray-400 text-white': index === 1,
                    'bg-gradient-to-br from-amber-600 to-amber-700 text-white': index === 2,
                    'bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-300': index > 2
                  }"
                >
                  {{ index + 1 }}
                </div>
                <div class="flex-grow min-w-0">
                  <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                    {{ user.name }}
                  </p>
                </div>
                <div class="text-sm font-bold text-amber-600 dark:text-amber-400 flex-shrink-0">
                  {{ formatPoints(user.total_points) }}
                </div>
              </div>
            </div>

            <div v-else class="text-center py-4 text-gray-500 dark:text-gray-400">
              <Icon icon="mdi:podium" class="w-8 h-8 mx-auto mb-2" />
              <p class="text-sm">กำลังโหลดอันดับ...</p>
            </div>
          </div>

          <!-- Tips Section -->
          <div class="bg-gradient-to-br from-indigo-500 to-purple-600 rounded-2xl shadow-lg p-6 text-white">
            <div class="flex items-center gap-2 mb-4">
              <Icon icon="mdi:lightbulb" class="w-5 h-5 text-yellow-300" />
              <h3 class="font-bold">เคล็ดลับสะสมแต้ม</h3>
            </div>
            <ul class="space-y-2 text-sm text-white/90">
              <li class="flex items-start gap-2">
                <Icon icon="mdi:check-circle" class="w-4 h-4 mt-0.5 flex-shrink-0" />
                <span>เข้าสู่ระบบทุกวันเพื่อรับแต้มและสะสม Streak</span>
              </li>
              <li class="flex items-start gap-2">
                <Icon icon="mdi:check-circle" class="w-4 h-4 mt-0.5 flex-shrink-0" />
                <span>เรียนบทเรียนและทำแบบทดสอบเพื่อรับแต้มเพิ่ม</span>
              </li>
              <li class="flex items-start gap-2">
                <Icon icon="mdi:check-circle" class="w-4 h-4 mt-0.5 flex-shrink-0" />
                <span>โพสต์และแชร์เนื้อหาเพื่อรับแต้มพิเศษ</span>
              </li>
              <li class="flex items-start gap-2">
                <Icon icon="mdi:check-circle" class="w-4 h-4 mt-0.5 flex-shrink-0" />
                <span>แนะนำเพื่อนมาเข้าร่วมเพื่อรับแต้มรางวัล</span>
              </li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes wave {
  0%, 100% { transform: rotate(0deg); }
  25% { transform: rotate(20deg); }
  75% { transform: rotate(-20deg); }
}

.animate-wave {
  animation: wave 1.5s ease-in-out infinite;
  transform-origin: 70% 70%;
  display: inline-block;
}

.dashboard-page > div > div {
  animation: fadeIn 0.5s ease-out;
}
</style>
