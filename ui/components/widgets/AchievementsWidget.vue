<template>
  <div class="achievements-widget bg-white dark:bg-gray-800 rounded-2xl shadow-lg overflow-hidden">
    <!-- Header -->
    <div class="bg-gradient-to-r from-indigo-500 to-purple-600 p-4 text-white">
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-2">
          <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center backdrop-blur">
            <Icon icon="mdi:trophy" class="w-6 h-6" />
          </div>
          <div>
            <h3 class="font-semibold text-sm">ความสำเร็จ</h3>
            <p class="text-xs text-white/80">Achievements</p>
          </div>
        </div>
        <NuxtLink 
          to="/badges"
          class="text-xs bg-white/20 px-3 py-1 rounded-full hover:bg-white/30 transition-colors"
        >
          ดูทั้งหมด
        </NuxtLink>
      </div>
    </div>
    
    <!-- Stats -->
    <div class="p-4 border-b border-gray-100 dark:border-gray-700">
      <div class="grid grid-cols-3 gap-3 text-center">
        <div>
          <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.unlocked }}</p>
          <p class="text-xs text-gray-500 dark:text-gray-400">บรรลุแล้ว</p>
        </div>
        <div>
          <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.total }}</p>
          <p class="text-xs text-gray-500 dark:text-gray-400">ทั้งหมด</p>
        </div>
        <div>
          <p class="text-2xl font-bold text-indigo-500">{{ stats.percentage }}%</p>
          <p class="text-xs text-gray-500 dark:text-gray-400">ความสำเร็จ</p>
        </div>
      </div>
    </div>
    
    <!-- Recent Achievements -->
    <div class="p-4">
      <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">ความสำเร็จล่าสุด</p>
      
      <div v-if="isLoading" class="flex justify-center py-4">
        <Icon icon="mdi:loading" class="w-6 h-6 text-primary-500 animate-spin" />
      </div>
      
      <div v-else-if="recentAchievements.length > 0" class="space-y-2">
        <div 
          v-for="achievement in recentAchievements" 
          :key="achievement.id"
          class="flex items-center gap-3 p-2 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
        >
          <div 
            class="w-10 h-10 rounded-full flex items-center justify-center"
            :class="achievement.completed ? 'bg-gradient-to-br from-yellow-400 to-amber-500' : 'bg-gray-200 dark:bg-gray-600'"
          >
            <img 
              v-if="achievement.icon" 
              :src="achievement.icon" 
              :alt="achievement.name"
              class="w-6 h-6"
            >
            <Icon v-else icon="mdi:trophy" :class="achievement.completed ? 'text-white' : 'text-gray-400'" class="w-5 h-5" />
          </div>
          
          <div class="flex-grow min-w-0">
            <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ achievement.name }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ achievement.description }}</p>
          </div>
          
          <div v-if="achievement.completed" class="flex-shrink-0">
            <Icon icon="mdi:check-circle" class="w-5 h-5 text-green-500" />
          </div>
          <div v-else class="flex-shrink-0 text-right">
            <p class="text-xs text-gray-500">{{ achievement.progress }}%</p>
          </div>
        </div>
      </div>
      
      <div v-else class="text-center py-4 text-gray-500 dark:text-gray-400">
        <Icon icon="mdi:trophy-outline" class="w-8 h-8 mx-auto mb-2" />
        <p class="text-sm">ยังไม่มีความสำเร็จ</p>
      </div>
    </div>
    
    <!-- In Progress -->
    <div v-if="inProgressAchievements.length > 0" class="px-4 pb-4">
      <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">กำลังดำเนินการ</p>
      <div class="space-y-2">
        <div 
          v-for="achievement in inProgressAchievements.slice(0, 2)" 
          :key="achievement.id"
          class="bg-gray-50 dark:bg-gray-700 rounded-xl p-3"
        >
          <div class="flex items-center justify-between mb-2">
            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ achievement.name }}</span>
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
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { Icon } from '@iconify/vue'
import { useGamification } from '~/composables/useGamification'

const { getAchievements, isLoading } = useGamification()

// State
const achievements = ref<any[]>([])
const stats = ref({
  unlocked: 0,
  total: 0,
  percentage: 0
})

// Computed
const recentAchievements = computed(() => {
  return achievements.value
    .filter(a => a.completed)
    .sort((a, b) => new Date(b.completed_at).getTime() - new Date(a.completed_at).getTime())
    .slice(0, 3)
})

const inProgressAchievements = computed(() => {
  return achievements.value
    .filter(a => !a.completed && a.progress > 0)
    .sort((a, b) => b.progress - a.progress)
})

// Load achievements
onMounted(async () => {
  try {
    const data = await getAchievements()
    if (data) {
      achievements.value = data
      
      const unlocked = data.filter((a: any) => a.completed).length
      const total = data.length
      
      stats.value = {
        unlocked,
        total,
        percentage: total > 0 ? Math.round((unlocked / total) * 100) : 0
      }
    }
  } catch (error) {
    console.error('Failed to load achievements:', error)
  }
})
</script>

<style scoped>
.achievements-widget {
  max-width: 100%;
}
</style>
