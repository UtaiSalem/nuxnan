<script setup lang="ts">
import { Icon } from '@iconify/vue'

const actions = [
  {
    title: 'รับแต้ม',
    icon: 'mdi:star-plus',
    link: '/Earn/Points',
    gradient: 'from-purple-500 to-indigo-600',
    hoverBorder: 'hover:border-purple-500/20'
  },
  {
    title: 'แลกแต้ม',
    icon: 'mdi:currency-exchange',
    link: '/Earn/Wallet',
    gradient: 'from-emerald-500 to-teal-600',
    hoverBorder: 'hover:border-emerald-500/20'
  },
  {
    title: 'ของรางวัล',
    icon: 'mdi:gift',
    link: '/Earn/Rewards',
    gradient: 'from-amber-500 to-orange-600',
    hoverBorder: 'hover:border-amber-500/20'
  },
  {
    title: 'ความสำเร็จ',
    icon: 'mdi:trophy',
    link: '/Earn/Gamification',
    gradient: 'from-rose-500 to-pink-600',
    hoverBorder: 'hover:border-rose-500/20'
  }
]
</script>

<template>
  <div class="bg-white dark:bg-vikinger-dark-200 rounded-2xl shadow-sm border border-gray-100 dark:border-vikinger-dark-100 p-4">
    <h2 class="text-xs md:text-sm font-bold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
      <Icon icon="mdi:lightning-bolt" class="w-4 h-4 text-yellow-500" />
      เมนูเข้าถึงด่วน
    </h2>
    
    <div class="grid grid-cols-2 gap-2 md:gap-3">
      <NuxtLink 
        v-for="action in actions" 
        :key="action.title"
        :to="action.link" 
        class="group flex flex-col items-center p-2.5 rounded-xl hover:bg-gray-50 dark:hover:bg-vikinger-dark-100 transition-all border border-transparent border-gray-100/50 dark:border-vikinger-dark-100 hover:border-vikinger-purple/20"
      >
        <div 
          class="w-9 h-9 bg-gradient-to-br rounded-xl flex items-center justify-center mb-2 shadow-md group-hover:scale-110 transition-transform"
          :class="action.gradient"
        >
          <Icon :icon="action.icon" class="w-4 h-4 text-white" />
        </div>
        <p class="font-bold text-gray-900 dark:text-white text-[10px] text-center whitespace-nowrap leading-tight transition-colors group-hover:text-vikinger-purple">
          {{ action.title }}
        </p>
      </NuxtLink>
    </div>
  </div>
</template>

