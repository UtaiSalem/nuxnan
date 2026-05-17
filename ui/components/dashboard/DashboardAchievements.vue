<script setup lang="ts">
import { Icon } from '@iconify/vue'

interface Achievement {
  id: number
  name: string
  description: string
  completed: boolean
  icon?: string
}

interface Props {
  achievements: Achievement[]
  isLoading?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  isLoading: false
})
</script>

<template>
  <div class="bg-white dark:bg-vikinger-dark-200 rounded-xl shadow-sm border border-gray-100 dark:border-vikinger-dark-100 overflow-hidden">
    <div class="bg-gradient-to-r from-indigo-500/90 to-purple-600/90 px-4 py-3 text-white">
      <div class="flex items-center justify-between">
        <h3 class="font-bold text-xs flex items-center gap-2">
          <Icon icon="mdi:trophy" class="w-4 h-4 text-amber-300" />
          ความสำเร็จ
        </h3>
        <span class="text-[8px] bg-white/20 px-1.5 py-0.5 rounded-full font-bold tracking-tighter">RECENT</span>
      </div>
    </div>

    <div class="p-3 space-y-2">
      <div v-if="isLoading" class="space-y-2">
        <div v-for="i in 2" :key="i" class="flex items-center gap-3 p-1 animate-pulse">
          <div class="w-8 h-8 rounded-full bg-gray-200 dark:bg-vikinger-dark-50"></div>
          <div class="flex-1 space-y-1.5">
            <div class="h-2 w-1/2 bg-gray-200 dark:bg-vikinger-dark-50 rounded"></div>
            <div class="h-1.5 w-1/3 bg-gray-200 dark:bg-vikinger-dark-50 rounded"></div>
          </div>
        </div>
      </div>

      <div v-else-if="achievements.length > 0" class="space-y-1.5">
        <div 
          v-for="achievement in achievements" 
          :key="achievement.id"
          class="flex items-center gap-3 p-1.5 rounded-lg hover:bg-gray-50 dark:hover:bg-vikinger-dark-100 transition-all group cursor-pointer"
        >
          <div class="w-8 h-8 rounded-full shrink-0 bg-gradient-to-br from-yellow-400 to-amber-500 flex items-center justify-center shadow-sm group-hover:scale-110 transition-transform">
            <Icon icon="mdi:medal" class="w-4 h-4 text-white" />
          </div>
          <div class="min-w-0 flex-1">
            <p class="text-[11px] font-bold text-gray-900 dark:text-white truncate group-hover:text-vikinger-purple transition-colors">
              {{ achievement.name }}
            </p>
            <p class="text-[9px] text-gray-500 dark:text-gray-400 truncate leading-none mt-0.5">{{ achievement.description }}</p>
          </div>
        </div>
      </div>
      
      <div v-else class="flex flex-col items-center justify-center py-6 px-2 text-center group/empty">
        <div class="w-10 h-10 rounded-full bg-gray-50 dark:bg-vikinger-dark-100 flex items-center justify-center mb-2 group-hover/empty:scale-110 transition-transform">
          <Icon icon="mdi:trophy-variant-outline" class="w-5 h-5 text-gray-300" />
        </div>
        <p class="text-[10px] font-bold text-gray-400 dark:text-gray-500 italic">ยังไม่มีความสำเร็จล่าสุด</p>
        <p class="text-[9px] text-gray-400 dark:text-gray-600 mt-0.5">ออกไปผจญภัยเพื่อรับเหรียญรางวัล!</p>
      </div>
      
      <NuxtLink to="/badges" class="mt-2 w-full flex items-center justify-center gap-1.5 py-1.5 rounded-lg bg-gray-50 dark:bg-vikinger-dark-100 text-gray-700 dark:text-white text-[10px] font-bold hover:bg-gray-100 dark:hover:bg-vikinger-dark-50 transition-colors">
        ดูความสำเร็จทั้งหมด
        <Icon icon="fluent:chevron-right-24-regular" class="w-3 h-3" />
      </NuxtLink>
    </div>
  </div>
</template>

