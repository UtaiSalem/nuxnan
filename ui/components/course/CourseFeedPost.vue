<script setup lang="ts">
/**
 * CourseFeedPost - Complete feed post component for course posts
 * Features: Like/Dislike, Comments, Replies, Share, Edit, Delete
 */
import { Icon } from '@iconify/vue'
import { ref, computed, watch } from 'vue'
import ShareModal from '~/components/share/ShareModal.vue'
import ImageLightbox from '~/components/feed/ImageLightbox.vue'

interface Props {
  post: any
  courseId: string | number
  currentUserId?: number
  isCourseAdmin?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  isCourseAdmin: false
})

const emit = defineEmits<{
  'edit': [post: any]
  'delete': [postId: number]
  'post-updated': [post: any]
}>()

const authStore = useAuthStore()
const api = useApi()
const swal = useSweetAlert()
const { getAvatarUrl } = useAvatar()

// User from auth store
const user = computed(() => authStore.user)

// State
const showMenu = ref(false)
const showComments = ref(true)
const showShareModal = ref(false)
const selectedImageIndex = ref<number | null>(null)

// Local reactive state for optimistic updates
const localIsLiked = ref(props.post?.isLikedByAuth || props.post?.is_liked || false)
const localIsDisliked = ref(props.post?.isDislikedByAuth || props.post?.is_disliked || false)
const localLikes = ref(props.post?.likes || props.post?.likes_count || 0)
const localDislikes = ref(props.post?.dislikes || props.post?.dislikes_count || 0)
const localCommentsCount = ref(props.post?.comments_count || 0)
const localShares = ref(props.post?.shares || props.post?.shares_count || 0)

// Loading states
const isLiking = ref(false)
const isDisliking = ref(false)
const isCommenting = ref(false)

// Comments state
const newComment = ref('')
const displayedComments = ref<any[]>(props.post?.post_comments || props.post?.comments || [])
const isLoadingComments = ref(false)
const currentPage = ref(1)
const hasMoreComments = ref(true)

// Reply state
const replyingTo = ref<any>(null)
const replyContent = ref('')
const isSubmittingReply = ref(false)
const expandedReplies = ref<Record<number, boolean>>({})
const commentReplies = ref<Record<number, any[]>>({})
const loadingReplies = ref<Record<number, boolean>>({})

// Check if current user is author
const isAuthor = computed(() => {
  const currentUser = user.value
  return props.post.user?.id === props.currentUserId || (currentUser && props.post.user?.id === currentUser.id)
})

// Post author info
const postAuthor = computed(() => props.post.author || props.post.user || {})

// Computed avatar for post author
const postAuthorAvatar = computed(() => getAvatarUrl(postAuthor.value))

// Computed avatar for current user
const currentUserAvatar = computed(() => getAvatarUrl(user.value))

// Images
const images = computed(() => {
  if (props.post.imagesResources?.length) return props.post.imagesResources
  if (props.post.images?.length) return props.post.images
  if (props.post.media?.length) return props.post.media
  return []
})

// Attachments
const attachments = computed(() => props.post.attachments || [])

// Poll data
const pollData = computed(() => props.post.poll || null)

// Format date
const formatDate = (date: string) => {
  if (!date) return ''
  
  // If already formatted (diff_humans_created_at)
  if (typeof date === 'string' && (date.includes('ที่แล้ว') || date.includes('เมื่อสักครู่'))) {
    return date
  }
  
  const postDate = new Date(date)
  const now = new Date()
  const diffMs = now.getTime() - postDate.getTime()
  const diffMins = Math.floor(diffMs / 60000)
  const diffHours = Math.floor(diffMs / 3600000)
  const diffDays = Math.floor(diffMs / 86400000)
  
  if (diffMins < 1) return 'เมื่อสักครู่'
  if (diffMins < 60) return `${diffMins} นาทีที่แล้ว`
  if (diffHours < 24) return `${diffHours} ชั่วโมงที่แล้ว`
  if (diffDays < 7) return `${diffDays} วันที่แล้ว`
  
  return postDate.toLocaleDateString('th-TH', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const createdTime = computed(() => {
  return props.post.diff_humans_created_at || formatDate(props.post.created_at)
})

// Watch for post changes
watch(() => props.post, (newPost) => {
  if (newPost) {
    localIsLiked.value = newPost.isLikedByAuth || newPost.is_liked || false
    localIsDisliked.value = newPost.isDislikedByAuth || newPost.is_disliked || false
    localLikes.value = newPost.likes || newPost.likes_count || 0
    localDislikes.value = newPost.dislikes || newPost.dislikes_count || 0
    localCommentsCount.value = newPost.comments_count || 0
    localShares.value = newPost.shares || newPost.shares_count || 0
    displayedComments.value = newPost.post_comments || newPost.comments || []
  }
}, { immediate: true })

// Handle Like
const handleLike = async () => {
  if (isLiking.value || !props.post?.id) return
  
  if (isAuthor.value) {
    swal.warning('คุณไม่สามารถกดถูกใจโพสต์ของตัวเองได้')
    return
  }
  
  isLiking.value = true
  const wasLiked = localIsLiked.value
  
  // Optimistic update
  localIsLiked.value = !wasLiked
  localLikes.value += wasLiked ? -1 : 1
  
  if (!wasLiked && localIsDisliked.value) {
    localIsDisliked.value = false
    localDislikes.value--
  }
  
  try {
    const response = await api.call(`/api/courses/${props.courseId}/posts/${props.post.id}/like`, {
      method: 'POST'
    })
    
    if (!response.success) {
      // Revert
      localIsLiked.value = wasLiked
      localLikes.value += wasLiked ? 1 : -1
      swal.error(response.message || 'ไม่สามารถกดถูกใจได้')
    }
  } catch (error) {
    // Revert
    localIsLiked.value = wasLiked
    localLikes.value += wasLiked ? 1 : -1
    console.error('Failed to like:', error)
  } finally {
    isLiking.value = false
  }
}

// Handle Dislike
const handleDislike = async () => {
  if (isDisliking.value || !props.post?.id) return
  
  if (isAuthor.value) {
    swal.warning('คุณไม่สามารถกดไม่ถูกใจโพสต์ของตัวเองได้')
    return
  }
  
  isDisliking.value = true
  const wasDisliked = localIsDisliked.value
  
  // Optimistic update
  localIsDisliked.value = !wasDisliked
  localDislikes.value += wasDisliked ? -1 : 1
  
  if (!wasDisliked && localIsLiked.value) {
    localIsLiked.value = false
    localLikes.value--
  }
  
  try {
    const response = await api.call(`/api/courses/${props.courseId}/posts/${props.post.id}/dislike`, {
      method: 'POST'
    })
    
    if (!response.success) {
      // Revert
      localIsDisliked.value = wasDisliked
      localDislikes.value += wasDisliked ? 1 : -1
      swal.error(response.message || 'ไม่สามารถกดไม่ถูกใจได้')
    }
  } catch (error) {
    // Revert
    localIsDisliked.value = wasDisliked
    localDislikes.value += wasDisliked ? 1 : -1
    console.error('Failed to dislike:', error)
  } finally {
    isDisliking.value = false
  }
}

// Add Comment
const addComment = async () => {
  if (!newComment.value.trim() || isCommenting.value) return
  
  isCommenting.value = true
  try {
    const response = await api.call(`/api/courses/${props.courseId}/posts/${props.post.id}/comments`, {
      method: 'POST',
      body: { content: newComment.value }
    })
    
    if (response.success && response.comment) {
      displayedComments.value.unshift(response.comment)
      localCommentsCount.value++
      newComment.value = ''
    } else {
      swal.error(response.message || 'ไม่สามารถเพิ่มความคิดเห็นได้')
    }
  } catch (error) {
    console.error('Failed to add comment:', error)
    swal.error('เกิดข้อผิดพลาดในการเพิ่มความคิดเห็น')
  } finally {
    isCommenting.value = false
  }
}

// Load More Comments
const loadMoreComments = async () => {
  if (isLoadingComments.value || !hasMoreComments.value) return
  
  isLoadingComments.value = true
  try {
    const nextPage = currentPage.value + 1
    const response = await api.get(`/api/courses/${props.courseId}/posts/${props.post.id}/comments?page=${nextPage}`)
    
    if (response.comments?.length) {
      const existingIds = new Set(displayedComments.value.map(c => c.id))
      const newComments = response.comments.filter((c: any) => !existingIds.has(c.id))
      displayedComments.value.push(...newComments)
      currentPage.value = nextPage
    }
    
    if (response.pagination) {
      hasMoreComments.value = response.pagination.has_more
    } else if (!response.comments?.length) {
      hasMoreComments.value = false
    }
  } catch (error) {
    console.error('Failed to load comments:', error)
  } finally {
    isLoadingComments.value = false
  }
}

// Handle Comment Like
const handleCommentLike = async (comment: any) => {
  const commentAuthor = comment.user || comment.author
  if (user.value?.id === commentAuthor?.id) {
    swal.warning('คุณไม่สามารถกดถูกใจคอมเมนต์ของตัวเองได้')
    return
  }

  if (comment.isLiking) return
  comment.isLiking = true

  const wasLiked = comment.isLikedByAuth || comment.is_liked || false
  
  // Optimistic update
  comment.isLikedByAuth = !wasLiked
  comment.is_liked = !wasLiked
  comment.likes = (comment.likes || 0) + (wasLiked ? -1 : 1)

  if (!wasLiked && (comment.isDislikedByAuth || comment.is_disliked)) {
    comment.isDislikedByAuth = false
    comment.is_disliked = false
    comment.dislikes = (comment.dislikes || 0) - 1
  }

  try {
    const response = await api.call(`/api/courses/posts/comments/${comment.id}/like`, {
      method: 'POST'
    })

    if (!response.success) {
      // Revert
      comment.isLikedByAuth = wasLiked
      comment.is_liked = wasLiked
      comment.likes = (comment.likes || 0) + (wasLiked ? 1 : -1)
      swal.error(response.message || 'ไม่สามารถกดถูกใจได้')
    }
  } catch (error) {
    // Revert
    comment.isLikedByAuth = wasLiked
    comment.is_liked = wasLiked
    comment.likes = (comment.likes || 0) + (wasLiked ? 1 : -1)
    console.error('Failed to like comment:', error)
  } finally {
    comment.isLiking = false
  }
}

// Handle Comment Dislike
const handleCommentDislike = async (comment: any) => {
  const commentAuthor = comment.user || comment.author
  if (user.value?.id === commentAuthor?.id) {
    swal.warning('คุณไม่สามารถกดไม่ถูกใจคอมเมนต์ของตัวเองได้')
    return
  }

  if (comment.isDisliking) return
  comment.isDisliking = true

  const wasDisliked = comment.isDislikedByAuth || comment.is_disliked || false
  
  // Optimistic update
  comment.isDislikedByAuth = !wasDisliked
  comment.is_disliked = !wasDisliked
  comment.dislikes = (comment.dislikes || 0) + (wasDisliked ? -1 : 1)

  if (!wasDisliked && (comment.isLikedByAuth || comment.is_liked)) {
    comment.isLikedByAuth = false
    comment.is_liked = false
    comment.likes = (comment.likes || 0) - 1
  }

  try {
    const response = await api.call(`/api/courses/posts/comments/${comment.id}/dislike`, {
      method: 'POST'
    })

    if (!response.success) {
      // Revert
      comment.isDislikedByAuth = wasDisliked
      comment.is_disliked = wasDisliked
      comment.dislikes = (comment.dislikes || 0) + (wasDisliked ? 1 : -1)
      swal.error(response.message || 'ไม่สามารถกดไม่ถูกใจได้')
    }
  } catch (error) {
    // Revert
    comment.isDislikedByAuth = wasDisliked
    comment.is_disliked = wasDisliked
    comment.dislikes = (comment.dislikes || 0) + (wasDisliked ? 1 : -1)
    console.error('Failed to dislike comment:', error)
  } finally {
    comment.isDisliking = false
  }
}

// Start Reply
const startReply = (comment: any) => {
  replyingTo.value = comment
  replyContent.value = ''
}

// Cancel Reply
const cancelReply = () => {
  replyingTo.value = null
  replyContent.value = ''
}

// Submit Reply
const submitReply = async () => {
  if (!replyContent.value.trim() || !replyingTo.value || isSubmittingReply.value) return
  
  isSubmittingReply.value = true
  try {
    const response = await api.call(`/api/courses/posts/comments/${replyingTo.value.id}/replies`, {
      method: 'POST',
      body: { content: replyContent.value }
    })
    
    if (response.success && response.reply) {
      // Add reply to comment's replies
      if (!commentReplies.value[replyingTo.value.id]) {
        commentReplies.value[replyingTo.value.id] = []
      }
      commentReplies.value[replyingTo.value.id].unshift(response.reply)
      
      // Increment reply count
      if (replyingTo.value.replies_count !== undefined) {
        replyingTo.value.replies_count++
      }
      
      // Expand replies for this comment
      expandedReplies.value[replyingTo.value.id] = true
      
      cancelReply()
    } else {
      swal.error(response.message || 'ไม่สามารถตอบกลับได้')
    }
  } catch (error) {
    console.error('Failed to submit reply:', error)
    swal.error('เกิดข้อผิดพลาดในการตอบกลับ')
  } finally {
    isSubmittingReply.value = false
  }
}

// Load Replies
const loadReplies = async (commentId: number) => {
  if (loadingReplies.value[commentId]) return
  
  loadingReplies.value[commentId] = true
  try {
    const response = await api.get(`/api/courses/posts/comments/${commentId}/replies`)
    
    if (response.replies) {
      commentReplies.value[commentId] = response.replies
      expandedReplies.value[commentId] = true
    }
  } catch (error) {
    console.error('Failed to load replies:', error)
  } finally {
    loadingReplies.value[commentId] = false
  }
}

// Toggle Replies
const toggleReplies = (commentId: number) => {
  if (expandedReplies.value[commentId]) {
    expandedReplies.value[commentId] = false
  } else {
    if (!commentReplies.value[commentId]) {
      loadReplies(commentId)
    } else {
      expandedReplies.value[commentId] = true
    }
  }
}

// Handle Reply Like
const handleReplyLike = async (reply: any, commentId: number) => {
  const replyAuthor = reply.user || reply.author
  if (user.value?.id === replyAuthor?.id) {
    swal.warning('คุณไม่สามารถกดถูกใจการตอบกลับของตัวเองได้')
    return
  }

  if (reply.isLiking) return
  reply.isLiking = true

  const wasLiked = reply.isLikedByAuth || reply.is_liked || false
  
  // Optimistic update
  reply.isLikedByAuth = !wasLiked
  reply.is_liked = !wasLiked
  reply.likes = (reply.likes || 0) + (wasLiked ? -1 : 1)

  try {
    const response = await api.call(`/api/courses/posts/comments/replies/${reply.id}/like`, {
      method: 'POST'
    })

    if (!response.success) {
      // Revert
      reply.isLikedByAuth = wasLiked
      reply.is_liked = wasLiked
      reply.likes = (reply.likes || 0) + (wasLiked ? 1 : -1)
    }
  } catch (error) {
    // Revert
    reply.isLikedByAuth = wasLiked
    reply.is_liked = wasLiked
    reply.likes = (reply.likes || 0) + (wasLiked ? 1 : -1)
    console.error('Failed to like reply:', error)
  } finally {
    reply.isLiking = false
  }
}

// Handle Reply Dislike
const handleReplyDislike = async (reply: any, commentId: number) => {
  const replyAuthor = reply.user || reply.author
  if (user.value?.id === replyAuthor?.id) {
    swal.warning('คุณไม่สามารถกดไม่ถูกใจการตอบกลับของตัวเองได้')
    return
  }

  if (reply.isDisliking) return
  reply.isDisliking = true

  const wasDisliked = reply.isDislikedByAuth || reply.is_disliked || false
  
  // Optimistic update
  reply.isDislikedByAuth = !wasDisliked
  reply.is_disliked = !wasDisliked
  reply.dislikes = (reply.dislikes || 0) + (wasDisliked ? -1 : 1)

  try {
    const response = await api.call(`/api/courses/posts/comments/replies/${reply.id}/dislike`, {
      method: 'POST'
    })

    if (!response.success) {
      // Revert
      reply.isDislikedByAuth = wasDisliked
      reply.is_disliked = wasDisliked
      reply.dislikes = (reply.dislikes || 0) + (wasDisliked ? 1 : -1)
    }
  } catch (error) {
    // Revert
    reply.isDislikedByAuth = wasDisliked
    reply.is_disliked = wasDisliked
    reply.dislikes = (reply.dislikes || 0) + (wasDisliked ? 1 : -1)
    console.error('Failed to dislike reply:', error)
  } finally {
    reply.isDisliking = false
  }
}

// Delete Post
const deletePost = async () => {
  const confirmed = await swal.confirm('คุณต้องการลบโพสต์นี้หรือไม่?', 'ลบโพสต์')
  if (!confirmed) return
  
  try {
    const response = await api.call(`/api/courses/${props.courseId}/posts/${props.post.id}`, {
      method: 'DELETE'
    })
    
    if (response.success) {
      emit('delete', props.post.id)
      swal.toast('ลบโพสต์สำเร็จ', 'success')
    } else {
      swal.error(response.message || 'ไม่สามารถลบโพสต์ได้')
    }
  } catch (error) {
    console.error('Failed to delete post:', error)
    swal.error('เกิดข้อผิดพลาดในการลบโพสต์')
  }
}

// Delete Comment
const deleteComment = async (comment: any) => {
  const confirmed = await swal.confirm('คุณต้องการลบความคิดเห็นนี้หรือไม่?', 'ลบความคิดเห็น')
  if (!confirmed) return
  
  try {
    const response = await api.call(`/api/courses/posts/comments/${comment.id}`, {
      method: 'DELETE'
    })
    
    if (response.success) {
      displayedComments.value = displayedComments.value.filter(c => c.id !== comment.id)
      localCommentsCount.value--
      swal.toast('ลบความคิดเห็นสำเร็จ', 'success')
    } else {
      swal.error(response.message || 'ไม่สามารถลบความคิดเห็นได้')
    }
  } catch (error) {
    console.error('Failed to delete comment:', error)
    swal.error('เกิดข้อผิดพลาดในการลบความคิดเห็น')
  }
}

// Share handlers
const openShareModal = () => {
  showShareModal.value = true
}

const handleShareSuccess = () => {
  localShares.value++
  showShareModal.value = false
}

// Open image lightbox
const openLightbox = (index: number) => {
  selectedImageIndex.value = index
}
</script>

<template>
  <div class="bg-white dark:bg-vikinger-dark-200 rounded-xl shadow-sm overflow-hidden">
    <!-- Header -->
    <div class="p-4 flex items-start gap-3">
      <NuxtLink :to="`/profile/${postAuthor.username}`">
        <img
          :src="postAuthorAvatar"
          :alt="postAuthor.name"
          class="w-10 h-10 rounded-full object-cover"
        />
      </NuxtLink>
      
      <div class="flex-1 min-w-0">
        <div class="flex items-center gap-2">
          <NuxtLink 
            :to="`/profile/${postAuthor.username}`"
            class="font-medium text-gray-900 dark:text-white hover:underline"
          >
            {{ postAuthor.name }}
          </NuxtLink>
          <span 
            v-if="postAuthor.role === 'teacher' || postAuthor.role === 'admin'"
            class="px-2 py-0.5 text-xs rounded-full bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400"
          >
            ผู้สอน
          </span>
        </div>
        <p class="text-sm text-gray-500 dark:text-gray-400">
          {{ createdTime }}
          <span v-if="post.is_edited" class="text-gray-400"> · แก้ไขแล้ว</span>
        </p>
      </div>
      
      <!-- Menu -->
      <div v-if="isAuthor || isCourseAdmin" class="relative">
        <button
          @click="showMenu = !showMenu"
          class="p-1 rounded-lg hover:bg-gray-100 dark:hover:bg-vikinger-dark-100 text-gray-400"
        >
          <Icon icon="fluent:more-horizontal-24-regular" class="w-5 h-5" />
        </button>
        
        <Transition name="fade">
          <div 
            v-if="showMenu"
            class="absolute right-0 top-full mt-1 w-36 bg-white dark:bg-vikinger-dark-300 rounded-lg shadow-lg border border-gray-200 dark:border-vikinger-dark-50/30 py-1 z-10"
          >
            <button
              v-if="isAuthor"
              @click="emit('edit', post); showMenu = false"
              class="w-full px-4 py-2 text-left text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-vikinger-dark-200 flex items-center gap-2"
            >
              <Icon icon="fluent:edit-24-regular" class="w-4 h-4" />
              แก้ไข
            </button>
            <button
              @click="deletePost(); showMenu = false"
              class="w-full px-4 py-2 text-left text-sm text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 flex items-center gap-2"
            >
              <Icon icon="fluent:delete-24-regular" class="w-4 h-4" />
              ลบ
            </button>
          </div>
        </Transition>
      </div>
    </div>
    
    <!-- Content -->
    <div class="px-4 pb-3">
      <div 
        v-if="post.content"
        class="prose dark:prose-invert prose-sm max-w-none whitespace-pre-wrap"
      >{{ post.content }}</div>
    </div>
    
    <!-- Images -->
    <div v-if="images.length > 0" class="relative">
      <!-- Single image -->
      <img
        v-if="images.length === 1"
        :src="images[0].url || images[0]"
        :alt="images[0].alt || 'Post image'"
        class="w-full max-h-96 object-cover cursor-pointer"
        @click="openLightbox(0)"
      />
      
      <!-- Multiple images grid -->
      <div 
        v-else
        class="grid gap-1"
        :class="[
          images.length === 2 ? 'grid-cols-2' : '',
          images.length === 3 ? 'grid-cols-3' : '',
          images.length >= 4 ? 'grid-cols-2' : ''
        ]"
      >
        <div 
          v-for="(image, index) in images.slice(0, 4)" 
          :key="index"
          class="relative aspect-square cursor-pointer"
          @click="openLightbox(index)"
        >
          <img
            :src="image.url || image"
            :alt="image.alt || `Image ${index + 1}`"
            class="w-full h-full object-cover"
          />
          <div 
            v-if="index === 3 && images.length > 4"
            class="absolute inset-0 bg-black/50 flex items-center justify-center"
          >
            <span class="text-2xl font-bold text-white">+{{ images.length - 4 }}</span>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Attachments -->
    <div v-if="attachments.length > 0" class="px-4 py-2">
      <div class="space-y-2">
        <a
          v-for="attachment in attachments"
          :key="attachment.id"
          :href="attachment.url"
          target="_blank"
          class="flex items-center gap-2 p-2 bg-gray-50 dark:bg-vikinger-dark-100 rounded-lg hover:bg-gray-100 dark:hover:bg-vikinger-dark-50 transition-colors"
        >
          <Icon icon="fluent:document-24-regular" class="w-5 h-5 text-blue-500" />
          <span class="flex-1 text-sm text-gray-700 dark:text-gray-300 truncate">
            {{ attachment.name || attachment.filename }}
          </span>
          <span class="text-xs text-gray-400">{{ attachment.size }}</span>
        </a>
      </div>
    </div>
    
    <!-- Poll -->
    <div v-if="pollData" class="px-4 py-3 bg-amber-50 dark:bg-amber-900/20 mx-4 mb-3 rounded-lg">
      <h4 class="font-medium text-gray-900 dark:text-white mb-2">{{ pollData.question }}</h4>
      <div class="space-y-2">
        <div 
          v-for="option in pollData.options" 
          :key="option.id"
          class="relative bg-white dark:bg-vikinger-dark-200 rounded-lg p-2"
        >
          <div 
            class="absolute left-0 top-0 h-full bg-amber-200 dark:bg-amber-700/30 rounded-lg transition-all"
            :style="{ width: `${option.percentage || 0}%` }"
          ></div>
          <div class="relative flex items-center justify-between">
            <span class="text-sm text-gray-700 dark:text-gray-300">{{ option.text }}</span>
            <span class="text-sm font-medium text-gray-600 dark:text-gray-400">
              {{ option.votes_count || 0 }} ({{ option.percentage || 0 }}%)
            </span>
          </div>
        </div>
      </div>
      <p class="text-xs text-gray-500 mt-2">
        {{ pollData.total_votes || 0 }} คนโหวต · 
        {{ pollData.is_ended ? 'สิ้นสุดแล้ว' : `เหลือ ${pollData.time_remaining || ''}` }}
      </p>
    </div>
    
    <!-- Stats -->
    <div class="px-4 py-2 flex items-center justify-between text-sm text-gray-500 dark:text-gray-400">
      <div class="flex items-center gap-3">
        <div v-if="localLikes > 0" class="flex items-center gap-1">
          <div class="w-5 h-5 rounded-full bg-blue-500 flex items-center justify-center">
            <Icon icon="fluent:thumb-like-24-filled" class="w-3 h-3 text-white" />
          </div>
          <span>{{ localLikes }}</span>
        </div>
        <div v-if="localDislikes > 0" class="flex items-center gap-1">
          <div class="w-5 h-5 rounded-full bg-gray-400 flex items-center justify-center">
            <Icon icon="fluent:thumb-dislike-24-filled" class="w-3 h-3 text-white" />
          </div>
          <span>{{ localDislikes }}</span>
        </div>
      </div>
      
      <div class="flex items-center gap-4">
        <button 
          v-if="localCommentsCount > 0"
          @click="showComments = !showComments"
          class="hover:underline"
        >
          {{ localCommentsCount }} ความคิดเห็น
        </button>
        <span v-if="localShares > 0">{{ localShares }} แชร์</span>
      </div>
    </div>
    
    <!-- Actions -->
    <div class="px-4 py-2 border-t border-gray-100 dark:border-vikinger-dark-50/30 flex items-center">
      <!-- Like -->
      <button
        @click="handleLike"
        :disabled="isLiking"
        :class="[
          'flex-1 flex items-center justify-center gap-2 py-2 rounded-lg transition-colors',
          localIsLiked 
            ? 'text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20' 
            : 'text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-vikinger-dark-100'
        ]"
      >
        <Icon 
          :icon="localIsLiked ? 'fluent:thumb-like-24-filled' : 'fluent:thumb-like-24-regular'" 
          class="w-5 h-5"
        />
        <span>ถูกใจ</span>
      </button>
      
      <!-- Dislike -->
      <button
        @click="handleDislike"
        :disabled="isDisliking"
        :class="[
          'flex-1 flex items-center justify-center gap-2 py-2 rounded-lg transition-colors',
          localIsDisliked 
            ? 'text-gray-700 dark:text-gray-200' 
            : 'text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-vikinger-dark-100'
        ]"
      >
        <Icon 
          :icon="localIsDisliked ? 'fluent:thumb-dislike-24-filled' : 'fluent:thumb-dislike-24-regular'" 
          class="w-5 h-5"
        />
        <span>ไม่ถูกใจ</span>
      </button>
      
      <!-- Comment -->
      <button
        @click="showComments = !showComments"
        class="flex-1 flex items-center justify-center gap-2 py-2 text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-vikinger-dark-100 rounded-lg transition-colors"
      >
        <Icon icon="fluent:chat-24-regular" class="w-5 h-5" />
        <span>ความคิดเห็น</span>
      </button>
      
      <!-- Share -->
      <button
        @click="openShareModal"
        class="flex-1 flex items-center justify-center gap-2 py-2 text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-vikinger-dark-100 rounded-lg transition-colors"
      >
        <Icon icon="fluent:share-24-regular" class="w-5 h-5" />
        <span>แชร์</span>
      </button>
    </div>
    
    <!-- Comments Section -->
    <Transition name="slide">
      <div v-if="showComments" class="border-t border-gray-100 dark:border-vikinger-dark-50/30">
        <!-- Comment Input -->
        <div class="p-4 flex gap-3">
          <img
            :src="currentUserAvatar"
            :alt="user?.name"
            class="w-8 h-8 rounded-full"
          />
          <div class="flex-1 flex gap-2">
            <input
              v-model="newComment"
              type="text"
              placeholder="เขียนความคิดเห็น..."
              class="flex-1 px-4 py-2 bg-gray-100 dark:bg-vikinger-dark-100 rounded-full text-sm text-gray-800 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500"
              @keyup.enter="addComment"
              :disabled="isCommenting"
            />
            <button
              @click="addComment"
              :disabled="!newComment.trim() || isCommenting"
              class="px-4 py-2 bg-blue-600 text-white rounded-full text-sm font-medium hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <Icon v-if="isCommenting" icon="fluent:spinner-ios-20-regular" class="w-4 h-4 animate-spin" />
              <span v-else>ส่ง</span>
            </button>
          </div>
        </div>
        
        <!-- Comments List -->
        <div v-if="displayedComments.length > 0" class="px-4 pb-4 space-y-4">
          <div 
            v-for="comment in displayedComments" 
            :key="comment.id"
            class="space-y-2"
          >
            <!-- Comment -->
            <div class="flex gap-2">
              <img
                :src="getAvatarUrl(comment.user || comment.author)"
                :alt="(comment.user || comment.author)?.name"
                class="w-8 h-8 rounded-full"
              />
              <div class="flex-1">
                <div class="bg-gray-100 dark:bg-vikinger-dark-100 rounded-xl px-3 py-2">
                  <div class="flex items-center gap-2">
                    <p class="font-medium text-sm text-gray-900 dark:text-white">
                      {{ (comment.user || comment.author)?.name }}
                    </p>
                    <span 
                      v-if="(comment.user || comment.author)?.role === 'teacher'"
                      class="px-1.5 py-0.5 text-xs rounded bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400"
                    >
                      ผู้สอน
                    </span>
                  </div>
                  <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap">
                    {{ comment.content }}
                  </p>
                </div>
                
                <!-- Comment Actions -->
                <div class="flex items-center gap-4 mt-1 ml-2 text-xs text-gray-500">
                  <button 
                    @click="handleCommentLike(comment)"
                    :class="[
                      'hover:text-blue-600',
                      (comment.isLikedByAuth || comment.is_liked) ? 'text-blue-600 font-medium' : ''
                    ]"
                  >
                    ถูกใจ{{ comment.likes > 0 ? ` (${comment.likes})` : '' }}
                  </button>
                  <button 
                    @click="handleCommentDislike(comment)"
                    :class="[
                      'hover:text-gray-700',
                      (comment.isDislikedByAuth || comment.is_disliked) ? 'text-gray-700 font-medium' : ''
                    ]"
                  >
                    ไม่ถูกใจ{{ comment.dislikes > 0 ? ` (${comment.dislikes})` : '' }}
                  </button>
                  <button @click="startReply(comment)" class="hover:text-blue-600">
                    ตอบกลับ
                  </button>
                  <span>{{ formatDate(comment.created_at) }}</span>
                  <button 
                    v-if="user?.id === (comment.user || comment.author)?.id || isCourseAdmin"
                    @click="deleteComment(comment)"
                    class="hover:text-red-600"
                  >
                    ลบ
                  </button>
                </div>
                
                <!-- Reply Input -->
                <div v-if="replyingTo?.id === comment.id" class="mt-2 flex gap-2">
                  <img
                    :src="currentUserAvatar"
                    :alt="user?.name"
                    class="w-6 h-6 rounded-full"
                  />
                  <div class="flex-1 flex gap-2">
                    <input
                      v-model="replyContent"
                      type="text"
                      :placeholder="`ตอบกลับ ${(comment.user || comment.author)?.name}...`"
                      class="flex-1 px-3 py-1.5 bg-gray-100 dark:bg-vikinger-dark-100 rounded-full text-sm text-gray-800 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500"
                      @keyup.enter="submitReply"
                      :disabled="isSubmittingReply"
                    />
                    <button
                      @click="submitReply"
                      :disabled="!replyContent.trim() || isSubmittingReply"
                      class="px-3 py-1.5 bg-blue-600 text-white rounded-full text-xs font-medium hover:bg-blue-700 disabled:opacity-50"
                    >
                      <Icon v-if="isSubmittingReply" icon="fluent:spinner-ios-20-regular" class="w-3 h-3 animate-spin" />
                      <span v-else>ส่ง</span>
                    </button>
                    <button
                      @click="cancelReply"
                      class="px-3 py-1.5 text-gray-500 hover:text-gray-700 text-xs"
                    >
                      ยกเลิก
                    </button>
                  </div>
                </div>
                
                <!-- View Replies Button -->
                <button
                  v-if="comment.replies_count > 0 || (commentReplies[comment.id]?.length > 0)"
                  @click="toggleReplies(comment.id)"
                  class="mt-2 ml-2 flex items-center gap-1 text-xs text-blue-600 hover:underline"
                >
                  <Icon 
                    :icon="expandedReplies[comment.id] ? 'fluent:chevron-up-24-regular' : 'fluent:chevron-down-24-regular'" 
                    class="w-4 h-4" 
                  />
                  {{ expandedReplies[comment.id] ? 'ซ่อน' : 'ดู' }} 
                  {{ comment.replies_count || commentReplies[comment.id]?.length || 0 }} การตอบกลับ
                </button>
                
                <!-- Loading Replies -->
                <div v-if="loadingReplies[comment.id]" class="mt-2 ml-8">
                  <Icon icon="fluent:spinner-ios-20-regular" class="w-4 h-4 animate-spin text-gray-400" />
                </div>
                
                <!-- Replies List -->
                <div 
                  v-if="expandedReplies[comment.id] && commentReplies[comment.id]?.length"
                  class="mt-2 ml-8 space-y-3"
                >
                  <div 
                    v-for="reply in commentReplies[comment.id]" 
                    :key="reply.id"
                    class="flex gap-2"
                  >
                    <img
                      :src="getAvatarUrl(reply.user || reply.author)"
                      :alt="(reply.user || reply.author)?.name"
                      class="w-6 h-6 rounded-full"
                    />
                    <div class="flex-1">
                      <div class="bg-gray-100 dark:bg-vikinger-dark-100 rounded-xl px-3 py-2">
                        <p class="font-medium text-xs text-gray-900 dark:text-white">
                          {{ (reply.user || reply.author)?.name }}
                        </p>
                        <p class="text-xs text-gray-700 dark:text-gray-300">
                          {{ reply.content }}
                        </p>
                      </div>
                      
                      <!-- Reply Actions -->
                      <div class="flex items-center gap-3 mt-1 ml-2 text-xs text-gray-500">
                        <button 
                          @click="handleReplyLike(reply, comment.id)"
                          :class="[
                            'hover:text-blue-600',
                            (reply.isLikedByAuth || reply.is_liked) ? 'text-blue-600 font-medium' : ''
                          ]"
                        >
                          ถูกใจ{{ reply.likes > 0 ? ` (${reply.likes})` : '' }}
                        </button>
                        <button 
                          @click="handleReplyDislike(reply, comment.id)"
                          :class="[
                            'hover:text-gray-700',
                            (reply.isDislikedByAuth || reply.is_disliked) ? 'text-gray-700 font-medium' : ''
                          ]"
                        >
                          ไม่ถูกใจ{{ reply.dislikes > 0 ? ` (${reply.dislikes})` : '' }}
                        </button>
                        <span>{{ formatDate(reply.created_at) }}</span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Load More Comments -->
          <button
            v-if="hasMoreComments && localCommentsCount > displayedComments.length"
            @click="loadMoreComments"
            :disabled="isLoadingComments"
            class="w-full py-2 text-sm text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg"
          >
            <Icon v-if="isLoadingComments" icon="fluent:spinner-ios-20-regular" class="w-4 h-4 animate-spin inline mr-2" />
            ดูความคิดเห็นเพิ่มเติม ({{ localCommentsCount - displayedComments.length }})
          </button>
        </div>
        
        <!-- Empty Comments -->
        <div v-else class="px-4 pb-4 text-center text-sm text-gray-500 dark:text-gray-400">
          ยังไม่มีความคิดเห็น เป็นคนแรกที่แสดงความคิดเห็น!
        </div>
      </div>
    </Transition>
    
    <!-- Share Modal -->
    <ShareModal
      v-if="showShareModal"
      :show="showShareModal"
      :shareable-type="'CoursePost'"
      :shareable-id="post.id"
      :post="post"
      @close="showShareModal = false"
      @shared="handleShareSuccess"
    />
    
    <!-- Image Lightbox -->
    <ImageLightbox
      v-if="selectedImageIndex !== null"
      :images="images"
      :initial-index="selectedImageIndex"
      @close="selectedImageIndex = null"
    />
  </div>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.15s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

.slide-enter-active,
.slide-leave-active {
  transition: all 0.2s ease;
}

.slide-enter-from,
.slide-leave-to {
  opacity: 0;
  max-height: 0;
}

.slide-enter-to,
.slide-leave-from {
  max-height: 2000px;
}
</style>
