<script setup lang="ts">
import { Icon } from '@iconify/vue'

interface Props {
  lessonId: number
  questionCount: number
  bestScore?: number
  timeLimit?: number
}

const props = withDefaults(defineProps<Props>(), {
  bestScore: undefined,
  timeLimit: undefined
})
</script>

<template>
  <div class="p-4 rounded-xl border-2 border-orange-300 dark:border-orange-700 bg-orange-100 dark:bg-orange-900/30 transition-all duration-300 hover:shadow-lg hover:scale-[1.02] cursor-pointer">
    <div class="flex items-start justify-between gap-4">
      <div class="flex-1">
        <h4 class="font-semibold text-gray-900 dark:text-white mb-2 flex items-center gap-2">
          <Icon icon="fluent:quiz-new-24-filled" class="w-5 h-5 text-orange-600 dark:text-orange-400" />
          แบบทดสอบประจำบทเรียน
        </h4>

        <!-- Meta Info -->
        <div class="flex items-center gap-4 text-sm text-gray-600 dark:text-gray-400 mb-3">
          <div class="flex items-center gap-1">
            <Icon icon="fluent:question-circle-24-regular" class="w-4 h-4" />
            <span>{{ questionCount }} คำถาม</span>
          </div>

          <div v-if="timeLimit" class="flex items-center gap-1">
            <Icon icon="fluent:timer-24-regular" class="w-4 h-4" />
            <span>{{ timeLimit }} นาที</span>
          </div>

          <div v-if="bestScore !== undefined" class="flex items-center gap-1 text-amber-600 dark:text-amber-400 font-medium">
            <Icon icon="fluent:trophy-24-filled" class="w-4 h-4" />
            <span>คะแนนสูงสุด: {{ bestScore }}%</span>
          </div>
        </div>

        <!-- CTA Button -->
        <button
          class="px-4 py-2 rounded-lg font-medium text-sm transition-all duration-300 hover:scale-105"
          :class="[
            bestScore !== undefined
              ? 'bg-blue-500 text-white hover:bg-blue-600'
              : 'bg-orange-500 text-white hover:bg-orange-600'
          ]"
        >
          {{ bestScore !== undefined ? 'ทำแบบทดสอบอีกครั้ง' : 'เริ่มทำแบบทดสอบ' }}
          <Icon icon="fluent:arrow-right-24-regular" class="w-4 h-4 inline ml-1" />
        </button>
      </div>

      <!-- Icon -->
      <div class="flex-shrink-0">
        <Icon 
          icon="fluent:brain-circuit-24-filled" 
          class="w-12 h-12 text-orange-600 dark:text-orange-400"
        />
      </div>
    </div>
  </div>
</template>
