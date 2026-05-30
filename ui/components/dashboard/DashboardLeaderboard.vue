<script setup lang="ts">
import { Icon } from '@iconify/vue'

interface User {
  id: number
  name: string
  total_points?: number | string | null
  points?: number | string | null
  score?: number | string | null
  pp?: number | string | null
}

interface Props {
  users: User[]
  isLoading?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  isLoading: false
})

const getRankBadgeClass = (index: number): string => {
  const classes = [
    'bg-gradient-to-br from-amber-400 to-amber-500 text-white',      // 1st - ทองนุ่มนวล
    'bg-gradient-to-br from-slate-300 to-slate-400 text-white',      // 2nd - เงินนุ่มนวล
    'bg-gradient-to-br from-orange-300 to-orange-400 text-white',    // 3rd - ทองแดงนุ่มนวล
    'bg-sky-400 text-white',                                          // 4th - ฟ้านุ่มนวล
    'bg-emerald-400 text-white',                                      // 5th - เขียวนุ่มนวล
  ]
  return classes[index] || 'bg-slate-300 text-gray-700'
}

const getUserPoints = (user: User): number => {
  const value = user.total_points ?? user.points ?? user.score ?? user.pp ?? 0
  const points = Number(value)
  return Number.isFinite(points) ? points : 0
}

const formatPoints = (value: number | string | null | undefined): string => {
  const points = Number(value ?? 0)
  return new Intl.NumberFormat('th-TH').format(Number.isFinite(points) ? points : 0)
}
</script>

<template>
  <div class="bg-white dark:bg-vikinger-dark-200 rounded-xl shadow-sm border border-gray-100 dark:border-vikinger-dark-100 overflow-hidden">
    <div class="px-4 py-3 border-b border-gray-100 dark:border-vikinger-dark-100 flex items-center justify-between">
      <h2 class="font-bold text-gray-900 dark:text-white flex items-center gap-2 text-xs">
        <Icon icon="fluent:podium-24-filled" class="w-4 h-4 text-rose-500" />
        อันดับแต้มสูงสุด
      </h2>
      <NuxtLink to="/Earn/Gamification" class="text-vikinger-purple hover:underline text-[10px] font-bold transition-colors">
        ทั้งหมด
      </NuxtLink>
    </div>

    <div class="p-2 space-y-1">
      <div v-if="isLoading" class="space-y-1">
        <div v-for="i in 5" :key="i" class="flex items-center gap-3 p-1.5 animate-pulse">
          <div class="w-6 h-6 rounded-full bg-gray-200 dark:bg-vikinger-dark-50"></div>
          <div class="flex-1 space-y-1.5">
            <div class="h-2 w-1/2 bg-gray-200 dark:bg-vikinger-dark-50 rounded"></div>
            <div class="h-1.5 w-1/4 bg-gray-200 dark:bg-vikinger-dark-50 rounded"></div>
          </div>
        </div>
      </div>

      <div v-else-if="users.length > 0" class="space-y-0.5">
        <div 
          v-for="(user, index) in users" 
          :key="user.id"
          class="flex items-center gap-3 p-1.5 rounded-lg hover:bg-gray-50 dark:hover:bg-vikinger-dark-100 transition-all group"
        >
          <div 
            class="w-6 h-6 rounded-full flex items-center justify-center font-black text-[9px] shrink-0 shadow-sm transition-transform group-hover:scale-110"
            :class="getRankBadgeClass(index)"
          >
            {{ index + 1 }}
          </div>
          <div class="min-w-0 flex-1">
            <p class="text-[11px] font-bold text-gray-900 dark:text-white truncate leading-tight group-hover:text-vikinger-purple transition-colors">
              {{ user.name }}
            </p>
            <p class="text-[9px] text-amber-600 dark:text-amber-400 font-bold leading-none mt-0.5">
              {{ formatPoints(getUserPoints(user)) }} P
            </p>
          </div>
        </div>
      </div>
      
      <div v-else class="flex flex-col items-center justify-center py-6 px-2 text-center group/empty">
        <div class="w-10 h-10 rounded-full bg-gray-50 dark:bg-vikinger-dark-100 flex items-center justify-center mb-2 group-hover/empty:scale-110 transition-transform">
          <Icon icon="fluent:people-community-24-regular" class="w-5 h-5 text-gray-300" />
        </div>
        <p class="text-[10px] font-bold text-gray-400 dark:text-gray-500 italic">ไม่มีข้อมูลอันดับ</p>
        <p class="text-[9px] text-gray-400 dark:text-gray-600 mt-0.5">เป็นคนแรกที่ขึ้นสู่จุดสูงสุด!</p>
      </div>
    </div>
  </div>
</template>

