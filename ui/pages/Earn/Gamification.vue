<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { Icon } from '@iconify/vue'
import { useAuthStore } from '~/stores/auth'
import { useGamification } from '~/composables/useGamification'
import BaseCard from '~/components/atoms/BaseCard.vue'
import AchievementsDisplay from '~/components/gamification/AchievementsDisplay.vue'
import LeaderboardDisplay from '~/components/gamification/LeaderboardDisplay.vue'
import StreakDisplay from '~/components/gamification/StreakDisplay.vue'

definePageMeta({
  layout: 'main',
  middleware: ['auth']
})

useHead({
  title: 'ความสำเร็จและอันดับ - Nuxni'
})

const authStore = useAuthStore()
const { 
  getAchievements, 
  getLeaderboard, 
  getStreakInfo, 
  getUserLevel,
  isLoading 
} = useGamification()

// State
const activeTab = ref('achievements') // 'achievements' | 'leaderboard' | 'streak'
const achievements = ref<any[]>([])
const leaderboard = ref<any[]>([])
const streak = ref<any>(null)
const level = ref<any>(null)
const stats = ref({
  total_achievements: 0,
  unlocked_achievements: 0,
  locked_achievements: 0,
  completion_percentage: 0,
  total_points_from_achievements: 0
})

// Computed
const user = computed(() => authStore.user)
const points = computed(() => authStore.points || 0)

// Load data
const loadData = async () => {
  try {
    const [achievementsData, leaderboardData, streakData, levelData] = await Promise.all([
      getAchievements(),
      getLeaderboard({ type: 'points', limit: 10 }),
      getStreakInfo(),
      getUserLevel()
    ])
    
    if (achievementsData) {
      achievements.value = achievementsData
      const unlocked = achievementsData.filter((a: any) => a.completed).length
      const total = achievementsData.length
      
      stats.value = {
        total_achievements: total,
        unlocked_achievements: unlocked,
        locked_achievements: total - unlocked,
        completion_percentage: total > 0 ? Math.round((unlocked / total) * 100) : 0,
        total_points_from_achievements: achievementsData
          .filter((a: any) => a.completed)
          .reduce((sum: number, a: any) => sum + (a.points_reward || 0), 0)
      }
    }
    
    if (leaderboardData) {
      leaderboard.value = leaderboardData.leaderboard || leaderboardData
    }
    
    if (streakData) {
      streak.value = streakData
    }
    
    if (levelData) {
      level.value = levelData
    }
  } catch (error) {
    console.error('Failed to load gamification data:', error)
  }
}

// Helpers
const formatPoints = (value: number): string => {
  return new Intl.NumberFormat('th-TH').format(value)
}

onMounted(loadData)
</script>

<template>
  <div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-8 px-4 sm:px-6 lg:px-8">
    <div class="max-w-6xl mx-auto">
      <!-- Page Header -->
      <div class="text-center mb-8">
        <div class="w-20 h-20 mx-auto mb-4 bg-gradient-to-br from-indigo-400 to-purple-600 rounded-full flex items-center justify-center shadow-lg">
          <Icon icon="mdi:trophy" class="w-10 h-10 text-white" />
        </div>
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
          ความสำเร็จและอันดับ
        </h1>
        <p class="text-gray-600 dark:text-gray-400 max-w-2xl mx-auto">
          ติดตามความก้าวหน้าของคุณ บรรลุความสำเร็จ และแข่งขันกับผู้ใช้คนอื่น
        </p>
      </div>

      <!-- Stats Overview -->
      <div class="grid grid-cols-2 lg:grid-cols-5 gap-4 mb-8">
        <!-- Level -->
        <BaseCard class="bg-gradient-to-br from-indigo-500 to-purple-600 border-0">
          <div class="text-white text-center">
            <div class="w-12 h-12 mx-auto mb-2 bg-white/20 rounded-full flex items-center justify-center backdrop-blur">
              <span class="text-2xl font-bold">{{ level?.level || user?.level || 1 }}</span>
            </div>
            <p class="text-sm font-medium opacity-80">เลเวล</p>
          </div>
        </BaseCard>
        
        <!-- Points -->
        <BaseCard>
          <div class="text-center">
            <Icon icon="mdi:star-circle" class="w-8 h-8 text-amber-500 mx-auto mb-2" />
            <p class="text-xl font-bold text-gray-900 dark:text-white">{{ formatPoints(points) }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400">แต้มสะสม</p>
          </div>
        </BaseCard>
        
        <!-- Achievements -->
        <BaseCard>
          <div class="text-center">
            <Icon icon="mdi:trophy" class="w-8 h-8 text-green-500 mx-auto mb-2" />
            <p class="text-xl font-bold text-gray-900 dark:text-white">
              {{ stats.unlocked_achievements }}/{{ stats.total_achievements }}
            </p>
            <p class="text-xs text-gray-500 dark:text-gray-400">ความสำเร็จ</p>
          </div>
        </BaseCard>
        
        <!-- Streak -->
        <BaseCard>
          <div class="text-center">
            <Icon icon="mdi:fire" class="w-8 h-8 text-orange-500 mx-auto mb-2" />
            <p class="text-xl font-bold text-gray-900 dark:text-white">
              {{ streak?.current_streak || 0 }}
            </p>
            <p class="text-xs text-gray-500 dark:text-gray-400">วันติดต่อกัน</p>
          </div>
        </BaseCard>
        
        <!-- Completion -->
        <BaseCard>
          <div class="text-center">
            <Icon icon="mdi:percent" class="w-8 h-8 text-blue-500 mx-auto mb-2" />
            <p class="text-xl font-bold text-gray-900 dark:text-white">
              {{ stats.completion_percentage }}%
            </p>
            <p class="text-xs text-gray-500 dark:text-gray-400">ความสำเร็จ</p>
          </div>
        </BaseCard>
      </div>

      <!-- Level Progress -->
      <BaseCard class="mb-8">
        <div class="p-2">
          <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">ความก้าวหน้าเลเวล</h3>
          
          <div class="flex items-center gap-6">
            <!-- Current Level -->
            <div class="flex-shrink-0">
              <div class="w-16 h-16 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-full flex items-center justify-center text-white">
                <span class="text-2xl font-bold">{{ level?.level || user?.level || 1 }}</span>
              </div>
            </div>
            
            <!-- Progress Bar -->
            <div class="flex-grow">
              <div class="flex items-center justify-between mb-2">
                <span class="text-sm font-medium text-gray-900 dark:text-white">
                  เลเวล {{ level?.level || user?.level || 1 }}
                </span>
                <span class="text-sm text-gray-500 dark:text-gray-400">
                  {{ level?.current_xp || user?.current_xp || 0 }} / {{ level?.xp_for_next_level || user?.xp_for_next_level || 100 }} XP
                </span>
              </div>
              <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                <div 
                  class="h-full bg-gradient-to-r from-indigo-500 to-purple-600 rounded-full transition-all duration-500"
                  :style="{ width: `${level?.progress_percentage || 0}%` }"
                ></div>
              </div>
              <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                อีก {{ (level?.xp_for_next_level || 100) - (level?.current_xp || 0) }} XP จะขึ้นเลเวลถัดไป
              </p>
            </div>
            
            <!-- Next Level -->
            <div class="flex-shrink-0">
              <div class="w-16 h-16 bg-gray-200 dark:bg-gray-700 rounded-full flex items-center justify-center text-gray-400 dark:text-gray-500">
                <span class="text-2xl font-bold">{{ (level?.level || user?.level || 1) + 1 }}</span>
              </div>
            </div>
          </div>
        </div>
      </BaseCard>

      <!-- Tab Navigation -->
      <div class="flex gap-4 mb-6">
        <button
          class="px-6 py-2 rounded-xl font-medium transition-all flex items-center gap-2"
          :class="activeTab === 'achievements' 
            ? 'bg-primary-500 text-white shadow-md' 
            : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700'"
          @click="activeTab = 'achievements'"
        >
          <Icon icon="mdi:trophy" class="w-5 h-5" />
          ความสำเร็จ
        </button>
        <button
          class="px-6 py-2 rounded-xl font-medium transition-all flex items-center gap-2"
          :class="activeTab === 'leaderboard' 
            ? 'bg-primary-500 text-white shadow-md' 
            : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700'"
          @click="activeTab = 'leaderboard'"
        >
          <Icon icon="mdi:chart-line" class="w-5 h-5" />
          อันดับ
        </button>
        <button
          class="px-6 py-2 rounded-xl font-medium transition-all flex items-center gap-2"
          :class="activeTab === 'streak' 
            ? 'bg-primary-500 text-white shadow-md' 
            : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700'"
          @click="activeTab = 'streak'"
        >
          <Icon icon="mdi:fire" class="w-5 h-5" />
          การเข้าใช้งานต่อเนื่อง
        </button>
      </div>

      <!-- Tab Content -->
      <div v-if="isLoading" class="py-12 text-center">
        <Icon icon="mdi:loading" class="w-12 h-12 text-primary-500 animate-spin mx-auto" />
        <p class="mt-4 text-gray-500 dark:text-gray-400">กำลังโหลด...</p>
      </div>
      
      <div v-else>
        <!-- Achievements Tab -->
        <div v-if="activeTab === 'achievements'">
          <AchievementsDisplay />
        </div>
        
        <!-- Leaderboard Tab -->
        <div v-if="activeTab === 'leaderboard'">
          <LeaderboardDisplay />
        </div>
        
        <!-- Streak Tab -->
        <div v-if="activeTab === 'streak'">
          <StreakDisplay />
        </div>
      </div>
    </div>
  </div>
</template>
