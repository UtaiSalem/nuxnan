<script setup lang="ts">
import { ref, computed } from 'vue'
import { Icon } from '@iconify/vue'
import { useAuthStore } from '~/stores/auth'

interface Props {
  comment: any
  lessonId: number
}

const props = defineProps<Props>()

const emit = defineEmits<{
  'comment-deleted': [commentId: number]
}>()

// Stores
const authStore = useAuthStore()

// API
const api = useApi()

// State
const isDeleting = ref(false)

// Computed
const isOwner = computed(() => authStore.user?.id === props.comment.user?.id)
const canDelete = computed(() => isOwner.value) // TODO: Add admin check

// Format timestamp
const timestamp = computed(() => props.comment.create_at || props.comment.timestamp)

// Handle like
const handleLike = async () => {
  if (props.comment.isLiking) return

  props.comment.isLiking = true
  const wasLiked = props.comment.isLikedByAuth

  // Optimistic update
  props.comment.isLikedByAuth = !wasLiked
  props.comment.likes = (props.comment.likes || 0) + (wasLiked ? -1 : 1)

  // Remove dislike if was disliked
  if (!wasLiked && props.comment.isDislikedByAuth) {
    props.comment.isDislikedByAuth = false
    props.comment.dislikes = (props.comment.dislikes || 0) - 1
  }

  try {
    const response = await api.post(
      `/api/lessons/${props.lessonId}/comments/${props.comment.id}/like`
    )

    if (!response.success) {
      // Revert on failure
      props.comment.isLikedByAuth = wasLiked
      props.comment.likes = (props.comment.likes || 0) + (wasLiked ? 1 : -1)

      const useSwal = useSweetAlert()
      useSwal.error(response.message || 'ไม่สามารถกดถูกใจได้')
    }
  } catch (error: any) {
    // Revert on error
    props.comment.isLikedByAuth = wasLiked
    props.comment.likes = (props.comment.likes || 0) + (wasLiked ? 1 : -1)

    console.error('Failed to like comment:', error)
  } finally {
    props.comment.isLiking = false
  }
}

// Handle dislike
const handleDislike = async () => {
  if (props.comment.isDisliking) return

  props.comment.isDisliking = true
  const wasDisliked = props.comment.isDislikedByAuth

  // Optimistic update
  props.comment.isDislikedByAuth = !wasDisliked
  props.comment.dislikes = (props.comment.dislikes || 0) + (wasDisliked ? -1 : 1)

  // Remove like if was liked
  if (!wasDisliked && props.comment.isLikedByAuth) {
    props.comment.isLikedByAuth = false
    props.comment.likes = (props.comment.likes || 0) - 1
  }

  try {
    const response = await api.post(
      `/api/lessons/${props.lessonId}/comments/${props.comment.id}/dislike`
    )

    if (!response.success) {
      // Revert on failure
      props.comment.isDislikedByAuth = wasDisliked
      props.comment.dislikes = (props.comment.dislikes || 0) + (wasDisliked ? 1 : -1)

      const useSwal = useSweetAlert()
      useSwal.error(response.message || 'ไม่สามารถกดไม่ถูกใจได้')
    }
  } catch (error: any) {
    // Revert on error
    props.comment.isDislikedByAuth = wasDisliked
    props.comment.dislikes = (props.comment.dislikes || 0) + (wasDisliked ? 1 : -1)

    console.error('Failed to dislike comment:', error)
  } finally {
    props.comment.isDisliking = false
  }
}

// Delete comment
const deleteComment = async () => {
  const useSwal = useSweetAlert()
  const confirmed = await useSwal.confirm('คุณแน่ใจหรือไม่?', 'ความคิดเห็นนี้จะถูกลบอย่างถาวร')

  if (!confirmed) return

  isDeleting.value = true

  try {
    const response = await api.delete(`/api/lessons/${props.lessonId}/comments/${props.comment.id}`)

    if (response.success) {
      emit('comment-deleted', props.comment.id)
      useSwal.toast('ลบความคิดเห็นสำเร็จ', 'success')
    }
  } catch (error: any) {
    console.error('Failed to delete comment:', error)
    useSwal.error(error?.data?.message || 'ไม่สามารถลบความคิดเห็นได้')
  } finally {
    isDeleting.value = false
  }
}
</script>

<template>
  <div class="flex gap-3 group">
    <!-- User Avatar -->
    <NuxtLink :to="`/profile/${comment.user?.id}`" class="flex-shrink-0">
      <img
        :src="comment.user?.avatar || '/default-avatar.png'"
        :alt="comment.user?.name"
        class="w-10 h-10 rounded-full object-cover ring-2 ring-gray-200 dark:ring-gray-700 hover:ring-blue-500 transition-all"
      />
    </NuxtLink>

    <!-- Comment Content -->
    <div class="flex-1 min-w-0">
      <!-- Comment Bubble -->
      <div class="bg-gray-50 dark:bg-gray-800 rounded-2xl px-4 py-3 relative">
        <!-- Delete Button (Owner only) -->
        <button
          v-if="canDelete"
          @click="deleteComment"
          :disabled="isDeleting"
          class="absolute top-2 right-2 p-1.5 text-gray-400 hover:text-red-600 dark:hover:text-red-400 opacity-0 group-hover:opacity-100 transition-all rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20"
          title="ลบความคิดเห็น"
        >
          <Icon icon="fluent:delete-24-regular" class="w-4 h-4" />
        </button>

        <!-- User Name and Time -->
        <div class="flex items-center gap-2 mb-1.5">
          <NuxtLink
            :to="`/profile/${comment.user?.id}`"
            class="font-semibold text-gray-900 dark:text-white hover:text-blue-600 dark:hover:text-blue-400 transition-colors"
          >
            {{ comment.user?.name }}
          </NuxtLink>
          <span class="text-sm text-gray-500 dark:text-gray-400">•</span>
          <span class="text-sm text-gray-500 dark:text-gray-400">
            {{ timestamp }}
          </span>
        </div>

        <!-- Comment Text -->
        <p class="text-gray-800 dark:text-gray-200 whitespace-pre-wrap break-words">
          {{ comment.content }}
        </p>

        <!-- TODO: Images if exists -->
        <div v-if="comment.images && comment.images.length > 0" class="mt-3 grid grid-cols-2 gap-2">
          <img
            v-for="(image, index) in comment.images"
            :key="index"
            :src="image.url"
            class="rounded-lg object-cover w-full h-32"
            alt="Comment image"
          />
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="flex items-center gap-4 mt-2 px-2">
        <!-- Like Button -->
        <button
          @click="handleLike"
          :disabled="comment.isLiking"
          class="group/btn flex items-center gap-1.5 text-sm transition-colors"
          :class="[
            comment.isLikedByAuth
              ? 'text-blue-600 dark:text-blue-400 font-medium'
              : 'text-gray-600 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400',
          ]"
        >
          <Icon
            :icon="
              comment.isLikedByAuth ? 'fluent:thumb-like-24-filled' : 'fluent:thumb-like-24-regular'
            "
            class="w-4 h-4 group-hover/btn:scale-110 transition-transform"
          />
          <span>{{ comment.likes || 0 }} ถูกใจ</span>
        </button>

        <!-- Dislike Button -->
        <button
          @click="handleDislike"
          :disabled="comment.isDisliking"
          class="group/btn flex items-center gap-1.5 text-sm transition-colors"
          :class="[
            comment.isDislikedByAuth
              ? 'text-red-600 dark:text-red-400 font-medium'
              : 'text-gray-600 dark:text-gray-400 hover:text-red-600 dark:hover:text-red-400',
          ]"
        >
          <Icon
            :icon="
              comment.isDislikedByAuth
                ? 'fluent:thumb-dislike-24-filled'
                : 'fluent:thumb-dislike-24-regular'
            "
            class="w-4 h-4 group-hover/btn:scale-110 transition-transform"
          />
          <span>{{ comment.dislikes || 0 }}</span>
        </button>

        <!-- Reply Button (TODO: Future feature) -->
        <!-- <button class="flex items-center gap-1.5 text-sm text-gray-600 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 transition-colors">
          <Icon icon="fluent:arrow-reply-24-regular" class="w-4 h-4" />
          <span>ตอบกลับ</span>
        </button> -->
      </div>
    </div>
  </div>
</template>
