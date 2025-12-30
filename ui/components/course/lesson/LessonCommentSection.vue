<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { Icon } from '@iconify/vue'
import LessonCommentInput from './LessonCommentInput.vue'
import LessonCommentItem from './LessonCommentItem.vue'

interface Props {
  lessonId: number
  initialComments?: any[]
  commentCount?: number
}

const props = withDefaults(defineProps<Props>(), {
  initialComments: () => [],
  commentCount: 0,
})

const emit = defineEmits<{
  'update:commentCount': [count: number]
}>()

// API
const api = useApi()

// State
const comments = ref<any[]>(props.initialComments || [])
const isLoadingMore = ref(false)
const currentPage = ref(1)
const hasMore = ref(props.commentCount > props.initialComments.length)

// Load more comments
const loadMoreComments = async () => {
  if (isLoadingMore.value) return

  isLoadingMore.value = true
  currentPage.value++

  try {
    const response = await api.get(
      `/api/lessons/${props.lessonId}/comments?page=${currentPage.value}`
    )
    const newComments = response.data || []

    comments.value = [...comments.value, ...newComments]
    hasMore.value = currentPage.value < (response.meta?.last_page || 1)
  } catch (error) {
    console.error('Failed to load more comments:', error)
    currentPage.value-- // Revert page increment
  } finally {
    isLoadingMore.value = false
  }
}

// Handle new comment
const handleCommentCreated = (newComment: any) => {
  comments.value.unshift(newComment)
  emit('update:commentCount', props.commentCount + 1)
}

// Handle comment deleted
const handleCommentDeleted = (commentId: number) => {
  const index = comments.value.findIndex((c) => c.id === commentId)
  if (index > -1) {
    comments.value.splice(index, 1)
    emit('update:commentCount', props.commentCount - 1)
  }
}

// Watch for initial comments changes
watch(
  () => props.initialComments,
  (newComments) => {
    if (newComments && newComments.length > 0) {
      comments.value = [...newComments]
    }
  },
  { immediate: true }
)
</script>

<template>
  <div class="border-t border-gray-200 dark:border-gray-700 pt-6 space-y-6">
    <!-- Comment Input -->
    <LessonCommentInput :lessonId="lessonId" @comment-created="handleCommentCreated" />

    <!-- Comments List -->
    <div class="space-y-4">
      <TransitionGroup name="comment">
        <LessonCommentItem
          v-for="comment in comments"
          :key="comment.id"
          :comment="comment"
          :lessonId="lessonId"
          @comment-deleted="handleCommentDeleted"
        />
      </TransitionGroup>

      <!-- Empty State -->
      <div v-if="comments.length === 0" class="text-center py-12 text-gray-500 dark:text-gray-400">
        <Icon icon="fluent:comment-24-regular" class="w-16 h-16 mx-auto mb-4 opacity-50" />
        <p class="text-lg font-medium">ยังไม่มีความคิดเห็น</p>
        <p class="text-sm mt-1">เป็นคนแรกที่แสดงความคิดเห็นในบทเรียนนี้</p>
      </div>

      <!-- Load More Button -->
      <button
        v-if="hasMore"
        @click="loadMoreComments"
        :disabled="isLoadingMore"
        class="w-full py-3 px-4 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 rounded-xl transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
      >
        <span v-if="isLoadingMore" class="flex items-center justify-center gap-2">
          <Icon icon="fluent:spinner-ios-20-filled" class="w-4 h-4 animate-spin" />
          กำลังโหลด...
        </span>
        <span v-else> โหลดความคิดเห็นเพิ่มเติม </span>
      </button>
    </div>
  </div>
</template>

<style scoped>
.comment-enter-active {
  animation: comment-in 0.3s ease-out;
}

.comment-leave-active {
  animation: comment-out 0.3s ease-in;
}

@keyframes comment-in {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes comment-out {
  from {
    opacity: 1;
    transform: scale(1);
  }
  to {
    opacity: 0;
    transform: scale(0.95);
  }
}
</style>
