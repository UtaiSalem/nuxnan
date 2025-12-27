<script setup>
import { Icon } from '@iconify/vue'
import { ref, computed, watch, nextTick } from 'vue'
import { useAuthStore } from '~/stores/auth'

const props = defineProps({
  show: {
    type: Boolean,
    default: false
  },
  images: {
    type: Array,
    default: () => []
  },
  initialIndex: {
    type: Number,
    default: 0
  },
  postId: {
    type: [Number, String],
    default: null
  }
})

const emit = defineEmits(['close', 'image-updated'])

const { $apiFetch } = useNuxtApp()
const authStore = useAuthStore()
const swal = useSweetAlert()

// Current image index
const currentIndex = ref(0)

// Current image data
const currentImage = computed(() => {
  return props.images[currentIndex.value] || null
})

// Local reactive state for current image
const localIsLiked = ref(false)
const localIsDisliked = ref(false)
const localLikes = ref(0)
const localDislikes = ref(0)
const localComments = ref(0)
const localViews = ref(0)

// Loading states
const isLiking = ref(false)
const isDisliking = ref(false)
const isLoadingComments = ref(false)
const isSubmittingComment = ref(false)

// Comments state
const showComments = ref(false)
const comments = ref([])
const newComment = ref('')
const hasMoreComments = ref(true)
const commentsPage = ref(1)

// Watch for show changes
watch(() => props.show, (newVal) => {
  if (newVal) {
    currentIndex.value = props.initialIndex
    loadImageData()
  } else {
    resetState()
  }
})

// Watch for current index changes
watch(currentIndex, () => {
  loadImageData()
})

// Load image data
const loadImageData = () => {
  if (!currentImage.value) return
  
  const img = currentImage.value
  localIsLiked.value = img.isLikedByAuth || false
  localIsDisliked.value = img.isDislikedByAuth || false
  localLikes.value = img.likes || 0
  localDislikes.value = img.dislikes || 0
  localComments.value = img.comments || 0
  localViews.value = img.views || 0
  
  // Load initial comments
  comments.value = img.image_comments || []
  commentsPage.value = 1
  hasMoreComments.value = localComments.value > comments.value.length
}

// Reset state
const resetState = () => {
  showComments.value = false
  comments.value = []
  newComment.value = ''
  commentsPage.value = 1
}

// Close modal
const closeModal = () => {
  emit('close')
}

// Navigate images
const prevImage = () => {
  if (currentIndex.value > 0) {
    currentIndex.value--
  }
}

const nextImage = () => {
  if (currentIndex.value < props.images.length - 1) {
    currentIndex.value++
  }
}

// Keyboard navigation
const handleKeydown = (e) => {
  if (!props.show) return
  
  switch (e.key) {
    case 'Escape':
      closeModal()
      break
    case 'ArrowLeft':
      prevImage()
      break
    case 'ArrowRight':
      nextImage()
      break
  }
}

// Add keyboard listener
if (typeof window !== 'undefined') {
  watch(() => props.show, (newVal) => {
    if (newVal) {
      window.addEventListener('keydown', handleKeydown)
    } else {
      window.removeEventListener('keydown', handleKeydown)
    }
  })
}

// Toggle Like
const toggleLike = async () => {
  if (!currentImage.value?.id || isLiking.value) return
  
  isLiking.value = true
  const wasLiked = localIsLiked.value
  const wasDisliked = localIsDisliked.value
  
  // Optimistic update
  if (wasLiked) {
    localIsLiked.value = false
    localLikes.value--
  } else {
    localIsLiked.value = true
    localLikes.value++
    if (wasDisliked) {
      localIsDisliked.value = false
      localDislikes.value--
    }
  }
  
  try {
    const response = await $apiFetch(`/api/post_images/${currentImage.value.id}/like`, {
      method: 'POST'
    })
    
    if (!response.success) {
      // Revert on failure
      localIsLiked.value = wasLiked
      localLikes.value = wasLiked ? localLikes.value + 1 : localLikes.value - 1
      if (wasDisliked) {
        localIsDisliked.value = true
        localDislikes.value++
      }
      swal.error(response.message || 'ไม่สามารถกดถูกใจได้')
    } else {
      // Update auth user points
      if (authStore.user) {
        authStore.user.pp = response.user_pp ?? authStore.user.pp
      }
    }
  } catch (error) {
    console.error('Error liking image:', error)
    // Revert
    localIsLiked.value = wasLiked
    localLikes.value = wasLiked ? localLikes.value + 1 : localLikes.value - 1
    if (wasDisliked) {
      localIsDisliked.value = true
      localDislikes.value++
    }
    swal.error('เกิดข้อผิดพลาด')
  } finally {
    isLiking.value = false
  }
}

// Toggle Dislike
const toggleDislike = async () => {
  if (!currentImage.value?.id || isDisliking.value) return
  
  isDisliking.value = true
  const wasLiked = localIsLiked.value
  const wasDisliked = localIsDisliked.value
  
  // Optimistic update
  if (wasDisliked) {
    localIsDisliked.value = false
    localDislikes.value--
  } else {
    localIsDisliked.value = true
    localDislikes.value++
    if (wasLiked) {
      localIsLiked.value = false
      localLikes.value--
    }
  }
  
  try {
    const response = await $apiFetch(`/api/post_images/${currentImage.value.id}/dislike`, {
      method: 'POST'
    })
    
    if (!response.success) {
      // Revert on failure
      localIsDisliked.value = wasDisliked
      localDislikes.value = wasDisliked ? localDislikes.value + 1 : localDislikes.value - 1
      if (wasLiked) {
        localIsLiked.value = true
        localLikes.value++
      }
      swal.error(response.message || 'ไม่สามารถกดไม่ถูกใจได้')
    }
  } catch (error) {
    console.error('Error disliking image:', error)
    // Revert
    localIsDisliked.value = wasDisliked
    localDislikes.value = wasDisliked ? localDislikes.value + 1 : localDislikes.value - 1
    if (wasLiked) {
      localIsLiked.value = true
      localLikes.value++
    }
    swal.error('เกิดข้อผิดพลาด')
  } finally {
    isDisliking.value = false
  }
}

// Toggle Comments Panel
const toggleComments = () => {
  showComments.value = !showComments.value
  if (showComments.value && comments.value.length === 0) {
    loadComments()
  }
}

// Load Comments
const loadComments = async () => {
  if (!currentImage.value?.id || isLoadingComments.value) return
  
  isLoadingComments.value = true
  
  try {
    const response = await $apiFetch(`/api/postimage/${currentImage.value.id}/comments?page=${commentsPage.value}`)
    
    if (response.success || response.data) {
      const newComments = response.data || response.comments || []
      if (commentsPage.value === 1) {
        comments.value = newComments
      } else {
        comments.value = [...comments.value, ...newComments]
      }
      hasMoreComments.value = newComments.length >= 10
    }
  } catch (error) {
    console.error('Error loading comments:', error)
  } finally {
    isLoadingComments.value = false
  }
}

// Load More Comments
const loadMoreComments = () => {
  commentsPage.value++
  loadComments()
}

// Submit Comment
const submitComment = async () => {
  if (!newComment.value.trim() || !currentImage.value?.id || isSubmittingComment.value) return
  
  isSubmittingComment.value = true
  
  try {
    const response = await $apiFetch(`/api/postimage/${currentImage.value.id}/comments`, {
      method: 'POST',
      body: {
        content: newComment.value
      }
    })
    
    if (response.success) {
      // Add new comment to top
      const newCommentData = response.comment || response.data
      if (newCommentData) {
        comments.value.unshift(newCommentData)
        localComments.value++
      }
      newComment.value = ''
      swal.toast('แสดงความคิดเห็นสำเร็จ', 'success')
    } else {
      swal.error(response.message || 'ไม่สามารถแสดงความคิดเห็นได้')
    }
  } catch (error) {
    console.error('Error submitting comment:', error)
    swal.error('เกิดข้อผิดพลาด')
  } finally {
    isSubmittingComment.value = false
  }
}

// Delete Comment
const deleteComment = async (commentId) => {
  const confirmed = await swal.confirmDelete('ความคิดเห็นนี้')
  if (!confirmed) return
  
  try {
    const response = await $apiFetch(`/api/post_image_comments/${commentId}`, {
      method: 'DELETE'
    })
    
    if (response.success) {
      comments.value = comments.value.filter(c => c.id !== commentId)
      localComments.value--
      swal.toast('ลบความคิดเห็นสำเร็จ', 'success')
    } else {
      swal.error(response.message || 'ไม่สามารถลบความคิดเห็นได้')
    }
  } catch (error) {
    console.error('Error deleting comment:', error)
    swal.error('เกิดข้อผิดพลาด')
  }
}

// Like Comment
const likeComment = async (comment) => {
  try {
    const response = await $apiFetch(`/api/post_image_comments/${comment.id}/like`, {
      method: 'POST'
    })
    
    if (response.success) {
      comment.isLikedByAuth = !comment.isLikedByAuth
      if (comment.isLikedByAuth) {
        comment.likes = (comment.likes || 0) + 1
        if (comment.isDislikedByAuth) {
          comment.isDislikedByAuth = false
          comment.dislikes = Math.max(0, (comment.dislikes || 0) - 1)
        }
      } else {
        comment.likes = Math.max(0, (comment.likes || 0) - 1)
      }
    }
  } catch (error) {
    console.error('Error liking comment:', error)
  }
}

// Format time
const formatTime = (dateString) => {
  if (!dateString) return ''
  const date = new Date(dateString)
  const now = new Date()
  const diffMs = now - date
  const diffMins = Math.floor(diffMs / 60000)
  const diffHours = Math.floor(diffMs / 3600000)
  const diffDays = Math.floor(diffMs / 86400000)
  
  if (diffMins < 1) return 'เมื่อสักครู่'
  if (diffMins < 60) return `${diffMins} นาทีที่แล้ว`
  if (diffHours < 24) return `${diffHours} ชั่วโมงที่แล้ว`
  if (diffDays < 7) return `${diffDays} วันที่แล้ว`
  return date.toLocaleDateString('th-TH')
}
</script>

<template>
  <Teleport to="body">
    <Transition name="fade">
      <div 
        v-if="show && currentImage" 
        class="fixed inset-0 z-[100] bg-black/95 flex"
        @click.self="closeModal"
      >
        <!-- Close Button -->
        <button 
          class="absolute top-4 right-4 z-10 p-2 bg-black/50 hover:bg-black/70 rounded-full text-white/80 hover:text-white transition-all"
          @click="closeModal"
        >
          <Icon icon="fluent:dismiss-24-regular" class="w-6 h-6" />
        </button>
        
        <!-- Image Counter -->
        <div class="absolute top-4 left-4 z-10 px-3 py-1.5 bg-black/50 rounded-full text-white text-sm">
          {{ currentIndex + 1 }} / {{ images.length }}
        </div>
        
        <!-- Main Content Area -->
        <div class="flex flex-1 h-full">
          <!-- Image Section -->
          <div class="flex-1 flex items-center justify-center relative" :class="showComments ? 'w-2/3' : 'w-full'">
            <!-- Navigation: Previous -->
            <button 
              v-if="currentIndex > 0"
              class="absolute left-4 z-10 p-3 bg-black/50 hover:bg-black/70 rounded-full text-white/80 hover:text-white transition-all"
              @click.stop="prevImage"
            >
              <Icon icon="fluent:chevron-left-24-regular" class="w-8 h-8" />
            </button>
            
            <!-- Image -->
            <img 
              :src="currentImage.url || currentImage.full_url" 
              class="max-w-full max-h-full object-contain"
              @click.stop
            />
            
            <!-- Navigation: Next -->
            <button 
              v-if="currentIndex < images.length - 1"
              class="absolute right-4 z-10 p-3 bg-black/50 hover:bg-black/70 rounded-full text-white/80 hover:text-white transition-all"
              @click.stop="nextImage"
            >
              <Icon icon="fluent:chevron-right-24-regular" class="w-8 h-8" />
            </button>
            
            <!-- Bottom Action Bar -->
            <div class="absolute bottom-0 left-0 right-0 p-4 bg-gradient-to-t from-black/80 to-transparent">
              <div class="flex items-center justify-between">
                <!-- Stats -->
                <div class="flex items-center gap-4 text-white/80 text-sm">
                  <span v-if="localViews > 0" class="flex items-center gap-1.5">
                    <Icon icon="fluent:eye-24-regular" class="w-4 h-4" />
                    {{ localViews.toLocaleString() }}
                  </span>
                </div>
                
                <!-- Actions -->
                <div class="flex items-center gap-2">
                  <!-- Like -->
                  <button 
                    @click.stop="toggleLike"
                    :disabled="isLiking"
                    :class="[
                      'flex items-center gap-2 px-4 py-2 rounded-full transition-all',
                      localIsLiked 
                        ? 'bg-vikinger-purple text-white' 
                        : 'bg-white/10 hover:bg-white/20 text-white'
                    ]"
                  >
                    <Icon :icon="localIsLiked ? 'fluent:thumb-like-24-filled' : 'fluent:thumb-like-24-regular'" class="w-5 h-5" />
                    <span class="text-sm font-medium">{{ localLikes || 'ถูกใจ' }}</span>
                  </button>
                  
                  <!-- Dislike -->
                  <button 
                    @click.stop="toggleDislike"
                    :disabled="isDisliking"
                    :class="[
                      'flex items-center gap-2 px-4 py-2 rounded-full transition-all',
                      localIsDisliked 
                        ? 'bg-red-500 text-white' 
                        : 'bg-white/10 hover:bg-white/20 text-white'
                    ]"
                  >
                    <Icon :icon="localIsDisliked ? 'fluent:thumb-dislike-24-filled' : 'fluent:thumb-dislike-24-regular'" class="w-5 h-5" />
                    <span class="text-sm font-medium">{{ localDislikes || '' }}</span>
                  </button>
                  
                  <!-- Comment Toggle -->
                  <button 
                    @click.stop="toggleComments"
                    :class="[
                      'flex items-center gap-2 px-4 py-2 rounded-full transition-all',
                      showComments 
                        ? 'bg-vikinger-cyan text-white' 
                        : 'bg-white/10 hover:bg-white/20 text-white'
                    ]"
                  >
                    <Icon icon="fluent:comment-24-regular" class="w-5 h-5" />
                    <span class="text-sm font-medium">{{ localComments || 'ความคิดเห็น' }}</span>
                  </button>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Comments Sidebar -->
          <Transition name="slide-left">
            <div 
              v-if="showComments" 
              class="w-96 bg-white dark:bg-vikinger-dark-300 h-full flex flex-col border-l border-gray-200 dark:border-vikinger-dark-50/30"
              @click.stop
            >
              <!-- Header -->
              <div class="flex items-center justify-between p-4 border-b border-gray-200 dark:border-vikinger-dark-50/30">
                <h3 class="font-bold text-gray-800 dark:text-white">ความคิดเห็น</h3>
                <button 
                  @click="showComments = false"
                  class="p-1 hover:bg-gray-100 dark:hover:bg-vikinger-dark-200 rounded-full"
                >
                  <Icon icon="fluent:dismiss-24-regular" class="w-5 h-5 text-gray-500" />
                </button>
              </div>
              
              <!-- Comments List -->
              <div class="flex-1 overflow-y-auto p-4 space-y-4">
                <div v-if="isLoadingComments && comments.length === 0" class="flex justify-center py-8">
                  <Icon icon="fluent:spinner-ios-20-regular" class="w-6 h-6 animate-spin text-vikinger-purple" />
                </div>
                
                <div v-else-if="comments.length === 0" class="text-center py-8 text-gray-500 dark:text-gray-400">
                  <Icon icon="fluent:comment-24-regular" class="w-12 h-12 mx-auto mb-2 opacity-50" />
                  <p>ยังไม่มีความคิดเห็น</p>
                  <p class="text-sm">เป็นคนแรกที่แสดงความคิดเห็น!</p>
                </div>
                
                <template v-else>
                  <div 
                    v-for="comment in comments" 
                    :key="comment.id"
                    class="flex gap-3"
                  >
                    <img 
                      :src="comment.user?.avatar || 'https://i.pravatar.cc/40'" 
                      class="w-8 h-8 rounded-full object-cover flex-shrink-0"
                    />
                    <div class="flex-1 min-w-0">
                      <div class="bg-gray-100 dark:bg-vikinger-dark-200 rounded-xl px-3 py-2">
                        <div class="font-medium text-sm text-gray-800 dark:text-white">
                          {{ comment.user?.name || comment.user?.username || 'ผู้ใช้' }}
                        </div>
                        <p class="text-sm text-gray-700 dark:text-gray-300 break-words">
                          {{ comment.content }}
                        </p>
                      </div>
                      <div class="flex items-center gap-3 mt-1 px-1 text-xs text-gray-500">
                        <span>{{ formatTime(comment.created_at) }}</span>
                        <button 
                          @click="likeComment(comment)"
                          :class="comment.isLikedByAuth ? 'text-vikinger-purple font-medium' : 'hover:text-vikinger-purple'"
                        >
                          ถูกใจ {{ comment.likes > 0 ? `(${comment.likes})` : '' }}
                        </button>
                        <button 
                          v-if="comment.user?.id === authStore.user?.id"
                          @click="deleteComment(comment.id)"
                          class="text-red-500 hover:text-red-600"
                        >
                          ลบ
                        </button>
                      </div>
                    </div>
                  </div>
                  
                  <!-- Load More -->
                  <button 
                    v-if="hasMoreComments && !isLoadingComments"
                    @click="loadMoreComments"
                    class="w-full py-2 text-sm text-vikinger-purple hover:text-vikinger-purple/80 transition-colors"
                  >
                    โหลดเพิ่มเติม...
                  </button>
                  
                  <div v-if="isLoadingComments" class="flex justify-center py-2">
                    <Icon icon="fluent:spinner-ios-20-regular" class="w-5 h-5 animate-spin text-vikinger-purple" />
                  </div>
                </template>
              </div>
              
              <!-- Comment Input -->
              <div class="p-4 border-t border-gray-200 dark:border-vikinger-dark-50/30">
                <div class="flex gap-2">
                  <img 
                    :src="authStore.user?.avatar || 'https://i.pravatar.cc/40'" 
                    class="w-8 h-8 rounded-full object-cover flex-shrink-0"
                  />
                  <div class="flex-1 relative">
                    <input 
                      v-model="newComment"
                      @keydown.enter="submitComment"
                      type="text"
                      placeholder="เขียนความคิดเห็น..."
                      class="w-full px-4 py-2 pr-10 bg-gray-100 dark:bg-vikinger-dark-200 rounded-full text-sm text-gray-800 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-vikinger-purple/50"
                      :disabled="isSubmittingComment"
                    />
                    <button 
                      @click="submitComment"
                      :disabled="!newComment.trim() || isSubmittingComment"
                      class="absolute right-2 top-1/2 -translate-y-1/2 p-1 text-vikinger-purple disabled:opacity-50"
                    >
                      <Icon v-if="isSubmittingComment" icon="fluent:spinner-ios-20-regular" class="w-5 h-5 animate-spin" />
                      <Icon v-else icon="fluent:send-24-filled" class="w-5 h-5" />
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </Transition>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
/* Fade Animation */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

/* Slide Left Animation for Comments Panel */
.slide-left-enter-active,
.slide-left-leave-active {
  transition: transform 0.3s ease, opacity 0.3s ease;
}

.slide-left-enter-from,
.slide-left-leave-to {
  transform: translateX(100%);
  opacity: 0;
}
</style>
