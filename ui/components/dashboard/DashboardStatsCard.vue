<template>
  <div 
    class="stats-card rounded-2xl p-6 text-white shadow-lg hover:shadow-xl transition-all hover:-translate-y-1"
    :class="gradientClass"
  >
    <div class="flex items-center justify-between mb-4">
      <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center backdrop-blur">
        <Icon :icon="icon" class="w-6 h-6" />
      </div>
      <NuxtLink 
        v-if="link" 
        :to="link" 
        class="text-white/80 hover:text-white text-sm transition-colors"
      >
        ดูทั้งหมด →
      </NuxtLink>
    </div>
    
    <p class="text-white/80 text-sm mb-1">{{ subtitle }}</p>
    <p class="text-3xl font-bold mb-2">{{ displayValue }}</p>
    
    <!-- Progress bar for points card -->
    <div v-if="showProgress" class="bg-white/10 rounded-lg p-2">
      <div class="flex items-center justify-between text-xs mb-1">
        <span>{{ progressLabel }}</span>
        <span>{{ progressText }}</span>
      </div>
      <div class="h-2 bg-white/20 rounded-full overflow-hidden">
        <div 
          class="h-full bg-white rounded-full transition-all duration-500"
          :style="{ width: `${progress}%` }"
        ></div>
      </div>
    </div>
    
    <!-- Additional info -->
    <p v-if="additionalInfo" class="text-white/70 text-xs mt-2">
      {{ additionalInfo }}
    </p>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { Icon } from '@iconify/vue'

interface Props {
  type: 'points' | 'wallet' | 'achievements' | 'leaderboard'
  value: number | string
  subtitle: string
  link?: string
  progress?: number
  progressLabel?: string
  progressText?: string
  additionalInfo?: string
}

const props = withDefaults(defineProps<Props>(), {
  progress: 0,
  progressLabel: 'Progress',
  progressText: '0 / 100',
})

const icon = computed(() => {
  const icons: Record<string, string> = {
    points: 'mdi:star',
    wallet: 'mdi:wallet',
    achievements: 'mdi:trophy',
    leaderboard: 'mdi:podium',
  }
  return icons[props.type] || 'mdi:star'
})

const gradientClass = computed(() => {
  const gradients: Record<string, string> = {
    points: 'bg-gradient-to-br from-purple-500 to-indigo-600',
    wallet: 'bg-gradient-to-br from-emerald-500 to-teal-600',
    achievements: 'bg-gradient-to-br from-amber-500 to-orange-600',
    leaderboard: 'bg-gradient-to-br from-rose-500 to-pink-600',
  }
  return gradients[props.type] || gradients.points
})

const displayValue = computed(() => {
  if (typeof props.value === 'number') {
    if (props.type === 'wallet') {
      return new Intl.NumberFormat('th-TH', {
        style: 'currency',
        currency: 'THB',
      }).format(props.value)
    }
    return new Intl.NumberFormat('th-TH').format(props.value)
  }
  return props.value
})

const showProgress = computed(() => {
  return props.type === 'points' && props.progress > 0
})
</script>

<style scoped>
.stats-card {
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
