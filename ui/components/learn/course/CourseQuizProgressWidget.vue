<script setup lang="ts">
import { Icon } from '@iconify/vue'

interface Props {
  quizzes: any[]
  isLoading?: boolean
  error?: string | null
}

const props = defineProps<Props>()

const passedQuizzes = computed(() => props.quizzes.filter(q => q.passed).length)
const totalQuizzes = computed(() => props.quizzes.length)
const progressPercentage = computed(() => {
  if (totalQuizzes.value === 0) return 0
  return Math.round((passedQuizzes.value / totalQuizzes.value) * 100)
})

const getStatusColor = (quiz: any) => {
  if (quiz.passed === true) return 'text-emerald-500'
  if (quiz.passed === false) return 'text-rose-500'
  if (quiz.completed) return 'text-amber-500'
  return 'text-gray-300'
}

const getStatusIcon = (quiz: any) => {
  if (quiz.passed === true) return 'fluent:quiz-new-24-filled'
  if (quiz.passed === false) return 'fluent:quiz-new-24-regular'
  if (quiz.completed) return 'fluent:quiz-new-24-regular'
  return 'fluent:quiz-new-24-regular'
}
</script>

<template>
  <div class="rounded-2xl border border-gray-100 bg-white shadow-xl dark:border-vikinger-dark-100 dark:bg-vikinger-dark-200 overflow-hidden">
    <!-- Header -->
    <div class="px-5 py-4 border-b border-gray-100 dark:border-vikinger-dark-100 flex items-center justify-between bg-gray-50/50 dark:bg-vikinger-dark-100/30">
      <h3 class="font-black text-[10px] uppercase tracking-widest text-gray-400 dark:text-gray-500 flex items-center gap-2">
        <Icon icon="fluent:quiz-new-24-filled" class="w-4 h-4 text-amber-500" />
        แบบทดสอบ
      </h3>
      <div v-if="totalQuizzes > 0" class="flex items-center gap-1.5">
        <span class="text-[10px] font-black text-gray-400">{{ passedQuizzes }}/{{ totalQuizzes }}</span>
        <div class="w-10 h-1 rounded-full bg-gray-100 dark:bg-vikinger-dark-100 overflow-hidden">
          <div 
            class="h-full bg-amber-500 transition-all duration-1000"
            :style="{ width: `${progressPercentage}%` }"
          ></div>
        </div>
      </div>
    </div>

    <!-- Content -->
    <div class="p-4">
      <div v-if="isLoading" class="space-y-3">
        <div v-for="i in 3" :key="i" class="h-10 bg-gray-50 dark:bg-vikinger-dark-100/50 rounded-xl animate-pulse" />
      </div>
      
      <div v-else-if="error" class="py-4 text-center">
        <Icon icon="fluent:error-circle-24-regular" class="w-8 h-8 text-red-500 mx-auto mb-2" />
        <p class="text-xs font-bold text-gray-500">{{ error }}</p>
      </div>

      <div v-else-if="quizzes.length === 0" class="py-6 text-center">
        <Icon icon="fluent:quiz-new-24-regular" class="w-10 h-10 text-gray-300 mx-auto mb-2" />
        <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">ไม่มีแบบทดสอบในวิชานี้</p>
      </div>

      <div v-else class="space-y-2 max-h-[300px] overflow-y-auto custom-scrollbar pr-1">
        <div 
          v-for="quiz in quizzes" 
          :key="quiz.id"
          class="flex items-center gap-3 p-2.5 rounded-xl bg-gray-50 dark:bg-vikinger-dark-100/50 border border-transparent hover:border-amber-200 dark:hover:border-amber-900/30 transition-all group"
        >
          <div class="shrink-0">
            <Icon 
              :icon="getStatusIcon(quiz)" 
              class="w-5 h-5 transition-transform group-hover:scale-110"
              :class="getStatusColor(quiz)"
            />
          </div>
          <div class="min-w-0 flex-1">
            <h4 class="text-[11px] font-black text-gray-700 dark:text-gray-300 truncate leading-none mb-1">
              {{ quiz.title }}
            </h4>
            <div class="flex items-center gap-2">
              <span class="text-[9px] font-bold uppercase tracking-tighter" :class="getStatusColor(quiz)">
                {{ quiz.status_label }}
              </span>
              <span v-if="quiz.attempt_count > 0" class="text-[9px] font-black text-gray-400">
                ทำแล้ว: {{ quiz.attempt_count }} ครั้ง
              </span>
              <span v-if="quiz.score !== null" class="text-[9px] font-black text-gray-400">
                คะแนนล่าสุด: {{ quiz.score }}/{{ quiz.max_score }}
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
  width: 3px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: #e2e8f0;
  border-radius: 10px;
}
.dark .custom-scrollbar::-webkit-scrollbar-thumb {
  background: #334155;
}
</style>
