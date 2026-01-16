<template>
  <div 
    class="learning-stats-card bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 hover:shadow-xl transition-shadow"
  >
    <div class="flex items-center justify-between mb-4">
      <div class="w-12 h-12 rounded-xl flex items-center justify-center" :class="iconClass">
        <Icon :icon="icon" class="w-6 h-6 text-white" />
      </div>
      <span class="text-xs text-gray-500 dark:text-gray-400">{{ subtitle }}</span>
    </div>
    
    <p class="text-3xl font-bold text-gray-900 dark:text-white mb-1">{{ displayValue }}</p>
    <p class="text-sm text-gray-500 dark:text-gray-400">{{ description }}</p>
    
    <!-- Progress bar if provided -->
    <div v-if="progress !== undefined" class="mt-4">
      <div class="flex items-center justify-between mb-1">
        <span class="text-xs text-gray-500 dark:text-gray-400">{{ progressLabel }}</span>
        <span class="text-xs font-medium" :class="progressTextColor">{{ progressText }}</span>
      </div>
      <div class="h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
        <div 
          class="h-full rounded-full transition-all duration-500"
          :class="progressBarClass"
          :style="{ width: `${progress}%` }"
        ></div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { Icon } from '@iconify/vue'

interface Props {
  type: 'progress' | 'courses' | 'lessons' | 'assignments' | 'quizzes' | 'attendance' | 'average'
  value: number | string
  subtitle: string
  description?: string
  progress?: number
  progressLabel?: string
  progressText?: string
}

const props = withDefaults(defineProps<Props>(), {
  description: '',
  progressLabel: 'Progress',
  progressText: '0/100',
})

const icon = computed(() => {
  const icons: Record<string, string> = {
    progress: 'fluent:chart-multiple-24-filled',
    courses: 'fluent:book-open-24-filled',
    lessons: 'fluent:learning-app-24-filled',
    assignments: 'fluent:clipboard-task-24-filled',
    quizzes: 'fluent:quiz-new-24-filled',
    attendance: 'fluent:calendar-clock-24-filled',
    average: 'fluent:star-24-filled',
  }
  return icons[props.type] || icons.progress
})

const iconClass = computed(() => {
  const classes: Record<string, string> = {
    progress: 'bg-gradient-to-br from-blue-500 to-indigo-600',
    courses: 'bg-gradient-to-br from-emerald-500 to-teal-600',
    lessons: 'bg-gradient-to-br from-violet-500 to-purple-600',
    assignments: 'bg-gradient-to-br from-amber-500 to-orange-600',
    quizzes: 'bg-gradient-to-br from-rose-500 to-pink-600',
    attendance: 'bg-gradient-to-br from-cyan-500 to-blue-600',
    average: 'bg-gradient-to-br from-yellow-400 to-amber-500',
  }
  return classes[props.type] || classes.progress
})

const displayValue = computed(() => {
  return typeof props.value === 'number' 
    ? new Intl.NumberFormat('th-TH').format(props.value)
    : props.value
})

const progressBarClass = computed(() => {
  if (props.progress === undefined) return ''
  
  if (props.progress >= 80) return 'bg-gradient-to-r from-green-400 to-green-600'
  if (props.progress >= 60) return 'bg-gradient-to-r from-blue-400 to-blue-600'
  if (props.progress >= 40) return 'bg-gradient-to-r from-yellow-400 to-yellow-600'
  return 'bg-gradient-to-r from-red-400 to-red-600'
})

const progressTextColor = computed(() => {
  if (props.progress === undefined) return 'text-gray-900 dark:text-white'
  
  if (props.progress >= 80) return 'text-green-600 dark:text-green-400'
  if (props.progress >= 60) return 'text-blue-600 dark:text-blue-400'
  if (props.progress >= 40) return 'text-yellow-600 dark:text-yellow-400'
  return 'text-red-600 dark:text-red-400'
})
</script>

<style scoped>
.learning-stats-card {
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
