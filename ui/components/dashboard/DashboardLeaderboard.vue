<template>
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

    <div v-if="users.length > 0" class="space-y-3">
      <div 
        v-for="(user, index) in users" 
        :key="user.id"
        class="leaderboard-item flex items-center gap-3 p-2 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"
      >
        <div 
          class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-sm flex-shrink-0"
          :class="getRankClass(index)"
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
</template>

<script setup lang="ts">
import { Icon } from '@iconify/vue'

interface LeaderboardUser {
  id: number
  name: string
  total_points: number
}

interface Props {
  users: LeaderboardUser[]
}

defineProps<Props>()

const getRankClass = (index: number): string => {
  if (index === 0) return 'bg-gradient-to-br from-yellow-400 to-amber-500 text-white'
  if (index === 1) return 'bg-gradient-to-br from-gray-300 to-gray-400 text-white'
  if (index === 2) return 'bg-gradient-to-br from-amber-600 to-amber-700 text-white'
  return 'bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-300'
}

const formatPoints = (value: number): string => {
  return new Intl.NumberFormat('th-TH').format(value)
}
</script>

<style scoped>
.leaderboard-item {
  animation: fadeIn 0.3s ease-out;
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateX(10px);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
}
</style>
