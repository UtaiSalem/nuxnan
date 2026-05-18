<script setup lang="ts">
import { Icon } from '@iconify/vue'
import { computed } from 'vue'

interface Props {
  type: 'points' | 'wallet' | 'achievements' | 'leaderboard'
  value: number | string
  subtitle: string
  link?: string
  progress?: number
  progressLabel?: string
  progressText?: string
  additionalInfo?: string
  isLoading?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  progress: 0,
  isLoading: false,
})

const config = computed(() => {
  const settings: Record<string, { cardBg: string; icon: string; iconBg: string }> = {
    points: {
      cardBg: 'bg-gradient-to-br from-purple-600 via-violet-600 to-indigo-700',
      icon: 'mdi:star',
      iconBg: 'bg-white/10',
    },
    wallet: {
      cardBg: 'bg-gradient-to-br from-emerald-500 via-teal-600 to-cyan-700',
      icon: 'mdi:wallet',
      iconBg: 'bg-white/10',
    },
    achievements: {
      cardBg: 'bg-gradient-to-br from-amber-400 via-orange-500 to-rose-600',
      icon: 'mdi:trophy',
      iconBg: 'bg-white/10',
    },
    leaderboard: {
      cardBg: 'bg-gradient-to-br from-rose-500 via-pink-600 to-fuchsia-700',
      icon: 'mdi:podium',
      iconBg: 'bg-white/10',
    },
  }

  return settings[props.type] || settings.points
})

const showSyncSpinner = computed(() => props.isLoading)
</script>

<template>
  <div
    class="rounded-2xl p-4 sm:p-5 text-white shadow-lg relative overflow-hidden group transition-all duration-300 min-h-[135px] lg:min-h-[145px] flex flex-col justify-between"
    :class="[config.cardBg]"
  >
    <!-- Background Icon (Reduced Opacity and Size) -->
    <Icon
      :icon="config.icon"
      class="absolute -right-2 -bottom-2 w-20 h-20 text-white/5 group-hover:scale-110 group-hover:text-white/10 transition-all duration-500"
    />

    <div class="relative z-10 h-full flex flex-col justify-between">
      <!-- Header -->
      <div class="flex items-center justify-between mb-1.5">
        <div class="w-8 h-8 rounded-lg flex items-center justify-center backdrop-blur-md" :class="config.iconBg">
          <Icon :icon="config.icon" class="w-4 h-4 text-white" />
        </div>

        <NuxtLink v-if="link" :to="link" class="text-white/50 hover:text-white p-1 transition-colors">
          <Icon icon="fluent:chevron-right-24-filled" class="w-4 h-4" />
        </NuxtLink>
      </div>

      <!-- Content -->
      <div>
        <p class="text-white/75 text-[9px] font-bold uppercase tracking-widest mb-0.5">{{ subtitle }}</p>

        <div class="flex items-center justify-between gap-2">
          <div class="flex items-center gap-2">
            <p 
              class="text-xl sm:text-2xl 2xl:text-3xl font-black truncate leading-tight break-words drop-shadow-sm"
              :class="{ 'animate-pulse opacity-50': isLoading && !value }"
            >
              {{ value || '0' }}
            </p>

            <!-- sync indicator (small only; no card pulse) -->
            <div
              v-if="showSyncSpinner"
              class="w-4 h-4 rounded-full border-2 border-white/40 border-t-white/90 animate-spin flex-none"
              aria-label="sync"
            />
          </div>

          <!-- Quick Actions -->
          <div v-if="!isLoading" class="flex items-center gap-1.5">
            <template v-if="type === 'points'">
              <NuxtLink 
                to="/Earn/Points" 
                class="px-2 py-1 rounded-lg bg-white/20 hover:bg-white/30 text-[9px] font-bold transition-colors backdrop-blur-sm"
              >
                รับแต้ม
              </NuxtLink>
            </template>
            <template v-else-if="type === 'wallet'">
              <NuxtLink 
                to="/Earn/Wallet?tab=deposit" 
                class="px-2 py-1 rounded-lg bg-white/20 hover:bg-white/30 text-[9px] font-bold transition-colors backdrop-blur-sm"
              >
                เติมเงิน
              </NuxtLink>
              <NuxtLink 
                to="/Earn/Wallet?tab=history" 
                class="px-2 py-1 rounded-lg bg-white/10 hover:bg-white/20 text-[9px] font-bold transition-colors backdrop-blur-sm"
              >
                ประวัติ
              </NuxtLink>
            </template>
          </div>
        </div>
      </div>

      <!-- Footer (Progress or Info) -->
      <div class="mt-2">
        <!-- Progress Section -->
        <div v-if="type === 'points' || (progress ?? 0) > 0">
          <div class="flex items-center justify-between mb-1" v-if="progressLabel || progressText">
            <span class="text-[8px] text-white/60 font-bold uppercase">{{ progressLabel }}</span>
            <span class="text-[8px] text-white/90 font-bold">
              {{ progressText || `${progress}%` }}
            </span>
          </div>

          <div class="h-1.5 bg-white/10 rounded-full overflow-hidden">
            <div
              class="h-full bg-white/90 rounded-full transition-all duration-1000 ease-out"
              :class="{ 'animate-pulse opacity-50': isLoading && progress === 0 }"
              :style="{ width: `${progress}%` }"
            ></div>
          </div>
        </div>

        <!-- Additional Info -->
        <p v-if="additionalInfo" class="text-white/60 text-[8px] font-bold truncate">{{ additionalInfo }}</p>
      </div>
    </div>
  </div>
</template>
