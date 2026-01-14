<script setup lang="ts">
import { computed } from 'vue'

interface Props {
  percentage: number
  isVoted?: boolean
  isLeading?: boolean
  color?: 'purple' | 'cyan' | 'green' | 'orange' | 'pink'
}

const props = withDefaults(defineProps<Props>(), {
  isVoted: false,
  isLeading: false,
  color: 'purple'
})

const emit = defineEmits<{
  click: []
}>()

const barColor = computed(() => {
  const colors = {
    purple: 'from-vikinger-purple to-vikinger-cyan',
    cyan: 'from-vikinger-cyan to-vikinger-purple',
    green: 'from-vikinger-green to-vikinger-cyan',
    orange: 'from-vikinger-orange to-vikinger-yellow',
    pink: 'from-vikinger-pink to-vikinger-purple',
  }
  return colors[props.color]
})

const barOpacity = computed(() => {
  return props.isVoted ? 'opacity-20' : 'opacity-10'
})

const barGlow = computed(() => {
  return props.isLeading ? 'shadow-cyan-glow' : ''
})
</script>

<template>
  <div class="poll-progress-bar-container">
    <!-- Background Progress -->
    <div 
      class="poll-progress-background"
      :class="barOpacity"
    ></div>
    
    <!-- Fill Progress -->
    <div 
      class="poll-progress-fill"
      :class="[barColor, barGlow]"
      :style="{ 
        width: `${percentage}%`,
        '--progress-width': `${percentage}%`
      }"
      role="progressbar"
      :aria-valuenow="percentage"
      :aria-valuemin="0"
      :aria-valuemax="100"
      :aria-label="`${percentage}% of votes`"
      @click="emit('click')"
    ></div>
    
    <!-- Content Slot -->
    <div class="poll-progress-content">
      <slot></slot>
    </div>
  </div>
</template>

<style scoped>
.poll-progress-bar-container {
  position: relative;
  height: 48px;
  border-radius: 12px;
  overflow: hidden;
}

.poll-progress-background {
  position: absolute;
  inset: 0;
  background: linear-gradient(90deg, 
    rgba(97, 93, 250, 0.1) 0%, 
    rgba(35, 210, 226, 0.1) 100%);
  border-radius: 12px;
  transition: opacity 0.3s ease;
}

.poll-progress-fill {
  position: absolute;
  inset: 0;
  border-radius: 12px;
  transition: width 0.5s ease-out;
  animation: progress-fill 0.5s ease-out;
}

.poll-progress-content {
  position: relative;
  z-index: 10;
  height: 100%;
  display: flex;
  align-items: center;
  padding: 0 16px;
}

@keyframes progress-fill {
  0% {
    width: 0%;
  }
  100% {
    width: var(--progress-width);
  }
}

/* Reduced motion */
@media (prefers-reduced-motion: reduce) {
  .poll-progress-fill {
    transition: none;
    animation: none;
  }
}
</style>
