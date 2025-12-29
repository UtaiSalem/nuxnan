<script setup lang="ts">
import { ref } from 'vue'
import { useAuthStore } from '../../stores/auth'
import { useSweetAlert } from '../../composables/useSweetAlert'

const authStore = useAuthStore()
const swal = useSweetAlert()

const props = defineProps({
  postId: {
    type: Number,
    required: true
  },
  likes: {
    type: Number,
    default: 0
  },
  comments: {
    type: Number,
    default: 0
  },
  shares: {
    type: Number,
    default: 0
  },
  isLiked: {
    type: Boolean,
    default: false
  },
  isDisliked: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['like', 'comment', 'share', 'dislike'])

const localLikes = ref(props.likes)
const localIsLiked = ref(props.isLiked)
const localIsDisliked = ref(props.isDisliked)
const isProcessing = ref(false)

const handleLike = async () => {
  if (isProcessing.value) return
  
  isProcessing.value = true
  
  const wasLiked = localIsLiked.value
  const pointsToDeduct = wasLiked ? 12 : 24
  
  const success = authStore.deductPoints(pointsToDeduct)
  
  if (success) {
    localIsLiked.value = !wasLiked
    localLikes.value += wasLiked ? -1 : 1
    emit('like')
  } else {
    swal.warning('คุณมีแต้มไม่เพียงพอ')
  }
  
  isProcessing.value = false
}

const handleDislike = async () => {
  if (isProcessing.value) return
  
  isProcessing.value = true
  
  const wasDisliked = localIsDisliked.value
  const pointsToDeduct = 12
  
  const success = authStore.deductPoints(pointsToDeduct)
  
  if (success) {
    localIsDisliked.value = !wasDisliked
    emit('dislike')
  } else {
    swal.warning('คุณมีแต้มไม่เพียงพอ')
  }
  
  isProcessing.value = false
}
</script>

<template>
  <div class="flex items-center gap-4 py-2 border-t border-b border-gray-100 dark:border-gray-700 my-3">
    <button
      class="flex items-center gap-2 px-3 py-1 rounded-md transition-colors disabled:opacity-50"
      :class="localIsLiked ? 'text-red-500' : 'text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-800'"
      :disabled="isProcessing"
      @click="handleLike"
    >
      <i :class="localIsLiked ? 'pi pi-heart-fill' : 'pi pi-heart'"></i>
      <span>{{ localLikes }} Likes</span>
    </button>
    
    <button
      class="flex items-center gap-2 px-3 py-1 rounded-md transition-colors disabled:opacity-50"
      :class="localIsDisliked ? 'text-blue-500' : 'text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-800'"
      :disabled="isProcessing"
      @click="handleDislike"
    >
      <i class="pi pi-thumbs-down"></i>
      <span>Dislike</span>
    </button>
    
    <button
      class="flex items-center gap-2 px-3 py-1 rounded-md text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-800 transition-colors"
      @click="$emit('comment')"
    >
      <i class="pi pi-comment"></i>
      <span>{{ comments }} Comments</span>
    </button>
    
    <button
      class="flex items-center gap-2 px-3 py-1 rounded-md text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-800 transition-colors ml-auto"
      @click="$emit('share')"
    >
      <i class="pi pi-share-alt"></i>
      <span>Share</span>
    </button>
  </div>
</template>
