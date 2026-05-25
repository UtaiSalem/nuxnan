<script setup lang="ts">
interface Props {
  wpm: number
  accuracy: number
  elapsedSeconds: number
  timeLimit?: number | null
  mistakes: number
}

const props = defineProps<Props>()

const formattedTime = computed(() => {
  const time = props.timeLimit 
    ? Math.max(0, props.timeLimit - props.elapsedSeconds)
    : props.elapsedSeconds
    
  const mins = Math.floor(time / 60)
  const secs = time % 60
  return `${mins}:${secs.toString().padStart(2, '0')}`
})

const timeColorClass = computed(() => {
  if (!props.timeLimit) return 'text-slate-600 dark:text-slate-400'
  const remaining = props.timeLimit - props.elapsedSeconds
  if (remaining <= 10) return 'text-red-500 animate-pulse'
  if (remaining <= 30) return 'text-orange-500'
  return 'text-slate-600 dark:text-slate-400'
})
</script>

<template>
  <div class="flex items-center justify-between p-4 bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700">
    <!-- WPM -->
    <div class="flex flex-col items-center px-4 border-r border-slate-200 dark:border-slate-700 last:border-0">
      <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">WPM</span>
      <span class="text-2xl font-black text-primary-600">{{ wpm }}</span>
    </div>

    <!-- Accuracy -->
    <div class="flex flex-col items-center px-4 border-r border-slate-200 dark:border-slate-700 last:border-0">
      <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Accuracy</span>
      <span class="text-2xl font-black text-blue-600">{{ accuracy }}%</span>
    </div>

    <!-- Timer -->
    <div class="flex flex-col items-center px-4 border-r border-slate-200 dark:border-slate-700 last:border-0">
      <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">{{ timeLimit ? 'Time Left' : 'Time' }}</span>
      <span class="text-2xl font-black font-mono" :class="timeColorClass">{{ formattedTime }}</span>
    </div>

    <!-- Mistakes -->
    <div class="flex flex-col items-center px-4 border-r border-slate-200 dark:border-slate-700 last:border-0">
      <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Mistakes</span>
      <span class="text-2xl font-black text-red-600">{{ mistakes }}</span>
    </div>
  </div>
</template>
