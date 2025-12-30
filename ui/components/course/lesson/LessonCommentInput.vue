<script setup lang="ts">
import { ref } from 'vue'
import { Icon } from '@iconify/vue'
import { useAuthStore } from '~/stores/auth'

interface Props {
  lessonId: number
}

const props = defineProps<Props>()

const emit = defineEmits<{
  'comment-created': [comment: any]
}>()

// Stores
const authStore = useAuthStore()

// API
const api = useApi()

// State
const content = ref('')
const isSubmitting = ref(false)

// Submit comment
const submitComment = async () => {
  if (!content.value.trim() || isSubmitting.value) return

  isSubmitting.value = true

  try {
    const response = await api.post(`/api/lessons/${props.lessonId}/comments`, {
      content: content.value.trim(),
    })

    if (response.success && response.comment) {
      emit('comment-created', response.comment)
      content.value = ''
    }
  } catch (error: any) {
    console.error('Failed to post comment:', error)
    const useSwal = useSweetAlert()
    useSwal.error(error?.data?.message || 'ไม่สามารถส่งความคิดเห็นได้ กรุณาลองใหม่อีกครั้ง')
  } finally {
    isSubmitting.value = false
  }
}

// Handle keyboard shortcuts
const handleKeydown = (event: KeyboardEvent) => {
  if (event.key === 'Enter' && (event.ctrlKey || event.metaKey)) {
    event.preventDefault()
    submitComment()
  }
}
</script>

<template>
  <div class="flex gap-3">
    <!-- User Avatar -->
    <NuxtLink :to="`/profile/${authStore.user?.id}`" class="flex-shrink-0">
      <img
        :src="authStore.user?.avatar || '/default-avatar.png'"
        :alt="authStore.user?.name"
        class="w-10 h-10 rounded-full object-cover ring-2 ring-gray-200 dark:ring-gray-700"
      />
    </NuxtLink>

    <!-- Comment Form -->
    <div class="flex-1">
      <div class="relative">
        <textarea
          v-model="content"
          @keydown="handleKeydown"
          placeholder="เขียนความคิดเห็น... (Ctrl+Enter เพื่อส่ง)"
          rows="3"
          class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none transition-all"
          :disabled="isSubmitting"
        />

        <!-- Character count (optional) -->
        <div v-if="content.length > 0" class="absolute bottom-2 left-2 text-xs text-gray-400">
          {{ content.length }} ตัวอักษร
        </div>
      </div>

      <!-- Action Row -->
      <div class="flex items-center justify-between mt-3">
        <!-- Left side - emoji/attachments (future) -->
        <div class="flex items-center gap-2">
          <!-- Placeholder for future features -->
        </div>

        <!-- Submit Button -->
        <button
          @click="submitComment"
          :disabled="!content.trim() || isSubmitting"
          class="group flex items-center gap-2 px-6 py-2.5 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white font-medium rounded-xl transition-all duration-300 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:from-blue-600 disabled:hover:to-blue-700 shadow-lg shadow-blue-500/30 hover:shadow-xl hover:shadow-blue-500/40"
        >
          <Icon
            v-if="isSubmitting"
            icon="fluent:spinner-ios-20-filled"
            class="w-5 h-5 animate-spin"
          />
          <Icon
            v-else
            icon="fluent:send-24-filled"
            class="w-5 h-5 group-hover:translate-x-0.5 transition-transform"
          />
          <span>{{ isSubmitting ? 'กำลังส่ง...' : 'ส่งความคิดเห็น' }}</span>
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped>
textarea:focus {
  outline: none;
}
</style>
