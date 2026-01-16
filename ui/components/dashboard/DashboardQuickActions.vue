<template>
  <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
    <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
      <Icon icon="mdi:lightning-bolt" class="w-5 h-5 text-yellow-500" />
      การกระทำด่วน
    </h2>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
      <NuxtLink 
        v-for="action in actions" 
        :key="action.id"
        :to="action.link"
        class="group action-card rounded-xl p-4 text-center hover:shadow-md transition-all hover:scale-105"
        :class="action.bgClass"
      >
        <div 
          class="w-12 h-12 rounded-xl flex items-center justify-center mx-auto mb-3 group-hover:scale-110 transition-transform"
          :class="action.iconClass"
        >
          <Icon :icon="action.icon" class="w-6 h-6 text-white" />
        </div>
        <p class="font-semibold text-gray-900 dark:text-white text-sm">{{ action.title }}</p>
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ action.description }}</p>
      </NuxtLink>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Icon } from '@iconify/vue'

interface Action {
  id: string
  title: string
  description: string
  icon: string
  link: string
  bgClass: string
  iconClass: string
}

const actions: Action[] = [
  {
    id: 'earn',
    title: 'รับแต้ม',
    description: 'ทำภารกิจต่างๆ',
    icon: 'mdi:star-plus',
    link: '/Earn/Points',
    bgClass: 'bg-gradient-to-br from-purple-50 to-indigo-50 dark:from-purple-900/20 dark:to-indigo-900/20',
    iconClass: 'bg-gradient-to-br from-purple-500 to-indigo-600'
  },
  {
    id: 'convert',
    title: 'แลกแต้ม',
    description: 'เป็นเงิน',
    icon: 'mdi:currency-exchange',
    link: '/Earn/Wallet',
    bgClass: 'bg-gradient-to-br from-emerald-50 to-teal-50 dark:from-emerald-900/20 dark:to-teal-900/20',
    iconClass: 'bg-gradient-to-br from-emerald-500 to-teal-600'
  },
  {
    id: 'rewards',
    title: 'รางวัล',
    description: 'แลกรางวัล',
    icon: 'mdi:gift',
    link: '/Earn/Rewards',
    bgClass: 'bg-gradient-to-br from-amber-50 to-orange-50 dark:from-amber-900/20 dark:to-orange-900/20',
    iconClass: 'bg-gradient-to-br from-amber-500 to-orange-600'
  },
  {
    id: 'achievements',
    title: 'ความสำเร็จ',
    description: 'ติดตามความสำเร็จ',
    icon: 'mdi:trophy',
    link: '/Earn/Gamification',
    bgClass: 'bg-gradient-to-br from-rose-50 to-pink-50 dark:from-rose-900/20 dark:to-pink-900/20',
    iconClass: 'bg-gradient-to-br from-rose-500 to-pink-600'
  }
]
</script>

<style scoped>
.action-card {
  animation: fadeIn 0.5s ease-out;
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
</style>
