<script setup lang="ts">
import { Icon } from '@iconify/vue'

interface Props {
  lesson: any
  isCourseAdmin?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  isCourseAdmin: false
})

const emit = defineEmits<{
  'edit': [lesson: any]
  'delete': [lessonId: number]
  'click': [lesson: any]
}>()

// Format date
const formatDate = (date: string) => {
  if (!date) return ''
  return new Date(date).toLocaleDateString('th-TH', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

// Truncate text
const truncate = (text: string, length: number) => {
  if (!text) return ''
  return text.length > length ? text.substring(0, length) + '...' : text
}
</script>

<template>
  <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden hover:shadow-lg transition-shadow">
    <!-- Header -->
    <div class="flex items-center justify-between p-4 border-b border-gray-100 dark:border-gray-700">
      <div class="flex items-center gap-3">
        <img 
          :src="lesson.creater?.avatar || lesson.user?.avatar || '/images/default-avatar.png'" 
          :alt="lesson.creater?.name || lesson.user?.name"
          class="w-10 h-10 rounded-full object-cover border-2 border-gray-200 dark:border-gray-600"
        />
        <div>
          <p class="font-medium text-gray-900 dark:text-white text-sm">
            {{ lesson.creater?.name || lesson.user?.name || 'ผู้สอน' }}
          </p>
          <div class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
            <span>{{ formatDate(lesson.created_at) }}</span>
            <span v-if="lesson.min_read" class="flex items-center gap-1">
              <Icon icon="fluent:clock-24-regular" class="w-3 h-3" />
              {{ lesson.min_read }} นาที
            </span>
          </div>
        </div>
      </div>
      
      <!-- Admin Actions -->
      <div v-if="isCourseAdmin" class="flex items-center gap-1">
        <button 
          @click="emit('edit', lesson)"
          class="p-2 text-gray-400 hover:text-blue-500 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-colors"
          title="แก้ไข"
        >
          <Icon icon="fluent:edit-24-regular" class="w-4 h-4" />
        </button>
        <button 
          @click="emit('delete', lesson.id)"
          class="p-2 text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors"
          title="ลบ"
        >
          <Icon icon="fluent:delete-24-regular" class="w-4 h-4" />
        </button>
      </div>
    </div>
    
    <!-- Content -->
    <button 
      @click="emit('click', lesson)"
      class="w-full text-left p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"
    >
      <h3 class="font-bold text-gray-900 dark:text-white mb-2 hover:text-cyan-600 dark:hover:text-cyan-400 transition-colors">
        {{ lesson.title || lesson.name }}
      </h3>
      <p v-if="lesson.description" class="text-sm text-gray-600 dark:text-gray-400 mb-3">
        {{ truncate(lesson.description, 120) }}
      </p>
      
      <!-- Stats -->
      <div class="flex items-center gap-4 text-xs text-gray-500 dark:text-gray-400">
        <span v-if="lesson.topics_count !== undefined" class="flex items-center gap-1">
          <Icon icon="fluent:document-24-regular" class="w-3.5 h-3.5" />
          {{ lesson.topics_count }} หัวข้อ
        </span>
        <span v-if="lesson.point_tuition_fee" class="flex items-center gap-1">
          <Icon icon="fluent:star-24-regular" class="w-3.5 h-3.5 text-yellow-500" />
          {{ lesson.point_tuition_fee }} แต้ม
        </span>
      </div>
    </button>
  </div>
</template>
