<script setup lang="ts">
import { onMounted } from 'vue'
import { Icon } from '@iconify/vue'
import { useMemberedAcademies } from '~/composables/useMemberedAcademies'

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

const { fetch, quickActionTarget, isLoading, approved } = useMemberedAcademies()

onMounted(() => {
  fetch()
})
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

    <!-- ปุ่มใหม่: เต็มความกว้าง, อยู่ใต้ grid 2×2 -->
    <NuxtLink
      :to="quickActionTarget"
      class="mt-3 group flex items-center gap-3 p-3 rounded-xl bg-gradient-to-r from-sky-50 to-indigo-50 dark:from-sky-900/20 dark:to-indigo-900/20 border border-sky-100 dark:border-sky-900/30 hover:border-sky-300 dark:hover:border-sky-600 transition-all cursor-pointer"
      :aria-busy="isLoading"
    >
      <div class="w-10 h-10 bg-gradient-to-br from-sky-500 to-indigo-600 rounded-xl flex items-center justify-center shadow-md group-hover:scale-110 transition-transform">
        <Icon icon="mdi:school" class="w-5 h-5 text-white" />
      </div>
      <div class="flex-1 min-w-0">
        <p class="font-bold text-gray-900 dark:text-white text-xs md:text-sm leading-tight transition-colors group-hover:text-sky-600 dark:group-hover:text-sky-400">
          โรงเรียนของฉัน
        </p>
        <p class="text-[10px] md:text-xs text-gray-500 dark:text-gray-400 truncate mt-0.5">
          <span v-if="isLoading">กำลังโหลด...</span>
          <span v-else-if="approved.length === 1">{{ approved[0].name }}</span>
          <span v-else-if="approved.length > 1">{{ approved.length }} โรงเรียน</span>
          <span v-else>ค้นหาหรือสมัครเข้าโรงเรียน</span>
        </p>
      </div>
      <Icon icon="mdi:chevron-right" class="w-5 h-5 text-gray-400 group-hover:text-sky-600 group-hover:translate-x-1 transition-all" />
    </NuxtLink>
  </div>
</template>

