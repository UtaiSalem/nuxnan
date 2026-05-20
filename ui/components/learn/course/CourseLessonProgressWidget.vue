<script setup lang="ts">
import { Icon } from '@iconify/vue'

interface Props {
  lessons: any[]
  isLoading?: boolean
  error?: string | null
}

const props = defineProps<Props>()

const completedLessons = computed(() => props.lessons.filter(l => l.completed).length)
const totalLessons = computed(() => props.lessons.length)
const progressPercentage = computed(() => {
  if (totalLessons.value === 0) return 0
  return Math.round((completedLessons.value / totalLessons.value) * 100)
})

const getStatusColor = (lesson: any) => {
  if (lesson.completed) return 'text-emerald-500'
  if (lesson.progress_percentage > 0) return 'text-amber-500'
  return 'text-gray-300'
}
</script>

<template>
  <div class="rounded-2xl border border-gray-100 bg-white shadow-xl dark:border-vikinger-dark-100 dark:bg-vikinger-dark-200 overflow-hidden">
    <!-- Header -->
    <div class="px-5 py-4 border-b border-gray-100 dark:border-vikinger-dark-100 flex items-center justify-between bg-gray-50/50 dark:bg-vikinger-dark-100/30">
      <h3 class="font-black text-[10px] uppercase tracking-widest text-gray-400 dark:text-gray-500 flex items-center gap-2">
        <Icon icon="fluent:book-24-filled" class="w-4 h-4 text-blue-500" />
        ความก้าวหน้าบทเรียน
      </h3>
      <div v-if="totalLessons > 0" class="flex items-center gap-1.5">
        <span class="text-[10px] font-black text-gray-400">{{ completedLessons }}/{{ totalLessons }}</span>
        <div class="w-10 h-1 rounded-full bg-gray-100 dark:bg-vikinger-dark-100 overflow-hidden">
          <div 
            class="h-full bg-blue-500 transition-all duration-1000"
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

      <div v-else-if="lessons.length === 0" class="py-6 text-center">
        <Icon icon="fluent:book-question-mark-24-regular" class="w-10 h-10 text-gray-300 mx-auto mb-2" />
        <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">ไม่มีบทเรียนในวิชานี้</p>
      </div>

      <div v-else class="space-y-2 max-h-[300px] overflow-y-auto custom-scrollbar pr-1">
        <div 
          v-for="lesson in lessons" 
          :key="lesson.id"
          class="flex items-center gap-3 p-2.5 rounded-xl bg-gray-50 dark:bg-vikinger-dark-100/50 border border-transparent hover:border-blue-200 dark:hover:border-blue-900/30 transition-all group"
        >
          <div class="shrink-0">
            <Icon 
              :icon="lesson.completed ? 'fluent:checkmark-circle-24-filled' : 'fluent:circle-24-regular'" 
              class="w-5 h-5 transition-transform group-hover:scale-110"
              :class="getStatusColor(lesson)"
            />
          </div>
          <div class="min-w-0 flex-1">
            <h4 class="text-[11px] font-black text-gray-700 dark:text-gray-300 truncate leading-none mb-1">
              {{ lesson.title }}
            </h4>
            <span class="text-[9px] font-bold uppercase tracking-tighter" :class="getStatusColor(lesson)">
              {{ lesson.status_label }}
            </span>
          </div>
          <div v-if="lesson.progress_percentage > 0 && !lesson.completed" class="text-[10px] font-black text-blue-500 font-audiowide">
            {{ lesson.progress_percentage }}%
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
