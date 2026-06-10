<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { Icon } from '@iconify/vue'
import { useDashboardData } from '~/composables/useDashboardData'
import { usePoints } from '~/composables/usePoints'
import { useWallet } from '~/composables/useWallet'
import { useAuthStore } from '~/stores/auth'

// Components
import DashboardHero from '~/components/dashboard/DashboardHero.vue'
import DashboardStatsCard from '~/components/dashboard/DashboardStatsCard.vue'
import DashboardQuickActions from '~/components/dashboard/DashboardQuickActions.vue'
import DashboardActivityFeed from '~/components/dashboard/DashboardActivityFeed.vue'
import DashboardLeaderboard from '~/components/dashboard/DashboardLeaderboard.vue'
import DashboardAchievements from '~/components/dashboard/DashboardAchievements.vue'
import DashboardRewards from '~/components/dashboard/DashboardRewards.vue'

definePageMeta({
  layout: 'main',
  middleware: 'auth',
})

useHead({
  title: 'แดชบอร์ด | Nuxnan',
})

const authStore = useAuthStore()
const { formatPoints } = usePoints()
const { formatMoney } = useWallet()

const {
  points,
  wallet,
  user,
  streakInfo,
  achievements,
  recentTransactions,
  leaderboardSummary,
  topUsers,
  featuredRewards,
  loadingMap,
  levelProgress,
  xpToday,
  xpThisWeek,
  recentXpLogs,
  loadAllData
} = useDashboardData()

const authUser = computed(() => authStore.user)

// Hero reads from authStore only so it is not blocked by dashboard/gamification API refreshes.
const authLevelProgress = computed(() => {
  const u = authUser.value
  if (!u || !u.xp_for_next_level) return 0
  const progress = Math.round(((u.current_xp || 0) / u.xp_for_next_level) * 100)
  return Math.min(100, Math.max(0, progress))
})

const authStreakInfo = computed(() => {
  const streak = Number(authUser.value?.current_streak || 0)
  return streak > 0 ? { current_streak: streak } : null
})

usePageLayoutWidgets({ left: true, right: true })

onMounted(() => {
  loadAllData()
})
</script>

<template>
  <div class="dashboard-page space-y-5">
    <!-- Hero Header (Full Width of Content Area) -->
    <ClientOnly><Teleport to="#hero-slot">
      <DashboardHero 
        :user="authUser"
        :level="authUser?.level || 1"
        :levelProgress="authLevelProgress"
        :streakInfo="authStreakInfo"
        :isLoading="authStore.isLoading || !authUser"
      />
    </Teleport></ClientOnly>

    <!-- Left Widgets (3/12) -->
    <ClientOnly><Teleport to="#left-widgets-slot">
      <div class="space-y-5">
        <!-- Quick Actions (Now in Sidebar) -->
        <DashboardQuickActions />
        
        <!-- Level & Progress Summary (Moved to Sidebar for Balance) -->
        <div class="bg-white dark:bg-vikinger-dark-200 rounded-2xl shadow-sm border border-gray-100 dark:border-vikinger-dark-100 p-4 relative overflow-hidden group">
          <div class="absolute -right-4 -bottom-4 w-16 h-16 bg-vikinger-purple/5 group-hover:bg-vikinger-purple/10 transition-colors rounded-full"></div>
          <h3 class="text-xs font-bold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
            <Icon icon="mdi:shield-star" class="w-4 h-4 text-vikinger-purple" />
            เลเวลของคุณ
          </h3>
          
          <div class="flex items-center gap-3 mb-3">
            <div class="w-10 h-10 bg-gradient-vikinger rounded-xl flex items-center justify-center text-white font-black text-sm shadow-sm">
              {{ user?.level || authStore.user?.level || 1 }}
            </div>
            <div class="flex-1 min-w-0">
              <div class="flex items-center justify-between mb-1">
                <span class="text-[10px] font-bold text-gray-500 uppercase">XP Progress</span>
                <span class="text-[10px] font-black text-vikinger-purple">{{ levelProgress }}%</span>
              </div>
              <div class="h-1.5 bg-gray-100 dark:bg-vikinger-dark-100 rounded-full overflow-hidden">
                <div class="h-full bg-gradient-to-r from-vikinger-purple to-vikinger-cyan rounded-full transition-all duration-1000" :style="{ width: `${levelProgress}%` }"></div>
              </div>
            </div>
          </div>
          
          <div v-if="streakInfo" class="flex items-center justify-between p-2 rounded-xl bg-orange-50 dark:bg-orange-900/10 border border-orange-100 dark:border-orange-900/20">
            <div class="flex items-center gap-2">
              <span class="text-lg">🔥</span>
              <span class="text-[10px] font-bold text-orange-700 dark:text-orange-400">ล็อกอินต่อเนื่อง</span>
            </div>
            <span class="text-xs font-black text-orange-700 dark:text-orange-400">{{ streakInfo.current_streak }} วัน</span>
          </div>
        </div>
      </div>
    </Teleport></ClientOnly>

    <!-- Center Content (6/12 - Default Slot) -->
    <div class="space-y-5">
      <!-- Stats Cards Grid (Adjusted to 2 columns for better fit in center) -->
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <DashboardStatsCard
          type="points"
          :value="formatPoints(points)"
          subtitle="แต้มสะสม"
          link="/Earn/Points"
          :progress="levelProgress"
          :isLoading="loadingMap.points"
        />

        <DashboardStatsCard
          type="wallet"
          :value="formatMoney(wallet)"
          subtitle="กระเป๋าเงิน"
          link="/Earn/Wallet"
          additionalInfo="1,080 แต้ม = 1 บาท"
          :isLoading="loadingMap.wallet"
        />

        <DashboardStatsCard
          type="achievements"
          :value="`${achievements.filter(a => a.completed).length} / ${achievements.length}`"
          subtitle="ความสำเร็จ"
          link="/badges"
          :progress="achievements.length > 0 ? Math.round((achievements.filter(a => a.completed).length / achievements.length) * 100) : 0"
          :isLoading="loadingMap.achievements"
        />

        <DashboardStatsCard
          type="points"
          :value="formatPoints(xpToday)"
          subtitle="XP วันนี้"
          link="/Earn/Gamification"
          color="text-vikinger-cyan"
          :isLoading="loadingMap.leaderboard"
        />

        <DashboardStatsCard
          type="points"
          :value="formatPoints(xpThisWeek)"
          subtitle="XP สัปดาห์นี้"
          link="/Earn/Gamification"
          color="text-vikinger-purple"
          :isLoading="loadingMap.leaderboard"
        />

        <DashboardStatsCard
          type="leaderboard"
          :value="`#${leaderboardSummary?.points_rank || '-'}`"
          subtitle="อันดับของคุณ"
          link="/Earn/Gamification"
          :additionalInfo="`จากผู้ใช้ ${leaderboardSummary?.total_users || 0} คน`"
          :isLoading="loadingMap.leaderboard"
        />
      </div>

      <!-- Recent Transactions/Activity Feed -->
      <DashboardActivityFeed 
        :transactions="recentTransactions"
        :xpLogs="recentXpLogs"
        :isLoading="loadingMap.transactions"
      />
    </div>

    <!-- Right Widgets (3/12) -->
    <ClientOnly><Teleport to="#right-widgets-slot">
      <div class="space-y-5">
        <!-- Recent Achievements (Only if not empty or show compact placeholder) -->
        <DashboardAchievements 
          :achievements="achievements.filter(a => a.completed).slice(0, 3)"
          :isLoading="loadingMap.achievements"
        />

        <!-- Featured Rewards -->
        <DashboardRewards 
          :rewards="featuredRewards"
          :isLoading="loadingMap.rewards"
        />

        <!-- Leaderboard Preview -->
        <DashboardLeaderboard 
          :users="topUsers"
          :isLoading="loadingMap.leaderboard"
        />

        <!-- Tips Section -->
        <div class="bg-gradient-vikinger rounded-xl shadow-vikinger p-5 text-white relative overflow-hidden group">
          <Icon icon="mdi:lightbulb" class="absolute -right-4 -top-4 w-20 h-20 text-white/10 group-hover:scale-110 transition-transform" />
          <div class="relative z-10">
            <h3 class="font-bold text-sm mb-3 flex items-center gap-2 text-white">
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
      </div>
    </Teleport></ClientOnly>
  </div>
</template>


<style scoped>
.shadow-vikinger {
  box-shadow: 0 10px 25px -5px rgba(111, 66, 193, 0.3), 0 8px 10px -6px rgba(111, 66, 193, 0.1);
}

.bg-gradient-vikinger {
  background: linear-gradient(135deg, #8B5CF6 0%, #06B6D4 100%);
}

.dashboard-page {
  animation: fadeIn 0.4s ease-out;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}
</style>
