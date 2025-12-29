<script setup lang="ts">
import { Icon } from '@iconify/vue'

interface Props {
  quiz: any
  isCourseAdmin?: boolean
  courseId: string | number
  quizIndex?: number
}

const props = withDefaults(defineProps<Props>(), {
  isCourseAdmin: false,
  quizIndex: 1
})

const emit = defineEmits<{
  'edit': [quiz: any]
  'delete': [quizId: number]
  'click': [quiz: any]
  'start': [quiz: any]
}>()

// Format date
const formatDate = (date: string) => {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('th-TH', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

// Get status badge
const getStatusBadge = computed(() => {
  if (props.quiz.status === 1 || props.quiz.is_published) {
    return { text: 'เผยแพร่แล้ว', class: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' }
  }
  return { text: 'ฉบับร่าง', class: 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400' }
})

// Check if quiz is available
const isAvailable = computed(() => {
  const now = new Date()
  if (props.quiz.start_date && new Date(props.quiz.start_date) > now) return false
  if (props.quiz.end_date && new Date(props.quiz.end_date) < now) return false
  return true
})
</script>

<template>
  <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden hover:shadow-lg transition-shadow">
    <div class="p-4">
      <div class="flex items-start justify-between gap-4">
        <!-- Content -->
        <div class="flex items-start gap-3 flex-1">
          <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-purple-500 to-indigo-600 flex items-center justify-center flex-shrink-0">
            <span class="text-white font-bold text-lg">{{ quizIndex }}</span>
          </div>
          <div class="flex-1 min-w-0">
            <h3 class="font-bold text-gray-900 dark:text-white mb-1">
              {{ quiz.title || quiz.name }}
            </h3>
            
            <!-- Stats -->
            <div class="flex flex-wrap items-center gap-3 text-sm text-gray-500 dark:text-gray-400 mb-3">
              <span class="flex items-center gap-1">
                <Icon icon="fluent:document-24-regular" class="w-4 h-4" />
                {{ quiz.questions_count || quiz.questions?.length || 0 }} ข้อ
              </span>
              <span class="flex items-center gap-1">
                <Icon icon="fluent:star-24-regular" class="w-4 h-4 text-yellow-500" />
                {{ quiz.total_score || 0 }} คะแนน
              </span>
              <span v-if="quiz.time_limit" class="flex items-center gap-1">
                <Icon icon="fluent:clock-24-regular" class="w-4 h-4" />
                {{ quiz.time_limit }} นาที
              </span>
            </div>
            
            <!-- Meta -->
            <div class="flex flex-wrap items-center gap-2 text-xs">
              <span class="px-2 py-1 rounded-full" :class="getStatusBadge.class">
                {{ getStatusBadge.text }}
              </span>
              <span v-if="quiz.start_date" class="text-gray-500 dark:text-gray-400">
                เริ่ม: {{ formatDate(quiz.start_date) }}
              </span>
              <span v-if="quiz.end_date" class="text-gray-500 dark:text-gray-400">
                สิ้นสุด: {{ formatDate(quiz.end_date) }}
              </span>
            </div>
          </div>
        </div>

        <!-- Actions -->
        <div class="flex flex-col items-end gap-2 flex-shrink-0">
          <!-- Admin Actions -->
          <div v-if="isCourseAdmin" class="flex items-center gap-1">
            <button 
              @click="emit('edit', quiz)"
              class="p-2 text-gray-400 hover:text-blue-500 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-colors"
              title="แก้ไข"
            >
              <Icon icon="fluent:edit-24-regular" class="w-4 h-4" />
            </button>
            <button 
              @click="emit('delete', quiz.id)"
              class="p-2 text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors"
              title="ลบ"
            >
              <Icon icon="fluent:delete-24-regular" class="w-4 h-4" />
            </button>
          </div>
          
          <!-- Start Quiz Button -->
          <button
            v-if="isAvailable && !isCourseAdmin"
            @click="emit('start', quiz)"
            class="flex items-center gap-2 px-4 py-2 bg-purple-500 text-white rounded-lg hover:bg-purple-600 transition-colors text-sm"
          >
            <Icon icon="fluent:play-24-filled" class="w-4 h-4" />
            เริ่มทำ
          </button>
          <button
            v-else-if="!isAvailable && !isCourseAdmin"
            disabled
            class="flex items-center gap-2 px-4 py-2 bg-gray-300 text-gray-500 rounded-lg text-sm cursor-not-allowed"
          >
            <Icon icon="fluent:lock-closed-24-regular" class="w-4 h-4" />
            ยังไม่เปิดให้ทำ
          </button>
        </div>
      </div>
    </div>

    <!-- Answer Status (for members) -->
    <div 
      v-if="quiz.attempt_status !== undefined" 
      class="px-4 py-2 border-t border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50"
    >
      <div class="flex items-center justify-between text-sm">
        <span class="text-gray-600 dark:text-gray-400">สถานะ:</span>
        <div class="flex items-center gap-2">
          <span 
            class="font-medium"
            :class="quiz.attempt_status === 'completed' ? 'text-green-600' : 'text-orange-600'"
          >
            {{ quiz.attempt_status === 'completed' ? 'ทำเสร็จแล้ว' : 'ยังไม่ได้ทำ' }}
          </span>
          <span v-if="quiz.score !== undefined" class="text-gray-600 dark:text-gray-400">
            ({{ quiz.score }}/{{ quiz.total_score }} คะแนน)
          </span>
        </div>
      </div>
    </div>
  </div>
</template>
