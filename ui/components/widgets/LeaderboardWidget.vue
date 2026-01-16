<template>
  <div class="leaderboard-widget bg-white dark:bg-gray-800 rounded-2xl shadow-lg overflow-hidden">
    <!-- Header -->
    <div class="bg-gradient-to-r from-cyan-500 to-blue-600 p-4 text-white">
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-2">
          <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center backdrop-blur">
            <Icon icon="mdi:chart-line" class="w-6 h-6" />
          </div>
          <div>
            <h3 class="font-semibold text-sm">อันดับ</h3>
            <p class="text-xs text-white/80">Leaderboard</p>
          </div>
        </div>
        <div class="flex gap-1">
          <button 
            v-for="t in types"
            :key="t.value"
            class="text-xs px-2 py-1 rounded-full transition-colors"
            :class="type === t.value ? 'bg-white/30' : 'hover:bg-white/20'"
            @click="type = t.value"
          >
            {{ t.label }}
          </button>
        </div>
      </div>
    </div>
    
    <!-- User Rank -->
    <div v-if="userRank" class="bg-gradient-to-r from-amber-50 to-yellow-50 dark:from-amber-900/20 dark:to-yellow-900/20 p-4 border-b border-gray-100 dark:border-gray-700">
      <div class="flex items-center gap-3">
        <div class="w-12 h-12 rounded-full bg-gradient-to-br from-amber-400 to-orange-500 flex items-center justify-center text-white font-bold text-lg shadow-lg">
          {{ userRank.rank }}
        </div>
        <div class="flex-grow">
          <p class="font-medium text-gray-900 dark:text-white">อันดับของคุณ</p>
          <p class="text-sm text-gray-500 dark:text-gray-400">{{ formatPoints(userRank.points) }} {{ type === 'points' ? 'แต้ม' : 'XP' }}</p>
        </div>
        <div v-if="userRank.change" class="text-right">
          <div 
            class="flex items-center gap-1"
            :class="userRank.change > 0 ? 'text-green-500' : userRank.change < 0 ? 'text-red-500' : 'text-gray-500'"
          >
            <Icon 
              :icon="userRank.change > 0 ? 'mdi:arrow-up' : userRank.change < 0 ? 'mdi:arrow-down' : 'mdi:minus'"
              class="w-4 h-4"
            />
            <span class="text-sm font-medium">{{ Math.abs(userRank.change) }}</span>
          </div>
          <p class="text-xs text-gray-500">จากสัปดาห์ที่แล้ว</p>
        </div>
      </div>
    </div>
    
    <!-- Leaderboard List -->
    <div class="p-4">
      <div v-if="isLoading" class="flex justify-center py-8">
        <Icon icon="mdi:loading" class="w-8 h-8 text-primary-500 animate-spin" />
      </div>
      
      <div v-else-if="leaderboard.length > 0" class="space-y-2">
        <div 
          v-for="(user, index) in leaderboard" 
          :key="user.id"
          class="flex items-center gap-3 p-2 rounded-xl transition-colors"
          :class="index < 3 ? 'bg-gray-50 dark:bg-gray-700' : ''"
        >
          <!-- Rank Badge -->
          <div 
            class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-sm"
            :class="{
              'bg-gradient-to-br from-yellow-400 to-amber-500 text-white': index === 0,
              'bg-gradient-to-br from-gray-300 to-gray-400 text-white': index === 1,
              'bg-gradient-to-br from-orange-400 to-orange-600 text-white': index === 2,
              'bg-gray-100 dark:bg-gray-600 text-gray-600 dark:text-gray-300': index > 2
            }"
          >
            {{ index + 1 }}
          </div>
          
          <!-- Avatar -->
          <div class="relative">
            <img 
              :src="user.avatar || '/images/default-avatar.png'" 
              :alt="user.name"
              class="w-10 h-10 rounded-full object-cover"
            >
            <div 
              v-if="index < 3"
              class="absolute -top-1 -right-1 w-5 h-5 rounded-full flex items-center justify-center"
              :class="{
                'bg-yellow-400': index === 0,
                'bg-gray-300': index === 1,
                'bg-orange-400': index === 2
              }"
            >
              <Icon icon="mdi:crown" class="w-3 h-3 text-white" />
            </div>
          </div>
          
          <!-- User Info -->
          <div class="flex-grow min-w-0">
            <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ user.name }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400">เลเวล {{ user.level }}</p>
          </div>
          
          <!-- Points -->
          <div class="text-right">
            <p class="text-sm font-bold text-gray-900 dark:text-white">{{ formatPoints(user.points) }}</p>
            <p class="text-xs text-gray-500">{{ type === 'points' ? 'แต้ม' : 'XP' }}</p>
          </div>
        </div>
      </div>
      
      <div v-else class="text-center py-8 text-gray-500 dark:text-gray-400">
        <Icon icon="mdi:chart-bar" class="w-12 h-12 mx-auto mb-3" />
        <p>ยังไม่มีข้อมูลอันดับ</p>
      </div>
    </div>
    
    <!-- View More -->
    <div class="px-4 pb-4">
      <NuxtLink 
        to="/members?sort=points"
        class="block w-full py-2 text-center text-primary-500 hover:text-primary-600 text-sm font-medium hover:bg-gray-50 dark:hover:bg-gray-700 rounded-xl transition-colors"
      >
        ดูอันดับทั้งหมด →
      </NuxtLink>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, watch, onMounted } from 'vue'
import { Icon } from '@iconify/vue'
import { useGamification } from '~/composables/useGamification'

const { getLeaderboard, getUserRank, isLoading } = useGamification()

// Types
const types = [
  { value: 'points', label: 'แต้ม' },
  { value: 'weekly', label: 'สัปดาห์' },
  { value: 'monthly', label: 'เดือน' }
]

// State
const type = ref('points')
const leaderboard = ref<any[]>([])
const userRank = ref<any>(null)

// Load leaderboard
const loadLeaderboard = async () => {
  try {
    const data = await getLeaderboard({ type: type.value, limit: 10 })
    if (data) {
      leaderboard.value = data.leaderboard || data
    }
    
    const rankData = await getUserRank({ type: type.value })
    if (rankData) {
      userRank.value = rankData
    }
  } catch (error) {
    console.error('Failed to load leaderboard:', error)
  }
}

// Helpers
const formatPoints = (value: number): string => {
  if (value >= 1000000) {
    return (value / 1000000).toFixed(1) + 'M'
  } else if (value >= 1000) {
    return (value / 1000).toFixed(1) + 'K'
  }
  return new Intl.NumberFormat('th-TH').format(value)
}

// Watch type changes
watch(type, () => {
  loadLeaderboard()
})

// Initial load
onMounted(loadLeaderboard)
</script>

<style scoped>
.leaderboard-widget {
  max-width: 100%;
}
</style>
