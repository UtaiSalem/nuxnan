<template>
  <div class="points-wallet-widget bg-white dark:bg-gray-800 rounded-2xl shadow-lg overflow-hidden">
    <!-- Header -->
    <div class="bg-gradient-to-r from-amber-400 via-orange-500 to-rose-500 p-4 text-white">
      <div class="flex items-center justify-between mb-3">
        <div class="flex items-center gap-2">
          <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center backdrop-blur">
            <Icon icon="mdi:star-circle" class="w-6 h-6" />
          </div>
          <div>
            <h3 class="font-semibold text-sm">แต้มและกระเป๋าเงิน</h3>
            <p class="text-xs text-white/80">Points & Wallet</p>
          </div>
        </div>
        <NuxtLink 
          to="/earn/points"
          class="text-xs bg-white/20 px-3 py-1 rounded-full hover:bg-white/30 transition-colors"
        >
          ดูทั้งหมด
        </NuxtLink>
      </div>
      
      <!-- Balance Display -->
      <div class="grid grid-cols-2 gap-3">
        <!-- Points -->
        <div class="bg-white/10 rounded-xl p-3 backdrop-blur">
          <div class="flex items-center gap-2 mb-1">
            <Icon icon="mdi:star" class="w-4 h-4 text-yellow-300" />
            <span class="text-xs text-white/80">แต้มสะสม</span>
          </div>
          <p class="text-xl font-bold">{{ formatPoints(points) }}</p>
        </div>
        
        <!-- Wallet -->
        <div class="bg-white/10 rounded-xl p-3 backdrop-blur">
          <div class="flex items-center gap-2 mb-1">
            <Icon icon="mdi:wallet" class="w-4 h-4 text-green-300" />
            <span class="text-xs text-white/80">กระเป๋าเงิน</span>
          </div>
          <p class="text-xl font-bold">฿{{ formatMoney(wallet) }}</p>
        </div>
      </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="p-4">
      <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">ทำรายการด่วน</p>
      <div class="grid grid-cols-4 gap-2">
        <NuxtLink 
          to="/earn/donates"
          class="quick-action flex flex-col items-center gap-1 p-2 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
        >
          <div class="w-10 h-10 bg-amber-100 dark:bg-amber-900/30 rounded-full flex items-center justify-center">
            <Icon icon="mdi:hand-coin" class="w-5 h-5 text-amber-500" />
          </div>
          <span class="text-xs text-gray-600 dark:text-gray-400">รับสนับสนุน</span>
        </NuxtLink>
        
        <NuxtLink 
          to="/earn/wallet"
          class="quick-action flex flex-col items-center gap-1 p-2 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
        >
          <div class="w-10 h-10 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center">
            <Icon icon="mdi:cash-plus" class="w-5 h-5 text-green-500" />
          </div>
          <span class="text-xs text-gray-600 dark:text-gray-400">เติมเงิน</span>
        </NuxtLink>
        
        <button 
          class="quick-action flex flex-col items-center gap-1 p-2 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
          @click="$emit('convert')"
        >
          <div class="w-10 h-10 bg-purple-100 dark:bg-purple-900/30 rounded-full flex items-center justify-center">
            <Icon icon="mdi:swap-horizontal" class="w-5 h-5 text-purple-500" />
          </div>
          <span class="text-xs text-gray-600 dark:text-gray-400">แลกเงิน</span>
        </button>
        
        <NuxtLink 
          to="/earn/rewards"
          class="quick-action flex flex-col items-center gap-1 p-2 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
        >
          <div class="w-10 h-10 bg-pink-100 dark:bg-pink-900/30 rounded-full flex items-center justify-center">
            <Icon icon="mdi:gift" class="w-5 h-5 text-pink-500" />
          </div>
          <span class="text-xs text-gray-600 dark:text-gray-400">แลกรางวัล</span>
        </NuxtLink>
      </div>
    </div>
    
    <!-- Level Progress -->
    <div v-if="showLevel && level" class="px-4 pb-4">
      <div class="bg-gray-100 dark:bg-gray-700 rounded-xl p-3">
        <div class="flex items-center justify-between mb-2">
          <div class="flex items-center gap-2">
            <div class="w-8 h-8 bg-gradient-to-br from-indigo-500 to-purple-500 rounded-full flex items-center justify-center">
              <span class="text-white text-sm font-bold">{{ level.level }}</span>
            </div>
            <span class="text-sm font-medium text-gray-900 dark:text-white">เลเวล {{ level.level }}</span>
          </div>
          <span class="text-xs text-gray-500 dark:text-gray-400">
            {{ level.current_xp }}/{{ level.xp_for_next_level }} XP
          </span>
        </div>
        <div class="h-2 bg-gray-200 dark:bg-gray-600 rounded-full overflow-hidden">
          <div 
            class="h-full bg-gradient-to-r from-indigo-500 to-purple-500 rounded-full transition-all duration-500"
            :style="{ width: `${level.progress_percentage}%` }"
          ></div>
        </div>
      </div>
    </div>
    
    <!-- Streak Info -->
    <div v-if="showStreak && streak" class="px-4 pb-4">
      <div class="flex items-center justify-between bg-orange-50 dark:bg-orange-900/20 rounded-xl p-3">
        <div class="flex items-center gap-2">
          <div class="w-8 h-8 bg-gradient-to-br from-orange-400 to-red-500 rounded-full flex items-center justify-center">
            <Icon icon="mdi:fire" class="w-5 h-5 text-white" />
          </div>
          <div>
            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ streak.current_streak }} วันติดต่อกัน</p>
            <p class="text-xs text-gray-500 dark:text-gray-400">เข้าใช้งานต่อเนื่อง</p>
          </div>
        </div>
        <div v-if="streak.bonus_points > 0" class="text-right">
          <p class="text-sm font-bold text-orange-500">+{{ streak.bonus_points }}</p>
          <p class="text-xs text-gray-500">โบนัส</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { Icon } from '@iconify/vue'
import { useAuthStore } from '~/stores/auth'

const props = defineProps({
  showLevel: {
    type: Boolean,
    default: true
  },
  showStreak: {
    type: Boolean,
    default: true
  }
})

defineEmits(['convert'])

const authStore = useAuthStore()

// Computed
const points = computed(() => authStore.points || 0)
const wallet = computed(() => authStore.user?.wallet || 0)
const level = computed(() => ({
  level: authStore.user?.level || 1,
  current_xp: authStore.user?.current_xp || 0,
  xp_for_next_level: authStore.user?.xp_for_next_level || 100,
  progress_percentage: authStore.user?.current_xp && authStore.user?.xp_for_next_level 
    ? Math.round((authStore.user.current_xp / authStore.user.xp_for_next_level) * 100)
    : 0
}))
const streak = computed(() => authStore.user?.streak || null)

// Helpers
const formatPoints = (value: number): string => {
  return new Intl.NumberFormat('th-TH').format(value)
}

const formatMoney = (value: number): string => {
  return new Intl.NumberFormat('th-TH', { 
    minimumFractionDigits: 2,
    maximumFractionDigits: 2 
  }).format(value)
}
</script>

<style scoped>
.points-wallet-widget {
  max-width: 100%;
}

.quick-action {
  transition: transform 0.2s;
}

.quick-action:hover {
  transform: translateY(-2px);
}
</style>
