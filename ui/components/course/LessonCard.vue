<script setup lang="ts">
import { Icon } from '@iconify/vue'
import { computed } from 'vue'

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

// Status labels
const statusText = computed(() => {
  return props.lesson.status === 1 ? 'เผยแพร่แล้ว' : 'แบบร่าง'
})

const statusColor = computed(() => {
  return props.lesson.status === 1 
    ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' 
    : 'bg-gray-100 text-gray-700 dark:bg-gray-700/30 dark:text-gray-400'
})

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

// Handle card click
const handleClick = () => {
  emit('click', props.lesson)
}

// Handle edit
const handleEdit = (e: Event) => {
  e.stopPropagation()
  emit('edit', props.lesson)
}

// Handle delete
const handleDelete = (e: Event) => {
  e.stopPropagation()
  emit('delete', props.lesson.id)
}
</script>

<template>
  <div
    @click="handleClick"
    class="group relative bg-white dark:bg-gray-800 rounded-2xl shadow-md hover:shadow-2xl transition-all duration-300 overflow-hidden cursor-pointer border border-gray-200 dark:border-gray-700 transform hover:scale-[1.02]"
  >
    <!-- Cover Image -->
    <div class="relative h-48 overflow-hidden bg-gradient-to-br from-blue-100 to-purple-100 dark:from-blue-900/20 dark:to-purple-900/20">
      <img
        v-if="lesson.images && lesson.images[0]"
        :src="lesson.images[0].full_url"
        :alt="lesson.title"
        class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-110"
      >
      <div v-else class="w-full h-full flex items-center justify-center">
        <Icon icon="fluent:book-24-regular" class="w-20 h-20 text-gray-300 dark:text-gray-600" />
      </div>
      
      <!-- Status Badge -->
      <div class="absolute top-3 right-3">
        <span :class="statusColor" class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold shadow-md backdrop-blur-sm">
          <Icon v-if="lesson.status === 1" icon="fluent:checkmark-circle-24-filled" class="w-3 h-3" />
          <Icon v-else icon="fluent:draft-24-regular" class="w-3 h-3" />
          {{ statusText }}
        </span>
      </div>

      <!-- Points Badge (if any) -->
      <div v-if="lesson.point_tuition_fee > 0" class="absolute top-3 left-3">
        <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-amber-500 text-white text-xs font-bold shadow-md">
          <Icon icon="fluent:diamond-24-filled" class="w-3 h-3" />
          {{ lesson.point_tuition_fee }} พอยต์
        </span>
      </div>

      <!-- Gradient Overlay -->
      <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300" />
    </div>

    <!-- Content -->
    <div class="p-5">
      <!-- Title -->
      <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2 line-clamp-2 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">
        {{ lesson.title || lesson.name }}
      </h3>

      <!-- Description -->
      <p v-if="lesson.description" class="text-sm text-gray-600 dark:text-gray-400 line-clamp-2 mb-4">
        {{ truncate(lesson.description, 100) }}
      </p>

      <!-- Creator Info -->
      <div class="flex items-center gap-2 mb-4 pb-4 border-b border-gray-100 dark:border-gray-700">
        <img 
          :src="lesson.creater?.avatar || lesson.user?.avatar || '/images/default-avatar.png'" 
          :alt="lesson.creater?.name || lesson.user?.name"
          class="w-6 h-6 rounded-full object-cover border border-gray-200 dark:border-gray-600"
        >
        <span class="text-xs text-gray-600 dark:text-gray-400">
          {{ lesson.creater?.name || lesson.user?.name || 'ผู้สอน' }}
        </span>
        <span class="text-xs text-gray-400 dark:text-gray-500">•</span>
        <span class="text-xs text-gray-500 dark:text-gray-400">
          {{ formatDate(lesson.created_at) }}
        </span>
      </div>

      <!-- Stats Row -->
      <div class="flex items-center gap-4 text-xs text-gray-500 dark:text-gray-400 mb-4">
        <!-- Reading Time -->
        <div v-if="lesson.min_read" class="flex items-center gap-1">
          <Icon icon="fluent:clock-24-regular" class="w-4 h-4" />
          <span>{{ lesson.min_read }} นาที</span>
        </div>

        <!-- Views -->
        <div v-if="lesson.view_count" class="flex items-center gap-1">
          <Icon icon="fluent:eye-24-regular" class="w-4 h-4" />
          <span>{{ lesson.view_count }}</span>
        </div>

        <!-- Topics -->
        <div v-if="lesson.topics_count !== undefined" class="flex items-center gap-1">
          <Icon icon="fluent:document-24-regular" class="w-4 h-4" />
          <span>{{ lesson.topics_count }} หัวข้อ</span>
        </div>

        <!-- Comments -->
        <div v-if="lesson.comment_count" class="flex items-center gap-1">
          <Icon icon="fluent:comment-24-regular" class="w-4 h-4" />
          <span>{{ lesson.comment_count }}</span>
        </div>
      </div>

      <!-- Actions (Admin Only) -->
      <div v-if="isCourseAdmin" class="flex items-center gap-2">
        <button
          @click="handleEdit"
          class="flex-1 flex items-center justify-center gap-2 px-3 py-2 bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 rounded-lg hover:bg-blue-100 dark:hover:bg-blue-900/30 transition-colors text-sm font-medium"
        >
          <Icon icon="fluent:edit-24-regular" class="w-4 h-4" />
          <span>แก้ไข</span>
        </button>

        <button
          @click="handleDelete"
          class="flex items-center justify-center gap-2 px-3 py-2 bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 rounded-lg hover:bg-red-100 dark:hover:bg-red-900/30 transition-colors text-sm font-medium"
        >
          <Icon icon="fluent:delete-24-regular" class="w-4 h-4" />
          <span>ลบ</span>
        </button>
      </div>
    </div>

    <!-- Hover Effect Overlay -->
    <div class="absolute inset-0 border-2 border-blue-500 rounded-2xl opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none" />
  </div>
</template>
