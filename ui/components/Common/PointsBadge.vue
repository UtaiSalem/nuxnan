<script setup lang="ts">
import { Icon } from '@iconify/vue'
import { onBeforeUnmount, onMounted, ref, watch } from 'vue'

interface Props {
  points: number | null
  loading?: boolean
  label?: string
}

const props = withDefaults(defineProps<Props>(), {
  label: 'แต้มโรงเรียน',
})
const displayValue = ref(props.points ?? 0)
const isMounted = ref(false)
let animationFrame: number | null = null

const animateTo = (target: number) => {
  if (!isMounted.value || typeof requestAnimationFrame === 'undefined') {
    displayValue.value = target
    return
  }

  if (animationFrame !== null) cancelAnimationFrame(animationFrame)
  const start = displayValue.value
  const startTime = performance.now()
  const duration = 800

  const step = (now: number) => {
    const progress = Math.min((now - startTime) / duration, 1)
    const eased = 1 - (1 - progress) ** 3
    displayValue.value = Math.round(start + (target - start) * eased)
    if (progress < 1) animationFrame = requestAnimationFrame(step)
    else animationFrame = null
  }

  animationFrame = requestAnimationFrame(step)
}

watch(() => props.points, (value) => {
  animateTo(value ?? 0)
}, { immediate: true })

onMounted(() => {
  isMounted.value = true
  displayValue.value = props.points ?? 0
})

onBeforeUnmount(() => {
  if (animationFrame !== null) cancelAnimationFrame(animationFrame)
})
</script>

<template>
  <button
    type="button"
    class="min-h-[44px] sm:min-h-0 relative inline-flex items-center gap-2 overflow-hidden rounded-full bg-gradient-to-r from-indigo-600 via-violet-600 to-fuchsia-500 px-4 py-2 text-sm text-white shadow-md transition-transform hover:scale-105 focus:outline-none focus-visible:ring-2 focus-visible:ring-violet-400"
  >
    <span class="pointer-events-none absolute inset-0 overflow-hidden">
      <span class="absolute bottom-0 top-0 w-1/3 -skew-x-12 bg-white/25 blur-sm animate-shine" />
    </span>
    <Icon icon="fluent:sparkle-24-filled" class="relative h-4 w-4 text-amber-300" />
    <span v-if="loading && points === null" class="relative h-4 w-16 animate-pulse rounded bg-white/30" />
    <span v-else class="relative font-bold tabular-nums">{{ displayValue.toLocaleString() }}</span>
    <span class="relative font-medium text-indigo-100">{{ label }}</span>
  </button>
</template>

<style scoped>
@keyframes shine {
  from { transform: translateX(-150%) skewX(-12deg); }
  to { transform: translateX(400%) skewX(-12deg); }
}

.animate-shine {
  animation: shine 2.8s ease-in-out infinite;
}
</style>
