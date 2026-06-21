<script setup>
import { Icon } from '@iconify/vue'
import { ref, computed, watch, nextTick } from 'vue'
import { useRoute } from 'vue-router'
import { useAuthStore } from '~/stores/auth'
import { getAcademyGroupTypeMeta, GROUP_TYPE_COLOR_CLASSES } from '~/constants/academyGroupTypes'
import ShareModal from '~/components/share/ShareModal.vue'
import EditPostModal from '~/components/play/feed/EditPostModal.vue'
import ImageLightbox from '~/components/play/feed/ImageLightbox.vue'
import PollCard from '~/components/play/poll/PollCard.vue'

const authStore = useAuthStore()
const toast = useToast()
const api = useApi()
const swal = useSweetAlert()
const { getAvatarUrl } = useAvatar()

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
    'receive_donation': 'ได้รับการสนับสนุน',
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
    'receive_donation': 'ได้รับการสนับสนุน',
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
      name: 'ได้รับการสนับสนุนจาก ' + (data.donation?.donor_name || 'ไม่ประสงค์ออกนาม'),
      link: data.donation?.donor?.id ? `/profile/${data.donation.donor.id}` : null,
      points: data.points_received || 240, // แสดงแต้มที่ได้รับแทนเงิน
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

const route = useRoute()

// Group that this post is "in the name of" - null when posted as user
const groupAuthor = computed(() => {
  const g = postData.value?.posted_as_group ?? postData.value?.activityable?.posted_as_group ?? null
  if (!g || !g.id) return null
  return {
    id: g.id,
    name: g.name,
    type: g.type,
    typeMeta: g.type_meta || getAcademyGroupTypeMeta(g.type) || null,
  }
})

const isGroupPost = computed(() => groupAuthor.value !== null)

const isDirector = computed(() => postData.value?.post_type === 'director')

const audienceLabel = computed(() => {
  const arr = postData.value?.target_audience || []
  if (!arr.length || arr.includes('all')) return null
  const map = {
    student: 'นักเรียน',
    teacher: 'ครู',
    parent: 'ผู้ปกครอง',
    staff: 'บุคลากร',
  }
  return arr.map(a => map[a] || a).join(' · ')
})

const eventData = computed(() => {
  if (postData.value?.post_type !== 'event') return null
  const d = postData.value?.embed_data || {}
  if (!d.event_date) return null
  const date = new Date(d.event_date)
  
  const formatTime = (iso) => {
    if (!iso) return ''
    return new Date(iso).toLocaleTimeString('th-TH', { hour: '2-digit', minute: '2-digit' })
  }
  
  return {
    ...d,
    dateObj: date,
    day: String(date.getDate()).padStart(2, '0'),
    monthShort: ['ม.ค.','ก.พ.','มี.ค.','เม.ย.','พ.ค.','มิ.ย.','ก.ค.','ส.ค.','ก.ย.','ต.ค.','พ.ย.','ธ.ค.'][date.getMonth()],
    timeRange: d.event_end
      ? `${formatTime(d.event_date)} - ${formatTime(d.event_end)}`
      : formatTime(d.event_date),
  }
})

const isEvent = computed(() => eventData.value !== null)

const progressData = computed(() => {
  if (postData.value?.post_type !== 'attendance') return null
  const d = postData.value?.embed_data
  if (!d?.current || !d?.total) return null
  const pct = Math.round((d.current / d.total) * 100)
  return { ...d, pct }
})

const academyName = computed(() => {
  if (route.params.name) return String(route.params.name)
  if (props.post.academy?.name) return props.post.academy.name
  if (props.post.activityable?.academy?.name) return props.post.activityable.academy.name
  return ''
})

const groupLink = computed(() => {
  if (!groupAuthor.value) return ''
  const name = academyName.value
  if (name) {
    return `/academies/${name}/groups/${groupAuthor.value.id}`
  }
  return `/groups/${groupAuthor.value.id}`
})

// Action by (for activities, this is the person who performed the action)
const actionBy = computed(() => {
  return props.post.action_by || postAuthor.value
})

// Avatar computed properties using useAvatar composable
const postAuthorAvatar = computed(() => getAvatarUrl(postAuthor.value))
const actionByAvatar = computed(() => getAvatarUrl(actionBy.value))
const currentUserAvatar = computed(() => getAvatarUrl(authStore.user))

// Get avatar for comment/reply author
const getCommentAvatar = (comment) => getAvatarUrl(comment?.user || comment?.author)

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

// Poll-related computed properties
const hasPoll = computed(() => {
  return actionTo.value === 'Poll' || postData.value.poll !== undefined
})

const pollData = computed(() => {
  if (actionTo.value === 'Poll') {
    return postData.value
  }
  return postData.value.poll || null
})

const isPollOwner = computed(() => {
  return authStore.user?.id === pollData.value?.user_id || authStore.user?.id === postAuthor.value?.id
})

// Determine if this activity should be rendered as a standalone PollCard (not nested in PostCard)
// This happens when:
// 1. The activity type is 'Poll' directly, OR
// 2. The post has a poll and post_type is 'poll'
const isPollOnlyActivity = computed(() => {
  // Direct Poll activity
  if (actionTo.value === 'Poll') return true
  
  // Post with poll where post_type is 'poll' (meaning the poll is the main content)
  if (postData.value.post_type === 'poll' && postData.value.poll) return true
  
  return false
})

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
  
  // For DonateRecipient type - ไม่แสดงข้อความซ้ำ เพราะมีกล่องแสดงแต้มแยกอยู่แล้ว
  if (actionTo.value === 'DonateRecipient') {
    const donation = postData.value.donation
    // แสดงแค่ notes ของ donation ถ้ามี ไม่ต้องแสดงจำนวนแต้มซ้ำ
    return donation?.notes || ''
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
const deletedCommentIds = ref(new Set())

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
    .filter(c => !deletedCommentIds.value.has(c.id))
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
  return authStore.user?.id != null && postAuthor.value?.id != null && 
    Number(authStore.user.id) === Number(postAuthor.value.id)
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
    // For Academy Posts
    else if (actionTo.value === 'AcademyPost' && postData.value.academy?.id) {
      apiUrl = `${config.public.apiBase}/api/academies/${postData.value.academy.id}/posts/${postData.value.id}/comments`
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
    // For Academy Posts
    else if (actionTo.value === 'AcademyPost' && postData.value.academy?.id) {
      apiUrl = `${config.public.apiBase}/api/academies/${postData.value.academy.id}/posts/${postData.value.id}/comments`
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

const isRegisteringEvent = ref(false)

const onRegisterEvent = async () => {
  if (!eventData.value?.event_id) return
  if (isRegisteringEvent.value) return
  isRegisteringEvent.value = true
  try {
    const name = academyName.value
    await api.call(`/api/academies/${name}/events/${eventData.value.event_id}/register`, {
      method: 'POST',
    })
    
    // Update registration status optimistically
    if (postData.value && postData.value.embed_data) {
      // Re-fetch post data or just toggle registration to show registered
      postData.value.embed_data.requires_register = false
    }
    
    swal.success('ลงทะเบียนเข้าร่วมกิจกรรมสำเร็จ')
  } catch (error) {
    const errorMsg = error?.data?.message || 'ไม่สามารถลงทะเบียนกิจกรรมได้'
    swal.error(errorMsg)
  } finally {
    isRegisteringEvent.value = false
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
    } else if (actionTo.value === 'AcademyPost' && postData.value.academy?.id) {
      apiUrl = `/api/academies/${postData.value.academy.id}/posts/${postData.value.id}/like`
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
    } else if (actionTo.value === 'AcademyPost' && postData.value.academy?.id) {
      apiUrl = `/api/academies/${postData.value.academy.id}/posts/${postData.value.id}/dislike`
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

// Delete Comment (for regular/course/academy posts)
const deleteComment = async (commentId) => {
  const confirmed = await swal.confirmDelete('ความคิดเห็นนี้')
  if (!confirmed) return

  try {
    const config = useRuntimeConfig()
    const postId = postData.value.id
    
    // Determine API endpoint
    let apiUrl = ''
    if (actionTo.value === 'CoursePost' && postData.value.course_id) {
      apiUrl = `${config.public.apiBase}/api/courses/${postData.value.course_id}/posts/${postId}/comments/${commentId}`
    } else if (actionTo.value === 'AcademyPost' && postData.value.academy?.id) {
      apiUrl = `${config.public.apiBase}/api/academies/${postData.value.academy.id}/posts/${postId}/comments/${commentId}`
    } else {
      apiUrl = `${config.public.apiBase}/api/posts/${postId}/comments/${commentId}`
    }

    const response = await $fetch(apiUrl, {
      method: 'DELETE',
      headers: { Authorization: `Bearer ${authStore.token}` }
    })

    if (response.success) {
      // Add to deleted set to hide from view
      deletedCommentIds.value.add(commentId)
      
      // Update count
      localCommentsCount.value--
      
      swal.toast('ลบความคิดเห็นสำเร็จ', 'success')
    } else {
      swal.error(response.message || 'ไม่สามารถลบความคิดเห็นได้')
    }
  } catch (error) {
    console.error('Failed to delete comment:', error)
    toast.error('เกิดข้อผิดพลาดในการลบความคิดเห็น')
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
  const itemName = actionTo.value === 'Poll' ? 'โพลนี้' : 'โพสต์นี้'
  const itemWarning = actionTo.value === 'Poll' 
    ? 'การลบโพลจะลบโหวตและความคิดเห็นทั้งหมดด้วย'
    : 'การลบโพสต์จะลบรูปภาพและความคิดเห็นทั้งหมดด้วย'
  
  const confirmed = await swal.confirmDelete(itemName, itemWarning)
  if (!confirmed) {
    return
  }

  isDeletingPost.value = true

  try {
    const config = useRuntimeConfig()
    
    // Determine the correct API endpoint based on model type
    let apiUrl = ''
    switch (actionTo.value) {
      case 'Poll':
        apiUrl = `${config.public.apiBase}/api/polls/${postData.value.id}`
        break
      case 'CoursePost':
        // For course posts, we need course_id to construct the proper URL
        const courseId = postData.value.course_id || postData.value.course?.id
        if (courseId) {
          apiUrl = `${config.public.apiBase}/api/courses/${courseId}/posts/${postData.value.id}`
        } else {
          swal.error('ไม่สามารถระบุรายวิชาของโพสต์นี้ได้')
          return
        }
        break
      case 'AcademyPost':
        const academyId = postData.value.academy_id || postData.value.academy?.id
        if (academyId) {
          apiUrl = `${config.public.apiBase}/api/academies/${academyId}/posts/${postData.value.id}`
        } else {
          swal.error('ไม่สามารถระบุสถาบันของโพสต์นี้ได้')
          return
        }
        break
      default:
        // Regular Post
        apiUrl = `${config.public.apiBase}/api/posts/${postData.value.id}`
    }
    
    const response = await $fetch(apiUrl, {
      method: 'DELETE',
      headers: { Authorization: `Bearer ${authStore.token}` }
    })

    if (response.success) {
      // Emit event to parent to remove activity from list
      emit('delete-success', props.post.id)
      
      // Show success notification
      const successMsg = actionTo.value === 'Poll' ? 'ลบโพลสำเร็จ' : 'ลบโพสต์สำเร็จ'
      swal.toast(successMsg, 'success')
    } else {
      swal.error(response.message || 'ไม่สามารถลบได้')
    }
  } catch (error) {
    console.error('❌ Failed to delete:', error)
    const errorMsg = error?.data?.message || error?.message || 'เกิดข้อผิดพลาดในการลบ'
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

// Poll event handlers
const handlePollDelete = () => {
  emit('delete-success', props.post.id)
}

const handlePollUpdate = (updatedPoll) => {
  // Update local poll data
  if (pollData.value) {
    Object.assign(pollData.value, updatedPoll)
  }
  emit('post-updated', props.post)
}

</script>

<template>
  <div :class="[
    isNested 
      ? 'rounded-lg p-2 bg-gray-50/80 dark:bg-vikinger-dark-200/50 -mx-1' 
      : isPollOnlyActivity
        ? '' 
        : 'vikinger-card group'
  ]">
    <!-- ========================================
         LAYOUT 0: Standalone Poll Activity
         Shows: PollCard directly without PostCard wrapper
         ======================================== -->
    <template v-if="isPollOnlyActivity && !isNested">
      <PollCard
        :poll="pollData"
        :show-actions="isPollOwner"
        :is-nested="false"
        @delete="handlePollDelete"
        @update="handlePollUpdate"
      />
    </template>

    <!-- ========================================
         LAYOUT 1: Share Activity (Different Actor)
         Shows: Sharer header + Nested original post
         ======================================== -->
    <template v-else-if="isShareActivity && !isNested">
      <!-- Sharer Header -->
      <div class="flex items-center gap-2 sm:gap-3 mb-3 sm:mb-4">
           <img :src="actionByAvatar"
             :alt="`Avatar of ${actionBy?.name || actionBy?.username || 'User'}`"
             class="w-9 h-9 sm:w-10 sm:h-10 rounded-full object-cover ring-2 ring-vikinger-cyan/30"
             loading="lazy"
             @error="(e) => e.target.src = '/images/default-avatar.png'" />
        <div class="flex-1 min-w-0">
          <div class="flex items-center gap-1.5 sm:gap-2 flex-wrap text-sm sm:text-base">
            <NuxtLink :to="`/profile/${actionBy?.id}`" class="font-bold text-gray-800 dark:text-white hover:text-vikinger-purple transition-colors truncate max-w-[100px] sm:max-w-none">
              {{ actionBy?.username || 'ผู้ใช้' }}
            </NuxtLink>
            <span class="text-gray-600 dark:text-gray-400 text-xs sm:text-sm">แชร์โพสต์ของ</span>
            <NuxtLink :to="`/profile/${postAuthor?.id}`" class="font-semibold text-vikinger-cyan hover:underline truncate max-w-[80px] sm:max-w-none">
              {{ postAuthor?.username || 'ผู้ใช้' }}
            </NuxtLink>
          </div>
          <div class="flex items-center gap-1.5 text-xs sm:text-sm text-gray-500 dark:text-gray-400">
            <Icon icon="fluent:share-20-regular" class="w-3 h-3 sm:w-3.5 sm:h-3.5" />
            <span>{{ props.post.diff_humans_created_at || 'เมื่อสักครู่' }}</span>
          </div>
        </div>
        <!-- More Options Dropdown -->
        <div class="relative flex-shrink-0">
          <button 
            @click.stop="showOptionsMenu = !showOptionsMenu"
            class="p-1.5 sm:p-2 hover:bg-gray-100 dark:hover:bg-vikinger-dark-200 rounded-lg transition-colors sm:opacity-0 sm:group-hover:opacity-100"
          >
            <Icon icon="fluent:more-horizontal-20-regular" class="w-5 h-5 text-gray-500 dark:text-gray-400" />
          </button>
          
          <!-- Dropdown Menu -->
          <Transition name="dropdown">
            <div 
              v-if="showOptionsMenu" 
              v-click-outside="() => showOptionsMenu = false"
              class="absolute right-0 top-full mt-2 w-44 sm:w-48 bg-white dark:bg-vikinger-dark-100 rounded-xl shadow-lg border border-gray-200 dark:border-vikinger-dark-50/30 overflow-hidden z-50"
            >
              <!-- Delete Share (only for share owner) -->
              <button
                v-if="isOwnShare"
                @click="deleteShare"
                :disabled="isDeletingShare"
                class="w-full flex items-center gap-3 px-3 sm:px-4 py-2.5 sm:py-3 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors text-left text-red-500"
              >
                <Icon 
                  v-if="!isDeletingShare"
                  icon="fluent:delete-20-regular" 
                  class="w-5 h-5" 
                />
                <Icon 
                  v-else 
                  icon="fluent:spinner-ios-16-regular" 
                  class="w-5 h-5 animate-spin" 
                />
                <span class="text-sm font-medium">ลบการแชร์</span>
              </button>
              
              <!-- Other options can be added here -->
              <button
                class="w-full flex items-center gap-3 px-3 sm:px-4 py-2.5 sm:py-3 hover:bg-gray-50 dark:hover:bg-vikinger-dark-200 transition-colors text-left"
              >
                <Icon icon="fluent:flag-20-regular" class="w-5 h-5 text-gray-500" />
                <span class="text-sm text-gray-700 dark:text-gray-300">รายงาน</span>
              </button>
            </div>
          </Transition>
        </div>
      </div>
      
      <!-- Share Comment (if any) -->
      <p v-if="shareComment" class="text-sm sm:text-base text-gray-700 dark:text-gray-300 mb-2 sm:mb-3 whitespace-pre-wrap">
        {{ shareComment }}
      </p>
      
      <!-- Nested Original Post (from shareable) -->
      <FeedPost 
        v-if="shareData?.shareable" 
        :post="shareData.shareable" 
        :is-nested="true" 
      />
      
      <!-- Debug: Show if shareable is missing -->
      <div v-else class="p-3 bg-gray-100 dark:bg-vikinger-dark-100 rounded-lg text-gray-500 text-sm">
        <Icon icon="fluent:warning-20-regular" class="w-4 h-4 inline-block mr-1.5" />
        ไม่พบโพสต์ต้นฉบับ
      </div>
      
      <!-- Share Actions (Modern Inline Style) -->
      <div class="mt-3 pt-3 border-t border-gray-100 dark:border-vikinger-dark-50/20">
        <!-- Combined Stats & Actions Row -->
        <div class="flex items-center justify-between">
          <!-- Left: Stats -->
          <div class="flex items-center gap-2 sm:gap-3 text-xs sm:text-sm text-gray-500 dark:text-gray-400">
            <span v-if="localShareLikes > 0" class="flex items-center gap-1">
              <Icon icon="fluent:thumb-like-16-filled" class="w-3.5 h-3.5 text-vikinger-purple" />
              {{ localShareLikes }}
            </span>
            <span v-if="localShareDislikes > 0" class="flex items-center gap-1">
              <Icon icon="fluent:thumb-dislike-16-filled" class="w-3.5 h-3.5 text-red-500" />
              {{ localShareDislikes }}
            </span>
            <span v-if="localShareComments > 0" class="flex items-center gap-1">
              <Icon icon="fluent:comment-16-filled" class="w-3.5 h-3.5 text-vikinger-cyan" />
              {{ localShareComments }}
            </span>
          </div>
        
          <!-- Right: Action Buttons (Inline) -->
          <div class="flex items-center gap-1">
            <button 
              @click="handleShareLike" 
              :disabled="isShareLiking"
              class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-full transition-all duration-200"
              :class="localShareIsLiked 
                ? 'bg-vikinger-purple/15 text-vikinger-purple' 
                : 'hover:bg-gray-100 dark:hover:bg-vikinger-dark-200 text-gray-500 dark:text-gray-400'"
            >
              <Icon :icon="localShareIsLiked ? 'fluent:thumb-like-20-filled' : 'fluent:thumb-like-20-regular'" class="w-[18px] h-[18px]" />
              <span class="text-xs font-medium hidden sm:inline">{{ localShareIsLiked ? 'ถูกใจแล้ว' : 'ถูกใจ' }}</span>
            </button>
            <button 
              @click="handleShareDislike" 
              :disabled="isShareDisliking"
              class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-full transition-all duration-200"
              :class="localShareIsDisliked 
                ? 'bg-red-500/15 text-red-500' 
                : 'hover:bg-gray-100 dark:hover:bg-vikinger-dark-200 text-gray-500 dark:text-gray-400'"
            >
              <Icon :icon="localShareIsDisliked ? 'fluent:thumb-dislike-20-filled' : 'fluent:thumb-dislike-20-regular'" class="w-[18px] h-[18px]" />
              <span class="text-xs font-medium hidden sm:inline">{{ localShareIsDisliked ? 'ไม่ถูกใจ' : 'ไม่ถูกใจ' }}</span>
            </button>
            <button 
              @click="toggleShareComments" 
              class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-full hover:bg-gray-100 dark:hover:bg-vikinger-dark-200 text-gray-500 dark:text-gray-400 transition-all duration-200"
            >
              <Icon icon="fluent:comment-20-regular" class="w-[18px] h-[18px]" />
              <span class="text-xs font-medium hidden sm:inline">ตอบ</span>
            </button>
          </div>
        </div>

        <!-- Share Comments Section -->
        <div v-if="showShareComments" class="mt-3 space-y-2.5">
          <!-- Add Comment Box -->
          <div class="flex gap-2">
            <img 
              :src="currentUserAvatar" 
              class="w-8 h-8 rounded-full object-cover flex-shrink-0" 
              alt="Your avatar"
              loading="lazy"
              decoding="async"
              @error="(e) => e.target.src = '/images/default-avatar.png'"
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
                :src="getCommentAvatar(comment)" 
                class="w-10 h-10 flex-shrink-0 aspect-square rounded-full object-cover" 
                :alt="comment.user?.username"
                loading="lazy"
                decoding="async"
                @error="(e) => e.target.src = '/images/default-avatar.png'"
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
                    v-if="isOwnShare || comment.user?.id === authStore.user?.id"
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
      <div v-if="isDirector" class="h-1 bg-gradient-to-r from-vikinger-purple via-vikinger-cyan to-vikinger-purple -mx-4 -mt-4 mb-3 md:-mx-5 md:-mt-5"></div>
      <!-- Post Header -->
      <div class="flex items-start justify-between mb-3 sm:mb-4">
        <div class="flex items-center gap-2 sm:gap-3 min-w-0 flex-1">
          <!-- Avatar with badge -->
          <div class="relative flex-shrink-0">
            <!-- Group Post Avatar -->
            <NuxtLink
              v-if="isGroupPost"
              :to="groupLink"
              :class="[
                'w-10 h-10 sm:w-12 sm:h-12 rounded-xl bg-gradient-to-br flex items-center justify-center shadow-md border border-white/10 flex-shrink-0 ring-2 ring-vikinger-purple/20 hover:ring-vikinger-purple transition-all duration-300',
                GROUP_TYPE_COLOR_CLASSES[getAcademyGroupTypeMeta(groupAuthor.type).color].gradient
              ]"
            >
              <Icon
                :icon="getAcademyGroupTypeMeta(groupAuthor.type).icon"
                class="w-5 h-5 sm:w-6 sm:h-6 text-white"
              />
            </NuxtLink>
            <!-- Regular User Avatar -->
            <img v-else
                 :src="postAuthorAvatar"
                 :alt="`Avatar of ${postAuthor?.name || postAuthor?.username || 'User'}`"
                 class="w-10 h-10 sm:w-12 sm:h-12 aspect-square rounded-full object-cover ring-2 ring-vikinger-purple/30 group-hover:ring-vikinger-purple transition-all duration-300"
                 loading="lazy"
                 @error="(e) => e.target.src = '/images/default-avatar.png'" />

            <!-- Post Type Badge -->
            <div v-if="!isGroupPost && postTypeBadge && !isNested" :class="[postTypeBadge.color, 'absolute -bottom-0.5 -right-0.5 w-4 h-4 sm:w-5 sm:h-5 rounded-full flex items-center justify-center shadow-sm']">
              <Icon :icon="postTypeBadge.icon" class="w-2.5 h-2.5 sm:w-3 sm:h-3 text-white" />
            </div>
          </div>
          <div class="min-w-0 flex-1">
            <div class="flex items-center gap-1.5 sm:gap-2 flex-wrap">
              <!-- Group Post Header Name -->
              <template v-if="isGroupPost">
                <NuxtLink :to="groupLink" class="font-bold text-sm sm:text-base text-gray-800 dark:text-white hover:text-vikinger-purple cursor-pointer transition-colors truncate max-w-[120px] sm:max-w-none flex items-center gap-1.5">
                  {{ groupAuthor.name }}
                  <Icon icon="heroicons:check-badge-solid" class="w-4 h-4 text-vikinger-cyan flex-shrink-0" />
                  <span
                    v-if="groupAuthor.typeMeta"
                    :class="[
                      'text-[10px] px-1.5 py-0.5 rounded-full font-bold',
                      GROUP_TYPE_COLOR_CLASSES[groupAuthor.typeMeta.color].badge,
                    ]"
                  >
                    {{ groupAuthor.typeMeta.label }}
                  </span>
                </NuxtLink>
              </template>
              <!-- Regular User Name -->
              <NuxtLink v-else :to="`/profile/${postAuthor?.id}`" class="font-bold text-sm sm:text-base text-gray-800 dark:text-white hover:text-vikinger-purple cursor-pointer transition-colors truncate max-w-[120px] sm:max-w-none">
                {{ postAuthor?.username || 'Unknown User' }}
              </NuxtLink>
              <Icon v-if="!isGroupPost && postAuthor?.verified" icon="fluent:checkmark-circle-20-filled" class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-green-500 flex-shrink-0" />
              
              <span v-if="isDirector" class="text-[10px] px-2 py-0.5 rounded-full font-bold bg-vikinger-purple/15 text-vikinger-purple flex-shrink-0">
                ฝ่ายบริหาร
              </span>
              
              <!-- Feeling/Activity Display (hidden on xs, shown on sm+) -->
              <span v-if="feelingDisplay" class="hidden sm:inline text-gray-600 dark:text-gray-400 text-xs sm:text-sm">
                — {{ feelingDisplay }}
              </span>
              
              <!-- Inline Action Text (for same actor activities) -->
              <template v-if="isActivity && isSameActor && actionTextShort">
                <span class="text-xs sm:text-sm text-gray-500 dark:text-gray-400">{{ actionTextShort }}</span>
                <span v-if="modelTypeText" class="text-xs sm:text-sm text-vikinger-cyan font-medium truncate max-w-[100px] sm:max-w-none">{{ modelTypeText }}</span>
              </template>
              
              <!-- Context Badge (Course/Academy) with Links - Responsive -->
              <div v-if="contextInfo" class="hidden sm:flex items-center gap-1 px-2 py-0.5 rounded-full bg-gray-100 dark:bg-vikinger-dark-200 text-xs">
                <Icon :icon="contextInfo.icon" :class="['w-3 h-3 sm:w-3.5 sm:h-3.5', contextInfo.color]" />
                <NuxtLink 
                  v-if="contextInfo.link" 
                  :to="contextInfo.link" 
                  :class="['hover:underline font-medium transition-colors truncate max-w-[80px]', contextInfo.color]"
                >
                  {{ contextInfo.name }}
                </NuxtLink>
                <span v-else class="text-gray-600 dark:text-gray-300 truncate max-w-[80px]">{{ contextInfo.name }}</span>
                <template v-if="contextInfo.academy">
                  <span class="text-gray-400 hidden md:inline">•</span>
                  <NuxtLink 
                    v-if="contextInfo.academyLink" 
                    :to="contextInfo.academyLink" 
                    class="hidden md:inline text-purple-500 hover:underline font-medium transition-colors truncate max-w-[80px]"
                  >
                    {{ contextInfo.academy }}
                  </NuxtLink>
                </template>
              </div>
            </div>
            <div class="flex items-center gap-1.5 sm:gap-2 text-xs sm:text-sm text-gray-500 dark:text-gray-400">
              <span v-if="isGroupPost" class="font-medium text-gray-700 dark:text-gray-300">
                โดย <NuxtLink :to="`/profile/${postAuthor?.id}`" class="hover:underline text-vikinger-purple">{{ postAuthor?.name || postAuthor?.username || 'ผู้ใช้' }}</NuxtLink>
              </span>
              <span v-if="isGroupPost" class="text-gray-300 dark:text-gray-700 font-light">•</span>
              <span class="flex items-center gap-1">
                <Icon :icon="privacyIcon" class="w-3 h-3 sm:w-3.5 sm:h-3.5" />
                {{ createdTime }}
              </span>
              <!-- Location (hidden on xs) -->
              <span v-if="location" class="hidden sm:flex items-center gap-1 text-vikinger-cyan">
                <Icon icon="fluent:location-20-regular" class="w-3 h-3 sm:w-3.5 sm:h-3.5" />
                <span class="truncate max-w-[100px]">{{ location }}</span>
              </span>
            </div>
          </div>
        </div>
        
        <!-- More Options -->
        <div v-if="!isNested" class="relative flex-shrink-0">
          <button 
            @click.stop="showPostOptionsMenu = !showPostOptionsMenu"
            class="p-1.5 sm:p-2 hover:bg-gray-100 dark:hover:bg-vikinger-dark-200 rounded-lg transition-colors sm:opacity-0 sm:group-hover:opacity-100"
          >
            <Icon icon="fluent:more-horizontal-20-regular" class="w-5 h-5 text-gray-500 dark:text-gray-400" />
          </button>
          
          <!-- Dropdown Menu -->
          <Transition name="dropdown">
            <div
              v-if="showPostOptionsMenu"
              v-click-outside="() => showPostOptionsMenu = false"
              class="absolute right-0 top-full mt-2 w-44 sm:w-48 bg-white dark:bg-vikinger-dark-100 rounded-xl shadow-lg border border-gray-200 dark:border-vikinger-dark-50/30 overflow-hidden z-50"
            >
              <!-- Poll-specific options -->
              <template v-if="hasPoll && isPollOwner">
                <!-- Edit Poll -->
                <button
                  class="w-full flex items-center gap-3 px-3 sm:px-4 py-2.5 sm:py-3 hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors text-left text-blue-600"
                >
                  <Icon icon="fluent:edit-20-regular" class="w-5 h-5" />
                  <span class="text-sm font-medium">แก้ไขโพล</span>
                </button>
                
                <!-- Close Poll -->
                <button
                  class="w-full flex items-center gap-3 px-3 sm:px-4 py-2.5 sm:py-3 hover:bg-yellow-50 dark:hover:bg-yellow-900/20 transition-colors text-left text-yellow-600"
                >
                  <Icon icon="fluent:checkmark-circle-20-regular" class="w-5 h-5" />
                  <span class="text-sm font-medium">ปิดโพล</span>
                </button>
                
                <!-- Delete Poll -->
                <button
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
                  <span class="text-sm font-medium">ลบโพล</span>
                </button>
              </template>
              
              <!-- Regular post options -->
              <template v-else>
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
              </template>
              
              <!-- Report option (always available) -->
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
      <div v-if="(contextInfo?.amount || contextInfo?.points) && !isNested" class="mb-4 p-4 bg-gradient-to-r from-pink-50 to-purple-50 dark:from-pink-900/20 dark:to-purple-900/20 rounded-xl">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-full bg-gradient-to-r from-pink-500 to-purple-500 flex items-center justify-center">
              <Icon :icon="contextInfo.type === 'donate_recipient' ? 'fluent:gift-24-filled' : 'fluent:heart-24-filled'" class="w-6 h-6 text-white" />
            </div>
            <div>
              <p class="text-sm text-gray-600 dark:text-gray-400">{{ contextInfo.type === 'donate' ? 'จำนวนบริจาค' : 'แต้มที่ได้รับ' }}</p>
              <p class="text-2xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-pink-500 to-purple-500">
                {{ contextInfo.points ? `${contextInfo.points} แต้ม` : contextInfo.amount }}
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
              :class="['w-full object-cover hover:scale-[1.02] transition-transform duration-300 motion-reduce:transition-none motion-reduce:hover:scale-100', isNested ? 'max-h-64' : 'max-h-[500px]']" 
              alt="Post image" 
              loading="lazy"
              decoding="async"
            />
          </div>
          
          <!-- Two Images -->
          <div v-else-if="images.length === 2" class="grid grid-cols-2 gap-1">
            <img 
              v-for="(image, index) in images" 
              :key="index" 
              :src="image.url || image" 
              :class="['w-full object-cover cursor-pointer hover:opacity-90 transition-opacity motion-reduce:transition-none', isNested ? 'h-32' : 'h-64']" 
              alt="Post image"
              loading="lazy"
              decoding="async"
              @click="openImage(index)"
            />
          </div>
          
          <!-- Three+ Images -->
          <div v-else class="grid grid-cols-2 gap-1 relative">
            <img 
              v-for="(image, index) in images.slice(0, 4)" 
              :key="index" 
              :src="image.url || image" 
              :class="['w-full object-cover cursor-pointer hover:opacity-90 transition-opacity motion-reduce:transition-none', isNested ? 'h-24' : 'h-40', { 'brightness-50': index === 3 && images.length > 4 }]" 
              alt="Post image"
              loading="lazy"
              decoding="async"
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

        <!-- Poll Display -->
        <div v-if="hasPoll && pollData" class="mt-4">
          <PollCard
            :poll="pollData"
            :show-actions="isPollOwner && !isNested"
            :is-nested="isNested"
            @delete="handlePollDelete"
            @update="handlePollUpdate"
          />
        </div>

        <!-- Target Audience Display -->
        <div
          v-if="audienceLabel"
          class="mt-3 inline-flex items-center gap-1.5 text-xs text-gray-500 bg-gray-100/50 dark:bg-vikinger-dark-200/50 px-2.5 py-1 rounded-full border border-gray-200/40 dark:border-gray-700/40"
        >
          <Icon icon="heroicons:user-group" class="w-4 h-4 text-vikinger-cyan" />
          <span>กลุ่มเป้าหมาย: {{ audienceLabel }}</span>
        </div>

        <!-- Event Variant Detail Card -->
        <div
          v-if="isEvent && eventData"
          class="mt-4 flex items-center gap-4 p-4 rounded-xl bg-gray-50 dark:bg-vikinger-dark-200 border border-gray-200 dark:border-gray-700/60 shadow-sm"
        >
          <!-- Date Chip -->
          <div class="w-14 text-center rounded-lg overflow-hidden shadow-sm flex-shrink-0 border border-vikinger-purple/20">
            <div class="bg-gradient-to-b from-vikinger-purple to-purple-600 text-white font-bold text-lg py-1 leading-none">{{ eventData.day }}</div>
            <div class="bg-white text-gray-700 text-xs font-bold py-1 leading-none">{{ eventData.monthShort }}</div>
          </div>

          <div class="flex-1 min-w-0">
            <!-- Time & Location info row -->
            <div class="flex flex-wrap gap-x-4 gap-y-1.5 text-xs text-gray-600 dark:text-gray-400 mb-2.5">
              <span class="inline-flex items-center gap-1">
                <Icon icon="heroicons:clock" class="w-4 h-4 text-vikinger-purple" />
                {{ eventData.timeRange }}
              </span>
              <span v-if="eventData.location" class="inline-flex items-center gap-1">
                <Icon icon="heroicons:map-pin" class="w-4 h-4 text-vikinger-cyan" />
                {{ eventData.location }}
              </span>
            </div>

            <!-- Register Button -->
            <button
              v-if="eventData.requires_register"
              type="button"
              :disabled="isRegisteringEvent"
              class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-gradient-to-r from-vikinger-purple to-purple-600 text-white text-xs font-semibold hover:from-vikinger-purple/90 hover:to-purple-600/90 shadow-sm transition-all duration-300 disabled:opacity-50"
              @click="onRegisterEvent"
            >
              <Icon v-if="!isRegisteringEvent" icon="heroicons:pencil-square" class="w-3.5 h-3.5" />
              <Icon v-else icon="svg-spinners:ring-resize" class="w-3.5 h-3.5" />
              ลงทะเบียน
            </button>
            <span
              v-else
              class="inline-flex items-center gap-1 text-xs font-semibold text-green-500 bg-green-500/10 px-2 py-1 rounded-md"
            >
              <Icon icon="heroicons:check-circle-solid" class="w-4 h-4" />
              ลงทะเบียนแล้ว
            </span>
          </div>
        </div>

        <!-- Attendance Progress Variant Card -->
        <div
          v-if="progressData"
          class="mt-4 p-4 rounded-xl bg-gray-50 dark:bg-vikinger-dark-200 border border-gray-200 dark:border-gray-700/60 shadow-sm"
        >
          <div class="flex justify-between items-baseline mb-2.5">
            <span class="text-xs font-bold text-gray-600 dark:text-gray-400">
              {{ progressData.label || 'การเข้าร่วม' }}
            </span>
            <span class="text-sm font-extrabold text-gray-900 dark:text-white">
              {{ progressData.current.toLocaleString() }}
              <span class="text-xs font-medium text-gray-400">/ {{ progressData.total.toLocaleString() }} คน</span>
            </span>
          </div>
          <div class="h-2.5 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
            <div
              class="h-full bg-gradient-to-r from-green-500 to-emerald-400 transition-all duration-500"
              :style="{ width: `${progressData.pct}%` }"
            ></div>
          </div>
          <div class="text-right text-[10px] font-bold text-gray-400 mt-1.5">{{ progressData.pct }}% ของทั้งหมด</div>
        </div>
      </div>

      <!-- Modern Post Stats & Actions Combined (Inline Style) -->
      <div :class="[
        'flex items-center justify-between mt-3 pt-3 border-t border-gray-100 dark:border-vikinger-dark-50/20',
        isNested ? 'text-xs' : 'text-sm'
      ]">
        <!-- Left Side: Stats -->
        <div class="flex items-center gap-3 sm:gap-4 text-gray-500 dark:text-gray-400">
          <span v-if="!isNested && views > 0" class="flex items-center gap-1 opacity-70">
            <Icon icon="fluent:eye-16-regular" class="w-4 h-4" />
            <span>{{ views }}</span>
          </span>
          <span v-if="localCommentsCount > 0" class="flex items-center gap-1">
            <Icon icon="fluent:comment-16-regular" :class="isNested ? 'w-3.5 h-3.5' : 'w-4 h-4'" />
            <span>{{ localCommentsCount }}</span>
          </span>
        </div>
        
        <!-- Right Side: Action Buttons (Modern Inline Style) -->
        <div v-if="!isNested" class="flex items-center gap-1">
          <!-- Reward Chip -->
          <span
            v-if="postData?.reward_points && postData.reward_points > 0"
            class="inline-flex items-center gap-1 text-xs font-bold text-amber-600 dark:text-amber-400 mr-2"
          >
            <Icon icon="heroicons:sparkles-solid" class="w-3.5 h-3.5" />
            +{{ postData.reward_points }} แต้ม
          </span>
          <!-- Like -->
          <button 
            @click="handleLike"
            :disabled="isLiking || isOwnPost"
            class="flex items-center gap-1.5 px-2.5 sm:px-3 py-1.5 rounded-full transition-all duration-200"
            :class="localIsLiked 
              ? 'bg-vikinger-purple/15 text-vikinger-purple' 
              : isOwnPost 
                ? 'opacity-40 cursor-not-allowed text-gray-400'
                : 'hover:bg-gray-100 dark:hover:bg-vikinger-dark-200 text-gray-600 dark:text-gray-300'"
          >
            <Icon 
              v-if="!isLiking"
              :icon="localIsLiked ? 'fluent:thumb-like-20-filled' : 'fluent:thumb-like-20-regular'" 
              class="w-[18px] h-[18px] sm:w-5 sm:h-5"
            />
            <Icon v-else icon="fluent:spinner-ios-16-regular" class="w-4 h-4 animate-spin" />
            <span v-if="localLikes > 0" class="text-xs sm:text-sm font-medium">{{ localLikes }}</span>
          </button>
          
          <!-- Dislike -->
          <button 
            @click="handleDislike"
            :disabled="isDisliking || isOwnPost"
            class="flex items-center gap-1.5 px-2.5 sm:px-3 py-1.5 rounded-full transition-all duration-200"
            :class="localIsDisliked 
              ? 'bg-red-500/15 text-red-500' 
              : isOwnPost
                ? 'opacity-40 cursor-not-allowed text-gray-400'
                : 'hover:bg-gray-100 dark:hover:bg-vikinger-dark-200 text-gray-600 dark:text-gray-300'"
          >
            <Icon 
              v-if="!isDisliking"
              :icon="localIsDisliked ? 'fluent:thumb-dislike-20-filled' : 'fluent:thumb-dislike-20-regular'" 
              class="w-[18px] h-[18px] sm:w-5 sm:h-5"
            />
            <Icon v-else icon="fluent:spinner-ios-16-regular" class="w-4 h-4 animate-spin" />
            <span v-if="localDislikes > 0" class="text-xs sm:text-sm font-medium">{{ localDislikes }}</span>
          </button>
          
          <!-- Comment -->
          <button 
            @click="toggleComments" 
            class="flex items-center gap-1.5 px-2.5 sm:px-3 py-1.5 rounded-full hover:bg-gray-100 dark:hover:bg-vikinger-dark-200 transition-all duration-200 text-gray-600 dark:text-gray-300"
          >
            <Icon icon="fluent:comment-20-regular" class="w-[18px] h-[18px] sm:w-5 sm:h-5" />
            <span class="text-xs sm:text-sm font-medium hidden sm:inline">ตอบ</span>
          </button>
          
          <!-- Share -->
          <div class="relative">
            <button 
              @click="showShareMenu = !showShareMenu"
              :disabled="isSharing || isOwnPost"
              class="flex items-center gap-1.5 px-2.5 sm:px-3 py-1.5 rounded-full transition-all duration-200"
              :class="isOwnPost 
                ? 'opacity-40 cursor-not-allowed text-gray-400'
                : 'hover:bg-gray-100 dark:hover:bg-vikinger-dark-200 text-gray-600 dark:text-gray-300'"
            >
              <Icon 
                v-if="!isSharing"
                icon="fluent:share-20-regular" 
                class="w-[18px] h-[18px] sm:w-5 sm:h-5" 
              />
              <Icon v-else icon="fluent:spinner-ios-16-regular" class="w-4 h-4 animate-spin" />
              <span v-if="localShares > 0" class="text-xs sm:text-sm font-medium">{{ localShares }}</span>
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
        
        <!-- View Original (nested only) -->
        <NuxtLink 
          v-if="isNested && postData.post_url"
          :to="postData.post_url"
          class="flex items-center gap-1 text-vikinger-purple hover:text-vikinger-cyan transition-colors ml-auto"
        >
          <span class="text-xs font-medium">ดูต้นฉบับ</span>
          <Icon icon="fluent:arrow-right-16-regular" class="w-3.5 h-3.5" />
        </NuxtLink>
      </div>

      <!-- Comments Section -->
      <div v-if="showComments && !isNested" class="mt-3 pt-3 border-t border-gray-100 dark:border-vikinger-dark-50/20 space-y-3">
        <!-- Add Comment -->
        <div class="flex gap-2">
          <img :src="currentUserAvatar" class="w-8 h-8 sm:w-10 sm:h-10 flex-shrink-0 aspect-square rounded-full object-cover" alt="Your avatar" loading="lazy" @error="(e) => e.target.src = '/images/default-avatar.png'" />
          <div class="flex-1 flex gap-1.5">
            <input 
              v-model="newComment"
              type="text" 
              placeholder="เขียนความคิดเห็น..." 
              class="flex-1 w-full min-w-0 px-3 py-2 text-sm rounded-full bg-gray-100 dark:bg-vikinger-dark-200 border-none outline-none text-gray-800 dark:text-white focus:ring-2 focus:ring-vikinger-purple/30 transition-all"
              :disabled="isCommenting"
              @keydown.enter="addComment"
            />
            <button 
              @click="addComment" 
              :disabled="isCommenting || !newComment.trim()"
              class="flex-shrink-0 p-2 rounded-full bg-gradient-to-r from-vikinger-purple to-vikinger-cyan text-white hover:shadow-lg transition-all duration-300 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <Icon v-if="!isCommenting" icon="fluent:send-20-filled" class="w-4 h-4 sm:w-5 sm:h-5" />
              <Icon v-else icon="fluent:spinner-ios-16-regular" class="w-4 h-4 animate-spin" />
            </button>
          </div>
        </div>

        <!-- Existing Comments -->
        <div v-if="displayedComments.length" class="space-y-2.5">
          <div v-for="comment in displayedComments" :key="comment.id" class="flex gap-2 group">
            <img :src="getCommentAvatar(comment)" 
                 class="w-8 h-8 flex-shrink-0 aspect-square rounded-full object-cover" 
                 :alt="comment.user?.username || comment.author?.username"
                 loading="lazy"
                 decoding="async"
                 @error="(e) => e.target.src = '/images/default-avatar.png'" />
            <div class="flex-1 min-w-0">
              <div class="bg-gray-100 dark:bg-vikinger-dark-200 rounded-2xl px-3 py-2">
                <h6 class="font-semibold text-xs sm:text-sm text-gray-800 dark:text-white">{{ comment.user?.username || comment.author?.username }}</h6>
                <p class="text-xs sm:text-sm text-gray-700 dark:text-gray-300 mt-0.5 break-words">{{ comment.content }}</p>
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
                <button 
                  v-if="isOwnPost || authStore.user?.id === (comment.user?.id || comment.author?.id)"
                  @click="deleteComment(comment.id)"
                  class="flex items-center gap-1 font-medium transition-colors px-1.5 py-0.5 rounded-md hover:bg-gray-100 dark:hover:bg-vikinger-dark-300 hover:text-red-500"
                >
                  <Icon icon="fluent:delete-20-regular" class="w-3.5 h-3.5" />
                  <span class="hidden sm:inline">ลบ</span>
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
                  :src="currentUserAvatar" 
                  class="w-8 h-8 rounded-full object-cover flex-shrink-0"
                  alt="You"
                  loading="lazy"
                  decoding="async"
                  @error="(e) => e.target.src = '/images/default-avatar.png'"
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
                      :src="getCommentAvatar(reply)" 
                      class="w-8 h-8 flex-shrink-0 rounded-full object-cover"
                      :alt="reply.user?.username"
                      loading="lazy"
                      decoding="async"
                      @error="(e) => e.target.src = '/images/default-avatar.png'"
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
