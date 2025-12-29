<script setup>
import { Icon } from '@iconify/vue'
import { ref, computed, watch, nextTick } from 'vue'
import { useAuthStore } from '~/stores/auth'
import ShareModal from '~/components/share/ShareModal.vue'
import EditPostModal from '~/components/feed/EditPostModal.vue'
import ImageLightbox from '~/components/feed/ImageLightbox.vue'

const authStore = useAuthStore()
const toast = useToast()
const api = useApi()
const swal = useSweetAlert()

const props = defineProps({
  post: {
    type: Object,
    required: true
  },
  // For nested posts (shared posts)
  isNested: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['delete-success', 'post-updated'])

// Handle both direct post data and activity-wrapped data
const isActivity = computed(() => !!props.post.target_resource)

const postData = computed(() => {
  if (props.post.target_resource) {
    return props.post.target_resource
  }
  return props.post
})

// Activity context - what action was performed
const activityAction = computed(() => props.post.action || null)
const actionTo = computed(() => props.post.action_to || null)

// Get share comment from activity_details (if it's a share activity)
const shareComment = computed(() => {
  // For Share Activities, get from target_resource (Share object)
  if (isShareActivity.value && props.post.target_resource?.share_comment) {
    return props.post.target_resource.share_comment
  }
  
  // Try direct property first
  if (props.post.share_comment) {
    return props.post.share_comment
  }
  
  // If activity_details exists, parse and get share_comment
  if (props.post.activity_details) {
    try {
      const details = typeof props.post.activity_details === 'string' 
        ? JSON.parse(props.post.activity_details) 
        : props.post.activity_details
      return details.share_comment || null
    } catch (e) {
      console.error('Failed to parse activity_details:', e)
      return null
    }
  }
  
  return null
})

// Determine if this is a "different actor" activity (like share)
const isShareActivity = computed(() => {
  const shareActions = ['share_post', 'share', 'repost']
  return shareActions.includes(activityAction.value)
})

// Check if actor and post author are the same person
const isSameActor = computed(() => {
  if (!isActivity.value) return true
  
  const actorId = props.post.action_by?.id
  const authorId = postAuthor.value?.id
  
  // For shares, actors are different
  if (isShareActivity.value) return false
  
  // If we can't determine, assume same actor
  if (!actorId || !authorId) return true
  
  return actorId === authorId
})

// Get action display text in Thai
const actionText = computed(() => {
  if (!activityAction.value) return null
  
  const actionMap = {
    'create_post': 'สร้างโพสต์ใหม่',
    'share_post': 'แชร์โพสต์',
    'share': 'แชร์',
    'comment': 'แสดงความคิดเห็น',
    'like': 'ถูกใจ',
    'donate': 'บริจาค',
    'receive_donation': 'ได้รับบริจาค',
    'create': 'สร้าง',
    'update': 'อัปเดต',
    'join': 'เข้าร่วม',
    'enroll': 'ลงทะเบียนเรียน',
    'complete': 'เรียนจบ',
  }
  
  return actionMap[activityAction.value] || activityAction.value
})

// Short action text for inline display
const actionTextShort = computed(() => {
  if (!activityAction.value) return null
  
  const actionMap = {
    'create_post': 'โพสต์',
    'share_post': 'แชร์',
    'share': 'แชร์',
    'comment': 'แสดงความคิดเห็น',
    'like': 'ถูกใจ',
    'donate': 'บริจาค',
    'receive_donation': 'ได้รับบริจาค',
    'create': 'สร้าง',
    'update': 'อัปเดต',
    'join': 'เข้าร่วม',
    'enroll': 'ลงทะเบียน',
    'complete': 'เรียนจบ',
  }
  
  return actionMap[activityAction.value] || null
})

// Get action icon
const actionIcon = computed(() => {
  if (!activityAction.value) return null
  
  const iconMap = {
    'create_post': 'fluent:add-circle-24-regular',
    'share_post': 'fluent:share-24-regular',
    'share': 'fluent:share-24-regular',
    'comment': 'fluent:comment-24-regular',
    'like': 'fluent:thumb-like-24-regular',
    'donate': 'fluent:heart-24-regular',
    'receive_donation': 'fluent:gift-24-regular',
    'create': 'fluent:add-24-regular',
    'update': 'fluent:edit-24-regular',
    'join': 'fluent:people-add-24-regular',
    'enroll': 'fluent:book-add-24-regular',
    'complete': 'fluent:checkmark-circle-24-regular',
  }
  
  return iconMap[activityAction.value] || 'fluent:flash-24-regular'
})

// Get model type display in Thai
const modelTypeText = computed(() => {
  if (!actionTo.value) return null
  
  const modelMap = {
    'Post': '',  // No suffix needed for regular posts
    'CoursePost': 'ในรายวิชา',  // Changed from 'โพสต์ในรายวิชา' to avoid duplication
    'AcademyPost': 'ในสถาบัน',  // Changed from 'โพสต์ในสถาบัน' to avoid duplication
    'Donate': '',
    'DonateRecipient': '',
    'Poll': 'โพล',
    'Support': 'การสนับสนุน',
    'SupportViewer': '',
    'Course': 'รายวิชา',
    'Academy': 'สถาบัน',
  }
  
  return modelMap[actionTo.value] || ''
})

// Get context info (course, academy, etc.) with clickable links
const contextInfo = computed(() => {
  const data = postData.value
  
  // For CoursePost - show course and academy
  if (data.course) {
    return {
      type: 'course',
      icon: 'fluent:book-24-regular',
      name: data.course.name || data.course.title,
      link: `/courses/${data.course_id || data.course.id}`,
      academy: data.academy?.name || null,
      academyLink: data.academy ? `/academies/${data.academy.id}` : null,
      color: 'text-blue-500'
    }
  }
  
  // For Academy post
  if (data.academy && !data.course) {
    return {
      type: 'academy',
      icon: 'fluent:building-24-regular',
      name: data.academy.name,
      link: `/academies/${data.academy.id}`,
      color: 'text-purple-500'
    }
  }
  
  // For Donate
  if (actionTo.value === 'Donate') {
    return {
      type: 'donate',
      icon: 'fluent:heart-24-regular',
      name: 'บริจาคให้ ' + (data.user?.username || 'Nuxni'),
      link: data.user?.id ? `/profile/${data.user.id}` : null,
      amount: data.amounts,
      color: 'text-pink-500'
    }
  }
  
  // For DonateRecipient
  if (actionTo.value === 'DonateRecipient') {
    return {
      type: 'donate_recipient',
      icon: 'fluent:gift-24-regular',
      name: 'ได้รับบริจาคจาก ' + (data.donation?.donor_name || 'ไม่ประสงค์ออกนาม'),
      link: data.donation?.donor?.id ? `/profile/${data.donation.donor.id}` : null,
      amount: data.donation?.amounts,
      color: 'text-green-500'
    }
  }
  
  // For Poll
  if (actionTo.value === 'Poll' || data.poll) {
    const pollId = data.poll?.id || data.id
    return {
      type: 'poll',
      icon: 'fluent:poll-24-regular',
      name: data.poll?.title || 'โพล',
      link: pollId ? `/polls/${pollId}` : null,
      color: 'text-yellow-500'
    }
  }
  
  return null
})

// Get user/author data - handle different field names
// For Share Activities: get the original post author from shareable
// For regular posts: get author from post data
const postAuthor = computed(() => {
  // For Share Activity, get the author of the original shared content
  if (isShareActivity.value && props.post.target_resource?.shareable) {
    return props.post.target_resource.shareable.author || 
           props.post.target_resource.shareable.user || 
           {}
  }
  // For regular posts/activities
  return postData.value.author || postData.value.user || {}
})

// Action by (for activities, this is the person who performed the action)
const actionBy = computed(() => {
  return props.post.action_by || postAuthor.value
})

// Get created time - handle different field names
const createdTime = computed(() => {
  return postData.value.diff_humans_created_at || 
         props.post.diff_humans_created_at || 
         postData.value.createdAt || 
         props.post.createdAt || 
         'เมื่อสักครู่'
})

// Additional data fields
const hashtags = computed(() => postData.value.hashtags || [])
const location = computed(() => postData.value.location)
const privacySetting = computed(() => postData.value.privacy_settings || postData.value.privacy_setting || 'public')
const postType = computed(() => postData.value.post_type || 'text')
const isLiked = computed(() => postData.value.isLikedByAuth || false)
const isDisliked = computed(() => postData.value.isDislikedByAuth || false)
const likes = computed(() => postData.value.likes || 0)
const dislikes = computed(() => postData.value.dislikes || 0)
const views = computed(() => postData.value.views || 0)
const commentsCount = computed(() => postData.value.comments_count || 0)
const shares = computed(() => postData.value.shares || 0)

// Feeling/Activity data
const feeling = computed(() => postData.value.feeling || null)
const feelingIcon = computed(() => postData.value.feeling_icon || null)
const activityType = computed(() => postData.value.activity_type || null)
const activityText = computed(() => postData.value.activity_text || null)

// Feeling display text
const feelingDisplay = computed(() => {
  const parts = []
  if (feeling.value) {
    const icon = feelingIcon.value || '😊'
    parts.push(`${icon} รู้สึก${feeling.value}`)
  }
  if (activityType.value) {
    const text = activityText.value ? ` ${activityText.value}` : ''
    parts.push(`กำลัง${activityType.value}${text}`)
  }
  return parts.length > 0 ? parts.join(' — ') : null
})

// Get content based on post type
const postContent = computed(() => {
  // For Donate type
  if (actionTo.value === 'Donate') {
    return postData.value.notes ? `💝 ${postData.value.notes}` : `💝 บริจาค ${postData.value.amounts}`
  }
  
  // For DonateRecipient type
  if (actionTo.value === 'DonateRecipient') {
    const donation = postData.value.donation
    return donation?.notes ? `🎁 ${donation.notes}` : `🎁 ได้รับบริจาค ${donation?.amounts || ''}`
  }
  
  return postData.value.content || postData.value.description || ''
})

// Content expansion
const isContentExpanded = ref(false)
const contentLimit = 300
const shouldTruncate = computed(() => {
  return postContent.value.length > contentLimit
})
const displayContent = computed(() => {
  if (!shouldTruncate.value || isContentExpanded.value) {
    return postContent.value
  }
  return postContent.value.substring(0, contentLimit) + '...'
})

// Images
const images = computed(() => {
  if (postData.value.imagesResources && postData.value.imagesResources.length) {
    return postData.value.imagesResources
  }
  if (postData.value.images && postData.value.images.length) {
    return postData.value.images
  }
  // For donate - show slip
  if (postData.value.slip) {
    return [{ url: postData.value.slip }]
  }
  return []
})

// Privacy icon
const privacyIcon = computed(() => {
  switch (privacySetting.value) {
    case 'friends':
      return 'fluent:people-24-regular'
    case 'private':
      return 'fluent:lock-closed-24-regular'
    default:
      return 'fluent:globe-24-regular'
  }
})

// Post type badge config
const postTypeBadge = computed(() => {
  const configs = {
    'CoursePost': { icon: 'fluent:book-24-regular', color: 'bg-blue-500', label: 'รายวิชา' },
    'Donate': { icon: 'fluent:heart-24-regular', color: 'bg-pink-500', label: 'บริจาค' },
    'DonateRecipient': { icon: 'fluent:gift-24-regular', color: 'bg-green-500', label: 'รับบริจาค' },
    'Poll': { icon: 'fluent:poll-24-regular', color: 'bg-yellow-500', label: 'โพล' },
    'Post': { icon: 'fluent:document-text-24-regular', color: 'bg-purple-500', label: 'โพสต์' },
  }
  return configs[actionTo.value] || configs[postType.value] || null
})

const showComments = ref(true)  // Show comments immediately with post
const newComment = ref('')
const showReactionPicker = ref(false)
const selectedImageIndex = ref(null)

// Reply system state
const replyingTo = ref(null)           // Comment being replied to
const replyContent = ref('')           // Reply text
const isSubmittingReply = ref(false)   // Loading state for reply submission
const expandedReplies = ref({})        // Track which comments have expanded replies { commentId: true }
const commentReplies = ref({})         // Store loaded replies { commentId: [...replies] }
const loadingReplies = ref({})         // Track loading state for replies { commentId: true }
const repliesPagination = ref({})      // Pagination info for replies { commentId: { page, hasMore } }

// Comments state for infinite scroll
const newlyAddedComments = ref([])  // Comments added by current user (shown at top)
const olderComments = ref([])       // Comments loaded from API (older than pre-loaded)
const isLoadingComments = ref(false)
const currentPage = ref(1)
const hasMorePages = ref(true)      // Assume more until API says otherwise

// Pre-loaded comments from post or share
const preLoadedComments = computed(() => {
  // For Share Activities, get comments from Share object
  if (isShareActivity.value && shareData.value?.share_comments) {
    return shareData.value.share_comments
  }
  // For Posts (regular and course posts)
  return postData.value?.post_comments || []
})

// All displayed comments: user's new comments + pre-loaded + older loaded
const displayedComments = computed(() => {
  return [...newlyAddedComments.value, ...preLoadedComments.value, ...olderComments.value]
})

// Check if there are more comments to load
const hasMoreComments = computed(() => {
  // If total count > displayed count, there are more
  const totalCount = localCommentsCount.value
  const displayedCount = displayedComments.value.length
  return hasMorePages.value && totalCount > displayedCount
})

const remainingCommentsCount = computed(() => {
  return Math.max(0, localCommentsCount.value - displayedComments.value.length)
})

// Local reactive state for optimistic updates
const localIsLiked = ref(postData.value?.isLikedByAuth || false)
const localIsDisliked = ref(postData.value?.isDislikedByAuth || false)
const localLikes = ref(postData.value?.likes || 0)
const localDislikes = ref(postData.value?.dislikes || 0)
const localCommentsCount = ref(postData.value?.comments_count || 0)

// Loading states
const isLiking = ref(false)
const isDisliking = ref(false)
const isCommenting = ref(false)
const isSharing = ref(false)

// Share states
const showShareModal = ref(false)
const showShareMenu = ref(false)
const showOptionsMenu = ref(false)
const showPostOptionsMenu = ref(false)
const showEditModal = ref(false)
const localShares = ref(postData.value?.shares || 0)
const isDeletingPost = ref(false)

// Check if current user is the post author
const isOwnPost = computed(() => {
  return authStore.user?.id === postAuthor.value?.id
})

// Check if current user is the owner of the share (for share activities)
const isOwnShare = computed(() => {
  if (!isShareActivity.value) return false
  return authStore.user?.id === actionBy.value?.id
})

// Watch for postData changes to update local state
watch(() => postData.value, (newData) => {
  if (newData) {
    localIsLiked.value = newData.isLikedByAuth || false
    localIsDisliked.value = newData.isDislikedByAuth || false
    localLikes.value = newData.likes || 0
    localDislikes.value = newData.dislikes || 0
    localCommentsCount.value = newData.comments_count || 0
    localShares.value = newData.shares || 0
  }
}, { immediate: true })

const reactions = [
  { id: 'like', icon: '👍', label: 'Like', color: 'hover:bg-blue-100 dark:hover:bg-blue-900/30' },
  { id: 'love', icon: '❤️', label: 'Love', color: 'hover:bg-red-100 dark:hover:bg-red-900/30' },
  { id: 'haha', icon: '😄', label: 'Haha', color: 'hover:bg-yellow-100 dark:hover:bg-yellow-900/30' },
  { id: 'wow', icon: '😮', label: 'Wow', color: 'hover:bg-orange-100 dark:hover:bg-orange-900/30' },
  { id: 'sad', icon: '😢', label: 'Sad', color: 'hover:bg-gray-100 dark:hover:bg-gray-900/30' },
  { id: 'angry', icon: '😠', label: 'Angry', color: 'hover:bg-red-100 dark:hover:bg-red-900/30' },
]

const toggleComments = () => {
  showComments.value = !showComments.value
}

const loadMoreComments = async () => {
  if (isLoadingComments.value || !hasMorePages.value) return
  
  // Check if we have a valid ID (either share or post)
  const hasValidId = (isShareActivity.value && shareData.value?.id) || postData.value?.id
  if (!hasValidId) return
  
  isLoadingComments.value = true
  try {
    // Calculate which page to load: skip already displayed comments
    const preLoadedCount = preLoadedComments.value.length
    const alreadyLoadedOlder = olderComments.value.length
    const nextPage = Math.floor((preLoadedCount + alreadyLoadedOlder) / 10) + 1
    
    const config = useRuntimeConfig()
    
    // Determine API endpoint based on activity/post type
    let apiUrl = ''
    
    // For Share Activities
    if (isShareActivity.value && shareData.value?.id) {
      apiUrl = `${config.public.apiBase}/api/shares/${shareData.value.id}/comments`
    }
    // For Course Posts
    else if (actionTo.value === 'CoursePost' && postData.value.course_id) {
      apiUrl = `${config.public.apiBase}/api/courses/${postData.value.course_id}/posts/${postData.value.id}/comments`
    }
    // For regular Posts
    else {
      apiUrl = `${config.public.apiBase}/api/posts/${postData.value.id}/comments`
    }
    
    const response = await api.get(`${apiUrl}?page=${nextPage}&per_page=10`)
    
    if (response.comments) {
      // Filter out comments we already have (pre-loaded or newly added)
      const existingIds = new Set([
        ...preLoadedComments.value.map(c => c.id),
        ...newlyAddedComments.value.map(c => c.id),
        ...olderComments.value.map(c => c.id)
      ])
      const newOlderComments = response.comments.filter(c => !existingIds.has(c.id))
      olderComments.value.push(...newOlderComments)
    }
    
    if (response.pagination) {
      hasMorePages.value = response.pagination.has_more
      currentPage.value = response.pagination.current_page
    }
  } catch (error) {
    console.error('Failed to load more comments:', error)
  } finally {
    isLoadingComments.value = false
  }
}

const addComment = async () => {
  if (!newComment.value.trim() || isCommenting.value) return
  
  isCommenting.value = true
  try {
    const config = useRuntimeConfig()
    
    // Determine API endpoint based on activity/post type
    let apiUrl = ''
    
    // For Share Activities, comment on the Share
    if (isShareActivity.value && shareData.value?.id) {
      apiUrl = `${config.public.apiBase}/api/shares/${shareData.value.id}/comments`
    }
    // For Course Posts
    else if (actionTo.value === 'CoursePost' && postData.value.course_id) {
      apiUrl = `${config.public.apiBase}/api/courses/${postData.value.course_id}/posts/${postData.value.id}/comments`
    }
    // For regular Posts
    else {
      apiUrl = `${config.public.apiBase}/api/posts/${postData.value.id}/comments`
    }
    
    const response = await $fetch(apiUrl, {
      method: 'POST',
      headers: { 
        Authorization: `Bearer ${authStore.token}`,
        Accept: 'application/json'
      },
      body: { content: newComment.value }
    })
    
    if (response.success) {
      // Add new comment to top of list
      if (response.comment) {
        newlyAddedComments.value.unshift(response.comment)
      }
      
      // Increment appropriate comment count
      if (isShareActivity.value && shareData.value) {
        localShareComments.value++
      } else {
        localCommentsCount.value++
      }
      
      newComment.value = ''
    }
  } catch (error) {
    console.error('Failed to add comment:', error)
    const errorMsg = error?.data?.message || 'ไม่สามารถเพิ่มความคิดเห็นได้'
    swal.error(errorMsg)
  } finally {
    isCommenting.value = false
  }
}

const handleLike = async () => {
  if (isLiking.value || !postData.value?.id) return
  
  // Prevent author from liking their own post
  if (isOwnPost.value) {
    swal.warning('คุณไม่สามารถกดถูกใจโพสต์ของตัวเองได้')
    return
  }
  
  isLiking.value = true
  
  // Optimistic update
  const wasLiked = localIsLiked.value
  
  // คำนวณแต้มที่ต้องใช้
  let pointsToUse = 0
  let pointsToAuthor = 0
  
  if (wasLiked) {
    // Unlike - ผู้ยกเลิกเสีย 12 แต้ม (เข้าระบบ) และไม่มีการคืนแต้ม
    pointsToUse = 12
    pointsToAuthor = 12 // ลดแต้มเจ้าของโพสต์
    
    const hasEnough = authStore.deductPoints(pointsToUse)
    if (!hasEnough) {
      swal.warning('แต้มของคุณไม่เพียงพอในการยกเลิก Like (ต้องการ 12 แต้ม)')
      isLiking.value = false
      return
    }
  } else {
    // Like - ผู้กดเสีย 24 แต้ม (12 ให้เจ้าของ, 12 เข้าระบบ)
    pointsToUse = 24
    pointsToAuthor = 12 // เจ้าของได้ 12 แต้ม
    
    const hasEnough = authStore.deductPoints(pointsToUse)
    if (!hasEnough) {
      swal.warning('แต้มของคุณไม่เพียงพอ (ต้องการ 24 แต้ม)')
      isLiking.value = false
      return
    }
  }
  
  localIsLiked.value = !wasLiked
  localLikes.value += wasLiked ? -1 : 1
  
  // If was disliked, remove dislike
  if (!wasLiked && localIsDisliked.value) {
    localIsDisliked.value = false
    localDislikes.value--
  }
  
  // อัพเดทแต้มเจ้าของโพสต์ (Frontend display)
  if (postAuthor.value?.points !== undefined) {
    if (wasLiked) {
      // Unlike: เจ้าของไม่ลดแต้ม (ไม่เปลี่ยนแปลง)
      // postAuthor.value.points ไม่เปลี่ยน
    } else {
      // Like: เจ้าของโพสต์ได้ 12 แต้ม
      postAuthor.value.points = (postAuthor.value.points || 0) + pointsToAuthor
    }
  }
  
  try {
    // Determine API endpoint based on post type
    let apiUrl = ''
    if (actionTo.value === 'CoursePost' && postData.value.course_id) {
      apiUrl = `/api/courses/${postData.value.course_id}/posts/${postData.value.id}/like`
    } else {
      apiUrl = `/api/posts/${postData.value.id}/like`
    }
    
    const response = await api.call(apiUrl, {
      method: 'POST'
    })
    
    if (!response.success) {
      // Revert optimistic update
      localIsLiked.value = wasLiked
      localLikes.value += wasLiked ? 1 : -1
      
      // Rollback points
      authStore.rollback(pointsToUse)
      
      // Rollback author points
      if (postAuthor.value?.points !== undefined) {
        if (wasLiked) {
          // Unlike rollback: เจ้าของไม่ได้เปลี่ยนแต้ม ไม่ต้อง rollback
        } else {
          // Like rollback: ลดแต้มที่เพิ่มไป
          postAuthor.value.points = (postAuthor.value.points || 0) - pointsToAuthor
        }
      }
      
      swal.error(response.message || 'ไม่สามารถกดถูกใจได้')
    }
  } catch (error) {
    // Revert optimistic update
    localIsLiked.value = wasLiked
    localLikes.value += wasLiked ? 1 : -1
    
    // Rollback points
    authStore.rollback(pointsToUse)
    
    // Rollback author points
    if (postAuthor.value?.points !== undefined) {
      if (wasLiked) {
        // คืนแต้มที่ลดไป
        postAuthor.value.points = (postAuthor.value.points || 0) + pointsToAuthor
      } else {
        // ลดแต้มที่เพิ่มไป
        postAuthor.value.points = (postAuthor.value.points || 0) - pointsToAuthor
      }
    }
    
    console.error('Failed to like:', error)
    swal.warning('คุณมีพอยต์ไม่เพียงพอในการกดถูกใจ')
  } finally {
    isLiking.value = false
  }
}

const handleDislike = async () => {
  if (isDisliking.value || !postData.value?.id) return
  
  // Prevent author from disliking their own post
  if (isOwnPost.value) {
    swal.warning('คุณไม่สามารถกดไม่ถูกใจโพสต์ของตัวเองได้')
    return
  }
  
  isDisliking.value = true
  
  // Optimistic update
  const wasDisliked = localIsDisliked.value
  
  // คำนวณแต้มที่ต้องใช้
  let pointsToUse = 12
  
  // ทั้ง Dislike และ Undislike ต้องตัดแต้ม 12 แต้ม
  const hasEnough = authStore.deductPoints(pointsToUse)
  if (!hasEnough) {
    if (wasDisliked) {
      swal.warning('แต้มของคุณไม่เพียงพอในการยกเลิก Dislike (ต้องการ 12 แต้ม)')
    } else {
      swal.warning('แต้มของคุณไม่เพียงพอ (ต้องการ 12 แต้ม)')
    }
    isDisliking.value = false
    return
  }
  
  localIsDisliked.value = !wasDisliked
  localDislikes.value += wasDisliked ? -1 : 1
  
  // If was liked, remove like
  if (!wasDisliked && localIsLiked.value) {
    localIsLiked.value = false
    localLikes.value--
  }
  
  // อัพเดทแต้มเจ้าของโพสต์
  if (postAuthor.value?.points !== undefined) {
    if (!wasDisliked) {
      // Dislike: เจ้าของโพสต์เสียแต้ม 12 แต้ม
      postAuthor.value.points = (postAuthor.value.points || 0) - pointsToUse
    }
    // Undislike: ไม่คืนแต้มให้เจ้าของโพสต์
  }
  
  try {
    // Determine API endpoint based on post type
    let apiUrl = ''
    if (actionTo.value === 'CoursePost' && postData.value.course_id) {
      apiUrl = `/api/courses/${postData.value.course_id}/posts/${postData.value.id}/dislike`
    } else {
      apiUrl = `/api/posts/${postData.value.id}/dislike`
    }
    
    const response = await api.call(apiUrl, {
      method: 'POST'
    })
    
    if (!response.success) {
      // Revert optimistic update
      localIsDisliked.value = wasDisliked
      localDislikes.value += wasDisliked ? 1 : -1
      
      // Rollback points - คืนแต้มที่ตัดไป
      authStore.rollback(pointsToUse)
      
      // Rollback author points
      if (postAuthor.value?.points !== undefined && !wasDisliked) {
        // คืนแต้มที่หักจากเจ้าของโพสต์
        postAuthor.value.points = (postAuthor.value.points || 0) + pointsToUse
      }
      
      swal.error(response.message || 'ไม่สามารถกดไม่ถูกใจได้')
    }
  } catch (error) {
    // Revert optimistic update
    localIsDisliked.value = wasDisliked
    localDislikes.value += wasDisliked ? 1 : -1
    
    // Rollback points - คืนแต้มที่ตัดไป
    authStore.rollback(pointsToUse)
    
    // Rollback author points
    if (postAuthor.value?.points !== undefined && !wasDisliked) {
      // คืนแต้มที่หักจากเจ้าของโพสต์
      postAuthor.value.points = (postAuthor.value.points || 0) + pointsToUse
    }
    
    console.error('Failed to dislike:', error)
    swal.warning('คุณมีพอยต์ไม่เพียงพอในการกดไม่ถูกใจ')
  } finally {
    isDisliking.value = false
  }
}

const handleCommentLike = async (comment) => {
  // Check if user is comment owner
  const commentAuthor = comment.user || comment.author
  if (authStore.user?.id === commentAuthor?.id) {
    swal.warning('คุณไม่สามารถกดถูกใจคอมเมนต์ของตัวเองได้')
    return
  }

  if (comment.isLiking) return
  comment.isLiking = true

  const wasLiked = comment.isLikedByAuth || false
  let pointsToUse = 0
  let pointsToAuthor = 0

  if (wasLiked) {
    // Unlike
    pointsToUse = 12
    pointsToAuthor = 0 // เจ้าของไม่ลดแต้ม
  } else {
    // Like
    pointsToUse = 24
    pointsToAuthor = 12
  }

  const hasEnough = authStore.deductPoints(pointsToUse)
  if (!hasEnough) {
    swal.warning(`แต้มของคุณไม่เพียงพอ (ต้องการ ${pointsToUse} แต้ม)`)
    comment.isLiking = false
    return
  }

  // Optimistic update
  comment.isLikedByAuth = !wasLiked
  comment.likes = (comment.likes || 0) + (wasLiked ? -1 : 1)

  // Remove dislike if was disliked
  if (!wasLiked && comment.isDislikedByAuth) {
    comment.isDislikedByAuth = false
    comment.dislikes = (comment.dislikes || 0) - 1
  }

  // Update comment author points in display
  if (commentAuthor?.point !== undefined && !wasLiked) {
    commentAuthor.point = (commentAuthor.point || 0) + pointsToAuthor
  }

  try {
    // Determine API endpoint
    let apiUrl = ''
    if (actionTo.value === 'CoursePost') {
      apiUrl = `/api/courses/posts/comments/${comment.id}/like`
    } else {
      apiUrl = `/api/post_comments/${comment.id}/like`
    }

    const response = await api.call(apiUrl, {
      method: 'POST'
    })

    if (!response.success) {
      // Revert
      comment.isLikedByAuth = wasLiked
      comment.likes = (comment.likes || 0) + (wasLiked ? 1 : -1)
      authStore.rollback(pointsToUse)
      if (commentAuthor?.point !== undefined && !wasLiked) {
        commentAuthor.point = (commentAuthor.point || 0) - pointsToAuthor
      }
      swal.error(response.message || 'ไม่สามารถกดถูกใจได้')
    }
  } catch (error) {
    // Revert
    comment.isLikedByAuth = wasLiked
    comment.likes = (comment.likes || 0) + (wasLiked ? 1 : -1)
    authStore.rollback(pointsToUse)
    if (commentAuthor?.point !== undefined && !wasLiked) {
      commentAuthor.point = (commentAuthor.point || 0) - pointsToAuthor
    }
    console.error('Failed to like comment:', error)
    const errorMsg = error?.data?.message || error?.message || 'เกิดข้อผิดพลาดในการกดถูกใจ'
    swal.error(errorMsg)
  } finally {
    comment.isLiking = false
  }
}

const handleCommentDislike = async (comment) => {
  // Check if user is comment owner
  const commentAuthor = comment.user || comment.author
  if (authStore.user?.id === commentAuthor?.id) {
    swal.warning('คุณไม่สามารถกดไม่ถูกใจคอมเมนต์ของตัวเองได้')
    return
  }

  if (comment.isDisliking) return
  comment.isDisliking = true

  const wasDisliked = comment.isDislikedByAuth || false
  const pointsToUse = 12

  const hasEnough = authStore.deductPoints(pointsToUse)
  if (!hasEnough) {
    swal.warning(`แต้มของคุณไม่เพียงพอ (ต้องการ ${pointsToUse} แต้ม)`)
    comment.isDisliking = false
    return
  }

  // Optimistic update
  comment.isDislikedByAuth = !wasDisliked
  comment.dislikes = (comment.dislikes || 0) + (wasDisliked ? -1 : 1)

  // Remove like if was liked
  if (!wasDisliked && comment.isLikedByAuth) {
    comment.isLikedByAuth = false
    comment.likes = (comment.likes || 0) - 1
  }

  // Update comment author points in display
  if (commentAuthor?.point !== undefined && !wasDisliked) {
    commentAuthor.point = (commentAuthor.point || 0) - pointsToUse
  }

  try {
    // Determine API endpoint
    let apiUrl = ''
    if (actionTo.value === 'CoursePost') {
      apiUrl = `/api/courses/posts/comments/${comment.id}/dislike`
    } else {
      apiUrl = `/api/post_comments/${comment.id}/dislike`
    }

    const response = await api.call(apiUrl, {
      method: 'POST'
    })

    if (!response.success) {
      // Revert
      comment.isDislikedByAuth = wasDisliked
      comment.dislikes = (comment.dislikes || 0) + (wasDisliked ? 1 : -1)
      authStore.rollback(pointsToUse)
      if (commentAuthor?.point !== undefined && !wasDisliked) {
        commentAuthor.point = (commentAuthor.point || 0) + pointsToUse
      }
      swal.error(response.message || 'ไม่สามารถกดไม่ถูกใจได้')
    }
  } catch (error) {
    // Revert
    comment.isDislikedByAuth = wasDisliked
    comment.dislikes = (comment.dislikes || 0) + (wasDisliked ? 1 : -1)
    authStore.rollback(pointsToUse)
    if (commentAuthor?.point !== undefined && !wasDisliked) {
      commentAuthor.point = (commentAuthor.point || 0) + pointsToUse
    }
    console.error('Failed to dislike comment:', error)
    const errorMsg = error?.data?.message || error?.message || 'เกิดข้อผิดพลาดในการกดไม่ถูกใจ'
    swal.error(errorMsg)
  } finally {
    comment.isDisliking = false
  }
}

// ========== Reply System Functions ==========

// Start replying to a comment
const startReply = (comment) => {
  replyingTo.value = comment
  replyContent.value = ''
  // Focus on reply input after DOM update
  nextTick(() => {
    const replyInput = document.getElementById(`reply-input-${comment.id}`)
    if (replyInput) replyInput.focus()
  })
}

// Cancel reply
const cancelReply = () => {
  replyingTo.value = null
  replyContent.value = ''
}

// Submit reply
const submitReply = async (comment) => {
  if (!replyContent.value.trim() || isSubmittingReply.value) return
  
  // Check points (12 required)
  const pointsRequired = 12
  const hasEnough = authStore.deductPoints(pointsRequired)
  if (!hasEnough) {
    swal.warning(`แต้มของคุณไม่เพียงพอในการตอบกลับ (ต้องการ ${pointsRequired} แต้ม)`)
    return
  }
  
  isSubmittingReply.value = true
  
  try {
    const response = await api.call(`/api/post_comments/${comment.id}/replies`, {
      method: 'POST',
      body: {
        content: replyContent.value.trim()
      }
    })
    
    if (response.success) {
      // Add reply to local state
      if (!commentReplies.value[comment.id]) {
        commentReplies.value[comment.id] = []
      }
      commentReplies.value[comment.id].push(response.reply)
      
      // Update reply count on parent comment
      comment.replies_count = (comment.replies_count || 0) + 1
      
      // Expand replies section
      expandedReplies.value[comment.id] = true
      
      // Clear reply form
      replyContent.value = ''
      replyingTo.value = null
      
      swal.toast('ตอบกลับสำเร็จ', 'success')
    } else {
      authStore.rollback(pointsRequired)
      swal.error(response.message || 'ไม่สามารถตอบกลับได้')
    }
  } catch (error) {
    authStore.rollback(pointsRequired)
    console.error('Failed to submit reply:', error)
    swal.error(error?.data?.message || 'เกิดข้อผิดพลาดในการตอบกลับ')
  } finally {
    isSubmittingReply.value = false
  }
}

// Toggle replies visibility
const toggleReplies = async (comment) => {
  const commentId = comment.id
  
  if (expandedReplies.value[commentId]) {
    // Collapse
    expandedReplies.value[commentId] = false
  } else {
    // Expand and load if not already loaded
    expandedReplies.value[commentId] = true
    
    if (!commentReplies.value[commentId]) {
      await loadReplies(comment)
    }
  }
}

// Load replies for a comment
const loadReplies = async (comment, page = 1) => {
  const commentId = comment.id
  
  if (loadingReplies.value[commentId]) return
  
  loadingReplies.value[commentId] = true
  
  try {
    const response = await api.call(`/api/post_comments/${commentId}/replies?page=${page}&per_page=5`)
    
    if (response.success) {
      if (page === 1) {
        commentReplies.value[commentId] = response.replies || []
      } else {
        commentReplies.value[commentId] = [
          ...(commentReplies.value[commentId] || []),
          ...(response.replies || [])
        ]
      }
      
      repliesPagination.value[commentId] = {
        page: response.pagination?.current_page || page,
        hasMore: response.pagination?.has_more || false
      }
    }
  } catch (error) {
    console.error('Failed to load replies:', error)
    swal.error('ไม่สามารถโหลดการตอบกลับได้')
  } finally {
    loadingReplies.value[commentId] = false
  }
}

// Load more replies
const loadMoreReplies = async (comment) => {
  const commentId = comment.id
  const currentPage = repliesPagination.value[commentId]?.page || 1
  await loadReplies(comment, currentPage + 1)
}

// Like reply (same as comment like)
const handleReplyLike = async (reply) => {
  await handleCommentLike(reply)
}

// Dislike reply (same as comment dislike)
const handleReplyDislike = async (reply) => {
  await handleCommentDislike(reply)
}

const openImage = (index) => {
  selectedImageIndex.value = index
}

const closeImageModal = () => {
  selectedImageIndex.value = null
}

// Quick Share (without dialog)
const handleQuickShare = async () => {
  if (isSharing.value || !postData.value?.id) return
  
  // Prevent sharing own post
  if (isOwnPost.value) {
    toast.warning('คุณไม่สามารถแชร์โพสต์ของตัวเองได้')
    return
  }
  
  // Check points
  const pointsRequired = 36
  const currentPoints = authStore.points || 0
  const hasEnough = authStore.deductPoints(pointsRequired)
  if (!hasEnough) {
    swal.warning(`แต้มของคุณไม่เพียงพอในการแชร์\n\nต้องการ: ${pointsRequired} แต้ม\nมีอยู่: ${currentPoints} แต้ม\nขาดอีก: ${pointsRequired - currentPoints} แต้ม`, 'แต้มไม่พอ')
    return
  }
  
  isSharing.value = true
  
  // Optimistic update
  localShares.value++
  
  // Update author points
  if (postAuthor.value?.points !== undefined) {
    postAuthor.value.points = (postAuthor.value.points || 0) + 18
  }
  
  try {
    const config = useRuntimeConfig()
    
    // Use new unified Share API
    let shareableType = 'Post'
    if (actionTo.value === 'CoursePost') {
      shareableType = 'CoursePost'
    } else if (actionTo.value === 'AcademyPost') {
      shareableType = 'AcademyPost'
    }
    
    const apiUrl = `${config.public.apiBase}/api/shares`
    
    const response = await $fetch(apiUrl, {
      method: 'POST',
      headers: { Authorization: `Bearer ${authStore.token}` },
      body: {
        shareable_type: shareableType,
        shareable_id: postData.value.id,
        share_comment: null,
        privacy: 'public'
      }
    })
    
    if (!response.success) {
      // Revert
      localShares.value--
      authStore.rollback(pointsRequired)
      if (postAuthor.value?.points !== undefined) {
        postAuthor.value.points = (postAuthor.value.points || 0) - 18
      }
      console.error('❌ Share failed:', response)
      swal.error(response.message || 'ไม่สามารถแชร์ได้')
    } else {
      // Success notification
      swal.toast('แชร์โพสต์สำเร็จ! 🎉', 'success')
    }
  } catch (error) {
    // Revert
    localShares.value--
    authStore.rollback(pointsRequired)
    if (postAuthor.value?.points !== undefined) {
      postAuthor.value.points = (postAuthor.value.points || 0) - 18
    }
    console.error('❌ Share error:', error)
    console.error('❌ Error data:', error?.data)
    console.error('❌ Error response:', error?.response)
    const errorMsg = error?.data?.message || error?.message || 'เกิดข้อผิดพลาดในการแชร์'
    swal.error(errorMsg)
  } finally {
    isSharing.value = false
    showShareMenu.value = false
  }
}

// Share with Options (with dialog)
const handleShareWithOptions = () => {
  if (isOwnPost.value) {
    swal.warning('คุณไม่สามารถแชร์โพสต์ของตัวเองได้')
    return
  }
  showShareMenu.value = false
  showShareModal.value = true
}

// Handle share from modal
const handleShareSubmit = async (shareData) => {
  if (isSharing.value || !postData.value?.id) return
  
  const pointsRequired = 36
  const currentPoints = authStore.points || 0
  const hasEnough = authStore.deductPoints(pointsRequired)
  if (!hasEnough) {
    swal.warning(`แต้มของคุณไม่เพียงพอในการแชร์\n\nต้องการ: ${pointsRequired} แต้ม\nมีอยู่: ${currentPoints} แต้ม\nขาดอีก: ${pointsRequired - currentPoints} แต้ม`, 'แต้มไม่พอ')
    return
  }
  
  isSharing.value = true
  
  // Optimistic update
  localShares.value++
  
  // Update author points
  if (postAuthor.value?.points !== undefined) {
    postAuthor.value.points = (postAuthor.value.points || 0) + 18
  }
  
  try {
    // Use new unified Share API
    let shareableType = 'Post'
    if (actionTo.value === 'CoursePost') {
      shareableType = 'CoursePost'
    } else if (actionTo.value === 'AcademyPost') {
      shareableType = 'AcademyPost'
    }
    
    const response = await api.call('/api/shares', {
      method: 'POST',
      body: {
        shareable_type: shareableType,
        shareable_id: postData.value.id,
        ...shareData
      }
    })
    
    if (!response.success) {
      // Revert
      localShares.value--
      authStore.rollback(pointsRequired)
      if (postAuthor.value?.points !== undefined) {
        postAuthor.value.points = (postAuthor.value.points || 0) - 18
      }
      swal.error(response.message || 'ไม่สามารถแชร์ได้')
    } else {
      swal.toast('แชร์โพสต์สำเร็จ! 🎉', 'success')
    }
  } catch (error) {
    // Revert
    localShares.value--
    authStore.rollback(pointsRequired)
    if (postAuthor.value?.points !== undefined) {
      postAuthor.value.points = (postAuthor.value.points || 0) - 18
    }
    console.error('Failed to share:', error)
    const errorMsg = error?.data?.message || error?.message || 'เกิดข้อผิดพลาดในการแชร์'
    swal.error(errorMsg)
  } finally {
    isSharing.value = false
  }
}

// ======== Share Reactions (for Share Activities) ========
// Computed properties for Share data
const shareData = computed(() => {
  // For Share Activities, the target_resource is the Share object
  if (isShareActivity.value && props.post.target_resource) {
    return props.post.target_resource
  }
  return null
})

// Local state for Share reactions
const localShareIsLiked = ref(shareData.value?.isLikedByAuth || false)
const localShareIsDisliked = ref(shareData.value?.isDislikedByAuth || false)
const localShareLikes = ref(shareData.value?.likes || 0)
const localShareDislikes = ref(shareData.value?.dislikes || 0)
const localShareComments = ref(shareData.value?.comments || 0)
const isShareLiking = ref(false)
const isShareDisliking = ref(false)

// Watch for shareData changes
watch(() => shareData.value, (newData) => {
  if (newData) {
    localShareIsLiked.value = newData.isLikedByAuth || false
    localShareIsDisliked.value = newData.isDislikedByAuth || false
    localShareLikes.value = newData.likes || 0
    localShareDislikes.value = newData.dislikes || 0
    localShareComments.value = newData.comments || 0
  }
}, { immediate: true })

// Handle Share Like
const handleShareLike = async () => {
  if (isShareLiking.value || !shareData.value?.id) return
  
  // Prevent author from liking their own share
  if (authStore.user?.id === actionBy.value?.id) {
    swal.warning('คุณไม่สามารถกดถูกใจการแชร์ของตัวเองได้')
    return
  }
  
  isShareLiking.value = true
  
  const wasLiked = localShareIsLiked.value
  let pointsToUse = wasLiked ? 12 : 24 // Unlike: 12pts, Like: 24pts
  let pointsToAuthor = 12 // Share author gets 12pts on like
  
  const hasEnough = authStore.deductPoints(pointsToUse)
  if (!hasEnough) {
    swal.warning(`แต้มของคุณไม่เพียงพอ (ต้องการ ${pointsToUse} แต้ม)`)
    isShareLiking.value = false
    return
  }
  
  // Optimistic update
  localShareIsLiked.value = !wasLiked
  localShareLikes.value += wasLiked ? -1 : 1
  
  // Remove dislike if was disliked
  if (!wasLiked && localShareIsDisliked.value) {
    localShareIsDisliked.value = false
    localShareDislikes.value--
  }
  
  // Update share author points
  if (actionBy.value?.points !== undefined && !wasLiked) {
    actionBy.value.points = (actionBy.value.points || 0) + pointsToAuthor
  }
  
  try {
    const response = await api.call(`/api/shares/${shareData.value.id}/like`, {
      method: 'POST'
    })
    
    if (!response.success) {
      // Revert
      localShareIsLiked.value = wasLiked
      localShareLikes.value += wasLiked ? 1 : -1
      authStore.rollback(pointsToUse)
      if (actionBy.value?.points !== undefined && !wasLiked) {
        actionBy.value.points = (actionBy.value.points || 0) - pointsToAuthor
      }
      swal.error(response.message || 'ไม่สามารถกดถูกใจได้')
    }
  } catch (error) {
    // Revert
    localShareIsLiked.value = wasLiked
    localShareLikes.value += wasLiked ? 1 : -1
    authStore.rollback(pointsToUse)
    if (actionBy.value?.points !== undefined && !wasLiked) {
      actionBy.value.points = (actionBy.value.points || 0) - pointsToAuthor
    }
    console.error('Failed to like share:', error)
    const errorMsg = error?.data?.message || error?.message || 'เกิดข้อผิดพลาดในการกดถูกใจ'
    swal.error(errorMsg)
  } finally {
    isShareLiking.value = false
  }
}

// Handle Share Dislike
const handleShareDislike = async () => {
  if (isShareDisliking.value || !shareData.value?.id) return
  
  // Prevent author from disliking their own share
  if (authStore.user?.id === actionBy.value?.id) {
    swal.warning('คุณไม่สามารถกดไม่ถูกใจการแชร์ของตัวเองได้')
    return
  }
  
  isShareDisliking.value = true
  
  const wasDisliked = localShareIsDisliked.value
  let pointsToUse = 12 // Both dislike and undislike cost 12pts
  
  const hasEnough = authStore.deductPoints(pointsToUse)
  if (!hasEnough) {
    swal.warning('แต้มของคุณไม่เพียงพอ (ต้องการ 12 แต้ม)')
    isShareDisliking.value = false
    return
  }
  
  // Optimistic update
  localShareIsDisliked.value = !wasDisliked
  localShareDislikes.value += wasDisliked ? -1 : 1
  
  // Remove like if was liked
  if (!wasDisliked && localShareIsLiked.value) {
    localShareIsLiked.value = false
    localShareLikes.value--
  }
  
  // Update share author points (lose points on dislike)
  if (actionBy.value?.points !== undefined && !wasDisliked) {
    actionBy.value.points = (actionBy.value.points || 0) - pointsToUse
  }
  
  try {
    const response = await api.call(`/api/shares/${shareData.value.id}/dislike`, {
      method: 'POST'
    })
    
    if (!response.success) {
      // Revert
      localShareIsDisliked.value = wasDisliked
      localShareDislikes.value += wasDisliked ? 1 : -1
      authStore.rollback(pointsToUse)
      if (actionBy.value?.points !== undefined && !wasDisliked) {
        actionBy.value.points = (actionBy.value.points || 0) + pointsToUse
      }
      swal.error(response.message || 'ไม่สามารถกดไม่ถูกใจได้')
    }
  } catch (error) {
    // Revert
    localShareIsDisliked.value = wasDisliked
    localShareDislikes.value += wasDisliked ? 1 : -1
    authStore.rollback(pointsToUse)
    if (actionBy.value?.points !== undefined && !wasDisliked) {
      actionBy.value.points = (actionBy.value.points || 0) + pointsToUse
    }
    console.error('Failed to dislike share:', error)
    const errorMsg = error?.data?.message || error?.message || 'เกิดข้อผิดพลาดในการกดไม่ถูกใจ'
    swal.error(errorMsg)
  } finally {
    isShareDisliking.value = false
  }
}

// ======== Share Comments ========
const showShareComments = ref(true)  // ✅ เปลี่ยนเป็น true เพื่อแสดงทันที
const newShareComment = ref('')
const isSubmittingShareComment = ref(false)
const isLoadingShareComments = ref(false)
const shareCommentsList = ref([])
const shareCommentsPagination = ref({
  currentPage: 1,
  lastPage: 1,
  perPage: 10,
  total: 0
})

// Toggle show/hide comments
const toggleShareComments = async () => {
  showShareComments.value = !showShareComments.value
  
  // Load comments when opening for first time
  if (showShareComments.value && shareCommentsList.value.length === 0) {
    await loadShareComments()
  }
}

// Load share comments
const loadShareComments = async (page = 1) => {
  if (!shareData.value?.id) return
  
  isLoadingShareComments.value = true
  
  try {
    const config = useRuntimeConfig()
    const apiUrl = `${config.public.apiBase}/api/shares/${shareData.value.id}/comments?page=${page}`
    
    const response = await $fetch(apiUrl, {
      headers: { Authorization: `Bearer ${authStore.token}` }
    })
    
    if (response.success) {
      if (page === 1) {
        shareCommentsList.value = response.comments || []
      } else {
        shareCommentsList.value = [...shareCommentsList.value, ...(response.comments || [])]
      }
      
      if (response.pagination) {
        shareCommentsPagination.value = response.pagination
      }
    }
  } catch (error) {
    console.error('Failed to load share comments:', error)
    toast.error('ไม่สามารถโหลดความคิดเห็นได้')
  } finally {
    isLoadingShareComments.value = false
  }
}

// Load more comments
const loadMoreShareComments = () => {
  const nextPage = shareCommentsPagination.value.currentPage + 1
  if (nextPage <= shareCommentsPagination.value.lastPage) {
    loadShareComments(nextPage)
  }
}

// Submit new comment
const submitShareComment = async () => {
  if (!newShareComment.value.trim() || !shareData.value?.id) return
  
  isSubmittingShareComment.value = true
  
  try {
    const config = useRuntimeConfig()
    const apiUrl = `${config.public.apiBase}/api/shares/${shareData.value.id}/comments`
    
    const response = await $fetch(apiUrl, {
      method: 'POST',
      headers: { Authorization: `Bearer ${authStore.token}` },
      body: {
        content: newShareComment.value.trim()
      }
    })
    
    if (response.success) {
      // Add comment to newlyAddedComments (shared with regular comments display)
      newlyAddedComments.value.unshift(response.comment)
      
      // Update count
      localShareComments.value++
      
      // Clear input
      newShareComment.value = ''
      
      // Show success
      toast.success('แสดงความคิดเห็นสำเร็จ')
    } else {
      toast.error(response.message || 'ไม่สามารถแสดงความคิดเห็นได้')
    }
  } catch (error) {
    console.error('Failed to submit comment:', error)
    const errorMsg = error?.data?.message || error?.message || 'เกิดข้อผิดพลาดในการแสดงความคิดเห็น'
    toast.error(errorMsg)
  } finally {
    isSubmittingShareComment.value = false
  }
}

// Delete comment
const deleteShareComment = async (commentId) => {
  const confirmed = await swal.confirmDelete('ความคิดเห็นนี้')
  if (!confirmed) return
  
  try {
    const config = useRuntimeConfig()
    const apiUrl = `${config.public.apiBase}/api/share-comments/${commentId}`
    
    const response = await $fetch(apiUrl, {
      method: 'DELETE',
      headers: { Authorization: `Bearer ${authStore.token}` }
    })
    
    if (response.success) {
      // Remove from list
      shareCommentsList.value = shareCommentsList.value.filter(c => c.id !== commentId)
      
      // Update count
      localShareComments.value--
      
      swal.toast('ลบความคิดเห็นสำเร็จ', 'success')
    } else {
      swal.error(response.message || 'ไม่สามารถลบความคิดเห็นได้')
    }
  } catch (error) {
    console.error('Failed to delete comment:', error)
    toast.error('เกิดข้อผิดพลาดในการลบความคิดเห็น')
  }
}

// Like share comment
const handleShareCommentLike = async (comment) => {
  // Check if user is comment owner
  const commentAuthor = comment.user
  if (authStore.user?.id === commentAuthor?.id) {
    swal.warning('คุณไม่สามารถกดถูกใจคอมเมนต์ของตัวเองได้')
    return
  }

  if (comment.isLiking) return
  comment.isLiking = true

  const wasLiked = comment.is_liked_by_auth || false
  let pointsToUse = 0

  if (wasLiked) {
    // Unlike
    pointsToUse = 12
  } else {
    // Like
    pointsToUse = 24
  }

  const hasEnough = authStore.deductPoints(pointsToUse)
  if (!hasEnough) {
    swal.warning(`แต้มของคุณไม่เพียงพอ (ต้องการ ${pointsToUse} แต้ม)`)
    comment.isLiking = false
    return
  }

  // Optimistic update
  comment.is_liked_by_auth = !wasLiked
  comment.likes = (comment.likes || 0) + (wasLiked ? -1 : 1)

  // Remove dislike if was disliked
  if (!wasLiked && comment.is_disliked_by_auth) {
    comment.is_disliked_by_auth = false
    comment.dislikes = (comment.dislikes || 0) - 1
  }

  try {
    const config = useRuntimeConfig()
    const apiUrl = `${config.public.apiBase}/api/share-comments/${comment.id}/like`

    const response = await $fetch(apiUrl, {
      method: 'POST',
      headers: { Authorization: `Bearer ${authStore.token}` }
    })

    if (!response.success) {
      // Revert
      comment.is_liked_by_auth = wasLiked
      comment.likes = (comment.likes || 0) + (wasLiked ? 1 : -1)
      authStore.rollback(pointsToUse)
      swal.error(response.message || 'ไม่สามารถกดถูกใจได้')
    }
  } catch (error) {
    // Revert
    comment.is_liked_by_auth = wasLiked
    comment.likes = (comment.likes || 0) + (wasLiked ? 1 : -1)
    authStore.rollback(pointsToUse)
    console.error('Failed to like share comment:', error)
    const errorMsg = error?.data?.message || error?.message || 'เกิดข้อผิดพลาดในการกดถูกใจ'
    swal.error(errorMsg)
  } finally {
    comment.isLiking = false
  }
}

// Dislike share comment
const handleShareCommentDislike = async (comment) => {
  // Check if user is comment owner
  const commentAuthor = comment.user
  if (authStore.user?.id === commentAuthor?.id) {
    swal.warning('คุณไม่สามารถกดไม่ถูกใจคอมเมนต์ของตัวเองได้')
    return
  }

  if (comment.isDisliking) return
  comment.isDisliking = true

  const wasDisliked = comment.is_disliked_by_auth || false
  const pointsToUse = 12

  const hasEnough = authStore.deductPoints(pointsToUse)
  if (!hasEnough) {
    swal.warning(`แต้มของคุณไม่เพียงพอ (ต้องการ ${pointsToUse} แต้ม)`)
    comment.isDisliking = false
    return
  }

  // Optimistic update
  comment.is_disliked_by_auth = !wasDisliked
  comment.dislikes = (comment.dislikes || 0) + (wasDisliked ? -1 : 1)

  // Remove like if was liked
  if (!wasDisliked && comment.is_liked_by_auth) {
    comment.is_liked_by_auth = false
    comment.likes = (comment.likes || 0) - 1
  }

  try {
    const config = useRuntimeConfig()
    const apiUrl = `${config.public.apiBase}/api/share-comments/${comment.id}/dislike`

    const response = await $fetch(apiUrl, {
      method: 'POST',
      headers: { Authorization: `Bearer ${authStore.token}` }
    })

    if (!response.success) {
      // Revert
      comment.is_disliked_by_auth = wasDisliked
      comment.dislikes = (comment.dislikes || 0) + (wasDisliked ? 1 : -1)
      authStore.rollback(pointsToUse)
      swal.error(response.message || 'ไม่สามารถกดไม่ถูกใจได้')
    }
  } catch (error) {
    // Revert
    comment.is_disliked_by_auth = wasDisliked
    comment.dislikes = (comment.dislikes || 0) + (wasDisliked ? 1 : -1)
    authStore.rollback(pointsToUse)
    console.error('Failed to dislike share comment:', error)
    const errorMsg = error?.data?.message || error?.message || 'เกิดข้อผิดพลาดในการกดไม่ถูกใจ'
    swal.error(errorMsg)
  } finally {
    comment.isDisliking = false
  }
}

// ======== Delete Share ========
const isDeletingShare = ref(false)
const deleteShare = async () => {
  // Get Share ID from activityable_id (new system only)
  const shareId = shareData.value?.id || props.post.activityable_id
  
  if (!shareId) {
    console.error('❌ No share ID available')
    swal.error('ไม่พบข้อมูลการแชร์ที่จะลบ')
    return
  }

  // Close menu first
  showOptionsMenu.value = false

  // Ask for confirmation using SweetAlert2
  const confirmed = await swal.confirmDelete('การแชร์นี้', 'โพสต์ต้นฉบับจะไม่ถูกลบ')
  if (!confirmed) {
    return
  }

  isDeletingShare.value = true

  try {
    const config = useRuntimeConfig()
    const apiUrl = `${config.public.apiBase}/api/shares/${shareId}`
    
    const response = await $fetch(apiUrl, {
      method: 'DELETE',
      headers: { Authorization: `Bearer ${authStore.token}` }
    })

    if (response.success) {
      // Emit event to parent to remove activity from list
      emit('delete-success', props.post.id)
      
      // Show success notification
      swal.toast('ลบการแชร์สำเร็จ', 'success')
    } else {
      swal.error(response.message || 'ไม่สามารถลบการแชร์ได้')
    }
  } catch (error) {
    console.error('❌ Failed to delete share:', error)
    const errorMsg = error?.data?.message || error?.message || 'เกิดข้อผิดพลาดในการลบการแชร์'
    swal.error(errorMsg)
  } finally {
    isDeletingShare.value = false
  }
}

// Delete Post function
const deletePost = async () => {
  if (!postData.value?.id) {
    swal.error('ไม่พบข้อมูลโพสต์ที่จะลบ')
    return
  }

  // Close menu first
  showPostOptionsMenu.value = false

  // Ask for confirmation using SweetAlert2
  const confirmed = await swal.confirmDelete('โพสต์นี้', 'การลบโพสต์จะลบรูปภาพและความคิดเห็นทั้งหมดด้วย')
  if (!confirmed) {
    return
  }

  isDeletingPost.value = true

  try {
    const config = useRuntimeConfig()
    const apiUrl = `${config.public.apiBase}/api/posts/${postData.value.id}`
    
    const response = await $fetch(apiUrl, {
      method: 'DELETE',
      headers: { Authorization: `Bearer ${authStore.token}` }
    })

    if (response.success) {
      // Emit event to parent to remove activity from list
      emit('delete-success', props.post.id)
      
      // Show success notification
      swal.toast('ลบโพสต์สำเร็จ', 'success')
    } else {
      swal.error(response.message || 'ไม่สามารถลบโพสต์ได้')
    }
  } catch (error) {
    console.error('❌ Failed to delete post:', error)
    const errorMsg = error?.data?.message || error?.message || 'เกิดข้อผิดพลาดในการลบโพสต์'
    swal.error(errorMsg)
  } finally {
    isDeletingPost.value = false
  }
}

// Open Edit Modal
const openEditModal = () => {
  showPostOptionsMenu.value = false
  showEditModal.value = true
}

// Handle Post Updated
const handlePostUpdated = (updatedPost) => {
  showEditModal.value = false
  emit('post-updated', updatedPost)
}

</script>

<template>
  <div :class="[
    isNested 
      ? 'border border-gray-200 dark:border-vikinger-dark-50/30 rounded-xl p-4 bg-gray-50 dark:bg-vikinger-dark-200/50' 
      : 'vikinger-card group hover:shadow-lg transition-shadow duration-300'
  ]">
    <!-- ========================================
         LAYOUT 1: Share Activity (Different Actor)
         Shows: Sharer header + Nested original post
         ======================================== -->
    <template v-if="isShareActivity && !isNested">
      <!-- Sharer Header -->
      <div class="flex items-center gap-3 mb-4">
        <img :src="actionBy?.avatar || `https://i.pravatar.cc/150?img=${post.id % 70}`" 
             class="w-10 h-10 rounded-full object-cover ring-2 ring-vikinger-cyan/30" 
             :alt="actionBy?.username" />
        <div class="flex-1">
          <div class="flex items-center gap-2 flex-wrap">
            <NuxtLink :to="`/profile/${actionBy?.id}`" class="font-bold text-gray-800 dark:text-white hover:text-vikinger-purple transition-colors">
              {{ actionBy?.username || 'ผู้ใช้' }}
            </NuxtLink>
            <span class="text-gray-600 dark:text-gray-400">แชร์โพสต์ของ</span>
            <NuxtLink :to="`/profile/${postAuthor?.id}`" class="font-semibold text-vikinger-cyan hover:underline">
              {{ postAuthor?.username || 'ผู้ใช้' }}
            </NuxtLink>
          </div>
          <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
            <Icon icon="fluent:share-24-regular" class="w-3.5 h-3.5" />
            <span>{{ props.post.diff_humans_created_at || 'เมื่อสักครู่' }}</span>
          </div>
        </div>
        <!-- More Options Dropdown -->
        <div class="relative">
          <button 
            @click="showOptionsMenu = !showOptionsMenu"
            class="p-2 hover:bg-gray-100 dark:hover:bg-vikinger-dark-200 rounded-lg transition-colors opacity-0 group-hover:opacity-100"
          >
            <Icon icon="fluent:more-horizontal-24-regular" class="w-5 h-5 text-gray-600 dark:text-gray-300" />
          </button>
          
          <!-- Dropdown Menu -->
          <Transition name="dropdown">
            <div 
              v-if="showOptionsMenu" 
              v-click-outside="() => showOptionsMenu = false"
              class="absolute right-0 top-full mt-2 w-48 bg-white dark:bg-vikinger-dark-100 rounded-xl shadow-lg border border-gray-200 dark:border-vikinger-dark-50/30 overflow-hidden z-50"
            >
              <!-- Delete Share (only for share owner) -->
              <button
                v-if="isOwnShare"
                @click="deleteShare"
                :disabled="isDeletingShare"
                class="w-full flex items-center gap-3 px-4 py-3 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors text-left text-red-500"
              >
                <Icon 
                  v-if="!isDeletingShare"
                  icon="fluent:delete-24-regular" 
                  class="w-5 h-5" 
                />
                <Icon 
                  v-else 
                  icon="fluent:spinner-ios-20-regular" 
                  class="w-5 h-5 animate-spin" 
                />
                <span class="text-sm font-medium">ลบการแชร์</span>
              </button>
              
              <!-- Other options can be added here -->
              <button
                class="w-full flex items-center gap-3 px-4 py-3 hover:bg-gray-50 dark:hover:bg-vikinger-dark-200 transition-colors text-left"
              >
                <Icon icon="fluent:flag-24-regular" class="w-5 h-5 text-gray-500" />
                <span class="text-sm text-gray-700 dark:text-gray-300">รายงาน</span>
              </button>
            </div>
          </Transition>
        </div>
      </div>
      
      <!-- Share Comment (if any) -->
      <p v-if="shareComment" class="text-gray-700 dark:text-gray-300 mb-4 whitespace-pre-wrap">
        {{ shareComment }}
      </p>
      
      <!-- Nested Original Post (from shareable) -->
      <FeedPost 
        v-if="shareData?.shareable" 
        :post="shareData.shareable" 
        :is-nested="true" 
      />
      
      <!-- Debug: Show if shareable is missing -->
      <div v-else class="p-4 bg-gray-100 dark:bg-vikinger-dark-100 rounded-lg text-gray-500">
        <Icon icon="fluent:warning-24-regular" class="w-5 h-5 inline-block mr-2" />
        ไม่พบโพสต์ต้นฉบับ
      </div>
      
      <!-- Share Actions (minimal) -->
      <div class="mt-4 pt-3 border-t border-gray-200 dark:border-vikinger-dark-50/30">
        <!-- Share Stats -->
        <div class="flex items-center gap-4 mb-3 text-sm text-gray-600 dark:text-gray-400">
          <span v-if="localShareLikes > 0" class="flex items-center gap-1">
            <Icon icon="fluent:thumb-like-24-filled" class="w-4 h-4 text-vikinger-purple" />
            {{ localShareLikes }}
          </span>
          <span v-if="localShareDislikes > 0" class="flex items-center gap-1">
            <Icon icon="fluent:thumb-dislike-24-filled" class="w-4 h-4 text-red-500" />
            {{ localShareDislikes }}
          </span>
          <span v-if="localShareComments > 0" class="flex items-center gap-1">
            <Icon icon="fluent:comment-24-filled" class="w-4 h-4 text-vikinger-cyan" />
            {{ localShareComments }}
          </span>
        </div>
        
        <!-- Share Action Buttons -->
        <div class="flex items-center gap-4">
          <button 
            @click="handleShareLike" 
            :disabled="isShareLiking"
            :class="[
              'flex items-center gap-2 transition-colors',
              localShareIsLiked 
                ? 'text-vikinger-purple dark:text-vikinger-purple' 
                : 'text-gray-500 dark:text-gray-400 hover:text-vikinger-purple'
            ]"
          >
            <Icon :icon="localShareIsLiked ? 'fluent:thumb-like-24-filled' : 'fluent:thumb-like-24-regular'" class="w-5 h-5" />
            <span class="text-sm">{{ localShareIsLiked ? 'ถูกใจแล้ว' : 'ถูกใจ' }}</span>
          </button>
          <button 
            @click="handleShareDislike" 
            :disabled="isShareDisliking"
            :class="[
              'flex items-center gap-2 transition-colors',
              localShareIsDisliked 
                ? 'text-red-500 dark:text-red-500' 
                : 'text-gray-500 dark:text-gray-400 hover:text-red-500'
            ]"
          >
            <Icon :icon="localShareIsDisliked ? 'fluent:thumb-dislike-24-filled' : 'fluent:thumb-dislike-24-regular'" class="w-5 h-5" />
            <span class="text-sm">{{ localShareIsDisliked ? 'ไม่ถูกใจแล้ว' : 'ไม่ถูกใจ' }}</span>
          </button>
          <button 
            @click="toggleShareComments" 
            class="flex items-center gap-2 text-gray-500 dark:text-gray-400 hover:text-vikinger-cyan transition-colors"
          >
            <Icon icon="fluent:comment-24-regular" class="w-5 h-5" />
            <span class="text-sm">แสดงความคิดเห็น</span>
          </button>
        </div>

        <!-- Share Comments Section -->
        <div v-if="showShareComments" class="mt-4 space-y-3">
          <!-- Add Comment Box -->
          <div class="flex gap-2">
            <img 
              :src="authStore.user?.avatar || `https://i.pravatar.cc/150`" 
              class="w-8 h-8 rounded-full object-cover flex-shrink-0" 
              alt="Your avatar"
            />
            <div class="flex-1 relative">
              <textarea
                v-model="newShareComment"
                @keydown.ctrl.enter="submitShareComment"
                placeholder="แสดงความคิดเห็น... (Ctrl+Enter เพื่อส่ง)"
                class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-vikinger-dark-50/30 bg-white dark:bg-vikinger-dark-100 text-gray-800 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-vikinger-purple resize-none"
                rows="2"
              ></textarea>
              <button
                @click="submitShareComment"
                :disabled="!newShareComment.trim() || isSubmittingShareComment"
                class="absolute bottom-2 right-2 p-1.5 rounded-lg bg-vikinger-purple text-white hover:bg-vikinger-purple/90 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
              >
                <Icon 
                  v-if="!isSubmittingShareComment"
                  icon="fluent:send-24-filled" 
                  class="w-4 h-4" 
                />
                <Icon 
                  v-else
                  icon="fluent:spinner-ios-20-regular" 
                  class="w-4 h-4 animate-spin" 
                />
              </button>
            </div>
          </div>

          <!-- Comments List -->
          <div v-if="displayedComments.length > 0" class="space-y-3">
            <div 
              v-for="comment in displayedComments" 
              :key="comment.id"
              class="flex gap-3 group"
            >
              <img 
                :src="comment.user?.avatar || `https://i.pravatar.cc/150?img=${comment.id}`" 
                class="w-10 h-10 flex-shrink-0 aspect-square rounded-full object-cover" 
                :alt="comment.user?.username"
              />
              <div class="flex-1">
                <div class="bg-gray-100 dark:bg-vikinger-dark-200 rounded-2xl p-3">
                  <h6 class="font-semibold text-sm text-gray-800 dark:text-white">
                    {{ comment.user?.username || 'Unknown' }}
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
                  <span>{{ comment.diff_humans_created_at || 'เมื่อสักครู่' }}</span>
                  <button 
                    @click="handleShareCommentLike(comment)"
                    :disabled="comment.isLiking || authStore.user?.id === comment.user?.id"
                    :class="[
                      'flex items-center gap-1 font-medium transition-colors',
                      comment.is_liked_by_auth ? 'text-vikinger-purple' : 'hover:text-vikinger-purple',
                      (authStore.user?.id === comment.user?.id) ? 'opacity-50 cursor-not-allowed' : ''
                    ]"
                  >
                    <Icon :icon="comment.is_liked_by_auth ? 'fluent:thumb-like-20-filled' : 'fluent:thumb-like-20-regular'" class="w-3.5 h-3.5" />
                    <span>{{ comment.is_liked_by_auth ? 'ถูกใจแล้ว' : 'ถูกใจ' }}</span>
                  </button>
                  <button 
                    @click="handleShareCommentDislike(comment)"
                    :disabled="comment.isDisliking || authStore.user?.id === comment.user?.id"
                    :class="[
                      'flex items-center gap-1 font-medium transition-colors',
                      comment.is_disliked_by_auth ? 'text-red-500' : 'hover:text-red-500',
                      (authStore.user?.id === comment.user?.id) ? 'opacity-50 cursor-not-allowed' : ''
                    ]"
                  >
                    <Icon :icon="comment.is_disliked_by_auth ? 'fluent:thumb-dislike-20-filled' : 'fluent:thumb-dislike-20-regular'" class="w-3.5 h-3.5" />
                    <span>{{ comment.is_disliked_by_auth ? 'ไม่ถูกใจแล้ว' : 'ไม่ถูกใจ' }}</span>
                  </button>
                  <button 
                    v-if="comment.user?.id === authStore.user?.id"
                    @click="deleteShareComment(comment.id)"
                    class="flex items-center gap-1 hover:text-red-500 font-medium transition-colors"
                  >
                    <Icon icon="fluent:delete-24-regular" class="w-3.5 h-3.5" />
                    <span>ลบ</span>
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- No Comments Message -->
          <div v-else-if="displayedComments.length === 0" class="text-center py-4 text-gray-500 dark:text-gray-400">
            ยังไม่มีความคิดเห็น เป็นคนแรกที่แสดงความคิดเห็น!
          </div>

          <!-- Load More Comments -->
          <button
            v-if="hasMoreComments && !isLoadingComments"
            @click="loadMoreComments"
            class="w-full py-2 text-sm text-vikinger-purple hover:bg-vikinger-purple/10 rounded-lg transition-colors flex items-center justify-center gap-2"
          >
            <Icon icon="fluent:arrow-down-24-regular" class="w-4 h-4" />
            ดูความคิดเห็นก่อนหน้า ({{ remainingCommentsCount }} รายการ)
          </button>

          <!-- Loading More Indicator -->
          <div v-if="isLoadingComments" class="flex justify-center py-3">
            <Icon icon="fluent:spinner-ios-20-regular" class="w-5 h-5 animate-spin text-vikinger-purple" />
            <span class="ml-2 text-sm text-gray-500">กำลังโหลด...</span>
          </div>
        </div>
      </div>
    </template>
    
    <!-- ========================================
         LAYOUT 2: Same Actor (Create, Donate, etc.)
         Shows: Author with inline action badge
         ======================================== -->
    <template v-else>
      <!-- Post Header -->
      <div class="flex items-center justify-between mb-4">
        <div class="flex items-center gap-3">
          <!-- Avatar with badge -->
          <div class="relative flex-shrink-0">
            <img :src="postAuthor?.avatar || `https://i.pravatar.cc/150?img=${post.id % 70}`" 
                 class="w-12 h-12 aspect-square rounded-full object-cover ring-2 ring-vikinger-purple/30 group-hover:ring-vikinger-purple transition-all duration-300" 
                 :alt="postAuthor?.username" />
            <!-- Post Type Badge -->
            <div v-if="postTypeBadge && !isNested" :class="[postTypeBadge.color, 'absolute -bottom-1 -right-1 w-5 h-5 rounded-full flex items-center justify-center shadow-sm']">
              <Icon :icon="postTypeBadge.icon" class="w-3 h-3 text-white" />
            </div>
          </div>
          <div>
            <div class="flex items-center gap-2 flex-wrap">
              <NuxtLink :to="`/profile/${postAuthor?.id}`" class="font-bold text-gray-800 dark:text-white hover:text-vikinger-purple cursor-pointer transition-colors">
                {{ postAuthor?.username || 'Unknown User' }}
              </NuxtLink>
              <Icon v-if="postAuthor?.verified" icon="fluent:checkmark-circle-24-filled" class="w-4 h-4 text-green-500" />
              
              <!-- Feeling/Activity Display -->
              <span v-if="feelingDisplay" class="text-gray-600 dark:text-gray-400 text-sm">
                — {{ feelingDisplay }}
              </span>
              
              <!-- Inline Action Text (for same actor activities) -->
              <template v-if="isActivity && isSameActor && actionTextShort">
                <span class="text-gray-500 dark:text-gray-400">{{ actionTextShort }}</span>
                <span v-if="modelTypeText" class="text-vikinger-cyan font-medium">{{ modelTypeText }}</span>
              </template>
              
              <!-- Context Badge (Course/Academy) with Links -->
              <div v-if="contextInfo" class="flex items-center gap-1 px-2 py-0.5 rounded-full bg-gray-100 dark:bg-vikinger-dark-200 text-xs">
                <Icon :icon="contextInfo.icon" :class="['w-3.5 h-3.5', contextInfo.color]" />
                <NuxtLink 
                  v-if="contextInfo.link" 
                  :to="contextInfo.link" 
                  :class="['hover:underline font-medium transition-colors', contextInfo.color]"
                >
                  {{ contextInfo.name }}
                </NuxtLink>
                <span v-else class="text-gray-600 dark:text-gray-300">{{ contextInfo.name }}</span>
                <template v-if="contextInfo.academy">
                  <span class="text-gray-400">•</span>
                  <NuxtLink 
                    v-if="contextInfo.academyLink" 
                    :to="contextInfo.academyLink" 
                    class="text-purple-500 hover:underline font-medium transition-colors"
                  >
                    {{ contextInfo.academy }}
                  </NuxtLink>
                  <span v-else class="text-gray-400">{{ contextInfo.academy }}</span>
                </template>
              </div>
            </div>
            <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
              <span class="flex items-center gap-1">
                <Icon :icon="privacyIcon" class="w-3.5 h-3.5" />
                {{ createdTime }}
              </span>
              <!-- Location -->
              <span v-if="location" class="flex items-center gap-1 text-vikinger-cyan">
                <Icon icon="fluent:location-24-regular" class="w-3.5 h-3.5" />
                {{ location }}
              </span>
            </div>
          </div>
        </div>
        
        <!-- More Options -->
        <div v-if="!isNested" class="relative">
          <button 
            @click="showPostOptionsMenu = !showPostOptionsMenu"
            class="p-2 hover:bg-gray-100 dark:hover:bg-vikinger-dark-200 rounded-lg transition-colors opacity-0 group-hover:opacity-100"
          >
            <Icon icon="fluent:more-horizontal-24-regular" class="w-5 h-5 text-gray-600 dark:text-gray-300" />
          </button>
          
          <!-- Dropdown Menu -->
          <Transition name="dropdown">
            <div 
              v-if="showPostOptionsMenu" 
              v-click-outside="() => showPostOptionsMenu = false"
              class="absolute right-0 top-full mt-2 w-48 bg-white dark:bg-vikinger-dark-100 rounded-xl shadow-lg border border-gray-200 dark:border-vikinger-dark-50/30 overflow-hidden z-50"
            >
              <!-- Edit Post (only for post owner) -->
              <button
                v-if="isOwnPost"
                @click="openEditModal"
                class="w-full flex items-center gap-3 px-4 py-3 hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors text-left text-blue-600"
              >
                <Icon 
                  icon="fluent:edit-24-regular" 
                  class="w-5 h-5" 
                />
                <span class="text-sm font-medium">แก้ไขโพสต์</span>
              </button>
              
              <!-- Delete Post (only for post owner) -->
              <button
                v-if="isOwnPost"
                @click="deletePost"
                :disabled="isDeletingPost"
                class="w-full flex items-center gap-3 px-4 py-3 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors text-left text-red-500"
              >
                <Icon 
                  v-if="!isDeletingPost"
                  icon="fluent:delete-24-regular" 
                  class="w-5 h-5" 
                />
                <Icon 
                  v-else 
                  icon="fluent:spinner-ios-20-regular" 
                  class="w-5 h-5 animate-spin" 
                />
                <span class="text-sm font-medium">ลบโพสต์</span>
              </button>
              
              <!-- Report option -->
              <button
                class="w-full flex items-center gap-3 px-4 py-3 hover:bg-gray-50 dark:hover:bg-vikinger-dark-200 transition-colors text-left"
              >
                <Icon icon="fluent:flag-24-regular" class="w-5 h-5 text-gray-500" />
                <span class="text-sm text-gray-700 dark:text-gray-300">รายงาน</span>
              </button>
            </div>
          </Transition>
        </div>
      </div>

      <!-- Donation Amount Display (for Donate posts) -->
      <div v-if="contextInfo?.amount && !isNested" class="mb-4 p-4 bg-gradient-to-r from-pink-50 to-purple-50 dark:from-pink-900/20 dark:to-purple-900/20 rounded-xl">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-full bg-gradient-to-r from-pink-500 to-purple-500 flex items-center justify-center">
              <Icon icon="fluent:heart-24-filled" class="w-6 h-6 text-white" />
            </div>
            <div>
              <p class="text-sm text-gray-600 dark:text-gray-400">{{ contextInfo.type === 'donate' ? 'จำนวนบริจาค' : 'จำนวนที่ได้รับ' }}</p>
              <p class="text-2xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-pink-500 to-purple-500">
                {{ contextInfo.amount }}
              </p>
            </div>
          </div>
          <Icon :icon="contextInfo.icon" class="w-16 h-16 text-pink-200 dark:text-pink-900/50" />
        </div>
      </div>

      <!-- Post Content -->
      <div :class="isNested ? 'mb-2' : 'mb-4'">
        <h4 v-if="postData.title" class="text-lg font-bold mb-2 text-gray-800 dark:text-white">{{ postData.title }}</h4>
        <p class="text-gray-700 dark:text-gray-300 whitespace-pre-wrap leading-relaxed">{{ displayContent }}</p>
        <button 
          v-if="shouldTruncate" 
          @click="isContentExpanded = !isContentExpanded"
          class="text-vikinger-purple hover:underline text-sm font-medium mt-1"
        >
          {{ isContentExpanded ? 'แสดงน้อยลง' : 'อ่านเพิ่มเติม' }}
        </button>
        
        <!-- Hashtags -->
        <div v-if="hashtags.length" class="flex flex-wrap gap-2 mt-3">
          <span 
            v-for="tag in hashtags" 
            :key="tag" 
            class="text-sm text-vikinger-purple hover:text-vikinger-cyan hover:underline cursor-pointer transition-colors"
          >
            #{{ tag }}
          </span>
        </div>
        
        <!-- Image Gallery -->
        <div v-if="images.length" :class="['mt-4 rounded-xl overflow-hidden', isNested ? 'max-h-64' : '']">
          <!-- Single Image -->
          <div v-if="images.length === 1" @click="openImage(0)" class="cursor-pointer">
            <img 
              :src="images[0].url || images[0]" 
              :class="['w-full object-cover hover:scale-[1.02] transition-transform duration-300', isNested ? 'max-h-64' : 'max-h-[500px]']" 
              alt="Post image" 
            />
          </div>
          
          <!-- Two Images -->
          <div v-else-if="images.length === 2" class="grid grid-cols-2 gap-1">
            <img 
              v-for="(image, index) in images" 
              :key="index" 
              :src="image.url || image" 
              :class="['w-full object-cover cursor-pointer hover:opacity-90 transition-opacity', isNested ? 'h-32' : 'h-64']" 
              alt="Post image"
              @click="openImage(index)"
            />
          </div>
          
          <!-- Three+ Images -->
          <div v-else class="grid grid-cols-2 gap-1 relative">
            <img 
              v-for="(image, index) in images.slice(0, 4)" 
              :key="index" 
              :src="image.url || image" 
              :class="['w-full object-cover cursor-pointer hover:opacity-90 transition-opacity', isNested ? 'h-24' : 'h-40', { 'brightness-50': index === 3 && images.length > 4 }]" 
              alt="Post image"
              @click="openImage(index)"
            />
            <div v-if="images.length > 4" class="absolute bottom-2 right-2 bg-black/60 text-white px-3 py-1 rounded-full text-sm font-medium">
              +{{ images.length - 4 }}
            </div>
          </div>
        </div>
        
        <!-- Media (Video/Audio) -->
        <div v-if="postData.media" class="mt-4 rounded-xl overflow-hidden">
          <video v-if="postData.media.type === 'video'" controls class="w-full rounded-xl">
            <source :src="postData.media.url" />
          </video>
          <audio v-else-if="postData.media.type === 'audio'" controls class="w-full">
            <source :src="postData.media.url" />
          </audio>
        </div>
      </div>

      <!-- Post Stats -->
      <div :class="[
        'flex items-center justify-between border-t border-gray-200 dark:border-vikinger-dark-50/30 text-gray-500 dark:text-gray-400',
        isNested ? 'py-2 text-xs border-b-0' : 'py-3 text-sm border-b'
      ]">
        <div class="flex items-center gap-4">
          <span v-if="!isNested" class="flex items-center gap-1.5 hover:text-vikinger-purple cursor-pointer transition-colors">
            <Icon icon="fluent:eye-24-regular" class="w-4 h-4" />
            <span class="font-medium">{{ views }}</span>
          </span>
          <span 
            :class="[
              'flex items-center gap-1.5 transition-colors',
              isNested ? '' : 'hover:text-vikinger-cyan cursor-pointer'
            ]"
            @click="!isNested && toggleComments()"
          >
            <Icon icon="fluent:comment-24-regular" :class="isNested ? 'w-3.5 h-3.5' : 'w-4 h-4'" />
            <span class="font-medium">{{ localCommentsCount }}</span>
          </span>
          <span 
            :class="[
              'flex items-center gap-1.5 transition-colors',
              isNested ? '' : 'hover:text-vikinger-purple cursor-pointer',
              { 'text-vikinger-purple': localIsLiked && !isNested }
            ]"
            @click="!isNested && handleLike()"
          >
            <Icon :icon="localIsLiked ? 'fluent:thumb-like-24-filled' : 'fluent:thumb-like-24-regular'" :class="isNested ? 'w-3.5 h-3.5' : 'w-4 h-4'" />
            <span class="font-medium">{{ localLikes }}</span>
          </span>
          <span 
            :class="[
              'flex items-center gap-1.5 transition-colors',
              isNested ? '' : 'hover:text-red-500 cursor-pointer',
              { 'text-red-500': localIsDisliked && !isNested }
            ]"
            @click="!isNested && handleDislike()"
          >
            <Icon :icon="localIsDisliked ? 'fluent:thumb-dislike-24-filled' : 'fluent:thumb-dislike-24-regular'" :class="isNested ? 'w-3.5 h-3.5' : 'w-4 h-4'" />
            <span class="font-medium">{{ localDislikes }}</span>
          </span>
          <span v-if="!isNested" class="flex items-center gap-1.5 hover:text-vikinger-green cursor-pointer transition-colors">
            <Icon icon="fluent:share-24-regular" class="w-4 h-4" />
            <span class="font-medium">{{ localShares }}</span>
          </span>
        </div>
        
        <!-- View Original Post Link (for nested posts) -->
        <NuxtLink 
          v-if="isNested && postData.post_url"
          :to="postData.post_url"
          class="flex items-center gap-1.5 text-vikinger-purple hover:text-vikinger-cyan transition-colors"
        >
          <span class="text-xs font-medium">ดูโพสต์ต้นฉบับ</span>
          <Icon icon="fluent:arrow-right-24-regular" class="w-3.5 h-3.5" />
        </NuxtLink>
      </div>

      <!-- Post Actions (only for non-nested posts) -->
      <div v-if="!isNested" :class="[
        'flex items-center gap-2',
        isNested ? 'mt-2' : 'mt-4'
      ]">
        <!-- Like Button -->
        <button 
          @click="handleLike"
          :disabled="isLiking || isOwnPost"
          class="flex-1 flex items-center justify-center gap-2 py-2.5 rounded-lg transition-all duration-300"
          :class="localIsLiked 
            ? 'bg-vikinger-purple/10 text-vikinger-purple' 
            : isOwnPost 
              ? 'opacity-50 cursor-not-allowed text-gray-400 dark:text-gray-600'
              : 'hover:bg-gray-100 dark:hover:bg-vikinger-dark-200 text-gray-600 dark:text-gray-300'"
        >
          <Icon 
            v-if="!isLiking"
            :icon="localIsLiked ? 'fluent:thumb-like-24-filled' : 'fluent:thumb-like-24-regular'" 
            class="w-5 h-5 transition-transform hover:scale-110"
          />
          <Icon v-else icon="fluent:spinner-ios-20-regular" class="w-5 h-5 animate-spin" />
          <span class="text-sm font-medium">{{ localIsLiked ? 'ถูกใจแล้ว' : 'ถูกใจ' }}</span>
        </button>
        
        <!-- Dislike Button -->
        <button 
          @click="handleDislike"
          :disabled="isDisliking || isOwnPost"
          class="flex-1 flex items-center justify-center gap-2 py-2.5 rounded-lg transition-all duration-300"
          :class="localIsDisliked 
            ? 'bg-red-500/10 text-red-500' 
            : isOwnPost
              ? 'opacity-50 cursor-not-allowed text-gray-400 dark:text-gray-600'
              : 'hover:bg-gray-100 dark:hover:bg-vikinger-dark-200 text-gray-600 dark:text-gray-300'"
        >
          <Icon 
            v-if="!isDisliking"
            :icon="localIsDisliked ? 'fluent:thumb-dislike-24-filled' : 'fluent:thumb-dislike-24-regular'" 
            class="w-5 h-5 transition-transform hover:scale-110"
          />
          <Icon v-else icon="fluent:spinner-ios-20-regular" class="w-5 h-5 animate-spin" />
          <span class="text-sm font-medium">{{ localIsDisliked ? 'ไม่ถูกใจแล้ว' : 'ไม่ถูกใจ' }}</span>
        </button>
        
        <!-- Comment Button -->
        <button 
          @click="toggleComments" 
          class="flex-1 flex items-center justify-center gap-2 py-2.5 rounded-lg hover:bg-gray-100 dark:hover:bg-vikinger-dark-200 transition-colors group"
        >
          <Icon icon="fluent:comment-24-regular" class="w-5 h-5 text-gray-600 dark:text-gray-300 group-hover:text-vikinger-cyan transition-colors" />
          <span class="text-sm font-medium text-gray-700 dark:text-gray-300">ความคิดเห็น</span>
        </button>
        
        <!-- Share Button (Hybrid) -->
        <div class="flex-1 relative">
          <button 
            @click="showShareMenu = !showShareMenu"
            :disabled="isSharing || isOwnPost"
            class="w-full flex items-center justify-center gap-2 py-2.5 rounded-lg transition-colors group"
            :class="isOwnPost 
              ? 'opacity-50 cursor-not-allowed text-gray-400 dark:text-gray-600'
              : 'hover:bg-gray-100 dark:hover:bg-vikinger-dark-200'"
          >
            <Icon 
              v-if="!isSharing"
              icon="fluent:share-24-regular" 
              class="w-5 h-5 text-gray-600 dark:text-gray-300 group-hover:text-vikinger-green transition-colors" 
            />
            <Icon v-else icon="fluent:spinner-ios-20-regular" class="w-5 h-5 animate-spin" />
            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">แชร์</span>
          </button>
          
          <!-- Share Menu -->
          <Transition name="dropdown">
            <div v-if="showShareMenu && !isOwnPost" class="absolute bottom-full left-0 right-0 mb-2 bg-white dark:bg-vikinger-dark-100 rounded-xl shadow-lg border border-gray-200 dark:border-vikinger-dark-50/30 overflow-hidden z-20">
              <button
                @click="handleQuickShare"
                class="w-full flex items-center gap-3 px-4 py-3 hover:bg-gray-50 dark:hover:bg-vikinger-dark-200 transition-colors text-left"
              >
                <Icon icon="fluent:flash-24-regular" class="w-5 h-5 text-vikinger-green" />
                <div>
                  <p class="text-sm font-medium text-gray-800 dark:text-white">แชร์เลย</p>
                  <p class="text-xs text-gray-500 dark:text-gray-400">แชร์ทันที - 36 แต้ม</p>
                </div>
              </button>
              <button
                @click="handleShareWithOptions"
                class="w-full flex items-center gap-3 px-4 py-3 hover:bg-gray-50 dark:hover:bg-vikinger-dark-200 transition-colors text-left"
              >
                <Icon icon="fluent:edit-24-regular" class="w-5 h-5 text-vikinger-purple" />
                <div>
                  <p class="text-sm font-medium text-gray-800 dark:text-white">แชร์พร้อมความคิดเห็น</p>
                  <p class="text-xs text-gray-500 dark:text-gray-400">เพิ่มข้อความและตั้งค่า</p>
                </div>
              </button>
            </div>
          </Transition>
        </div>
      </div>

      <!-- Reactions Display -->
      <div v-if="post.reactions && !isNested" class="flex items-center gap-2 mt-4 pt-4 border-t border-gray-200 dark:border-vikinger-dark-50/30">
        <div class="flex -space-x-1">
          <div v-for="reaction in reactions.slice(0, 3)" :key="reaction.id" 
               class="w-7 h-7 rounded-full bg-white dark:bg-vikinger-dark-200 flex items-center justify-center text-sm border-2 border-white dark:border-vikinger-dark-100 shadow-sm">
            {{ reaction.icon }}
          </div>
        </div>
        <span class="text-sm text-gray-500 dark:text-gray-400 font-medium">{{ post.reactions.total || '10+' }} คนถูกใจ</span>
      </div>

      <!-- Comments Section -->
      <div v-if="showComments && !isNested" class="mt-4 pt-4 border-t border-gray-200 dark:border-vikinger-dark-50/30 space-y-4">
        <!-- Add Comment -->
        <div class="flex gap-3">
          <img :src="authStore.user?.avatar || 'https://i.pravatar.cc/150?img=99'" class="w-10 h-10 flex-shrink-0 aspect-square rounded-full object-cover" alt="You" />
          <div class="flex-1 flex gap-2">
            <input 
              v-model="newComment"
              type="text" 
              placeholder="เขียนความคิดเห็น..." 
              class="flex-1 px-4 py-2.5 rounded-full bg-gray-100 dark:bg-vikinger-dark-200 border-none outline-none text-gray-800 dark:text-white focus:ring-2 focus:ring-vikinger-purple/30 transition-all"
              :disabled="isCommenting"
              @keydown.enter="addComment"
            />
            <button 
              @click="addComment" 
              :disabled="isCommenting || !newComment.trim()"
              class="p-2.5 rounded-full bg-gradient-to-r from-vikinger-purple to-vikinger-cyan text-white hover:shadow-lg transition-all duration-300 hover:scale-105 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <Icon v-if="!isCommenting" icon="fluent:send-24-filled" class="w-5 h-5" />
              <Icon v-else icon="fluent:spinner-ios-20-regular" class="w-5 h-5 animate-spin" />
            </button>
          </div>
        </div>

        <!-- Existing Comments -->
        <div v-if="displayedComments.length" class="space-y-3">
          <div v-for="comment in displayedComments" :key="comment.id" class="flex gap-3 group">
            <img :src="comment.user?.avatar || comment.author?.avatar || `https://i.pravatar.cc/150?img=${comment.id}`" 
                 class="w-10 h-10 flex-shrink-0 aspect-square rounded-full object-cover" 
                 :alt="comment.user?.username || comment.author?.username" />
            <div class="flex-1">
              <div class="bg-gray-100 dark:bg-vikinger-dark-200 rounded-2xl p-3">
                <h6 class="font-semibold text-sm text-gray-800 dark:text-white">{{ comment.user?.username || comment.author?.username }}</h6>
                <p class="text-sm text-gray-700 dark:text-gray-300 mt-1">{{ comment.content }}</p>
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
              <div class="flex flex-wrap items-center gap-x-2 gap-y-1 mt-1.5 text-xs text-gray-500 dark:text-gray-400 px-2">
                <span class="text-[11px]">{{ comment.create_at || comment.diff_humans_created_at || comment.createdAt || 'เมื่อสักครู่' }}</span>
                <span class="text-gray-300 dark:text-gray-600">•</span>
                <button 
                  @click="handleCommentLike(comment)"
                  :disabled="comment.isLiking || authStore.user?.id === (comment.user?.id || comment.author?.id)"
                  :class="[
                    'flex items-center gap-1 font-medium transition-colors px-1.5 py-0.5 rounded-md hover:bg-gray-100 dark:hover:bg-vikinger-dark-300',
                    comment.isLikedByAuth ? 'text-vikinger-purple bg-vikinger-purple/10' : 'hover:text-vikinger-purple',
                    (authStore.user?.id === (comment.user?.id || comment.author?.id)) ? 'opacity-50 cursor-not-allowed' : ''
                  ]"
                >
                  <Icon :icon="comment.isLikedByAuth ? 'fluent:thumb-like-20-filled' : 'fluent:thumb-like-20-regular'" class="w-3.5 h-3.5" />
                  <span class="hidden sm:inline">{{ comment.isLikedByAuth ? 'ถูกใจแล้ว' : 'ถูกใจ' }}</span>
                </button>
                <button 
                  @click="handleCommentDislike(comment)"
                  :disabled="comment.isDisliking || authStore.user?.id === (comment.user?.id || comment.author?.id)"
                  :class="[
                    'flex items-center gap-1 font-medium transition-colors px-1.5 py-0.5 rounded-md hover:bg-gray-100 dark:hover:bg-vikinger-dark-300',
                    comment.isDislikedByAuth ? 'text-red-500 bg-red-500/10' : 'hover:text-red-500',
                    (authStore.user?.id === (comment.user?.id || comment.author?.id)) ? 'opacity-50 cursor-not-allowed' : ''
                  ]"
                >
                  <Icon :icon="comment.isDislikedByAuth ? 'fluent:thumb-dislike-20-filled' : 'fluent:thumb-dislike-20-regular'" class="w-3.5 h-3.5" />
                  <span class="hidden sm:inline">{{ comment.isDislikedByAuth ? 'ไม่ถูกใจ' : 'ไม่ถูกใจ' }}</span>
                </button>
                <button 
                  @click="startReply(comment)"
                  class="flex items-center gap-1 font-medium transition-colors px-1.5 py-0.5 rounded-md hover:bg-gray-100 dark:hover:bg-vikinger-dark-300 hover:text-vikinger-purple"
                >
                  <Icon icon="fluent:arrow-reply-20-regular" class="w-3.5 h-3.5" />
                  <span class="hidden sm:inline">ตอบกลับ</span>
                </button>
                <!-- View Replies Toggle -->
                <button 
                  v-if="comment.replies_count > 0"
                  @click="toggleReplies(comment)"
                  class="flex items-center gap-1 font-medium transition-colors px-1.5 py-0.5 rounded-md hover:bg-vikinger-cyan/10 text-vikinger-cyan"
                >
                  <Icon 
                    :icon="expandedReplies[comment.id] ? 'fluent:chevron-up-20-regular' : 'fluent:chevron-down-20-regular'" 
                    class="w-3.5 h-3.5" 
                  />
                  <span class="hidden xs:inline">{{ expandedReplies[comment.id] ? 'ซ่อน' : '' }}</span>
                  <span>{{ comment.replies_count }}</span>
                  <span class="hidden sm:inline">การตอบกลับ</span>
                </button>
              </div>

              <!-- Reply Input Form (shown when replying to this comment) -->
              <div 
                v-if="replyingTo?.id === comment.id" 
                class="mt-2 ml-2 flex gap-2"
              >
                <img 
                  :src="authStore.user?.avatar || `https://i.pravatar.cc/150?u=${authStore.user?.id}`" 
                  class="w-8 h-8 rounded-full object-cover flex-shrink-0"
                  alt="You"
                />
                <div class="flex-1 flex gap-2">
                  <input
                    :id="`reply-input-${comment.id}`"
                    v-model="replyContent"
                    type="text"
                    :placeholder="`ตอบกลับ ${comment.user?.username || comment.author?.username}...`"
                    class="flex-1 px-3 py-2 text-sm rounded-full bg-gray-100 dark:bg-vikinger-dark-300 border-none focus:ring-2 focus:ring-vikinger-purple/50 placeholder-gray-400 dark:text-white"
                    @keydown.enter="submitReply(comment)"
                    @keydown.escape="cancelReply"
                  />
                  <button 
                    @click="submitReply(comment)"
                    :disabled="isSubmittingReply || !replyContent.trim()"
                    class="p-2 rounded-full bg-gradient-to-r from-vikinger-purple to-vikinger-cyan text-white hover:shadow-lg transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                  >
                    <Icon v-if="!isSubmittingReply" icon="fluent:send-20-filled" class="w-4 h-4" />
                    <Icon v-else icon="fluent:spinner-ios-20-regular" class="w-4 h-4 animate-spin" />
                  </button>
                  <button 
                    @click="cancelReply"
                    class="p-2 text-gray-500 hover:text-red-500 transition-colors"
                  >
                    <Icon icon="fluent:dismiss-20-regular" class="w-4 h-4" />
                  </button>
                </div>
              </div>

              <!-- Replies Section -->
              <div 
                v-if="expandedReplies[comment.id]" 
                class="mt-3 ml-4 pl-4 border-l-2 border-gray-200 dark:border-vikinger-dark-300 space-y-3"
              >
                <!-- Loading Replies -->
                <div v-if="loadingReplies[comment.id]" class="flex items-center gap-2 py-2">
                  <Icon icon="fluent:spinner-ios-20-regular" class="w-4 h-4 animate-spin text-vikinger-purple" />
                  <span class="text-xs text-gray-500">กำลังโหลดการตอบกลับ...</span>
                </div>

                <!-- Reply Items -->
                <template v-else>
                  <div 
                    v-for="reply in (commentReplies[comment.id] || [])" 
                    :key="reply.id" 
                    class="flex gap-2"
                  >
                    <img 
                      :src="reply.user?.avatar || `https://i.pravatar.cc/150?img=${reply.id}`" 
                      class="w-8 h-8 flex-shrink-0 rounded-full object-cover"
                      :alt="reply.user?.username"
                    />
                    <div class="flex-1">
                      <div class="bg-gray-100 dark:bg-vikinger-dark-300 rounded-xl p-2.5">
                        <h6 class="font-semibold text-xs text-gray-800 dark:text-white">{{ reply.user?.username }}</h6>
                        <p class="text-xs text-gray-700 dark:text-gray-300 mt-0.5">{{ reply.content }}</p>
                      </div>
                      
                      <!-- Reply Stats -->
                      <div v-if="reply.likes || reply.dislikes" class="flex items-center gap-2 mt-1 px-2 text-[10px] text-gray-500">
                        <span v-if="reply.likes" class="flex items-center gap-0.5">
                          <Icon icon="fluent:thumb-like-16-filled" class="w-2.5 h-2.5 text-vikinger-purple" />
                          {{ reply.likes }}
                        </span>
                        <span v-if="reply.dislikes" class="flex items-center gap-0.5">
                          <Icon icon="fluent:thumb-dislike-16-filled" class="w-2.5 h-2.5 text-red-500" />
                          {{ reply.dislikes }}
                        </span>
                      </div>

                      <!-- Reply Actions -->
                      <div class="flex flex-wrap items-center gap-x-2 gap-y-0.5 mt-1 text-[10px] text-gray-500 px-2">
                        <span>{{ reply.create_at || 'เมื่อสักครู่' }}</span>
                        <span class="text-gray-300 dark:text-gray-600">•</span>
                        <button 
                          @click="handleReplyLike(reply)"
                          :disabled="reply.isLiking || authStore.user?.id === reply.user?.id"
                          :class="[
                            'flex items-center gap-0.5 font-medium transition-colors px-1 py-0.5 rounded hover:bg-gray-100 dark:hover:bg-vikinger-dark-400',
                            reply.isLikedByAuth ? 'text-vikinger-purple' : 'hover:text-vikinger-purple'
                          ]"
                        >
                          <Icon :icon="reply.isLikedByAuth ? 'fluent:thumb-like-16-filled' : 'fluent:thumb-like-16-regular'" class="w-3 h-3" />
                          <span class="hidden sm:inline">{{ reply.isLikedByAuth ? 'ถูกใจแล้ว' : 'ถูกใจ' }}</span>
                        </button>
                        <button 
                          @click="handleReplyDislike(reply)"
                          :disabled="reply.isDisliking || authStore.user?.id === reply.user?.id"
                          :class="[
                            'flex items-center gap-0.5 font-medium transition-colors px-1 py-0.5 rounded hover:bg-gray-100 dark:hover:bg-vikinger-dark-400',
                            reply.isDislikedByAuth ? 'text-red-500' : 'hover:text-red-500'
                          ]"
                        >
                          <Icon :icon="reply.isDislikedByAuth ? 'fluent:thumb-dislike-16-filled' : 'fluent:thumb-dislike-16-regular'" class="w-3 h-3" />
                          <span class="hidden sm:inline">{{ reply.isDislikedByAuth ? 'ไม่ถูกใจ' : 'ไม่ถูกใจ' }}</span>
                        </button>
                      </div>
                    </div>
                  </div>

                  <!-- Load More Replies -->
                  <button 
                    v-if="repliesPagination[comment.id]?.hasMore"
                    @click="loadMoreReplies(comment)"
                    class="text-xs text-vikinger-purple hover:text-vikinger-cyan font-medium transition-colors flex items-center gap-1"
                  >
                    <Icon icon="fluent:arrow-down-20-regular" class="w-3 h-3" />
                    โหลดการตอบกลับเพิ่มเติม
                  </button>
                </template>
              </div>
            </div>
          </div>
        </div>

        <!-- No Comments Message -->
        <div v-else-if="displayedComments.length === 0" class="text-center py-4 text-gray-500 dark:text-gray-400">
          ยังไม่มีความคิดเห็น เป็นคนแรกที่แสดงความคิดเห็น!
        </div>

        <!-- Load More Comments Button (at bottom for loading older comments) -->
        <button 
          v-if="hasMoreComments && !isLoadingComments"
          @click="loadMoreComments"
          class="w-full py-2 text-sm text-vikinger-purple hover:text-vikinger-cyan font-medium transition-colors flex items-center justify-center gap-2"
        >
          <Icon icon="fluent:arrow-down-24-regular" class="w-4 h-4" />
          ดูความคิดเห็นก่อนหน้า ({{ remainingCommentsCount }} รายการ)
        </button>

        <!-- Loading More Indicator -->
        <div v-if="isLoadingComments" class="flex justify-center py-3">
          <Icon icon="fluent:spinner-ios-20-regular" class="w-5 h-5 animate-spin text-vikinger-purple" />
          <span class="ml-2 text-sm text-gray-500">กำลังโหลด...</span>
        </div>
      </div>
    </template>

    <!-- Share Modal -->
    <ShareModal 
      v-if="!isNested"
      :show="showShareModal" 
      :post="postData" 
      @close="showShareModal = false" 
      @share="handleShareSubmit" 
    />

    <!-- Edit Post Modal -->
    <EditPostModal 
      v-if="!isNested && isOwnPost"
      :show="showEditModal" 
      :post="postData" 
      @close="showEditModal = false" 
      @post-updated="handlePostUpdated" 
    />

    <!-- Image Lightbox Modal -->
    <ImageLightbox 
      v-if="!isNested"
      :show="selectedImageIndex !== null"
      :images="images"
      :initial-index="selectedImageIndex || 0"
      :post-id="postData?.id"
      @close="closeImageModal"
    />

  </div>
</template>

<style scoped>
/* Reaction Picker Animation */
.reaction-picker-enter-active,
.reaction-picker-leave-active {
  transition: all 0.2s ease;
}

.reaction-picker-enter-from,
.reaction-picker-leave-to {
  opacity: 0;
  transform: translateY(10px) scale(0.95);
}

/* Fade Animation for Lightbox */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

/* Dropdown Animation */
.dropdown-enter-active,
.dropdown-leave-active {
  transition: all 0.2s ease;
}

.dropdown-enter-from,
.dropdown-leave-to {
  opacity: 0;
  transform: translateY(10px);
}
</style>
