<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { Icon } from '@iconify/vue'
import LessonAssignmentSection from './LessonAssignmentSection.vue'
import AssignmentFormModal from './AssignmentFormModal.vue'
import AssignmentGradingModal from './AssignmentGradingModal.vue'
// import QuizPreview from './QuizPreview.vue' // Replaced by LessonQuizSection
import LessonQuizSection from './LessonQuizSection.vue'
import QuestionFormModal from './QuestionFormModal.vue'

interface Props {
  lesson: any
  isAdmin?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  isAdmin: false
})

const emit = defineEmits<{
  like: []
  dislike: []
  bookmark: []
  share: []
  comment: [content: string]
  'add-comment': [content: string]
  complete: [lessonId: number]
}>()

// Avatar composable
const { getAvatarUrl } = useAvatar()
const authStore = useAuthStore()
const api = useApi()
const swal = useSweetAlert()

// Active tab
type TabType = 'reaction' | 'assignment' | 'quiz'
const activeTab = ref<TabType>('reaction')

// State for reactions
const isLiked = ref(props.lesson.is_liked_by_auth || false)
const isDisliked = ref(props.lesson.is_disliked_by_auth || false)
const isBookmarked = ref(props.lesson.is_bookmarked_by_auth || false)

// Progress state
const isCompleted = ref(props.lesson.progress?.status === 'completed' || false)
const isTogglingProgress = ref(false)

const likeCount = ref(props.lesson.like_count || 0)
const dislikeCount = ref(props.lesson.dislike_count || 0)
const bookmarkCount = ref(props.lesson.bookmarks_count || 0)
const commentCount = ref(props.lesson.comment_count || 0)
const shareCount = ref(props.lesson.share_count || 0)

// Comment form state
const newComment = ref('')
const isCommenting = ref(false)
const currentUserAvatar = computed(() => getAvatarUrl(authStore.user))

// Assignment Management
const showAssignmentModal = ref(false)
const showGradingModal = ref(false)
const editingAssignment = ref<any>(null)
const gradingAssignment = ref<any>(null)

const isCreator = computed(() => {
  return props.isAdmin || authStore.user?.id === props.lesson.creater?.id
})

const openAddAssignment = () => {
  editingAssignment.value = null
  showAssignmentModal.value = true
}

const openGradingModal = (assignment: any) => {
  gradingAssignment.value = assignment
  showGradingModal.value = true
}

const openEditAssignment = (assignment: any) => {
  editingAssignment.value = assignment
  showAssignmentModal.value = true
}

const deleteAssignment = async (assignment: any) => {
  const confirmed = await swal.confirm('ลบแบบฝึกหัด', `คุณแน่ใจหรือไม่ที่จะลบ "${assignment.title}"?`)
  if (!confirmed) return

  try {
    const response = await api.delete(`/api/lessons/${props.lesson.id}/assignments/${assignment.id}`) as any
    if (response) { 
      const index = props.lesson.assignments.findIndex((a: any) => a.id === assignment.id)
      if (index !== -1) {
        props.lesson.assignments.splice(index, 1)
        swal.toast('ลบแบบฝึกหัดสำเร็จ', 'success')
      }
    }
  } catch (error: any) {
    console.error('Failed to delete assignment:', error)
    swal.error(error?.data?.message || 'ไม่สามารถลบแบบฝึกหัดได้')
  }
}

const handleSubmitAnswer = async (assignmentId: number, answerData: { content: string, files: File[], deleted_images?: number[] }) => {
  try {
    const formData = new FormData()
    formData.append('content', answerData.content)
    // Add course_id if available, though backend might infer or it's optional
    if (props.lesson.course_id) {
       formData.append('course_id', props.lesson.course_id.toString())
    }
    
    if (answerData.files) {
      answerData.files.forEach((file) => {
        formData.append('images[]', file)
      })
    }

    if (answerData.deleted_images && answerData.deleted_images.length > 0) {
      answerData.deleted_images.forEach((id) => {
        formData.append('deleted_images[]', id.toString())
      })
    }
    
    const response = await api.post(`/api/assignments/${assignmentId}/answers`, formData) as any
    
    if (response.success) {
      const assignment = props.lesson.assignments.find((a: any) => a.id === assignmentId)
      if (assignment) {
        if (!assignment.answers) assignment.answers = []
        // Update local: since we return newAnswer, we should update the list
        // Filter out old answer by same user if specific logic needed, or just push/replace
        // Ideally backend handles one answer per user.
        // Let's assume response.newAnswer is the full answer object
        const existingIndex = assignment.answers.findIndex((a: any) => {
            const userId = authStore.user?.id
            return a.user_id === userId || a.user === userId || a.student?.id === userId
        })
        if (existingIndex !== -1) {
            assignment.answers[existingIndex] = response.newAnswer
        } else {
            assignment.answers.push(response.newAnswer)
        }
      }
      swal.toast('ส่งคำตอบเรียบร้อยแล้ว', 'success')
    }
  } catch (error: any) {
    console.error('Failed to submit answer:', error)
    swal.error(error?.data?.message || 'ไม่สามารถส่งคำตอบได้')
  }
}

// Quiz Management
const showQuestionModal = ref(false)
const editingQuestion = ref<any>(null)

const openCreateQuestion = () => {
  editingQuestion.value = null
  showQuestionModal.value = true
}

const openEditQuestion = (question: any) => {
  editingQuestion.value = question
  showQuestionModal.value = true
}

const handleQuestionSubmit = (question: any) => {
    if (!question) return;

    if (!props.lesson.questions) props.lesson.questions = []
    
    // Filter out potential undefined entries to prevent crashes
    props.lesson.questions = props.lesson.questions.filter((q: any) => q && q.id);

    if (editingQuestion.value) {
        // Update
        const index = props.lesson.questions.findIndex((q: any) => q.id === question.id)
        if (index !== -1) {
            props.lesson.questions[index] = question
        }
    } else {
        // Create
        props.lesson.questions.push(question)
    }
}

const updateQuestions = (newQuestions: any[]) => {
    props.lesson.questions = newQuestions
}

const handleAssignmentSubmit = (newAssignment: any) => {
  if (editingAssignment.value) {
    // Update existing
    const index = props.lesson.assignments.findIndex((a: any) => a.id === newAssignment.id)
    if (index !== -1) {
      props.lesson.assignments[index] = newAssignment
    }
  } else {
    // Add new
    if (!props.lesson.assignments) props.lesson.assignments = []
    props.lesson.assignments.push(newAssignment)
  }
}

// Computed
const hasAssignments = computed(() => props.lesson.assignments && props.lesson.assignments.length > 0)
const hasQuestions = computed(() => props.lesson.questions && props.lesson.questions.length > 0)
const assignmentCount = computed(() => props.lesson.assignments?.length || 0)
const questionCount = computed(() => props.lesson.questions?.length || 0)

// Comments data - use localComments for reactive updates
const getCommentAvatar = (comment: any) => getAvatarUrl(comment?.user || comment?.author)

// Tab configuration
const tabs = computed(() => [
  {
    id: 'reaction' as TabType,
    label: 'รีแอคชั่น',
    icon: 'fluent:emoji-24-regular',
    activeIcon: 'fluent:emoji-24-filled',
    count: likeCount.value + commentCount.value,
    color: 'blue',
  },
  {
    id: 'assignment' as TabType,
    label: 'แบบฝึกหัด',
    icon: 'fluent:clipboard-task-24-regular',
    activeIcon: 'fluent:clipboard-task-24-filled',
    count: assignmentCount.value,
    color: 'green',
  },
  {
    id: 'quiz' as TabType,
    label: 'แบบทดสอบ',
    icon: 'fluent:quiz-new-24-regular',
    activeIcon: 'fluent:quiz-new-24-filled',
    count: questionCount.value,
    color: 'orange',
  },
])

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

// Toggle lesson complete
const toggleProgress = async () => {
  if (isTogglingProgress.value) return
  isTogglingProgress.value = true
  
  try {
    const response = await api.post(`/api/lessons/${props.lesson.id}/progress/toggle`) as any
    if (response.success) {
      isCompleted.value = response.completed
      if (response.completed) {
        swal.toast('ทำเครื่องหมายว่าอ่านแล้ว ✓', 'success')
      } else {
        swal.toast('ยกเลิกเครื่องหมายอ่านแล้ว', 'info')
      }
      emit('complete', props.lesson.id)
    }
  } catch (error: any) {
    console.error('Failed to toggle progress:', error)
    swal.error(error?.data?.message || 'ไม่สามารถอัพเดทสถานะได้')
  } finally {
    isTogglingProgress.value = false
  }
}

// Fetch progress on mount
const fetchProgress = async () => {
  try {
    const response = await api.get(`/api/lessons/${props.lesson.id}/progress`) as any
    if (response.success && response.progress) {
      isCompleted.value = response.progress.is_completed
    }
  } catch (error) {
    console.error('Failed to fetch progress:', error)
  }
}

onMounted(() => {
  fetchProgress()
})

const handleComment = () => {
  emit('comment', '')
}

// Local comments list for optimistic updates
const localComments = ref<any[]>(props.lesson.comments || [])

// Pagination state
const nextPageUrl = ref<string | null>(null)
const isLoadingMore = ref(false)
const hasMoreComments = computed(() => localComments.value.length < commentCount.value)

// Reply system state
const replyingTo = ref<number | null>(null)
const replyContent = ref('')
const isSubmittingReply = ref(false)
const expandedReplies = ref<Record<number, boolean>>({})

// Check if user can delete a comment
const canDeleteComment = (comment: any) => {
  const userId = authStore.user?.id
  const commentOwnerId = comment.user?.id
  // Can delete if user is comment owner or course admin
  return userId === commentOwnerId || props.isAdmin || props.lesson.creater?.id === userId
}

// Delete comment
const deleteComment = async (comment: any, index: number) => {
  const confirmed = await swal.confirm('ลบความคิดเห็น', 'คุณแน่ใจหรือไม่ที่จะลบความคิดเห็นนี้?')
  if (!confirmed) return

  try {
    const response = await api.delete(`/api/lessons/${props.lesson.id}/comments/${comment.id}`) as { success?: boolean }
    if (response.success) {
      localComments.value.splice(index, 1)
      commentCount.value--
      swal.success('ความคิดเห็นถูกลบแล้ว', 'สำเร็จ')
    }
  } catch (error: any) {
    console.error('Failed to delete comment:', error)
    swal.error(error?.data?.message || 'ไม่สามารถลบความคิดเห็นได้')
  }
}

// Delete reply
const deleteReply = async (parentComment: any, reply: any, replyIndex: number) => {
  const confirmed = await swal.confirm('ลบการตอบกลับ', 'คุณแน่ใจหรือไม่ที่จะลบการตอบกลับนี้?')
  if (!confirmed) return

  try {
    const response = await api.delete(`/api/lessons/${props.lesson.id}/comments/${reply.id}`) as { success?: boolean }
    if (response.success) {
      parentComment.replies.splice(replyIndex, 1)
      swal.success('การตอบกลับถูกลบแล้ว', 'สำเร็จ')
    }
  } catch (error: any) {
    console.error('Failed to delete reply:', error)
    swal.error(error?.data?.message || 'ไม่สามารถลบการตอบกลับได้')
  }
}

// Load more comments
const loadMoreComments = async () => {
  if (isLoadingMore.value || !hasMoreComments.value) return
  
  isLoadingMore.value = true
  try {
    const page = Math.ceil(localComments.value.length / 10) + 1
    const response = await api.get(`/api/lessons/${props.lesson.id}/comments?page=${page}`) as { data?: any[] }
    
    if (response.data && response.data.length > 0) {
      // Filter out duplicates
      const existingIds = new Set(localComments.value.map(c => c.id))
      const newComments = response.data.filter(c => !existingIds.has(c.id))
      
      if (newComments.length > 0) {
        localComments.value.push(...newComments)
      } else {
        // No new comments, update count to match loaded
        commentCount.value = localComments.value.length
        swal.toast('ไม่มีความคิดเห็นเพิ่มเติมแล้ว', 'info')
      }
    } else {
      // No more comments available
      commentCount.value = localComments.value.length
      swal.toast('ไม่มีความคิดเห็นเพิ่มเติมแล้ว', 'info')
    }
  } catch (error: any) {
    console.error('Failed to load more comments:', error)
    swal.error('ไม่สามารถโหลดความคิดเห็นเพิ่มเติมได้')
  } finally {
    isLoadingMore.value = false
  }
}

const submitComment = async () => {
  if (!newComment.value.trim() || isCommenting.value) return
  
  isCommenting.value = true
  try {
    const response = await api.post(`/api/lessons/${props.lesson.id}/comments`, {
      content: newComment.value.trim(),
    }) as { success?: boolean; comment?: any; message?: string }

    if (response.success && response.comment) {
      // Add new comment to the top of the list
      localComments.value.unshift(response.comment)
      commentCount.value++
      newComment.value = ''
      emit('add-comment', response.comment)
    } else {
      swal.error(response.message || 'ไม่สามารถส่งความคิดเห็นได้')
    }
  } catch (error: any) {
    console.error('Failed to post comment:', error)
    swal.error(error?.data?.message || 'ไม่สามารถส่งความคิดเห็นได้ กรุณาลองใหม่อีกครั้ง')
  } finally {
    isCommenting.value = false
  }
}

// Handle comment like
const handleCommentLike = async (comment: any) => {
  if (comment.isLiking) return
  
  // Check if user is comment owner
  if (authStore.user?.id === comment.user?.id) {
    swal.warning('คุณไม่สามารถกดถูกใจคอมเมนต์ของตัวเองได้')
    return
  }
  
  comment.isLiking = true
  const wasLiked = comment.isLikedByAuth
  
  // Optimistic update
  comment.isLikedByAuth = !wasLiked
  comment.likes = (comment.likes || 0) + (wasLiked ? -1 : 1)
  if (!wasLiked && comment.isDislikedByAuth) {
    comment.isDislikedByAuth = false
    comment.dislikes = (comment.dislikes || 0) - 1
  }
  
  try {
    const response = await api.post(`/api/lessons/${props.lesson.id}/comments/${comment.id}/like`) as { success?: boolean }
    
    if (!response.success) {
      // Revert
      comment.isLikedByAuth = wasLiked
      comment.likes = (comment.likes || 0) + (wasLiked ? 1 : -1)
    }
  } catch (error: any) {
    // Revert
    comment.isLikedByAuth = wasLiked
    comment.likes = (comment.likes || 0) + (wasLiked ? 1 : -1)
    console.error('Failed to like comment:', error)
  } finally {
    comment.isLiking = false
  }
}

// Handle comment dislike
const handleCommentDislike = async (comment: any) => {
  if (comment.isDisliking) return
  
  // Check if user is comment owner
  if (authStore.user?.id === comment.user?.id) {
    swal.warning('คุณไม่สามารถกดไม่ถูกใจคอมเมนต์ของตัวเองได้')
    return
  }
  
  comment.isDisliking = true
  const wasDisliked = comment.isDislikedByAuth
  
  // Optimistic update
  comment.isDislikedByAuth = !wasDisliked
  comment.dislikes = (comment.dislikes || 0) + (wasDisliked ? -1 : 1)
  if (!wasDisliked && comment.isLikedByAuth) {
    comment.isLikedByAuth = false
    comment.likes = (comment.likes || 0) - 1
  }
  
  try {
    const response = await api.post(`/api/lessons/${props.lesson.id}/comments/${comment.id}/dislike`) as { success?: boolean }
    
    if (!response.success) {
      // Revert
      comment.isDislikedByAuth = wasDisliked
      comment.dislikes = (comment.dislikes || 0) + (wasDisliked ? 1 : -1)
    }
  } catch (error: any) {
    // Revert
    comment.isDislikedByAuth = wasDisliked
    comment.dislikes = (comment.dislikes || 0) + (wasDisliked ? 1 : -1)
    console.error('Failed to dislike comment:', error)
  } finally {
    comment.isDisliking = false
  }
}

// Toggle reply section (combines reply form + show replies)
const toggleReplySection = (commentId: number) => {
  if (replyingTo.value === commentId) {
    // Close everything
    replyingTo.value = null
    replyContent.value = ''
    expandedReplies.value[commentId] = false
  } else {
    // Open reply form and show replies
    replyingTo.value = commentId
    replyContent.value = ''
    expandedReplies.value[commentId] = true
  }
}

// Cancel reply (close form but keep replies visible)
const cancelReply = () => {
  replyingTo.value = null
  replyContent.value = ''
}

// Toggle replies visibility (used by chevron button inside expanded section)
const toggleReplies = (commentId: number) => {
  expandedReplies.value[commentId] = !expandedReplies.value[commentId]
}

// Submit reply
const submitReply = async (parentComment: any) => {
  if (!replyContent.value.trim() || isSubmittingReply.value) return
  
  isSubmittingReply.value = true
  try {
    const response = await api.post(`/api/lessons/${props.lesson.id}/comments`, {
      content: replyContent.value.trim(),
      parent_id: parentComment.id,
    }) as { success?: boolean; comment?: any; message?: string }

    if (response.success && response.comment) {
      // Add reply to parent comment's replies
      if (!parentComment.replies) {
        parentComment.replies = []
      }
      parentComment.replies.push(response.comment)
      
      // Expand replies to show new one
      expandedReplies.value[parentComment.id] = true
      
      // Reset
      replyingTo.value = null
      replyContent.value = ''
    } else {
      swal.error(response.message || 'ไม่สามารถตอบกลับได้')
    }
  } catch (error: any) {
    console.error('Failed to submit reply:', error)
    swal.error(error?.data?.message || 'ไม่สามารถตอบกลับได้ กรุณาลองใหม่อีกครั้ง')
  } finally {
    isSubmittingReply.value = false
  }
}
</script>

<template>
  <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
    <!-- Tab Navigation -->
    <div class="flex items-center gap-1 mb-4 bg-gray-100 dark:bg-gray-700/50 p-1 rounded-xl">
      <button
        v-for="tab in tabs"
        :key="tab.id"
        @click="activeTab = tab.id"
        class="flex-1 flex items-center justify-center gap-2 py-3 px-4 rounded-lg font-medium text-sm transition-all duration-200"
        :class="[
          activeTab === tab.id
            ? `bg-white dark:bg-gray-800 text-${tab.color}-600 dark:text-${tab.color}-400 shadow-md`
            : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200',
        ]"
      >
        <Icon
          :icon="activeTab === tab.id ? tab.activeIcon : tab.icon"
          class="w-5 h-5"
        />
        <span>{{ tab.label }}</span>
        <span
          v-if="tab.count > 0"
          class="px-2 py-0.5 text-xs font-bold rounded-full"
          :class="[
            activeTab === tab.id
              ? `bg-${tab.color}-100 dark:bg-${tab.color}-900/30 text-${tab.color}-600 dark:text-${tab.color}-400`
              : 'bg-gray-200 dark:bg-gray-600 text-gray-600 dark:text-gray-400',
          ]"
        >
          {{ tab.count }}
        </span>
      </button>
    </div>

    <!-- Tab Content -->
    <div class="min-h-[120px]">
      <!-- Reaction Tab -->
      <div v-show="activeTab === 'reaction'" class="space-y-4">
        <!-- Stats Row - matching FeedPost style -->
        <div class="flex items-center justify-between py-3 border-b border-gray-200 dark:border-gray-700 text-sm text-gray-500 dark:text-gray-400">
          <div class="flex items-center gap-4">
            <span 
              v-if="likeCount > 0 || dislikeCount > 0" 
              class="flex items-center gap-1.5 hover:text-vikinger-purple cursor-pointer transition-colors"
            >
              <Icon icon="fluent:thumb-like-24-filled" class="w-4 h-4 text-vikinger-purple" />
              <span class="font-medium">{{ likeCount }}</span>
            </span>
            <span 
              v-if="dislikeCount > 0" 
              class="flex items-center gap-1.5 hover:text-red-500 cursor-pointer transition-colors"
            >
              <Icon icon="fluent:thumb-dislike-24-filled" class="w-4 h-4 text-red-500" />
              <span class="font-medium">{{ dislikeCount }}</span>
            </span>
            <span 
              v-if="commentCount > 0"
              @click="handleComment"
              class="flex items-center gap-1.5 hover:text-vikinger-cyan cursor-pointer transition-colors"
            >
              <Icon icon="fluent:comment-24-regular" class="w-4 h-4" />
              <span class="font-medium">{{ commentCount }}</span>
            </span>
          </div>
          <div class="flex items-center gap-4">
            <span 
              v-if="shareCount > 0"
              class="flex items-center gap-1.5 hover:text-vikinger-green cursor-pointer transition-colors"
            >
              <Icon icon="fluent:share-24-regular" class="w-4 h-4" />
              <span class="font-medium">{{ shareCount }}</span>
            </span>
            <span 
              v-if="bookmarkCount > 0"
              class="flex items-center gap-1.5 hover:text-amber-500 cursor-pointer transition-colors"
            >
              <Icon icon="fluent:bookmark-24-filled" class="w-4 h-4 text-amber-500" />
              <span class="font-medium">{{ bookmarkCount }}</span>
            </span>
          </div>
        </div>

        <!-- Action Buttons - matching FeedPost style (flex layout) -->
        <div class="flex items-center gap-2">
          <!-- Like Button -->
          <button
            @click="handleLike"
            class="flex-1 flex items-center justify-center gap-2 py-2.5 rounded-lg transition-all duration-300"
            :class="isLiked 
              ? 'bg-vikinger-purple/10 text-vikinger-purple' 
              : 'hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-300'"
          >
            <Icon
              :icon="isLiked ? 'fluent:thumb-like-24-filled' : 'fluent:thumb-like-24-regular'"
              class="w-5 h-5 transition-transform hover:scale-110"
            />
            <span class="text-sm font-medium">{{ isLiked ? 'ถูกใจแล้ว' : 'ถูกใจ' }}</span>
          </button>

          <!-- Dislike Button -->
          <button
            @click="handleDislike"
            class="flex-1 flex items-center justify-center gap-2 py-2.5 rounded-lg transition-all duration-300"
            :class="isDisliked 
              ? 'bg-red-500/10 text-red-500' 
              : 'hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-300'"
          >
            <Icon
              :icon="isDisliked ? 'fluent:thumb-dislike-24-filled' : 'fluent:thumb-dislike-24-regular'"
              class="w-5 h-5 transition-transform hover:scale-110"
            />
            <span class="text-sm font-medium">{{ isDisliked ? 'ไม่ถูกใจแล้ว' : 'ไม่ถูกใจ' }}</span>
          </button>

          <!-- Comment Button -->
          <button
            @click="handleComment"
            class="flex-1 flex items-center justify-center gap-2 py-2.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors group"
          >
            <Icon
              icon="fluent:comment-24-regular"
              class="w-5 h-5 text-gray-600 dark:text-gray-300 group-hover:text-vikinger-cyan transition-colors"
            />
            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">ความคิดเห็น</span>
          </button>

          <!-- Share Button -->
          <button
            @click="handleShare"
            class="flex-1 flex items-center justify-center gap-2 py-2.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors group"
          >
            <Icon
              icon="fluent:share-24-regular"
              class="w-5 h-5 text-gray-600 dark:text-gray-300 group-hover:text-vikinger-green transition-colors"
            />
            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">แชร์</span>
          </button>

          <!-- Bookmark Button -->
          <button
            @click="handleBookmark"
            class="flex-1 flex items-center justify-center gap-2 py-2.5 rounded-lg transition-all duration-300"
            :class="isBookmarked 
              ? 'bg-amber-500/10 text-amber-500' 
              : 'hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-300'"
          >
            <Icon
              :icon="isBookmarked ? 'fluent:bookmark-24-filled' : 'fluent:bookmark-24-regular'"
              class="w-5 h-5 transition-transform hover:scale-110"
            />
            <span class="text-sm font-medium">{{ isBookmarked ? 'บันทึกแล้ว' : 'บันทึก' }}</span>
          </button>
        </div>

        <!-- Mark as Complete Button -->
        <div class="mt-4">
          <button
            @click="toggleProgress"
            :disabled="isTogglingProgress"
            class="w-full flex items-center justify-center gap-3 py-3 px-6 rounded-xl font-semibold transition-all duration-300 transform hover:scale-[1.02]"
            :class="isCompleted 
              ? 'bg-gradient-to-r from-green-500 to-emerald-600 text-white shadow-lg shadow-green-500/30' 
              : 'bg-gradient-to-r from-blue-500 to-blue-600 text-white shadow-lg shadow-blue-500/30 hover:from-blue-600 hover:to-blue-700'"
          >
            <Icon 
              v-if="isTogglingProgress"
              icon="eos-icons:bubble-loading" 
              class="w-6 h-6"
            />
            <template v-else>
              <Icon 
                :icon="isCompleted ? 'fluent:checkmark-circle-24-filled' : 'fluent:checkbox-unchecked-24-regular'" 
                class="w-6 h-6"
              />
              <span>{{ isCompleted ? '✓ อ่านแล้ว' : 'ทำเครื่องหมายว่าอ่านแล้ว' }}</span>
            </template>
          </button>
        </div>

        <!-- Comment Form Section -->
        <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
          <div class="flex gap-3">
            <img 
              :src="currentUserAvatar" 
              class="w-10 h-10 flex-shrink-0 rounded-full object-cover ring-2 ring-vikinger-purple/20" 
              alt="Your avatar" 
            />
            <div class="flex-1 flex gap-2">
              <input 
                v-model="newComment"
                type="text" 
                placeholder="เขียนความคิดเห็นเกี่ยวกับบทเรียนนี้..." 
                class="flex-1 px-4 py-2.5 rounded-full bg-gray-100 dark:bg-gray-700 border-none outline-none text-gray-800 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-vikinger-purple/30 transition-all"
                :disabled="isCommenting"
                @keydown.enter.prevent="submitComment"
              />
              <button 
                @click="submitComment" 
                :disabled="isCommenting || !newComment.trim()"
                class="p-2.5 rounded-full bg-gradient-to-r from-vikinger-purple to-vikinger-cyan text-white hover:shadow-lg transition-all duration-300 hover:scale-105 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                <Icon v-if="!isCommenting" icon="fluent:send-24-filled" class="w-5 h-5" />
                <Icon v-else icon="fluent:spinner-ios-20-regular" class="w-5 h-5 animate-spin" />
              </button>
            </div>
          </div>
        </div>

        <!-- Existing Comments List -->
        <div v-if="localComments.length > 0" class="mt-4 space-y-4">
          <div 
            v-for="comment in localComments" 
            :key="comment.id" 
            class="flex gap-3"
          >
            <img 
              :src="getCommentAvatar(comment)" 
              class="w-10 h-10 flex-shrink-0 aspect-square rounded-full object-cover" 
              :alt="comment.user?.username || comment.author?.username"
            />
            <div class="flex-1">
              <div class="bg-gray-100 dark:bg-gray-700 rounded-2xl p-3">
                <h6 class="font-semibold text-sm text-gray-800 dark:text-white">
                  {{ comment.user?.username || comment.author?.username || 'ผู้ใช้' }}
                </h6>
                <p class="text-sm text-gray-700 dark:text-gray-300 mt-1 whitespace-pre-wrap">
                  {{ comment.content }}
                </p>
              </div>
              
              <!-- Comment Stats -->
              <div v-if="comment.likes || comment.dislikes" class="flex items-center gap-3 mt-1 px-2 text-[11px] text-gray-500 dark:text-gray-400">
                <span v-if="comment.likes" class="flex items-center gap-1">
                  <Icon icon="fluent:thumb-like-16-filled" class="w-3 h-3 text-vikinger-purple" />
                  <span class="font-medium">{{ comment.likes }}</span>
                </span>
                <span v-if="comment.dislikes" class="flex items-center gap-1">
                  <Icon icon="fluent:thumb-dislike-16-filled" class="w-3 h-3 text-red-500" />
                  <span class="font-medium">{{ comment.dislikes }}</span>
                </span>
              </div>
              
              <!-- Comment Actions -->
              <div class="flex items-center gap-3 mt-1 text-xs text-gray-500 dark:text-gray-400 px-2">
                <span>{{ comment.create_at || comment.created_at_for_humans || 'เมื่อสักครู่' }}</span>
                <button 
                  @click="handleCommentLike(comment)"
                  :disabled="comment.isLiking || authStore.user?.id === comment.user?.id"
                  :class="[
                    'flex items-center gap-1 font-medium transition-colors px-1.5 py-0.5 rounded-md',
                    comment.isLikedByAuth ? 'text-vikinger-purple bg-vikinger-purple/10' : 'hover:text-vikinger-purple hover:bg-gray-100 dark:hover:bg-gray-600',
                    (authStore.user?.id === comment.user?.id) ? 'opacity-50 cursor-not-allowed' : ''
                  ]"
                >
                  <Icon :icon="comment.isLikedByAuth ? 'fluent:thumb-like-20-filled' : 'fluent:thumb-like-20-regular'" class="w-3.5 h-3.5" />
                  <span>{{ comment.isLikedByAuth ? 'ถูกใจแล้ว' : 'ถูกใจ' }}</span>
                </button>
                <button 
                  @click="handleCommentDislike(comment)"
                  :disabled="comment.isDisliking || authStore.user?.id === comment.user?.id"
                  :class="[
                    'flex items-center gap-1 font-medium transition-colors px-1.5 py-0.5 rounded-md',
                    comment.isDislikedByAuth ? 'text-red-500 bg-red-500/10' : 'hover:text-red-500 hover:bg-gray-100 dark:hover:bg-gray-600',
                    (authStore.user?.id === comment.user?.id) ? 'opacity-50 cursor-not-allowed' : ''
                  ]"
                >
                  <Icon :icon="comment.isDislikedByAuth ? 'fluent:thumb-dislike-20-filled' : 'fluent:thumb-dislike-20-regular'" class="w-3.5 h-3.5" />
                  <span>{{ comment.isDislikedByAuth ? 'ไม่ถูกใจ' : 'ไม่ถูกใจ' }}</span>
                </button>
                <button 
                  @click="toggleReplySection(comment.id)"
                  :class="[
                    'flex items-center gap-1 font-medium px-1.5 py-0.5 rounded-md transition-colors',
                    replyingTo === comment.id || expandedReplies[comment.id] 
                      ? 'text-vikinger-cyan bg-vikinger-cyan/10' 
                      : 'hover:text-vikinger-cyan hover:bg-gray-100 dark:hover:bg-gray-600'
                  ]"
                >
                  <Icon :icon="replyingTo === comment.id ? 'fluent:chevron-up-20-regular' : 'fluent:arrow-reply-20-regular'" class="w-3.5 h-3.5" />
                  <span v-if="comment.replies && comment.replies.length > 0">
                    {{ replyingTo === comment.id ? 'ซ่อน' : 'ตอบกลับ' }} ({{ comment.replies.length }})
                  </span>
                  <span v-else>ตอบกลับ</span>
                </button>
                <!-- Delete Comment Button (owner/admin only) -->
                <button 
                  v-if="canDeleteComment(comment)"
                  @click="deleteComment(comment, localComments.indexOf(comment))"
                  class="flex items-center gap-1 font-medium px-1.5 py-0.5 rounded-md transition-colors text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20"
                >
                  <Icon icon="fluent:delete-20-regular" class="w-3.5 h-3.5" />
                  <span>ลบ</span>
                </button>
              </div>
              
              <!-- Reply Form -->
              <div v-if="replyingTo === comment.id" class="mt-3 pl-4 border-l-2 border-vikinger-purple/30">
                <div class="flex gap-2">
                  <img 
                    :src="currentUserAvatar" 
                    class="w-8 h-8 flex-shrink-0 rounded-full object-cover" 
                    alt="Your avatar" 
                  />
                  <div class="flex-1 flex gap-2">
                    <input 
                      v-model="replyContent"
                      type="text" 
                      :placeholder="`ตอบกลับ ${comment.user?.username || 'ผู้ใช้'}...`" 
                      class="flex-1 px-3 py-2 rounded-full bg-gray-100 dark:bg-gray-700 border-none outline-none text-sm text-gray-800 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-vikinger-purple/30 transition-all"
                      :disabled="isSubmittingReply"
                      @keydown.enter.prevent="submitReply(comment)"
                    />
                    <button 
                      @click="submitReply(comment)" 
                      :disabled="isSubmittingReply || !replyContent.trim()"
                      class="p-2 rounded-full bg-vikinger-purple text-white hover:bg-vikinger-purple/90 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                    >
                      <Icon v-if="!isSubmittingReply" icon="fluent:send-24-filled" class="w-4 h-4" />
                      <Icon v-else icon="fluent:spinner-ios-20-regular" class="w-4 h-4 animate-spin" />
                    </button>
                    <button 
                      @click="cancelReply"
                      class="p-2 rounded-full hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-500 transition-colors"
                    >
                      <Icon icon="fluent:dismiss-24-regular" class="w-4 h-4" />
                    </button>
                  </div>
                </div>
              </div>
              
              <!-- Replies List -->
              <div v-if="expandedReplies[comment.id] && comment.replies && comment.replies.length > 0" class="mt-3 pl-4 border-l-2 border-gray-200 dark:border-gray-600 space-y-3">
                <div 
                  v-for="reply in comment.replies" 
                  :key="reply.id" 
                  class="flex gap-2"
                >
                  <img 
                    :src="getCommentAvatar(reply)" 
                    class="w-8 h-8 flex-shrink-0 aspect-square rounded-full object-cover" 
                    :alt="reply.user?.username"
                  />
                  <div class="flex-1">
                    <div class="bg-gray-50 dark:bg-gray-600 rounded-xl p-2.5">
                      <h6 class="font-semibold text-xs text-gray-800 dark:text-white">
                        {{ reply.user?.username || 'ผู้ใช้' }}
                      </h6>
                      <p class="text-xs text-gray-700 dark:text-gray-300 mt-0.5 whitespace-pre-wrap">
                        {{ reply.content }}
                      </p>
                    </div>
                    <!-- Reply Actions -->
                    <div class="flex items-center gap-2 mt-1 text-[10px] text-gray-500 dark:text-gray-400 px-1">
                      <span>{{ reply.create_at || 'เมื่อสักครู่' }}</span>
                      <button 
                        @click="handleCommentLike(reply)"
                        :disabled="reply.isLiking || authStore.user?.id === reply.user?.id"
                        :class="[
                          'flex items-center gap-0.5 font-medium transition-colors',
                          reply.isLikedByAuth ? 'text-vikinger-purple' : 'hover:text-vikinger-purple',
                          (authStore.user?.id === reply.user?.id) ? 'opacity-50 cursor-not-allowed' : ''
                        ]"
                      >
                        <Icon :icon="reply.isLikedByAuth ? 'fluent:thumb-like-16-filled' : 'fluent:thumb-like-16-regular'" class="w-3 h-3" />
                        <span v-if="reply.likes">{{ reply.likes }}</span>
                      </button>
                      <button 
                        @click="handleCommentDislike(reply)"
                        :disabled="reply.isDisliking || authStore.user?.id === reply.user?.id"
                        :class="[
                          'flex items-center gap-0.5 font-medium transition-colors',
                          reply.isDislikedByAuth ? 'text-red-500' : 'hover:text-red-500',
                          (authStore.user?.id === reply.user?.id) ? 'opacity-50 cursor-not-allowed' : ''
                        ]"
                      >
                        <Icon :icon="reply.isDislikedByAuth ? 'fluent:thumb-dislike-16-filled' : 'fluent:thumb-dislike-16-regular'" class="w-3 h-3" />
                        <span v-if="reply.dislikes">{{ reply.dislikes }}</span>
                      </button>
                      <!-- Delete Reply Button -->
                      <button 
                        v-if="canDeleteComment(reply)"
                        @click="deleteReply(comment, reply, comment.replies.indexOf(reply))"
                        class="flex items-center gap-0.5 font-medium transition-colors text-gray-400 hover:text-red-500"
                      >
                        <Icon icon="fluent:delete-16-regular" class="w-3 h-3" />
                        <span>ลบ</span>
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Load More Button -->
        <div v-if="hasMoreComments && localComments.length > 0" class="mt-4 text-center">
          <button 
            @click="loadMoreComments"
            :disabled="isLoadingMore"
            class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-colors disabled:opacity-50"
          >
            <Icon v-if="isLoadingMore" icon="eos-icons:bubble-loading" class="w-4 h-4" />
            <Icon v-else icon="fluent:chevron-down-20-regular" class="w-4 h-4" />
            {{ isLoadingMore ? 'กำลังโหลด...' : 'ดูความคิดเห็นเพิ่มเติม' }}
          </button>
        </div>

        <!-- No Comments Message -->
        <div v-else-if="localComments.length === 0" class="mt-4 text-center py-6 text-gray-500 dark:text-gray-400">
          <Icon icon="fluent:comment-24-regular" class="w-10 h-10 mx-auto mb-2 opacity-50" />
          <p class="text-sm">ยังไม่มีความคิดเห็น เป็นคนแรกที่แสดงความคิดเห็น!</p>
        </div>
      </div>

      <!-- Assignment Tab -->
      <div v-show="activeTab === 'assignment'">
        <!-- Admin Controls -->
        <div v-if="isCreator" class="mb-4 flex justify-end">
          <button
            @click="openAddAssignment"
            class="px-4 py-2 bg-green-500 hover:bg-green-600 text-white rounded-lg text-sm font-medium transition-colors flex items-center gap-2 shadow-sm"
          >
            <Icon icon="fluent:add-24-filled" class="w-4 h-4" />
            เพิ่มแบบฝึกหัด
          </button>
        </div>

        <LessonAssignmentSection
          v-if="lesson.assignments?.length"
          :assignments="lesson.assignments"
          :lesson-id="lesson.id"
          :course-id="lesson.course_id"
          :is-creator="isCreator"
          @submit="handleSubmitAnswer"
          @close="activeTab = 'reaction'"
          @edit="openEditAssignment"
          @delete="deleteAssignment"
          @view-submissions="openGradingModal"
        />
        
        <!-- No Assignments Message -->
        <div
          v-else
          class="flex flex-col items-center justify-center py-12 text-center"
        >
          <div class="w-20 h-20 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center mb-4">
            <Icon icon="fluent:clipboard-task-24-regular" class="w-10 h-10 text-green-500 dark:text-green-400" />
          </div>
          <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
            ยังไม่มีแบบฝึกหัด
          </h4>
          <p class="text-gray-500 dark:text-gray-400 max-w-sm">
            บทเรียนนี้ยังไม่มีแบบฝึกหัดให้ทำ {{ isCreator ? 'คุณสามารถเพิ่มแบบฝึกหัดได้โดยคลิกที่ปุ่มด้านบน' : 'กรุณารอผู้สอนเพิ่มแบบฝึกหัด' }}
          </p>
        </div>
      </div>

      <!-- Quiz Tab -->
    <div v-show="activeTab === 'quiz'">
      <LessonQuizSection
          :questions="lesson.questions || []"
          :lesson-id="lesson.id"
          :is-creator="isCreator"
          @create="openCreateQuestion"
          @edit="openEditQuestion"
          @update:questions="updateQuestions"
      />
    </div>
    </div>
  </div>

  <!-- Assignment Modal -->
  <AssignmentFormModal
    :show="showAssignmentModal"
    :lesson-id="lesson.id"
    :assignment="editingAssignment"
    @close="showAssignmentModal = false"
    @submit="handleAssignmentSubmit"
  />
  
  <!-- Grading Modal -->
  <AssignmentGradingModal
    :show="showGradingModal"
    :lesson-id="lesson.id"
    :assignment="gradingAssignment"
    @close="showGradingModal = false"
  />

  <QuestionFormModal
    :show="showQuestionModal"
    :lesson-id="lesson.id"
    :question="editingQuestion"
    @close="showQuestionModal = false"
    @submit="handleQuestionSubmit"
  />
</template>

<style scoped>
/* Dynamic color classes for Tailwind JIT */
.text-blue-600 { color: rgb(37 99 235); }
.text-green-600 { color: rgb(22 163 74); }
.text-orange-600 { color: rgb(234 88 12); }
.bg-blue-100 { background-color: rgb(219 234 254); }
.bg-green-100 { background-color: rgb(220 252 231); }
.bg-orange-100 { background-color: rgb(255 237 213); }
</style>
