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
  title: 'แดชบอร์ด | Nuxnan',
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

// สีนุ่มนวลสบายตาสำหรับอันดับ (Muted/Pastel colors)
const getRankBadgeClass = (index: number): string => {
  const classes = [
    'bg-gradient-to-br from-amber-400 to-amber-500 text-white',      // 1st - ทองนุ่มนวล
    'bg-gradient-to-br from-slate-300 to-slate-400 text-white',      // 2nd - เงินนุ่มนวล
    'bg-gradient-to-br from-orange-300 to-orange-400 text-white',    // 3rd - ทองแดงนุ่มนวล
    'bg-sky-400 text-white',                                          // 4th - ฟ้านุ่มนวล
    'bg-emerald-400 text-white',                                      // 5th - เขียวนุ่มนวล
  ]
  return classes[index] || 'bg-slate-300 text-gray-700'
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
  <div class="dashboard-page">
    <Teleport to="#hero-slot" v-if="false">
      <!-- We don't have actual teleport target named hero-slot in main.vue, 
           it uses named slot <template #hero>. 
           So we use named slot below. -->
    </Teleport>

    <!-- Use Main Layout slots -->
    <NuxtLayout name="main">
      <!-- Hero Header -->
      <template #hero>
        <div class="relative overflow-hidden rounded-2xl md:rounded-3xl shadow-2xl">
          <!-- Background with animated gradient -->
          <div class="absolute inset-0 bg-gradient-to-r from-vikinger-dark via-vikinger-purple to-vikinger-dark"></div>
          <div class="absolute inset-0 bg-[url('/images/patterns/hexagon-pattern.svg')] opacity-5"></div>
          
          <!-- Floating particles effect -->
          <div class="absolute inset-0 overflow-hidden">
            <div class="absolute top-10 left-10 w-2 h-2 bg-vikinger-cyan rounded-full animate-pulse"></div>
            <div class="absolute top-20 right-20 w-3 h-3 bg-vikinger-purple rounded-full animate-bounce"></div>
            <div class="absolute bottom-10 left-1/4 w-2 h-2 bg-pink-400 rounded-full animate-ping"></div>
            <div class="absolute top-1/2 right-1/3 w-1.5 h-1.5 bg-yellow-400 rounded-full animate-pulse"></div>
          </div>
          
          <div class="relative px-4 sm:px-6 lg:px-8 py-8 md:py-12">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6 sm:gap-8">
              <!-- Left: Welcome Message -->
              <div class="flex flex-col sm:flex-row items-center sm:items-start gap-4 sm:gap-6 text-center sm:text-left">
                <!-- Avatar with Level Badge -->
                <div class="relative shrink-0">
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
                
                <div class="min-w-0">
                  <p class="text-vikinger-cyan text-sm font-medium mb-1">{{ currentDate }}</p>
                  <h1 class="text-xl md:text-3xl lg:text-4xl font-black text-white mb-2 truncate">
                    {{ greeting }}, <span class="bg-gradient-to-r from-vikinger-cyan to-vikinger-purple bg-clip-text text-transparent">{{ userName }}</span>! 
                  </h1>
                  <p class="text-gray-400 text-sm md:text-base">ยินดีต้อนรับสู่การผจญภัยของคุณ <span class="inline-block animate-wave">👋</span></p>
                </div>
              </div>
              
              <!-- Right: Stats Badges -->
              <div class="flex flex-wrap items-center justify-center lg:justify-end gap-2 sm:gap-3">
                <!-- Level Badge -->
                <div class="relative group">
                  <div class="absolute -inset-0.5 bg-gradient-to-r from-vikinger-purple to-vikinger-cyan rounded-xl blur opacity-30 group-hover:opacity-60 transition duration-300"></div>
                  <div class="relative bg-vikinger-dark-100/80 backdrop-blur border border-vikinger-purple/30 rounded-xl px-3 py-2 flex items-center gap-2">
                    <div class="w-8 h-8 bg-gradient-vikinger rounded-lg flex items-center justify-center shadow-vikinger">
                      <Icon icon="mdi:shield-star" class="w-4 h-4 text-white" />
                    </div>
                    <div>
                      <p class="text-[10px] text-gray-400 uppercase tracking-wider">Level</p>
                      <p class="text-base font-bold text-white leading-none">Lv. {{ currentLevel }}</p>
                    </div>
                  </div>
                </div>
                
                <!-- XP Progress -->
                <div class="relative group">
                  <div class="absolute -inset-0.5 bg-gradient-to-r from-vikinger-cyan to-green-400 rounded-xl blur opacity-30 group-hover:opacity-60 transition duration-300"></div>
                  <div class="relative bg-vikinger-dark-100/80 backdrop-blur border border-vikinger-cyan/30 rounded-xl px-3 py-2">
                    <div class="flex items-center gap-2 mb-1">
                      <Icon icon="mdi:lightning-bolt" class="w-4 h-4 text-vikinger-cyan" />
                      <span class="text-[10px] text-gray-400 uppercase tracking-wider">XP Progress</span>
                    </div>
                    <div class="flex items-center gap-2">
                      <div class="w-20 sm:w-24 h-1.5 bg-vikinger-dark rounded-full overflow-hidden">
                        <div 
                          class="h-full bg-gradient-to-r from-vikinger-cyan to-green-400 rounded-full transition-all duration-500"
                          :style="{ width: `${levelProgress}%` }"
                        ></div>
                      </div>
                      <span class="text-[10px] text-gray-400 font-bold">{{ levelProgress }}%</span>
                    </div>
                  </div>
                </div>
                
                <!-- Streak Badge -->
                <div v-if="streakInfo" class="relative group">
                  <div class="absolute -inset-0.5 bg-gradient-to-r from-orange-500 to-red-500 rounded-xl blur opacity-30 group-hover:opacity-60 transition duration-300"></div>
                  <div class="relative bg-vikinger-dark-100/80 backdrop-blur border border-orange-500/30 rounded-xl px-3 py-2 flex items-center gap-2">
                    <div class="text-xl leading-none">{{ getStreakIcon(streakInfo.current_streak) }}</div>
                    <div>
                      <p class="text-[10px] text-gray-400 uppercase tracking-wider">Streak</p>
                      <p class="text-base font-bold text-orange-400 leading-none">{{ streakInfo.current_streak }} วัน</p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </template>

      <!-- Left Widgets -->
      <template #leftWidgets>
        <!-- Leaderboard Preview -->
        <div class="bg-white dark:bg-vikinger-dark-200 rounded-xl shadow-sm border border-gray-100 dark:border-vikinger-dark-100 overflow-hidden">
          <div class="px-5 py-4 border-b border-gray-100 dark:border-vikinger-dark-100 flex items-center justify-between">
            <h2 class="font-bold text-gray-900 dark:text-white flex items-center gap-2 text-sm">
              <Icon icon="fluent:podium-24-filled" class="w-5 h-5 text-rose-500" />
              อันดับแต้มสูงสุด
            </h2>
            <NuxtLink to="/Earn/Gamification" class="text-vikinger-purple hover:underline text-xs font-bold">
              ทั้งหมด
            </NuxtLink>
          </div>

          <div class="p-3">
            <div v-if="topUsers.length > 0" class="space-y-2">
              <div 
                v-for="(user, index) in topUsers" 
                :key="user.id"
                class="flex items-center gap-3 p-2 rounded-xl hover:bg-gray-50 dark:hover:bg-vikinger-dark-100 transition-colors"
              >
                <div 
                  class="w-7 h-7 rounded-full flex items-center justify-center font-black text-[10px] shrink-0 shadow-sm"
                  :class="getRankBadgeClass(index)"
                >
                  {{ index + 1 }}
                </div>
                <div class="min-w-0 flex-1">
                  <p class="text-xs font-bold text-gray-900 dark:text-white truncate">
                    {{ user.name }}
                  </p>
                  <p class="text-[10px] text-amber-600 dark:text-amber-400 font-black">
                    {{ formatPoints(user.total_points) }} P
                  </p>
                </div>
              </div>
            </div>
            <div v-else class="text-center py-6 text-gray-400 text-xs">
              กำลังโหลดอันดับ...
            </div>
          </div>
        </div>

        <!-- Tips Section -->
        <div class="bg-gradient-vikinger rounded-xl shadow-vikinger p-5 text-white relative overflow-hidden mt-4">
          <Icon icon="mdi:lightbulb" class="absolute -right-4 -top-4 w-20 h-20 text-white/10" />
          <div class="relative z-10">
            <h3 class="font-bold text-sm mb-3 flex items-center gap-2">
              <Icon icon="mdi:lightbulb" class="w-4 h-4 text-yellow-300" />
              เคล็ดลับสะสมแต้ม
            </h3>
            <ul class="space-y-2 text-[11px] text-white/90">
              <li class="flex items-start gap-2">
                <Icon icon="mdi:check-circle" class="w-3.5 h-3.5 mt-0.5 shrink-0" />
                <span>เข้าสู่ระบบทุกวันเพื่อรับแต้มและสะสม Streak</span>
              </li>
              <li class="flex items-start gap-2">
                <Icon icon="mdi:check-circle" class="w-3.5 h-3.5 mt-0.5 shrink-0" />
                <span>เรียนบทเรียนและทำแบบทดสอบเพื่อรับแต้มเพิ่ม</span>
              </li>
            </ul>
          </div>
        </div>
      </template>

      <!-- Main Content Area -->
      <div class="space-y-6">
        <!-- Stats Cards Grid -->
        <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 md:gap-6">
          <!-- Points Card -->
          <div class="bg-gradient-to-br from-purple-600 to-indigo-700 rounded-2xl p-4 sm:p-5 text-white shadow-lg relative overflow-hidden group">
            <Icon icon="mdi:star" class="absolute -right-4 -bottom-4 w-24 h-24 text-white/10 group-hover:scale-110 transition-transform duration-500" />
            <div class="relative z-10">
              <div class="flex items-center justify-between mb-3">
                <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center">
                  <Icon icon="mdi:star" class="w-5 h-5" />
                </div>
                <NuxtLink to="/Earn/Points" class="text-white/70 hover:text-white p-1">
                  <Icon icon="fluent:chevron-right-24-filled" class="w-4 h-4" />
                </NuxtLink>
              </div>
              <p class="text-white/70 text-[10px] font-bold uppercase tracking-wider">แต้มสะสม</p>
              <p class="text-xl sm:text-2xl font-black truncate leading-tight">{{ formatPoints(points) }}</p>
              <div class="mt-3 bg-white/10 rounded-lg p-1.5">
                <div class="h-1 bg-white/20 rounded-full overflow-hidden">
                  <div class="h-full bg-white rounded-full transition-all duration-500" :style="{ width: `${levelProgress}%` }"></div>
                </div>
              </div>
            </div>
          </div>

          <!-- Wallet Card -->
          <div class="bg-gradient-to-br from-emerald-500 to-teal-600 rounded-2xl p-4 sm:p-5 text-white shadow-lg relative overflow-hidden group">
            <Icon icon="mdi:wallet" class="absolute -right-4 -bottom-4 w-24 h-24 text-white/10 group-hover:scale-110 transition-transform duration-500" />
            <div class="relative z-10">
              <div class="flex items-center justify-between mb-3">
                <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center">
                  <Icon icon="mdi:wallet" class="w-5 h-5" />
                </div>
                <NuxtLink to="/Earn/Wallet" class="text-white/70 hover:text-white p-1">
                  <Icon icon="fluent:chevron-right-24-filled" class="w-4 h-4" />
                </NuxtLink>
              </div>
              <p class="text-white/70 text-[10px] font-bold uppercase tracking-wider">กระเป๋าเงิน</p>
              <p class="text-xl sm:text-2xl font-black truncate leading-tight">{{ formatMoney(wallet) }}</p>
              <p class="text-white/60 text-[9px] mt-1 font-bold">1,080 แต้ม = 1 บาท</p>
            </div>
          </div>

          <!-- Achievements Card -->
          <div class="bg-gradient-to-br from-amber-500 to-orange-600 rounded-2xl p-4 sm:p-5 text-white shadow-lg relative overflow-hidden group">
            <Icon icon="mdi:trophy" class="absolute -right-4 -bottom-4 w-24 h-24 text-white/10 group-hover:scale-110 transition-transform duration-500" />
            <div class="relative z-10">
              <div class="flex items-center justify-between mb-3">
                <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center">
                  <Icon icon="mdi:trophy" class="w-5 h-5" />
                </div>
                <NuxtLink to="/badges" class="text-white/70 hover:text-white p-1">
                  <Icon icon="fluent:chevron-right-24-filled" class="w-4 h-4" />
                </NuxtLink>
              </div>
              <p class="text-white/70 text-[10px] font-bold uppercase tracking-wider">ความสำเร็จ</p>
              <p class="text-xl sm:text-2xl font-black leading-tight">
                {{ achievements.filter(a => a.completed).length }} / {{ achievements.length }}
              </p>
              <p class="text-white/60 text-[9px] mt-1 font-bold">ปลดล็อกแล้ว {{ achievements.length > 0 ? Math.round((achievements.filter(a => a.completed).length / achievements.length) * 100) : 0 }}%</p>
            </div>
          </div>

          <!-- Rank Card -->
          <div class="bg-gradient-to-br from-rose-500 to-pink-600 rounded-2xl p-4 sm:p-5 text-white shadow-lg relative overflow-hidden group">
            <Icon icon="mdi:podium" class="absolute -right-4 -bottom-4 w-24 h-24 text-white/10 group-hover:scale-110 transition-transform duration-500" />
            <div class="relative z-10">
              <div class="flex items-center justify-between mb-3">
                <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center">
                  <Icon icon="mdi:podium" class="w-5 h-5" />
                </div>
                <NuxtLink to="/Earn/Gamification" class="text-white/70 hover:text-white p-1">
                  <Icon icon="fluent:chevron-right-24-filled" class="w-4 h-4" />
                </NuxtLink>
              </div>
              <p class="text-white/70 text-[10px] font-bold uppercase tracking-wider">อันดับของคุณ</p>
              <p class="text-xl sm:text-2xl font-black leading-tight">#{{ leaderboardSummary?.points_rank || '-' }}</p>
              <p class="text-white/60 text-[9px] mt-1 font-bold">จากผู้ใช้ {{ leaderboardSummary?.total_users || 0 }} คน</p>
            </div>
          </div>
        </div>

        <!-- Quick Actions -->
        <div class="bg-white dark:bg-vikinger-dark-200 rounded-2xl shadow-sm border border-gray-100 dark:border-vikinger-dark-100 p-4 sm:p-6">
          <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4 sm:mb-6 flex items-center gap-2">
            <Icon icon="mdi:lightning-bolt" class="w-5 h-5 text-yellow-500" />
            เมนูเข้าถึงด่วน
          </h2>
          <div class="grid grid-cols-2 md:grid-cols-4 gap-3 sm:gap-4">
            <NuxtLink to="/Earn/Points" class="group flex flex-col items-center p-3 sm:p-4 rounded-xl hover:bg-gray-50 dark:hover:bg-vikinger-dark-100 transition-all border border-transparent hover:border-vikinger-purple/20">
              <div class="w-12 h-12 bg-gradient-to-br from-purple-500 to-indigo-600 rounded-2xl flex items-center justify-center mb-3 shadow-lg group-hover:scale-110 transition-transform">
                <Icon icon="mdi:star-plus" class="w-6 h-6 text-white" />
              </div>
              <p class="font-bold text-gray-900 dark:text-white text-xs sm:text-sm">รับแต้ม</p>
            </NuxtLink>

            <NuxtLink to="/Earn/Wallet" class="group flex flex-col items-center p-3 sm:p-4 rounded-xl hover:bg-gray-50 dark:hover:bg-vikinger-dark-100 transition-all border border-transparent hover:border-emerald-500/20">
              <div class="w-12 h-12 bg-gradient-to-br from-emerald-500 to-teal-600 rounded-2xl flex items-center justify-center mb-3 shadow-lg group-hover:scale-110 transition-transform">
                <Icon icon="mdi:currency-exchange" class="w-6 h-6 text-white" />
              </div>
              <p class="font-bold text-gray-900 dark:text-white text-xs sm:text-sm">แลกแต้ม</p>
            </NuxtLink>

            <NuxtLink to="/Earn/Rewards" class="group flex flex-col items-center p-3 sm:p-4 rounded-xl hover:bg-gray-50 dark:hover:bg-vikinger-dark-100 transition-all border border-transparent hover:border-amber-500/20">
              <div class="w-12 h-12 bg-gradient-to-br from-amber-500 to-orange-600 rounded-2xl flex items-center justify-center mb-3 shadow-lg group-hover:scale-110 transition-transform">
                <Icon icon="mdi:gift" class="w-6 h-6 text-white" />
              </div>
              <p class="font-bold text-gray-900 dark:text-white text-xs sm:text-sm">ของรางวัล</p>
            </NuxtLink>

            <NuxtLink to="/Earn/Gamification" class="group flex flex-col items-center p-3 sm:p-4 rounded-xl hover:bg-gray-50 dark:hover:bg-vikinger-dark-100 transition-all border border-transparent hover:border-rose-500/20">
              <div class="w-12 h-12 bg-gradient-to-br from-rose-500 to-pink-600 rounded-2xl flex items-center justify-center mb-3 shadow-lg group-hover:scale-110 transition-transform">
                <Icon icon="mdi:trophy" class="w-6 h-6 text-white" />
              </div>
              <p class="font-bold text-gray-900 dark:text-white text-xs sm:text-sm">ความสำเร็จ</p>
            </NuxtLink>
          </div>
        </div>

        <!-- Recent Transactions -->
        <div class="bg-white dark:bg-vikinger-dark-200 rounded-2xl shadow-sm border border-gray-100 dark:border-vikinger-dark-100 p-4 sm:p-6">
          <div class="flex items-center justify-between mb-6">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
              <Icon icon="mdi:history" class="w-5 h-5 text-blue-500" />
              กิจกรรมล่าสุด
            </h2>
            <NuxtLink to="/Earn/Points" class="text-vikinger-purple hover:underline text-xs font-bold">
              ดูทั้งหมด
            </NuxtLink>
          </div>

          <div v-if="recentTransactions.length > 0" class="space-y-2">
            <div 
              v-for="transaction in recentTransactions" 
              :key="transaction.id"
              class="flex items-center gap-3 p-3 rounded-xl hover:bg-gray-50 dark:hover:bg-vikinger-dark-100 transition-colors group border border-transparent hover:border-gray-100 dark:hover:border-vikinger-dark-50"
            >
              <div 
                class="w-10 h-10 rounded-full flex items-center justify-center shrink-0 bg-gray-50 dark:bg-vikinger-dark-50"
                :class="getTransactionColor(transaction.type)"
              >
                <Icon :icon="getTransactionIcon(transaction.type)" class="w-5 h-5" />
              </div>
              <div class="min-w-0 flex-1">
                <p class="font-bold text-gray-900 dark:text-white text-sm truncate">
                  {{ transaction.description || getTypeLabel(transaction.type) }}
                </p>
                <p class="text-[10px] text-gray-500 dark:text-gray-400 font-medium">
                  {{ formatDate(transaction.created_at) }}
                </p>
              </div>
              <div 
                class="text-sm font-black shrink-0"
                :class="getTransactionColor(transaction.type)"
              >
                {{ ['earn', 'refund'].includes(transaction.type) ? '+' : '-' }}{{ formatPoints(transaction.amount) }}
              </div>
            </div>
          </div>
          <div v-else class="text-center py-12">
            <Icon icon="fluent:history-24-regular" class="w-12 h-12 text-gray-200 dark:text-gray-700 mx-auto mb-3" />
            <p class="text-gray-500 dark:text-gray-400 text-sm">ยังไม่มีประวัติกิจกรรม</p>
          </div>
        </div>
      </div>

      <!-- Right Widgets -->
      <template #rightWidgets>
        <!-- Recent Achievements -->
        <div class="bg-white dark:bg-vikinger-dark-200 rounded-xl shadow-sm border border-gray-100 dark:border-vikinger-dark-100 overflow-hidden">
          <div class="bg-gradient-to-r from-indigo-500 to-purple-600 px-5 py-4 text-white">
            <div class="flex items-center justify-between">
              <h3 class="font-bold text-sm flex items-center gap-2">
                <Icon icon="mdi:trophy" class="w-5 h-5" />
                ความสำเร็จ
              </h3>
              <span class="text-[10px] bg-white/20 px-2 py-0.5 rounded-full font-bold">RECENT</span>
            </div>
          </div>

          <div class="p-4">
            <div v-if="achievements.filter(a => a.completed).length > 0" class="space-y-3">
              <div 
                v-for="achievement in achievements.filter(a => a.completed).slice(0, 3)" 
                :key="achievement.id"
                class="flex items-center gap-3 group cursor-pointer"
              >
                <div class="w-10 h-10 rounded-full shrink-0 bg-gradient-to-br from-yellow-400 to-amber-500 flex items-center justify-center shadow-sm group-hover:scale-110 transition-transform">
                  <Icon icon="mdi:medal" class="w-6 h-6 text-white" />
                </div>
                <div class="min-w-0 flex-1">
                  <p class="text-xs font-bold text-gray-900 dark:text-white truncate">{{ achievement.name }}</p>
                  <p class="text-[10px] text-gray-500 dark:text-gray-400 truncate">{{ achievement.description }}</p>
                </div>
              </div>
            </div>
            <div v-else class="text-center py-6 text-gray-400 text-xs italic">
              ไม่มีความสำเร็จล่าสุด
            </div>
            
            <NuxtLink to="/badges" class="mt-4 w-full flex items-center justify-center gap-2 py-2.5 rounded-lg bg-gray-50 dark:bg-vikinger-dark-100 text-gray-700 dark:text-white text-xs font-bold hover:bg-gray-100 dark:hover:bg-vikinger-dark-50 transition-colors">
              ดูความสำเร็จทั้งหมด
              <Icon icon="fluent:chevron-right-24-regular" class="w-3.5 h-3.5" />
            </NuxtLink>
          </div>
        </div>

        <!-- Featured Rewards -->
        <div v-if="featuredRewards.length > 0" class="bg-white dark:bg-vikinger-dark-200 rounded-xl shadow-sm border border-gray-100 dark:border-vikinger-dark-100 overflow-hidden mt-4">
           <div class="px-5 py-4 border-b border-gray-100 dark:border-vikinger-dark-100 flex items-center justify-between">
              <h3 class="font-bold text-sm text-gray-900 dark:text-white flex items-center gap-2">
                <Icon icon="mdi:gift" class="w-5 h-5 text-amber-500" />
                รางวัลแนะนำ
              </h3>
           </div>
           <div class="p-4 space-y-4">
              <div v-for="reward in featuredRewards" :key="reward.id" class="flex items-center gap-3 group cursor-pointer">
                <div class="w-12 h-12 bg-gray-50 dark:bg-vikinger-dark-50 rounded-xl flex items-center justify-center shrink-0 border border-gray-100 dark:border-vikinger-dark-100 group-hover:border-amber-400 transition-colors">
                  <Icon :icon="getRewardTypeIcon(reward.type)" class="w-6 h-6 text-amber-500" />
                </div>
                <div class="min-w-0 flex-1">
                  <p class="text-xs font-bold text-gray-900 dark:text-white line-clamp-1">{{ reward.name }}</p>
                  <p class="text-[10px] text-amber-600 font-black">{{ formatRewardsPoints(reward.points_required) }} P</p>
                </div>
              </div>
              <NuxtLink to="/Earn/Rewards" class="w-full flex items-center justify-center gap-2 py-2.5 rounded-lg bg-vikinger-purple/10 text-vikinger-purple text-xs font-bold hover:bg-vikinger-purple hover:text-white transition-all">
                ดูรางวัลทั้งหมด
                <Icon icon="fluent:chevron-right-24-regular" class="w-3.5 h-3.5" />
              </NuxtLink>
           </div>
        </div>
      </template>
    </NuxtLayout>
  </div>
</template>

<style scoped>
@keyframes wave {
  0%, 100% { transform: rotate(0deg); }
  25% { transform: rotate(20deg); }
  75% { transform: rotate(-20deg); }
}

.animate-wave {
  animation: wave 1.5s ease-in-out infinite;
  transform-origin: 70% 70%;
}

.shadow-vikinger {
  box-shadow: 0 10px 25px -5px rgba(111, 66, 193, 0.3), 0 8px 10px -6px rgba(111, 66, 193, 0.1);
}

.bg-gradient-vikinger {
  background: linear-gradient(135deg, #8B5CF6 0%, #06B6D4 100%);
}

/* Smooth layout transitions */
.dashboard-page {
  animation: fadeIn 0.4s ease-out;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}
</style>
