<script setup lang="ts">
import { ref } from 'vue'
import { Icon } from '@iconify/vue'

interface Props {
  lesson: any
}

const props = defineProps<Props>()

const emit = defineEmits<{
  like: []
  dislike: []
  bookmark: []
  share: []
  comment: []
}>()

// State
const isLiked = ref(props.lesson.is_liked_by_auth || false)
const isDisliked = ref(props.lesson.is_disliked_by_auth || false)
const isBookmarked = ref(props.lesson.is_bookmarked_by_auth || false)

const likeCount = ref(props.lesson.like_count || 0)
const dislikeCount = ref(props.lesson.dislike_count || 0)
const bookmarkCount = ref(props.lesson.bookmarks_count || 0)
const commentCount = ref(props.lesson.comment_count || 0)
const shareCount = ref(props.lesson.share_count || 0)

// Methods
const handleLike = () => {
  if (isLiked.value) {
    isLiked.value = false
    likeCount.value--
  } else {
    isLiked.value = true
    likeCount.value++
    if (isDisliked.value) {
      isDisliked.value = false
      dislikeCount.value--
    }
  }
  emit('like')
}

const handleDislike = () => {
  if (isDisliked.value) {
    isDisliked.value = false
    dislikeCount.value--
  } else {
    isDisliked.value = true
    dislikeCount.value++
    if (isLiked.value) {
      isLiked.value = false
      likeCount.value--
    }
  }
  emit('dislike')
}

const handleBookmark = () => {
  isBookmarked.value = !isBookmarked.value
  bookmarkCount.value += isBookmarked.value ? 1 : -1
  emit('bookmark')
}

const handleShare = () => {
  shareCount.value++
  emit('share')
}

const handleComment = () => {
  emit('comment')
}
</script>

<template>
  <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
    <!-- Stats Row -->
    <div class="flex items-center justify-between mb-4 text-sm text-gray-600 dark:text-gray-400">
      <div class="flex items-center gap-4">
        <button
          v-if="likeCount > 0 || dislikeCount > 0"
          class="hover:text-blue-600 transition-colors"
        >
          {{ likeCount }} ถูกใจ · {{ dislikeCount }} ไม่ถูกใจ
        </button>
      </div>
      <div class="flex items-center gap-4">
        <button
          v-if="commentCount > 0"
          @click="handleComment"
          class="hover:text-blue-600 transition-colors"
        >
          {{ commentCount }} ความคิดเห็น
        </button>
        <button v-if="shareCount > 0" class="hover:text-blue-600 transition-colors">
          {{ shareCount }} การแชร์
        </button>
        <button v-if="bookmarkCount > 0" class="hover:text-blue-600 transition-colors">
          {{ bookmarkCount }} บุ๊กมาร์ก
        </button>
      </div>
    </div>

    <!-- Action Buttons -->
    <div class="grid grid-cols-5 gap-2">
      <!-- Like Button -->
      <button
        @click="handleLike"
        class="group flex items-center justify-center gap-2 px-4 py-3 rounded-xl transition-all duration-300 hover:scale-105"
        :class="[
          isLiked
            ? 'bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400'
            : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 hover:bg-blue-50 dark:hover:bg-blue-900/20',
        ]"
      >
        <Icon
          :icon="isLiked ? 'fluent:thumb-like-24-filled' : 'fluent:thumb-like-24-regular'"
          class="w-5 h-5 transition-transform group-hover:scale-110"
          :class="isLiked && 'animate-bounce'"
        />
        <span class="font-medium text-sm">ถูกใจ</span>
      </button>

      <!-- Dislike Button -->
      <button
        @click="handleDislike"
        class="group flex items-center justify-center gap-2 px-4 py-3 rounded-xl transition-all duration-300 hover:scale-105"
        :class="[
          isDisliked
            ? 'bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400'
            : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 hover:bg-red-50 dark:hover:bg-red-900/20',
        ]"
      >
        <Icon
          :icon="isDisliked ? 'fluent:thumb-dislike-24-filled' : 'fluent:thumb-dislike-24-regular'"
          class="w-5 h-5 transition-transform group-hover:scale-110"
          :class="isDisliked && 'animate-bounce'"
        />
        <span class="font-medium text-sm">ไม่ถูกใจ</span>
      </button>

      <!-- Bookmark Button -->
      <button
        @click="handleBookmark"
        class="group flex items-center justify-center gap-2 px-4 py-3 rounded-xl transition-all duration-300 hover:scale-105"
        :class="[
          isBookmarked
            ? 'bg-amber-100 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400'
            : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 hover:bg-amber-50 dark:hover:bg-amber-900/20',
        ]"
      >
        <Icon
          :icon="isBookmarked ? 'fluent:bookmark-24-filled' : 'fluent:bookmark-24-regular'"
          class="w-5 h-5 transition-transform group-hover:scale-110"
          :class="isBookmarked && 'animate-pulse'"
        />
        <span class="font-medium text-sm">บันทึก</span>
      </button>

      <!-- Comment Button -->
      <button
        @click="handleComment"
        class="group flex items-center justify-center gap-2 px-4 py-3 rounded-xl bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 hover:bg-green-50 dark:hover:bg-green-900/20 hover:text-green-600 dark:hover:text-green-400 transition-all duration-300 hover:scale-105"
      >
        <Icon
          icon="fluent:comment-24-regular"
          class="w-5 h-5 transition-transform group-hover:scale-110"
        />
        <span class="font-medium text-sm">แสดงความคิดเห็น</span>
      </button>

      <!-- Share Button -->
      <button
        @click="handleShare"
        class="group flex items-center justify-center gap-2 px-4 py-3 rounded-xl bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 hover:bg-purple-50 dark:hover:bg-purple-900/20 hover:text-purple-600 dark:hover:text-purple-400 transition-all duration-300 hover:scale-105"
      >
        <Icon
          icon="fluent:share-24-regular"
          class="w-5 h-5 transition-transform group-hover:scale-110"
        />
        <span class="font-medium text-sm">แชร์</span>
      </button>
    </div>
  </div>
</template>
