<script setup lang="ts">
import { Icon } from '@iconify/vue'
import { computed } from 'vue'

interface Props {
  user: any
  level: number
  levelProgress: number
  streakInfo?: any
  isLoading?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  isLoading: false
})

const greeting = computed(() => {
  const hour = new Date().getHours()
  if (hour < 12) return 'สวัสดีตอนเช้า'
  if (hour < 18) return 'สวัสดีตอนบ่าย'
  return 'สวัสดีตอนค่ำ'
})

const currentDate = computed(() => {
  return new Date().toLocaleDateString('th-TH', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
})

const getStreakIcon = (streak: number): string => {
  if (streak >= 30) return '🔥🔥🔥'
  if (streak >= 14) return '🔥🔥'
  if (streak >= 7) return '🔥'
  return '⚡'
}
</script>

<template>
  <div class="relative overflow-hidden rounded-2xl shadow-xl">
    <!-- Background with animated gradient (More subtle) -->
    <div class="absolute inset-0 bg-gradient-to-r from-vikinger-dark via-vikinger-purple/90 to-vikinger-dark"></div>
    <div class="absolute inset-0 bg-[url('/images/patterns/hexagon-pattern.svg')] opacity-5"></div>
    
    <!-- Floating particles effect (Subtle) -->
    <div class="absolute inset-0 overflow-hidden opacity-30">
      <div class="absolute top-10 left-10 w-1.5 h-1.5 bg-vikinger-cyan rounded-full animate-pulse"></div>
      <div class="absolute top-20 right-20 w-2 h-2 bg-vikinger-purple rounded-full animate-bounce"></div>
      <div class="absolute bottom-10 left-1/4 w-1.5 h-1.5 bg-pink-400 rounded-full animate-ping"></div>
    </div>
    
    <div class="relative px-4 sm:px-6 lg:px-8 xl:px-10 py-6 md:py-8 lg:py-10">
      <div v-if="isLoading" class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6 animate-pulse">
        <div class="flex items-center gap-6">
          <div class="w-16 h-16 md:w-20 md:h-20 rounded-full bg-white/10"></div>
          <div class="space-y-3">
            <div class="h-3 w-24 bg-white/10 rounded"></div>
            <div class="h-6 w-48 bg-white/10 rounded"></div>
            <div class="h-3 w-32 bg-white/10 rounded"></div>
          </div>
        </div>
        <div class="flex gap-3">
          <div class="h-10 w-20 bg-white/10 rounded-xl"></div>
          <div class="h-10 w-24 bg-white/10 rounded-xl"></div>
        </div>
      </div>

      <div v-else class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
        <!-- Left: Welcome Message -->
        <div class="flex flex-col sm:flex-row items-center sm:items-start gap-4 sm:gap-6 text-center sm:text-left">
          <!-- Avatar with Level Badge -->
          <div class="relative shrink-0">
            <div class="w-16 h-16 md:w-20 md:h-20 rounded-full bg-gradient-to-br from-vikinger-purple to-vikinger-cyan p-0.5 shadow-vikinger">
              <img 
                :src="user?.avatar || user?.profile_photo_url || '/images/default-avatar.png'" 
                :alt="user?.name"
                class="w-full h-full rounded-full object-cover border-2 border-vikinger-dark"
                @error="(e: any) => e.target.src = '/images/default-avatar.png'"
              />
            </div>
            <!-- Level Badge -->
            <div class="absolute -bottom-1 -right-1 w-8 h-8 bg-gradient-vikinger rounded-full flex items-center justify-center text-white font-bold text-xs border-2 border-vikinger-dark shadow-lg">
              {{ level }}
            </div>
          </div>
          
          <div class="min-w-0">
            <p class="text-vikinger-cyan text-[10px] md:text-xs font-bold uppercase tracking-widest mb-1">{{ currentDate }}</p>
            <h1 class="text-lg md:text-2xl lg:text-3xl font-black text-white mb-1 truncate leading-tight">
              {{ greeting }}, <span class="bg-gradient-to-r from-vikinger-cyan to-vikinger-purple bg-clip-text text-transparent">{{ user?.name || 'ผู้ใช้' }}</span>! 
            </h1>
            <p class="text-gray-400 text-xs md:text-sm">ยินดีต้อนรับสู่การผจญภัยของคุณ <span class="inline-block animate-wave">👋</span></p>
          </div>
        </div>
        
        <!-- Right: Stats Badges -->
        <div class="flex flex-wrap items-center justify-center lg:justify-end gap-2 sm:gap-3">
          <!-- Level Badge -->
          <div class="relative group">
            <div class="absolute -inset-0.5 bg-gradient-to-r from-vikinger-purple to-vikinger-cyan rounded-xl blur opacity-20 group-hover:opacity-40 transition duration-300"></div>
            <div class="relative bg-vikinger-dark-100/60 backdrop-blur-sm border border-vikinger-purple/20 rounded-xl px-3 py-1.5 flex items-center gap-2">
              <div class="w-7 h-7 bg-gradient-vikinger rounded-lg flex items-center justify-center shadow-sm">
                <Icon icon="mdi:shield-star" class="w-3.5 h-3.5 text-white" />
              </div>
              <div>
                <p class="text-[8px] text-gray-400 uppercase tracking-wider leading-none mb-0.5">Level</p>
                <p class="text-sm font-bold text-white leading-none">Lv. {{ level }}</p>
              </div>
            </div>
          </div>
          
          <!-- XP Progress -->
          <div class="relative group">
            <div class="absolute -inset-0.5 bg-gradient-to-r from-vikinger-cyan to-green-400 rounded-xl blur opacity-20 group-hover:opacity-40 transition duration-300"></div>
            <div class="relative bg-vikinger-dark-100/60 backdrop-blur-sm border border-vikinger-cyan/20 rounded-xl px-3 py-1.5">
              <div class="flex items-center gap-2 mb-1">
                <Icon icon="mdi:lightning-bolt" class="w-3 h-3 text-vikinger-cyan" />
                <span class="text-[8px] text-gray-400 uppercase tracking-wider leading-none">XP Progress</span>
              </div>
              <div class="flex items-center gap-2">
                <div class="w-16 sm:w-20 h-1 bg-vikinger-dark rounded-full overflow-hidden">
                  <div 
                    class="h-full bg-gradient-to-r from-vikinger-cyan to-green-400 rounded-full transition-all duration-1000 ease-out"
                    :style="{ width: `${levelProgress}%` }"
                  ></div>
                </div>
                <span class="text-[9px] text-vikinger-cyan font-bold">{{ levelProgress }}%</span>
              </div>
            </div>
          </div>
          
          <!-- Streak Badge -->
          <div v-if="streakInfo" class="relative group">
            <div class="absolute -inset-0.5 bg-gradient-to-r from-orange-500 to-red-500 rounded-xl blur opacity-20 group-hover:opacity-40 transition duration-300"></div>
            <div class="relative bg-vikinger-dark-100/60 backdrop-blur-sm border border-orange-500/20 rounded-xl px-3 py-1.5 flex items-center gap-2">
              <div class="text-lg leading-none">{{ getStreakIcon(streakInfo.current_streak) }}</div>
              <div>
                <p class="text-[8px] text-gray-400 uppercase tracking-wider leading-none mb-0.5">Streak</p>
                <p class="text-sm font-bold text-orange-400 leading-none">{{ streakInfo.current_streak }} วัน</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

</template>

<style scoped>
@keyframes wave {
  0%, 100% { transform: rotate(0deg); }
  25% { transform: rotate(20deg); }
  75% { transform: rotate(-20deg); }
}

.animate-wave {
  animation: wave 1.5s ease-in-out infinite;
  transform-origin: 70% 70%;
}

.shadow-vikinger {
  box-shadow: 0 10px 25px -5px rgba(111, 66, 193, 0.3), 0 8px 10px -6px rgba(111, 66, 193, 0.1);
}

.bg-gradient-vikinger {
  background: linear-gradient(135deg, #8B5CF6 0%, #06B6D4 100%);
}
</style>
