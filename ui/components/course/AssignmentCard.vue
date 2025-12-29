<script setup lang="ts">
import { Icon } from '@iconify/vue'

interface Props {
  assignment: any
  isCourseAdmin?: boolean
  courseId: string | number
}

const props = withDefaults(defineProps<Props>(), {
  isCourseAdmin: false
})

const emit = defineEmits<{
  'edit': [assignment: any]
  'delete': [assignmentId: number]
  'click': [assignment: any]
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
  if (props.assignment.status === 1 || props.assignment.is_published) {
    return { text: 'เผยแพร่แล้ว', class: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' }
  }
  return { text: 'ฉบับร่าง', class: 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400' }
})

// Check if overdue
const isOverdue = computed(() => {
  if (!props.assignment.due_date) return false
  return new Date(props.assignment.due_date) < new Date()
})

// Truncate text
const truncate = (text: string, length: number) => {
  if (!text) return ''
  return text.length > length ? text.substring(0, length) + '...' : text
}
</script>

<template>
  <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden hover:shadow-lg transition-shadow">
    <div class="p-4">
      <div class="flex items-start justify-between gap-4">
        <!-- Content -->
        <button 
          @click="emit('click', assignment)"
          class="flex-1 text-left"
        >
          <div class="flex items-start gap-3">
            <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-orange-400 to-red-500 flex items-center justify-center flex-shrink-0">
              <Icon icon="material-symbols:assignment-outline" class="w-5 h-5 text-white" />
            </div>
            <div class="flex-1 min-w-0">
              <h3 class="font-bold text-gray-900 dark:text-white mb-1 hover:text-cyan-600 dark:hover:text-cyan-400 transition-colors">
                {{ assignment.title || assignment.name }}
              </h3>
              <p v-if="assignment.description" class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                {{ truncate(assignment.description, 100) }}
              </p>
              
              <!-- Meta -->
              <div class="flex flex-wrap items-center gap-3 text-xs">
                <span class="px-2 py-1 rounded-full" :class="getStatusBadge.class">
                  {{ getStatusBadge.text }}
                </span>
                <span v-if="assignment.points" class="flex items-center gap-1 text-gray-500 dark:text-gray-400">
                  <Icon icon="fluent:star-24-regular" class="w-3.5 h-3.5 text-yellow-500" />
                  {{ assignment.points }} คะแนน
                </span>
                <span 
                  v-if="assignment.due_date" 
                  class="flex items-center gap-1"
                  :class="isOverdue ? 'text-red-500' : 'text-gray-500 dark:text-gray-400'"
                >
                  <Icon icon="fluent:clock-24-regular" class="w-3.5 h-3.5" />
                  กำหนดส่ง: {{ formatDate(assignment.due_date) }}
                </span>
              </div>
            </div>
          </div>
        </button>

        <!-- Admin Actions -->
        <div v-if="isCourseAdmin" class="flex items-center gap-1 flex-shrink-0">
          <button 
            @click="emit('edit', assignment)"
            class="p-2 text-gray-400 hover:text-blue-500 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-colors"
            title="แก้ไข"
          >
            <Icon icon="fluent:edit-24-regular" class="w-4 h-4" />
          </button>
          <button 
            @click="emit('delete', assignment.id)"
            class="p-2 text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors"
            title="ลบ"
          >
            <Icon icon="fluent:delete-24-regular" class="w-4 h-4" />
          </button>
        </div>
      </div>
    </div>

    <!-- Answer Status (for members) -->
    <div 
      v-if="assignment.answer_status !== undefined" 
      class="px-4 py-2 border-t border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50"
    >
      <div class="flex items-center justify-between text-sm">
        <span class="text-gray-600 dark:text-gray-400">สถานะการส่ง:</span>
        <span 
          class="font-medium"
          :class="assignment.answer_status === 'submitted' ? 'text-green-600' : 'text-orange-600'"
        >
          {{ assignment.answer_status === 'submitted' ? 'ส่งแล้ว' : 'ยังไม่ได้ส่ง' }}
        </span>
      </div>
    </div>
  </div>
</template>
