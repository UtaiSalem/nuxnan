<script setup lang="ts">
import { Icon } from '@iconify/vue'

interface Reward {
  id: number
  name: string
  type: string
  points_required: number
}

interface Props {
  rewards: Reward[]
  isLoading?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  isLoading: false
})

const getRewardTypeIcon = (type: string): string => {
  const icons: Record<string, string> = {
    wallet: 'mdi:wallet',
    badge: 'mdi:medal',
    feature: 'mdi:star-shooting',
    discount: 'mdi:percent',
  }
  return icons[type] || 'mdi:gift'
}

const formatPoints = (value: number): string => {
  return new Intl.NumberFormat('th-TH').format(value)
}
</script>

<template>
  <div class="bg-white dark:bg-vikinger-dark-200 rounded-xl shadow-sm border border-gray-100 dark:border-vikinger-dark-100 overflow-hidden">
    <div class="px-4 py-3 border-b border-gray-100 dark:border-vikinger-dark-100 flex items-center justify-between">
      <h3 class="font-bold text-xs text-gray-900 dark:text-white flex items-center gap-2">
        <Icon icon="mdi:gift" class="w-4 h-4 text-amber-500" />
        รางวัลแนะนำ
      </h3>
    </div>

    <div class="p-3 space-y-3">
      <div v-if="isLoading" class="space-y-3">
        <div v-for="i in 2" :key="i" class="flex items-center gap-3 p-1 animate-pulse">
          <div class="w-10 h-10 bg-gray-200 dark:bg-vikinger-dark-50 rounded-xl"></div>
          <div class="flex-1 space-y-1.5">
            <div class="h-2 w-3/4 bg-gray-200 dark:bg-vikinger-dark-50 rounded"></div>
            <div class="h-1.5 w-1/4 bg-gray-200 dark:bg-vikinger-dark-50 rounded"></div>
          </div>
        </div>
      </div>

      <div v-else-if="rewards.length > 0" class="space-y-2">
        <div 
          v-for="reward in rewards" 
          :key="reward.id" 
          class="flex items-center gap-3 p-1 rounded-lg hover:bg-gray-50 dark:hover:bg-vikinger-dark-100 transition-all group cursor-pointer"
        >
          <div class="w-10 h-10 bg-gray-50 dark:bg-vikinger-dark-50 rounded-xl flex items-center justify-center shrink-0 border border-gray-100 dark:border-vikinger-dark-100 group-hover:border-amber-400 transition-all group-hover:scale-105">
            <Icon :icon="getRewardTypeIcon(reward.type)" class="w-5 h-5 text-amber-500" />
          </div>
          <div class="min-w-0 flex-1">
            <p class="text-[11px] font-bold text-gray-900 dark:text-white line-clamp-1 group-hover:text-amber-600 transition-colors">
              {{ reward.name }}
            </p>
            <p class="text-[9px] text-amber-600 font-black mt-0.5">{{ formatPoints(reward.points_required) }} P</p>
          </div>
        </div>
        
        <NuxtLink to="/Earn/Rewards" class="w-full flex items-center justify-center gap-1.5 py-1.5 rounded-lg bg-vikinger-purple/10 text-vikinger-purple text-[10px] font-bold hover:bg-vikinger-purple hover:text-white transition-all">
          ดูรางวัลทั้งหมด
          <Icon icon="fluent:chevron-right-24-regular" class="w-3 h-3" />
        </NuxtLink>
      </div>

      <div v-else class="flex flex-col items-center justify-center py-6 px-2 text-center group/empty">
        <div class="w-10 h-10 rounded-full bg-gray-50 dark:bg-vikinger-dark-100 flex items-center justify-center mb-2 group-hover/empty:scale-110 transition-transform">
          <Icon icon="mdi:gift-outline" class="w-5 h-5 text-gray-300" />
        </div>
        <p class="text-[10px] font-bold text-gray-400 dark:text-gray-500 italic">ไม่มีรางวัลแนะนำในขณะนี้</p>
        <p class="text-[9px] text-gray-400 dark:text-gray-600 mt-0.5">สะสมแต้มเพื่อแลกของรางวัลเร็วๆ นี้!</p>
      </div>
    </div>
  </div>
</template>

