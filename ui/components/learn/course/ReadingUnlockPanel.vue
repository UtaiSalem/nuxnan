<script setup lang="ts">
import { Icon } from '@iconify/vue'

interface LessonProgress {
  id: number
  title: string
  completed: boolean
}

interface ProgressData {
  mode: string
  required: number
  completed: number
  percentage: number
  lessons: LessonProgress[]
  can_unlock_now: boolean
}

const props = defineProps<{
  progress: ProgressData
  courseId: string | number
}>()

defineEmits(['unlock'])
</script>

<template>
  <div class="p-4 bg-gray-50 dark:bg-gray-900/50 border border-gray-200 dark:border-gray-700 rounded-xl space-y-4 shadow-inner">
    <!-- Header/Summary -->
    <div class="flex items-center justify-between">
      <div>
        <h4 class="text-sm font-bold text-gray-800 dark:text-white">ความคืบหน้าการอ่าน</h4>
        <p class="text-[10px] text-gray-500">อ่านบทเรียนที่กำหนดเพื่อขอคืนสิทธิ์สอบ</p>
      </div>
      <div class="text-right">
        <span class="text-xs font-black text-blue-600 dark:text-blue-400">{{ progress.completed }}/{{ progress.required }}</span>
        <span class="text-[10px] text-gray-400 block">บทเรียน</span>
      </div>
    </div>

    <!-- Progress Bar -->
    <div class="relative w-full h-3 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
      <div 
        class="absolute top-0 left-0 h-full bg-gradient-to-r from-blue-500 to-indigo-600 transition-all duration-500 ease-out shadow-[0_0_10px_rgba(59,130,246,0.5)]"
        :style="{ width: progress.percentage + '%' }"
      ></div>
    </div>

    <!-- Lesson List -->
    <div class="space-y-1.5 max-h-48 overflow-y-auto pr-1 custom-scrollbar">
      <div 
        v-for="lesson in progress.lessons" 
        :key="lesson.id" 
        class="flex items-center gap-3 p-2 bg-white dark:bg-gray-800 rounded-lg border border-gray-100 dark:border-gray-700/50 text-sm group transition-all"
      >
        <div class="flex-shrink-0">
          <Icon 
            :icon="lesson.completed ? 'heroicons:check-circle-20-solid' : 'heroicons:clock-20-solid'" 
            :class="lesson.completed ? 'text-green-500' : 'text-gray-300 dark:text-gray-600'"
            class="w-5 h-5"
          />
        </div>
        
        <span 
          :class="[
            'flex-1 truncate',
            lesson.completed ? 'text-gray-400 line-through italic' : 'text-gray-700 dark:text-gray-300'
          ]"
        >
          {{ lesson.title }}
        </span>

        <NuxtLink 
          v-if="!lesson.completed"
          :to="`/courses/${courseId}/lessons/${lesson.id}`"
          class="flex-shrink-0 flex items-center gap-1 text-[10px] font-bold text-blue-600 dark:text-blue-400 hover:text-blue-700 transition-colors"
        >
          ไปอ่าน
          <Icon icon="heroicons:arrow-right-20-solid" class="w-3 h-3" />
        </NuxtLink>
        <div v-else class="text-[10px] font-bold text-green-600 dark:text-green-400 opacity-60">สำเร็จ</div>
      </div>
    </div>

    <!-- Actions -->
    <div class="pt-2">
      <button 
        v-if="progress.can_unlock_now" 
        @click="$emit('unlock')"
        class="w-full flex items-center justify-center gap-2 py-3 bg-green-600 hover:bg-green-700 text-white font-black rounded-xl shadow-lg shadow-green-500/20 transition-all active:scale-95"
      >
        <Icon icon="heroicons:lock-open-20-solid" class="w-5 h-5" />
        ปลดล็อคสิทธิ์สอบทันที!
      </button>
      <div v-else class="text-center py-2 px-3 bg-blue-50 dark:bg-blue-900/10 rounded-lg border border-blue-100 dark:border-blue-800/30">
        <p class="text-[11px] text-blue-700 dark:text-blue-300 font-medium">อ่านให้ครบเพื่อปลดล็อคสิทธิ์สอบแบบอัตโนมัติ</p>
      </div>
    </div>
  </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
  width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: rgba(156, 163, 175, 0.3);
  border-radius: 10px;
}
</style>
