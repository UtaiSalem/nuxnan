<script setup lang="ts">
import { Icon } from '@iconify/vue'
const props = defineProps<{ wpm: number; accuracy: number; elapsedSeconds: number; timeLimit?: number | null; mistakes: number }>()
const { t } = useI18n()
const remaining = computed(() => Math.max(0, (props.timeLimit ?? 0) - props.elapsedSeconds))
const formattedTime = computed(() => {
  const time = Math.floor(props.timeLimit ? remaining.value : props.elapsedSeconds)
  return `${Math.floor(time / 60)}:${(time % 60).toString().padStart(2, '0')}`
})
const urgent = computed(() => !!props.timeLimit && remaining.value <= 10)
const stats = computed(() => [
  { label: t('typingArena.wpm'), value: Math.round(props.wpm), icon: 'heroicons:bolt', color: 'text-vikinger-purple dark:text-vikinger-cyan' },
  { label: t('typingArena.accuracy'), value: `${props.accuracy}%`, icon: 'heroicons:check-badge', color: 'text-emerald-600 dark:text-vikinger-green' },
  { label: t(props.timeLimit ? 'typingArena.timeLeft' : 'typingArena.time'), value: formattedTime.value, icon: 'heroicons:clock', color: urgent.value ? 'text-rose-600 dark:text-vikinger-pink' : 'text-vikinger-purple dark:text-vikinger-cyan' },
  { label: t('typingArena.mistakes'), value: props.mistakes, icon: 'heroicons:backspace', color: 'text-rose-600 dark:text-vikinger-pink' },
])
</script>

<template>
  <div class="grid grid-cols-2 gap-2 sm:grid-cols-4 sm:gap-3">
    <div v-for="(stat, index) in stats" :key="index" class="min-w-0 rounded-vikinger-lg border bg-white p-3 dark:bg-vikinger-dark-100 sm:p-4" :class="index === 2 && urgent ? 'border-vikinger-pink shadow-[0_0_16px_rgba(255,107,129,.15)]' : 'border-vikinger-purple/15'">
      <div class="mb-2 flex items-center gap-2 text-xs font-bold text-slate-600 dark:text-slate-300"><Icon :icon="stat.icon" class="shrink-0 text-base" :class="stat.color" /><span>{{ stat.label }}</span></div>
      <p class="truncate text-2xl font-black tabular-nums sm:text-3xl" :class="stat.color">{{ stat.value }}</p>
    </div>
  </div>
</template>
