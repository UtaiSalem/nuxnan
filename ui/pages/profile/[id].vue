<script setup lang="ts">
import { Icon } from '@iconify/vue'
import BaseCard from '~/components/atoms/BaseCard.vue'
import FeedPost from '~/components/play/feed/FeedPost.vue'
import ProfileCompletionWidget from '~/components/organisms/ProfileCompletionWidget.vue'
import CreatePostBox from '~/components/play/feed/CreatePostBox.vue'
import FriendsList from '~/components/profile/FriendsList.vue'
import PhotosGallery from '~/components/profile/PhotosGallery.vue'
import BadgesDisplay from '~/components/profile/BadgesDisplay.vue'
import FriendRequestsWidget from '~/components/profile/FriendRequestsWidget.vue'
import ProfileAboutSection from '~/components/profile/ProfileAboutSection.vue'
import GroupsList from '~/components/profile/GroupsList.vue'
import EventsList from '~/components/profile/EventsList.vue'
import VideosList from '~/components/profile/VideosList.vue'
import type { UserProfile, FriendshipStatus } from '~/composables/useProfile'

definePageMeta({
  layout: 'main',
  middleware: 'auth'
})

const route = useRoute()
const { fetchUserProfile, fetchMyProfile } = useProfile()
const { sendFriendRequest, acceptFriendRequest, cancelFriendRequest, unfriend } = useFriends()
const authStore = useAuthStore()
const toast = useToast()

// State
const profile = ref<UserProfile | null>(null)
const friendshipStatus = ref<FriendshipStatus | null>(null)
const canViewFullProfile = ref(true)
const isOwnProfile = ref(false)
const isLoading = ref(true)
const activeTab = ref('timeline')
const activities = ref<any[]>([])

// Activities pagination
const activitiesPage = ref(1)
const activitiesLastPage = ref(1)
const isLoadingMore = ref(false)
const hasMoreActivities = computed(() => activitiesPage.value < activitiesLastPage.value)

// Tabs carousel
const tabsContainer = ref<HTMLElement | null>(null)
const canScrollLeft = ref(false)
const canScrollRight = ref(true)

// Edit Cover & Avatar
const showCoverModal = ref(false)
const showAvatarModal = ref(false)
const coverFileInput = ref<HTMLInputElement | null>(null)
const avatarFileInput = ref<HTMLInputElement | null>(null)
const coverPreview = ref<string | null>(null)
const avatarPreview = ref<string | null>(null)
const isUploadingCover = ref(false)
const isUploadingAvatar = ref(false)

// Get user ID or reference code from route
const referenceCode = computed(() => route.params.id as string)

// Check if viewing own profile
const isViewingOwnProfile = computed(() => {
  if (!authStore.user) return false
  return referenceCode.value === 'me' || 
         referenceCode.value === authStore.user.reference_code ||
         referenceCode.value === String(authStore.user.id)
})

// Load profile data
const loadProfile = async () => {
  isLoading.value = true
  try {
    if (isViewingOwnProfile.value) {
      // Fetch own profile
      const data = await fetchMyProfile()
      if (data) {
        profile.value = data
        isOwnProfile.value = true
      }
    } else {
      // Fetch other user's profile
      const result = await fetchUserProfile(referenceCode.value)
      if (result) {
        profile.value = result.profile
        friendshipStatus.value = result.friendshipStatus
        canViewFullProfile.value = result.canViewFullProfile
        isOwnProfile.value = result.isOwnProfile
      }
    }
    
    // Load activities/posts
    if (profile.value) {
      await loadActivities()
    }
  } catch (error) {
    console.error('Error loading profile:', error)
  } finally {
    isLoading.value = false
  }
}

// Load user activities/posts
const loadActivities = async (page: number = 1) => {
  console.log('[loadActivities] Starting...', { 
    page, 
    referenceCode: referenceCode.value, 
    isViewingOwnProfile: isViewingOwnProfile.value,
    profileLoaded: !!profile.value 
  })
  
  try {
    const api = useApi()
    // API จะตรวจสอบ privacy settings ตาม:
    // - เจ้าของโปรไฟล์: เห็นได้ทุกโพสต์ (privacy 1, 2, 3)
    // - เพื่อน: เห็นโพสต์ Friends + Global (privacy 2, 3)
    // - คนอื่น: เห็นเฉพาะ Global (privacy 3)
    
    // Use actual user identifier (for 'me', use auth user's reference_code or id)
    let userIdentifier = referenceCode.value
    if (isViewingOwnProfile.value && authStore.user) {
      userIdentifier = authStore.user.reference_code || authStore.user.id
    } else if (profile.value) {
      // Use loaded profile's reference_code or user_id
      userIdentifier = profile.value.reference_code || profile.value.user_id || referenceCode.value
    }
    
    console.log('[loadActivities] Fetching activities for:', userIdentifier, 'URL:', `/api/users/${userIdentifier}/activities?page=${page}`)
    const response = await api.get(`/api/users/${userIdentifier}/activities?page=${page}`)
    console.log('[loadActivities] Response:', response)
    
    if (response.success && response.activities) {
      if (page === 1) {
        activities.value = response.activities
      } else {
        activities.value = [...activities.value, ...response.activities]
      }
      // Update pagination info
      if (response.meta) {
        activitiesPage.value = response.meta.current_page
        activitiesLastPage.value = response.meta.last_page
      }
    } else if (response.data?.activities) {
      // Fallback for different response structure
      if (page === 1) {
        activities.value = response.data.activities
      } else {
        activities.value = [...activities.value, ...response.data.activities]
      }
      if (response.data.meta) {
        activitiesPage.value = response.data.meta.current_page
        activitiesLastPage.value = response.data.meta.last_page
      }
    } else {
      if (page === 1) {
        activities.value = []
      }
    }
  } catch (error) {
    console.error('Error loading activities:', error)
    if (page === 1) {
      activities.value = []
    }
  }
}

// Load more activities (pagination)
const loadMoreActivities = async () => {
  if (isLoadingMore.value || !hasMoreActivities.value) return
  
  isLoadingMore.value = true
  try {
    await loadActivities(activitiesPage.value + 1)
  } finally {
    isLoadingMore.value = false
  }
}

// Go to edit profile
const goToEditProfile = () => {
  navigateTo('/profile/edit')
}

// Tabs carousel scroll functions
const scrollTabs = (direction: 'left' | 'right') => {
  if (!tabsContainer.value) return
  const scrollAmount = 200
  const newScrollLeft = direction === 'left' 
    ? tabsContainer.value.scrollLeft - scrollAmount
    : tabsContainer.value.scrollLeft + scrollAmount
  
  tabsContainer.value.scrollTo({
    left: newScrollLeft,
    behavior: 'smooth'
  })
}

const updateScrollButtons = () => {
  if (!tabsContainer.value) return
  const { scrollLeft, scrollWidth, clientWidth } = tabsContainer.value
  canScrollLeft.value = scrollLeft > 0
  canScrollRight.value = scrollLeft < scrollWidth - clientWidth - 5
}

// ========== Cover Photo Functions ==========
const openCoverModal = () => {
  coverPreview.value = null
  showCoverModal.value = true
}

const closeCoverModal = () => {
  showCoverModal.value = false
  coverPreview.value = null
}

const triggerCoverUpload = () => {
  coverFileInput.value?.click()
}

const handleCoverFileChange = (event: Event) => {
  const input = event.target as HTMLInputElement
  const file = input.files?.[0]
  if (!file) return
  
  // Validate file type
  if (!file.type.startsWith('image/')) {
    alert('Please select an image file')
    return
  }
  
  // Validate file size (max 5MB)
  if (file.size > 5 * 1024 * 1024) {
    alert('File size must be less than 5MB')
    return
  }
  
  // Create preview
  const reader = new FileReader()
  reader.onload = (e) => {
    coverPreview.value = e.target?.result as string
  }
  reader.readAsDataURL(file)
}

const uploadCover = async () => {
  if (!coverFileInput.value?.files?.[0]) return
  
  isUploadingCover.value = true
  try {
    const api = useApi()
    const formData = new FormData()
    formData.append('cover', coverFileInput.value.files[0])
    
    // Don't set Content-Type header - let browser set it automatically with boundary
    const response = await api.post('/api/profile/cover', formData)
    
    if (response.data?.cover_image) {
      profile.value!.cover_image = response.data.cover_image
    } else if (response.cover_image) {
      profile.value!.cover_image = response.cover_image
    }
    
    closeCoverModal()
  } catch (error) {
    console.error('Error uploading cover:', error)
    alert('Failed to upload cover photo')
  } finally {
    isUploadingCover.value = false
  }
}

// ========== Avatar Functions ==========
const openAvatarModal = () => {
  avatarPreview.value = null
  showAvatarModal.value = true
}

const closeAvatarModal = () => {
  showAvatarModal.value = false
  avatarPreview.value = null
}

const triggerAvatarUpload = () => {
  avatarFileInput.value?.click()
}

const handleAvatarFileChange = (event: Event) => {
  const input = event.target as HTMLInputElement
  const file = input.files?.[0]
  if (!file) return
  
  // Validate file type
  if (!file.type.startsWith('image/')) {
    alert('Please select an image file')
    return
  }
  
  // Validate file size (max 2MB)
  if (file.size > 2 * 1024 * 1024) {
    alert('File size must be less than 2MB')
    return
  }
  
  // Create preview
  const reader = new FileReader()
  reader.onload = (e) => {
    avatarPreview.value = e.target?.result as string
  }
  reader.readAsDataURL(file)
}

const uploadAvatar = async () => {
  if (!avatarFileInput.value?.files?.[0]) return
  
  isUploadingAvatar.value = true
  try {
    const api = useApi()
    const formData = new FormData()
    formData.append('avatar', avatarFileInput.value.files[0])
    
    // Don't set Content-Type header - let browser set it automatically with boundary
    const response = await api.post('/api/profile/avatar', formData)
    
    if (response.data?.avatar) {
      profile.value!.avatar = response.data.avatar
      // Also update auth store
      if (authStore.user) {
        authStore.user.avatar = response.data.avatar
      }
    } else if (response.avatar) {
      profile.value!.avatar = response.avatar
      if (authStore.user) {
        authStore.user.avatar = response.avatar
      }
    }
    
    closeAvatarModal()
  } catch (error) {
    console.error('Error uploading avatar:', error)
    alert('Failed to upload avatar')
  } finally {
    isUploadingAvatar.value = false
  }
}

// On mount
onMounted(async () => {
  await loadProfile()
  // Initialize scroll buttons after DOM is ready
  nextTick(() => {
    updateScrollButtons()
  })
})

// Watch for route changes
watch(() => route.params.id, async () => {
  await loadProfile()
})

// Computed properties
const displayName = computed(() => {
  if (!profile.value) return ''
  return profile.value.username || `${profile.value.first_name || ''} ${profile.value.last_name || ''}`.trim() || 'User'
})

const memberSince = computed(() => {
  if (!profile.value?.join_date) return ''
  return new Date(profile.value.join_date).toLocaleDateString('th-TH', { year: 'numeric', month: 'long' })
})

// Profile Completion data
const profileCompletionPercentage = computed(() => {
  return profile.value?.profile_completion?.percentage ?? 0
})

const profileMissingFields = computed(() => {
  return profile.value?.profile_completion?.missing_fields ?? []
})

const userQuests = computed(() => {
  // TODO: Replace with actual quests data when API is ready
  return {
    completed: profile.value?.quests_completed ?? 0,
    total: profile.value?.quests_total ?? 30
  }
})

const userBadges = computed(() => {
  // TODO: Replace with actual badges data when API is ready
  return {
    unlocked: profile.value?.badges_unlocked ?? 0,
    total: profile.value?.badges_total ?? 46
  }
})

// Format number to K/M format
const formatNumber = (num: number): string => {
  if (num >= 1000000) {
    return (num / 1000000).toFixed(1).replace(/\.0$/, '') + 'M'
  }
  if (num >= 1000) {
    return (num / 1000).toFixed(1).replace(/\.0$/, '') + 'K'
  }
  return num.toLocaleString()
}

const friendButtonConfig = computed(() => {
  if (!friendshipStatus.value) {
    return { text: 'เพิ่มเพื่อน', icon: 'fluent:person-add-24-regular', class: 'bg-vikinger-purple', action: 'add' }
  }
  
  switch (friendshipStatus.value.status) {
    case 'pending_sent':
      return { text: 'ยกเลิกคำขอ', icon: 'fluent:clock-24-regular', class: 'bg-gray-500', action: 'cancel' }
    case 'pending_received':
      return { text: 'ยอมรับคำขอ', icon: 'fluent:checkmark-24-regular', class: 'bg-green-500', action: 'accept' }
    case 'friends':
      return { text: 'เพื่อนแล้ว', icon: 'fluent:people-24-filled', class: 'bg-vikinger-cyan', action: 'unfriend' }
    default:
      return { text: 'เพิ่มเพื่อน', icon: 'fluent:person-add-24-regular', class: 'bg-vikinger-purple', action: 'add' }
  }
})

// Friend action state
const isProcessingFriend = ref(false)

// Handle friend action
const handleFriendAction = async () => {
  if (!profile.value || isProcessingFriend.value) return
  
  isProcessingFriend.value = true
  const action = friendButtonConfig.value.action
  const userId = profile.value.user_id
  
  try {
    let success = false
    
    switch (action) {
      case 'add':
        success = await sendFriendRequest(userId)
        if (success) {
          friendshipStatus.value = { status: 'pending_sent', label: 'คำขอถูกส่งแล้ว' }
          toast.success('ส่งคำขอเป็นเพื่อนแล้ว!')
        }
        break
        
      case 'cancel':
        success = await cancelFriendRequest(userId)
        if (success) {
          friendshipStatus.value = { status: 'none', label: '' }
          toast.success('ยกเลิกคำขอแล้ว')
        }
        break
        
      case 'accept':
        success = await acceptFriendRequest(userId)
        if (success) {
          friendshipStatus.value = { status: 'friends', label: 'เพื่อน' }
          toast.success('เพิ่มเพื่อนสำเร็จ!')
        }
        break
        
      case 'unfriend':
        // Show confirmation first
        if (confirm(`ต้องการเลิกเป็นเพื่อนกับ ${displayName.value} หรือไม่?`)) {
          success = await unfriend(userId)
          if (success) {
            friendshipStatus.value = { status: 'none', label: '' }
            toast.success('เลิกเป็นเพื่อนแล้ว')
          }
        }
        break
    }
    
    if (!success && action !== 'unfriend') {
      toast.error('ไม่สามารถดำเนินการได้ กรุณาลองใหม่')
    }
  } catch (error) {
    console.error('Friend action error:', error)
    toast.error('เกิดข้อผิดพลาด กรุณาลองใหม่')
  } finally {
    isProcessingFriend.value = false
  }
}

// Handle post created - add new post to activities list
const handlePostCreated = (activity: any) => {
  if (activity) {
    activities.value.unshift(activity)
  }
}

// Handle post deleted - remove from activities list
const handleDeletePost = (postId: number) => {
  activities.value = activities.value.filter((a: any) => {
    // Check if it's the activity id or the nested post id
    const activityId = a.id
    const nestedPostId = a.target_resource?.id || a.action_to_id
    return activityId !== postId && nestedPostId !== postId
  })
}

// Handle post updated - update in activities list
const handlePostUpdated = (updatedPost: any) => {
  const index = activities.value.findIndex((a: any) => {
    const activityId = a.id
    const nestedPostId = a.target_resource?.id
    return activityId === updatedPost.id || nestedPostId === updatedPost.id
  })
  
  if (index !== -1) {
    // Update the target_resource if it exists, otherwise update the whole activity
    if (activities.value[index].target_resource) {
      activities.value[index].target_resource = updatedPost
    } else {
      activities.value[index] = { ...activities.value[index], ...updatedPost }
    }
  }
}

// Country flag emoji
const countryFlag = computed(() => {
  if (!profile.value?.location) return ''
  const country = profile.value.location.toLowerCase()
  if (country.includes('thai') || country.includes('ไทย')) return '🇹🇭'
  if (country.includes('usa') || country.includes('america')) return '🇺🇸'
  return '🌍'
})

// Calculate age from birthdate
const calculateAge = (birthdate: string) => {
  const birth = new Date(birthdate)
  const today = new Date()
  let age = today.getFullYear() - birth.getFullYear()
  const monthDiff = today.getMonth() - birth.getMonth()
  if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birth.getDate())) {
    age--
  }
  return age
}

// Tabs configuration - Vikinger style with icons
const tabs = [
  { key: 'about', label: 'About', icon: 'fluent:person-info-24-regular' },
  { key: 'timeline', label: 'Timeline', icon: 'fluent:timeline-24-regular' },
  { key: 'friends', label: 'Friends', icon: 'fluent:people-24-regular' },
  { key: 'photos', label: 'Photos', icon: 'fluent:image-24-regular' },
  { key: 'videos', label: 'Videos', icon: 'fluent:video-24-regular' },
  { key: 'badges', label: 'Badges', icon: 'fluent:trophy-24-regular' },
  { key: 'groups', label: 'Groups', icon: 'fluent:people-community-24-regular' },
  { key: 'events', label: 'Events', icon: 'fluent:calendar-24-regular' },
  { key: 'blog', label: 'Blog', icon: 'fluent:document-text-24-regular' },
  { key: 'forum', label: 'Forum', icon: 'fluent:chat-multiple-24-regular' },
  { key: 'marketplace', label: 'Marketplace', icon: 'fluent:building-shop-24-regular' },
  { key: 'cart', label: 'Cart', icon: 'fluent:cart-24-regular' },
]

// Social media icons mapping
const socialIcons: Record<string, { icon: string; color: string }> = {
  facebook: { icon: 'ri:facebook-fill', color: '#1877f2' },
  twitter: { icon: 'ri:twitter-x-fill', color: '#000000' },
  instagram: { icon: 'ri:instagram-fill', color: '#e4405f' },
  linkedin: { icon: 'ri:linkedin-fill', color: '#0077b5' },
  youtube: { icon: 'ri:youtube-fill', color: '#ff0000' },
  tiktok: { icon: 'ri:tiktok-fill', color: '#000000' },
}
</script>

<template>
  <div class="max-w-6xl mx-auto">
    <!-- Loading State -->
    <template v-if="isLoading">
      <BaseCard class="bg-gray-800 border-gray-700 animate-pulse">
        <div class="h-48 md:h-64 bg-gray-700 rounded-t-xl"></div>
        <div class="pt-20 pb-6 px-6">
          <div class="h-8 bg-gray-700 rounded w-1/3 mx-auto mb-4"></div>
          <div class="h-4 bg-gray-700 rounded w-1/4 mx-auto"></div>
        </div>
      </BaseCard>
    </template>

    <!-- Profile Content -->
    <template v-else-if="profile">
      <!-- Hidden File Inputs -->
      <input 
        ref="coverFileInput"
        type="file"
        accept="image/*"
        class="hidden"
        @change="handleCoverFileChange"
      />
      <input 
        ref="avatarFileInput"
        type="file"
        accept="image/*"
        class="hidden"
        @change="handleAvatarFileChange"
      />

      <!-- Profile Header Card - Premium Gaming Style -->
      <div class="vikinger-card relative overflow-hidden mb-6 !rounded-2xl !p-0 dark:!bg-gray-900 !shadow-2xl">
        <!-- Animated Background Pattern -->
        <div class="absolute inset-0 opacity-10 pointer-events-none">
          <div class="absolute inset-0 bg-[url('/images/patterns/hexagon.svg')] bg-repeat opacity-20"></div>
          <div class="absolute top-0 right-0 w-96 h-96 bg-vikinger-purple/20 rounded-full blur-3xl animate-pulse"></div>
          <div class="absolute bottom-0 left-0 w-96 h-96 bg-vikinger-cyan/20 rounded-full blur-3xl animate-pulse delay-1000"></div>
        </div>

        <!-- Cover Photo Section -->
        <div class="relative h-48 md:h-64 overflow-hidden">
          <!-- Cover Image -->
          <div class="absolute inset-0 bg-gradient-to-br from-vikinger-purple via-purple-800 to-vikinger-cyan">
            <img 
              v-if="profile.cover_image"
              :src="profile.cover_image" 
              :alt="`${displayName}'s cover`"
              class="w-full h-full object-cover opacity-90"
            />
          </div>
          
          <!-- Gradient Overlay -->
          <div class="absolute inset-0 bg-gradient-to-t from-gray-900 via-gray-900/30 to-transparent"></div>
          
          <!-- Edit Cover Button -->
          <button 
            v-if="isOwnProfile"
            @click="openCoverModal"
            class="absolute top-4 right-4 px-4 py-2 bg-black/40 backdrop-blur-sm text-white rounded-xl hover:bg-black/60 transition-all flex items-center gap-2 border border-white/10"
          >
            <Icon icon="fluent:camera-24-regular" class="w-5 h-5" />
            <span class="hidden sm:inline text-sm font-medium">Edit Cover</span>
          </button>
        </div>

        <!-- Profile Info Section -->
        <div class="relative px-6 pb-6">
          <!-- Avatar Row -->
          <div class="flex flex-col md:flex-row items-center md:items-end gap-4 -mt-16 md:-mt-20">
            
            <!-- Avatar -->
            <div class="relative flex-shrink-0">
              <!-- Glow Effect -->
              <div class="absolute inset-0 -m-1 rounded-full bg-gradient-to-r from-vikinger-purple to-vikinger-cyan opacity-60 blur-sm animate-pulse"></div>
              
              <!-- Avatar Image -->
              <div class="relative w-28 h-28 md:w-36 md:h-36 rounded-full overflow-hidden ring-4 ring-vikinger-cyan ring-offset-4 ring-offset-gray-900 dark:ring-offset-gray-900 shadow-2xl">
                <img 
                  :src="profile.avatar || '/images/default-avatar.png'"
                  :alt="displayName"
                  class="w-full h-full object-cover"
                />
              </div>
              
              <!-- Edit Avatar Button -->
              <button 
                v-if="isOwnProfile"
                @click="openAvatarModal"
                class="absolute top-0 right-0 p-2 bg-vikinger-purple text-white rounded-full shadow-lg hover:bg-vikinger-purple/80 transition-all hover:scale-110 border-2 border-white/30"
              >
                <Icon icon="fluent:camera-24-filled" class="w-4 h-4" />
              </button>
            </div>

            <!-- Name & Action Buttons Row -->
            <div class="flex-1 flex flex-col md:flex-row items-center md:items-center justify-between gap-4 mt-2 md:mt-0">
              
              <!-- Name & Verified Badge -->
              <div class="text-center md:text-left">
                <h1 class="text-2xl md:text-3xl font-black text-gray-900 dark:text-white flex items-center justify-center md:justify-start gap-2">
                  {{ displayName }}
                  <Icon v-if="profile.is_verified" icon="fluent:checkmark-circle-24-filled" class="w-6 h-6 text-vikinger-cyan" />
                </h1>
                <p v-if="profile.title || profile.bio" class="text-gray-500 dark:text-gray-400 text-sm mt-1 max-w-md">
                  {{ profile.title || (profile.bio?.substring(0, 60) + (profile.bio?.length > 60 ? '...' : '')) }}
                </p>
              </div>

              <!-- Action Buttons -->
              <div class="flex gap-3 flex-shrink-0">
                <template v-if="!isOwnProfile">
                  <button 
                    @click="handleFriendAction"
                    :disabled="isProcessingFriend"
                    :class="[
                      'px-5 py-2.5 rounded-xl font-bold transition-all flex items-center gap-2 shadow-lg disabled:opacity-70 hover:scale-105',
                      friendshipStatus?.status === 'friends' 
                        ? 'bg-vikinger-cyan/20 text-vikinger-cyan border border-vikinger-cyan/50' 
                        : 'bg-gradient-to-r from-vikinger-purple to-vikinger-cyan text-white'
                    ]"
                  >
                    <Icon 
                      v-if="isProcessingFriend" 
                      icon="fluent:spinner-ios-20-regular" 
                      class="w-5 h-5 animate-spin" 
                    />
                    <Icon v-else :icon="friendButtonConfig.icon" class="w-5 h-5" />
                    <span class="hidden sm:inline">{{ friendButtonConfig.text }}</span>
                  </button>
                  
                  <button class="px-5 py-2.5 bg-gray-200 dark:bg-gray-800 text-gray-700 dark:text-white rounded-xl hover:bg-gray-300 dark:hover:bg-gray-700 transition-all flex items-center gap-2 font-bold">
                    <Icon icon="fluent:chat-24-regular" class="w-5 h-5" />
                    <span class="hidden sm:inline">ส่งข้อความ</span>
                  </button>
                </template>
                
                <template v-else>
                  <button 
                    @click="goToEditProfile"
                    class="px-5 py-2.5 bg-gradient-to-r from-vikinger-purple to-vikinger-cyan text-white rounded-xl hover:opacity-90 transition-all flex items-center gap-2 font-bold shadow-lg hover:scale-105"
                  >
                    <Icon icon="fluent:edit-24-regular" class="w-5 h-5" />
                    <span>แก้ไขโปรไฟล์</span>
                  </button>
                </template>
              </div>
            </div>
          </div>
        </div>

        <!-- Stats Bar -->
        <div class="border-t border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-900/50">
          <div class="flex items-center justify-center gap-4 md:gap-8 px-6 py-4">
            <!-- Posts -->
            <div class="stat-card group flex items-center gap-3 p-2 md:p-3 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-800/50 transition-all cursor-pointer">
              <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-violet-500 to-purple-600 flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform">
                <Icon icon="fluent:document-24-filled" class="w-5 h-5 text-white" />
              </div>
              <div class="text-left">
                <span class="block text-lg md:text-xl font-black text-gray-900 dark:text-white">{{ profile.posts_count || 0 }}</span>
                <span class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider">โพสต์</span>
              </div>
            </div>
            
            <!-- Friends -->
            <div class="stat-card group flex items-center gap-3 p-2 md:p-3 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-800/50 transition-all cursor-pointer">
              <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform">
                <Icon icon="fluent:people-24-filled" class="w-5 h-5 text-white" />
              </div>
              <div class="text-left">
                <span class="block text-lg md:text-xl font-black text-gray-900 dark:text-white">{{ profile.friends_count || 0 }}</span>
                <span class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider">เพื่อน</span>
              </div>
            </div>
            
            <!-- Visits -->
            <div class="stat-card group flex items-center gap-3 p-2 md:p-3 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-800/50 transition-all cursor-pointer">
              <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform">
                <Icon icon="fluent:eye-24-filled" class="w-5 h-5 text-white" />
              </div>
              <div class="text-left">
                <span class="block text-lg md:text-xl font-black text-gray-900 dark:text-white">{{ profile.visits_count || 0 }}</span>
                <span class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider">เข้าชม</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Tabs Navigation Bar - Premium Carousel Style -->
      <div class="vikinger-card relative overflow-hidden !rounded-2xl !p-0 dark:!bg-gradient-to-br dark:!from-gray-900 dark:!via-gray-800 dark:!to-gray-900 mb-6">
        <!-- Background Pattern -->
        <div class="absolute inset-0 pointer-events-none opacity-50">
          <div class="absolute inset-0 bg-[url('/images/patterns/circuit.svg')] bg-repeat opacity-5"></div>
        </div>
        
        <div class="relative flex items-center">
          <!-- Left Arrow - Enhanced -->
          <button 
            @click="scrollTabs('left')"
            :class="[
              'p-4 transition-all duration-300 flex-shrink-0 relative z-10',
              canScrollLeft 
                ? 'text-white hover:text-vikinger-cyan hover:bg-gradient-to-r hover:from-vikinger-purple/20 hover:to-transparent cursor-pointer' 
                : 'text-gray-700 cursor-not-allowed'
            ]"
            :disabled="!canScrollLeft"
          >
            <Icon icon="fluent:chevron-left-24-filled" class="w-6 h-6" />
          </button>
          
          <!-- Tab Items - Scrollable Container -->
          <div 
            ref="tabsContainer"
            @scroll="updateScrollButtons"
            class="flex-1 flex overflow-x-auto scrollbar-hide scroll-smooth py-2"
          >
            <button
              v-for="tab in tabs"
              :key="tab.key"
              @click="activeTab = tab.key"
              :class="[
                'relative flex-shrink-0 min-w-[80px] lg:min-w-[100px] flex flex-col items-center justify-center gap-2 py-4 px-5 mx-1 transition-all duration-300 group rounded-xl',
                activeTab === tab.key 
                  ? 'bg-gradient-to-br from-vikinger-purple/30 to-vikinger-cyan/20 border border-vikinger-cyan/30' 
                  : 'hover:bg-gray-800/50 border border-transparent'
              ]"
            >
              <!-- Icon Container with Effects -->
              <div class="relative">
                <!-- Glow effect on active -->
                <div 
                  v-if="activeTab === tab.key"
                  class="absolute inset-0 -m-2 bg-vikinger-cyan/20 blur-xl rounded-full animate-pulse"
                />
                
                <!-- Icon -->
                <div 
                  :class="[
                    'relative w-10 h-10 rounded-xl flex items-center justify-center transition-all duration-300',
                    activeTab === tab.key 
                      ? 'bg-gradient-to-br from-vikinger-purple to-vikinger-cyan shadow-lg scale-110' 
                      : 'bg-gray-800 group-hover:bg-gray-700 group-hover:scale-105'
                  ]"
                >
                  <Icon 
                    :icon="tab.icon" 
                    :class="[
                      'w-5 h-5 transition-all duration-300',
                      activeTab === tab.key ? 'text-white' : 'text-gray-400 group-hover:text-white'
                    ]" 
                  />
                </div>
              </div>
              
              <!-- Label -->
              <span 
                :class="[
                  'text-xs font-bold transition-all duration-300 whitespace-nowrap',
                  activeTab === tab.key 
                    ? 'text-vikinger-cyan' 
                    : 'text-gray-500 group-hover:text-white'
                ]"
              >
                {{ tab.label }}
              </span>
              
              <!-- Active Indicator Dot -->
              <div 
                v-if="activeTab === tab.key"
                class="absolute -bottom-1 left-1/2 -translate-x-1/2 w-2 h-2 bg-vikinger-cyan rounded-full shadow-lg shadow-vikinger-cyan/50"
              />
            </button>
          </div>
          
          <!-- Right Arrow - Enhanced -->
          <button 
            @click="scrollTabs('right')"
            :class="[
              'p-4 transition-all duration-300 flex-shrink-0 relative z-10',
              canScrollRight 
                ? 'text-white hover:text-vikinger-cyan hover:bg-gradient-to-l hover:from-vikinger-purple/20 hover:to-transparent cursor-pointer' 
                : 'text-gray-700 cursor-not-allowed'
            ]"
            :disabled="!canScrollRight"
          >
            <Icon icon="fluent:chevron-right-24-filled" class="w-6 h-6" />
          </button>
        </div>
      </div>

      <!-- Tab Content - 3 Column Layout like Vikinger -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        
        <!-- Left Sidebar (About, Badges, Friends) -->
        <div class="lg:col-span-3 space-y-6">
          <!-- Profile Completion Widget (Own Profile) -->
          <ProfileCompletionWidget 
            v-if="isOwnProfile" 
            :percentage="profileCompletionPercentage"
            :missing-fields="profileMissingFields"
            :quests="userQuests"
            :badges="userBadges"
            @edit-profile="goToEditProfile"
          />

          <!-- Friend Requests Widget (Own Profile) -->
          <FriendRequestsWidget v-if="isOwnProfile" />

          <!-- Points & Wallet Card - Compact Style -->
          <div class="vikinger-card vikinger-card-hover overflow-hidden !rounded-2xl !p-0">
            <!-- Header - Compact -->
            <div class="relative bg-gradient-to-r from-amber-500 via-orange-500 to-red-500 px-3 py-2.5">
              <div class="absolute inset-0 bg-[url('/images/patterns/circuit.svg')] bg-repeat opacity-10"></div>
              <h3 class="relative text-sm font-bold text-white flex items-center gap-2">
                <div class="w-6 h-6 rounded-md bg-white/20 flex items-center justify-center">
                  <Icon icon="fluent:wallet-24-filled" class="w-3.5 h-3.5" />
                </div>
                แต้ม & กระเป๋าเงิน
              </h3>
            </div>
            
            <div class="p-3 space-y-2">
              <!-- Points Row -->
              <div class="flex items-center gap-3 p-2.5 bg-gradient-to-r from-amber-50 to-orange-50 dark:from-amber-900/20 dark:to-orange-900/20 rounded-xl">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-amber-400 to-orange-500 flex items-center justify-center shadow-md flex-shrink-0">
                  <Icon icon="fluent:star-24-filled" class="w-5 h-5 text-white" />
                </div>
                <div class="flex-1 min-w-0">
                  <p class="text-[10px] text-amber-600 dark:text-amber-400 uppercase tracking-wider font-semibold">แต้มสะสม</p>
                  <p class="text-xl font-black text-gray-900 dark:text-white">{{ formatNumber(profile.points || profile.pp || 0) }}</p>
                </div>
              </div>
              
              <!-- Wallet Row -->
              <div class="flex items-center gap-3 p-2.5 bg-gradient-to-r from-emerald-50 to-green-50 dark:from-emerald-900/20 dark:to-green-900/20 rounded-xl">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-emerald-400 to-green-500 flex items-center justify-center shadow-md flex-shrink-0">
                  <Icon icon="fluent:money-24-filled" class="w-5 h-5 text-white" />
                </div>
                <div class="flex-1 min-w-0">
                  <p class="text-[10px] text-emerald-600 dark:text-emerald-400 uppercase tracking-wider font-semibold">Wallet</p>
                  <p class="text-xl font-black text-gray-900 dark:text-white">฿{{ formatNumber(profile.wallet || 0) }}</p>
                </div>
              </div>

              <!-- Quick Actions - Compact -->
              <div class="flex gap-2 pt-1">
                <button class="flex-1 py-2 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-700 dark:text-white rounded-xl text-xs font-bold flex items-center justify-center gap-1.5 transition-all">
                  <Icon icon="fluent:arrow-upload-24-regular" class="w-3.5 h-3.5 text-amber-500" />
                  เติมเงิน
                </button>
                <button class="flex-1 py-2 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-700 dark:text-white rounded-xl text-xs font-bold flex items-center justify-center gap-1.5 transition-all">
                  <Icon icon="fluent:history-24-regular" class="w-3.5 h-3.5 text-emerald-500" />
                  ประวัติ
                </button>
              </div>
            </div>
          </div>

          <!-- Level & XP Progress Card - Premium Gaming Style -->
          <div class="vikinger-card vikinger-card-hover relative overflow-hidden !rounded-2xl !p-0 dark:!bg-gradient-to-br dark:!from-gray-900 dark:!via-gray-800 dark:!to-gray-900">
            <!-- Animated Background -->
            <div class="absolute inset-0 pointer-events-none overflow-hidden">
              <div class="absolute top-0 right-0 w-60 h-60 bg-vikinger-purple/10 rounded-full blur-3xl animate-pulse"></div>
              <div class="absolute bottom-0 left-0 w-60 h-60 bg-vikinger-cyan/10 rounded-full blur-3xl animate-pulse delay-500"></div>
            </div>
            
            <!-- Header -->
            <div class="relative bg-gradient-to-r from-vikinger-purple via-purple-600 to-vikinger-cyan p-4">
              <div class="absolute inset-0 bg-[url('/images/patterns/circuit.svg')] bg-repeat opacity-10"></div>
              <div class="relative flex items-center justify-between">
                <h3 class="text-lg font-black text-white flex items-center gap-2 drop-shadow-lg">
                  <div class="w-8 h-8 rounded-lg bg-white/20 flex items-center justify-center">
                    <Icon icon="fluent:trophy-24-filled" class="w-5 h-5" />
                  </div>
                  Level & Experience
                </h3>
                <div class="flex items-center gap-2 px-4 py-1.5 bg-white/20 rounded-full backdrop-blur-sm border border-white/30">
                  <Icon icon="fluent:star-24-filled" class="w-4 h-4 text-yellow-300" />
                  <span class="text-white font-black text-sm">LV.{{ profile.level || 1 }}</span>
                </div>
              </div>
            </div>
            
            <div class="relative p-5 space-y-5">
              <!-- Level Circle Display - Enhanced -->
              <div class="flex items-center justify-center py-2">
                <div class="relative">
                  <!-- Outer Glow -->
                  <div class="absolute inset-0 -m-4 bg-gradient-to-r from-vikinger-purple to-vikinger-cyan rounded-full blur-xl opacity-30 animate-pulse"></div>
                  
                  <!-- Level Ring SVG -->
                  <svg class="relative w-36 h-36 transform -rotate-90" viewBox="0 0 100 100">
                    <!-- Background circle -->
                    <circle 
                      cx="50" cy="50" r="42" 
                      stroke="currentColor" 
                      stroke-width="6" 
                      fill="none" 
                      class="text-gray-700"
                    />
                    <!-- Progress circle with gradient -->
                    <circle 
                      cx="50" cy="50" r="42" 
                      stroke="url(#levelGradient2)" 
                      stroke-width="6" 
                      fill="none" 
                      stroke-linecap="round"
                      :stroke-dasharray="`${(profile.level_progress || 65) * 2.64} 264`"
                      class="transition-all duration-1000 drop-shadow-[0_0_10px_rgba(97,93,250,0.5)]"
                    />
                    <!-- Inner decorative circle -->
                    <circle 
                      cx="50" cy="50" r="35" 
                      stroke="currentColor" 
                      stroke-width="1" 
                      fill="none" 
                      class="text-gray-700"
                    />
                    <defs>
                      <linearGradient id="levelGradient2" x1="0%" y1="0%" x2="100%" y2="0%">
                        <stop offset="0%" style="stop-color:#9333ea" />
                        <stop offset="50%" style="stop-color:#615dfa" />
                        <stop offset="100%" style="stop-color:#23d2e2" />
                      </linearGradient>
                    </defs>
                  </svg>
                  
                  <!-- Level number in center -->
                  <div class="absolute inset-0 flex items-center justify-center">
                    <div class="text-center">
                      <span class="text-4xl font-black text-transparent bg-clip-text bg-gradient-to-r from-vikinger-purple to-vikinger-cyan">{{ profile.level || 1 }}</span>
                      <p class="text-[10px] text-gray-400 uppercase font-bold tracking-widest">LEVEL</p>
                    </div>
                  </div>
                </div>
              </div>

              <!-- XP Progress Bar - Enhanced -->
              <div class="space-y-3">
                <div class="flex justify-between text-sm">
                  <span class="text-gray-400 font-medium">Experience Points</span>
                  <span class="text-vikinger-cyan font-bold">{{ (profile.level_progress || 65) }}%</span>
                </div>
                <div class="relative h-4 bg-gray-800 rounded-full overflow-hidden border border-gray-700">
                  <!-- Progress Fill -->
                  <div 
                    class="absolute inset-y-0 left-0 bg-gradient-to-r from-vikinger-purple via-purple-500 to-vikinger-cyan rounded-full transition-all duration-1000 shadow-lg shadow-vikinger-purple/30"
                    :style="{ width: (profile.level_progress || 65) + '%' }"
                  >
                    <!-- Shimmer effect -->
                    <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/30 to-transparent animate-shimmer" />
                  </div>
                  
                  <!-- XP Text Inside Bar -->
                  <div class="absolute inset-0 flex items-center justify-center">
                    <span class="text-[10px] font-bold text-white drop-shadow-lg">
                      {{ ((profile.points || profile.pp || 0) * 0.65).toLocaleString() }} / {{ ((profile.points || profile.pp || 0) * 0.65 + 1000).toLocaleString() }} XP
                    </span>
                  </div>
                </div>
              </div>

              <!-- Next Level Reward - Enhanced -->
              <div class="relative overflow-hidden p-4 bg-gradient-to-r from-yellow-900/30 via-amber-900/20 to-yellow-900/30 rounded-xl border border-yellow-600/30">
                <!-- Shine Effect -->
                <div class="absolute inset-0 bg-gradient-to-r from-transparent via-yellow-400/5 to-transparent animate-shimmer"></div>
                
                <div class="relative flex items-center gap-4">
                  <div class="relative">
                    <div class="absolute inset-0 bg-yellow-400 rounded-xl blur-md opacity-40 animate-pulse"></div>
                    <div class="relative w-12 h-12 rounded-xl bg-gradient-to-br from-yellow-400 via-amber-500 to-orange-500 flex items-center justify-center shadow-lg border border-yellow-300/30">
                      <Icon icon="fluent:gift-24-filled" class="w-6 h-6 text-white" />
                    </div>
                  </div>
                  <div class="flex-1">
                    <p class="text-sm font-bold text-white">Next Level Reward</p>
                    <p class="text-xs text-yellow-400/80">Unlock: +500 points & special badge</p>
                  </div>
                  <Icon icon="fluent:chevron-right-24-regular" class="w-5 h-5 text-yellow-400" />
                </div>
              </div>
            </div>
          </div>

          <!-- About Me Card - Premium Glass Style -->
          <div class="vikinger-card vikinger-card-hover relative overflow-hidden !rounded-2xl dark:!bg-gradient-to-br dark:!from-gray-900 dark:!via-gray-800 dark:!to-gray-900">
            <!-- Background Pattern -->
            <div class="absolute inset-0 pointer-events-none">
              <div class="absolute top-0 right-0 w-32 h-32 bg-vikinger-cyan/5 rounded-full blur-2xl"></div>
            </div>
            
            <div class="relative">
              <!-- Header -->
              <div class="flex items-center justify-between mb-5">
                <h3 class="text-lg font-black text-white flex items-center gap-2">
                  <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-vikinger-purple to-vikinger-cyan flex items-center justify-center">
                    <Icon icon="fluent:person-info-24-filled" class="w-4 h-4 text-white" />
                  </div>
                  About Me
                </h3>
                <button class="p-2 hover:bg-gray-700/50 rounded-lg transition-colors">
                  <Icon icon="fluent:more-horizontal-24-regular" class="w-5 h-5 text-gray-400 hover:text-white" />
                </button>
              </div>
              
              <!-- Bio Text -->
              <div v-if="profile.bio" class="mb-6">
                <p class="text-gray-300 text-sm leading-relaxed">
                  {{ profile.bio }}
                </p>
              </div>
              <div v-else class="mb-6 p-4 bg-gray-800/50 rounded-xl border border-dashed border-gray-600">
                <p class="text-gray-500 text-sm text-center italic flex items-center justify-center gap-2">
                  <Icon icon="fluent:text-description-24-regular" class="w-4 h-4" />
                  No bio yet...
                </p>
              </div>

              <!-- Info List - Enhanced -->
              <div class="space-y-3">
                <div v-if="memberSince" class="flex items-center gap-4 p-3 bg-gray-800/50 rounded-xl hover:bg-gray-800 transition-colors">
                  <div class="w-10 h-10 rounded-lg bg-blue-500/20 flex items-center justify-center">
                    <Icon icon="fluent:calendar-24-regular" class="w-5 h-5 text-blue-400" />
                  </div>
                  <div class="flex-1">
                    <span class="text-xs text-gray-400 uppercase tracking-wider">เข้าร่วมเมื่อ</span>
                    <p class="text-white font-medium">{{ memberSince }}</p>
                  </div>
                </div>
                
                <div v-if="profile.location" class="flex items-center gap-4 p-3 bg-gray-800/50 rounded-xl hover:bg-gray-800 transition-colors">
                  <div class="w-10 h-10 rounded-lg bg-green-500/20 flex items-center justify-center">
                    <Icon icon="fluent:location-24-regular" class="w-5 h-5 text-green-400" />
                  </div>
                  <div class="flex-1">
                    <span class="text-xs text-gray-400 uppercase tracking-wider">ที่อยู่</span>
                    <p class="text-white font-medium">{{ profile.location }}</p>
                  </div>
                </div>
                
                <div class="flex items-center gap-4 p-3 bg-gray-800/50 rounded-xl hover:bg-gray-800 transition-colors">
                  <div class="w-10 h-10 rounded-lg bg-red-500/20 flex items-center justify-center text-xl">
                    {{ countryFlag || '🌍' }}
                  </div>
                  <div class="flex-1">
                    <span class="text-xs text-gray-400 uppercase tracking-wider">ประเทศ</span>
                    <p class="text-white font-medium">Thailand</p>
                  </div>
                </div>
                
                <div v-if="profile.birthdate" class="flex items-center gap-4 p-3 bg-gray-800/50 rounded-xl hover:bg-gray-800 transition-colors">
                  <div class="w-10 h-10 rounded-lg bg-purple-500/20 flex items-center justify-center">
                    <Icon icon="fluent:person-24-regular" class="w-5 h-5 text-purple-400" />
                  </div>
                  <div class="flex-1">
                    <span class="text-xs text-gray-400 uppercase tracking-wider">อายุ</span>
                    <p class="text-white font-medium">{{ calculateAge(profile.birthdate) }} ปี</p>
                  </div>
                </div>
                
                <div v-if="profile.website" class="flex items-center gap-4 p-3 bg-gray-800/50 rounded-xl hover:bg-gray-800 transition-colors group">
                  <div class="w-10 h-10 rounded-lg bg-cyan-500/20 flex items-center justify-center">
                    <Icon icon="fluent:globe-24-regular" class="w-5 h-5 text-cyan-400" />
                  </div>
                  <div class="flex-1">
                    <span class="text-xs text-gray-400 uppercase tracking-wider">เว็บไซต์</span>
                    <a :href="profile.website" target="_blank" class="text-vikinger-cyan font-medium hover:underline block truncate">
                      {{ profile.website.replace(/^https?:\/\//, '') }}
                    </a>
                  </div>
                  <Icon icon="fluent:open-24-regular" class="w-4 h-4 text-gray-400 group-hover:text-vikinger-cyan transition-colors" />
                </div>
              </div>
            </div>
          </div>

          <!-- Badges Card - Premium Gaming Style -->
          <div class="vikinger-card vikinger-card-hover relative overflow-hidden !rounded-2xl dark:!bg-gradient-to-br dark:!from-gray-900 dark:!via-gray-800 dark:!to-gray-900">
            <!-- Background Pattern -->
            <div class="absolute inset-0 pointer-events-none">
              <div class="absolute bottom-0 left-0 w-32 h-32 bg-yellow-500/5 rounded-full blur-2xl"></div>
            </div>
            
            <div class="relative">
              <!-- Header -->
              <div class="flex items-center justify-between mb-5">
                <h3 class="text-lg font-black text-white flex items-center gap-2">
                  <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-yellow-500 to-amber-600 flex items-center justify-center">
                    <Icon icon="fluent:trophy-24-filled" class="w-4 h-4 text-white" />
                  </div>
                  Badges
                  <span class="ml-1 px-2 py-0.5 bg-vikinger-cyan/20 text-vikinger-cyan text-xs font-bold rounded-full">{{ profile.badges_count || 0 }}</span>
                </h3>
                <button class="p-2 hover:bg-gray-700/50 rounded-lg transition-colors">
                  <Icon icon="fluent:more-horizontal-24-regular" class="w-5 h-5 text-gray-400 hover:text-white" />
                </button>
              </div>
              
              <!-- Badges Grid - Enhanced -->
              <div class="grid grid-cols-5 gap-2 mb-4">
                <div 
                  v-for="i in 10" 
                  :key="i"
                  :class="[
                    'group relative aspect-square rounded-xl flex items-center justify-center transition-all cursor-pointer hover:scale-110',
                    i <= 3 ? 'bg-gradient-to-br from-yellow-500/20 to-amber-600/20 border border-yellow-500/30' : 'bg-gray-800/50 border border-gray-700'
                  ]"
                >
                  <Icon 
                    :icon="i <= 3 ? 'fluent:trophy-24-filled' : 'fluent:trophy-24-regular'" 
                    :class="[
                      'w-6 h-6 transition-colors',
                      i <= 3 ? 'text-yellow-400' : 'text-gray-600 group-hover:text-gray-400'
                    ]" 
                  />
                  <!-- Tooltip -->
                  <div class="absolute -top-10 left-1/2 -translate-x-1/2 px-2 py-1 bg-gray-900 text-white text-xs rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap pointer-events-none z-10 border border-gray-700">
                    {{ i <= 3 ? 'Badge Unlocked!' : 'Locked' }}
                  </div>
                </div>
              </div>
              
              <!-- View All Button -->
              <button class="w-full py-2.5 px-4 bg-gray-800 hover:bg-gray-700 text-white rounded-xl text-sm font-medium flex items-center justify-center gap-2 transition-all">
                <span>View All Badges</span>
                <Icon icon="fluent:chevron-right-24-regular" class="w-4 h-4" />
              </button>
            </div>
          </div>

          <!-- Friends Preview Card - Premium Style -->
          <div class="vikinger-card vikinger-card-hover relative overflow-hidden !rounded-2xl dark:!bg-gradient-to-br dark:!from-gray-900 dark:!via-gray-800 dark:!to-gray-900">
            <!-- Background Pattern -->
            <div class="absolute inset-0 pointer-events-none">
              <div class="absolute top-0 left-0 w-32 h-32 bg-blue-500/5 rounded-full blur-2xl"></div>
            </div>
            
            <div class="relative">
              <!-- Header -->
              <div class="flex items-center justify-between mb-5">
                <h3 class="text-lg font-black text-white flex items-center gap-2">
                  <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center">
                    <Icon icon="fluent:people-24-filled" class="w-4 h-4 text-white" />
                  </div>
                  Friends
                  <span class="ml-1 px-2 py-0.5 bg-vikinger-cyan/20 text-vikinger-cyan text-xs font-bold rounded-full">{{ profile.friends_count || 0 }}</span>
                </h3>
                <button class="p-2 hover:bg-gray-700/50 rounded-lg transition-colors">
                  <Icon icon="fluent:more-horizontal-24-regular" class="w-5 h-5 text-gray-400 hover:text-white" />
                </button>
              </div>
              
              <!-- Friends Avatar Stack -->
              <div class="flex items-center -space-x-3 mb-4">
                <div v-for="i in 6" :key="i" class="relative group">
                  <div class="w-12 h-12 rounded-full bg-gradient-to-br from-gray-600 to-gray-700 border-3 border-gray-900 flex items-center justify-center overflow-hidden hover:scale-110 hover:z-10 transition-transform cursor-pointer shadow-lg">
                    <Icon icon="fluent:person-24-regular" class="w-6 h-6 text-gray-400" />
                  </div>
                </div>
                <div v-if="(profile.friends_count || 0) > 6" class="w-12 h-12 rounded-full bg-gray-800 border-3 border-gray-900 flex items-center justify-center text-xs font-bold text-gray-400 hover:bg-gray-700 cursor-pointer transition-colors">
                  +{{ (profile.friends_count || 0) - 6 }}
                </div>
              </div>
              
              <!-- Quick Stats -->
              <div class="grid grid-cols-3 gap-2 mb-4">
                <div class="text-center p-2 bg-gray-800/50 rounded-lg">
                  <p class="text-lg font-bold text-white">{{ profile.mutual_friends || 0 }}</p>
                  <p class="text-xs text-gray-400">Mutual</p>
                </div>
                <div class="text-center p-2 bg-gray-800/50 rounded-lg">
                  <p class="text-lg font-bold text-white">{{ profile.online_friends || 0 }}</p>
                  <p class="text-xs text-gray-400">Online</p>
                </div>
                <div class="text-center p-2 bg-gray-800/50 rounded-lg">
                  <p class="text-lg font-bold text-white">{{ profile.new_friends || 0 }}</p>
                  <p class="text-xs text-gray-400">New</p>
                </div>
              </div>
              
              <!-- View All Button -->
              <button 
                @click="activeTab = 'friends'"
                class="w-full py-2.5 px-4 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white rounded-xl text-sm font-medium flex items-center justify-center gap-2 transition-all"
              >
                <span>View All Friends</span>
                <Icon icon="fluent:chevron-right-24-regular" class="w-4 h-4" />
              </button>
            </div>
          </div>
        </div>

        <!-- Main Content Area (Center) -->
        <div class="lg:col-span-6 space-y-6">
          <!-- Timeline Tab -->
          <template v-if="activeTab === 'timeline'">
            <!-- Create Post (Own Profile Only) -->
            <CreatePostBox v-if="isOwnProfile" @post-created="handlePostCreated" />

            <!-- Posts -->
            <template v-if="activities.length > 0">
              <FeedPost 
                v-for="activity in activities" 
                :key="activity.id" 
                :post="activity"
                @delete-success="handleDeletePost"
                @post-updated="handlePostUpdated"
              />
              
              <!-- Load More Button -->
              <div v-if="hasMoreActivities" class="text-center py-4">
                <button 
                  @click="loadMoreActivities"
                  :disabled="isLoadingMore"
                  class="px-6 py-3 bg-gray-700 text-white rounded-lg hover:bg-gray-600 transition-colors disabled:opacity-50 flex items-center gap-2 mx-auto"
                >
                  <Icon 
                    v-if="isLoadingMore" 
                    icon="fluent:spinner-ios-20-regular" 
                    class="w-5 h-5 animate-spin" 
                  />
                  <span>{{ isLoadingMore ? 'Loading...' : 'Load More' }}</span>
                </button>
              </div>
            </template>
            <BaseCard v-else class="bg-gray-800 border-gray-700 text-center py-12">
              <Icon icon="fluent:document-24-regular" class="w-16 h-16 text-gray-600 mx-auto mb-4" />
              <p class="text-gray-400">No posts yet</p>
              <p v-if="isOwnProfile" class="text-sm text-gray-500 mt-2">
                Share your first post with friends!
              </p>
            </BaseCard>
          </template>

          <!-- About Tab -->
          <template v-if="activeTab === 'about'">
            <ProfileAboutSection 
              :profile="profile" 
              :is-own-profile="isOwnProfile" 
            />
          </template>

          <!-- Friends Tab -->
          <template v-if="activeTab === 'friends'">
            <FriendsList 
              :user-id="profile.reference_code || profile.user_id"
              :is-own-profile="isOwnProfile"
            />
          </template>

          <!-- Photos Tab -->
          <template v-if="activeTab === 'photos'">
            <PhotosGallery 
              :user-id="profile.reference_code || profile.user_id"
              :is-own-profile="isOwnProfile"
            />
          </template>

          <!-- Videos Tab -->
          <template v-if="activeTab === 'videos'">
            <VideosList 
              :user-id="profile.reference_code || profile.user_id"
              :is-own-profile="isOwnProfile"
            />
          </template>

          <!-- Badges Tab -->
          <template v-if="activeTab === 'badges'">
            <BadgesDisplay 
              :user-id="profile.reference_code || profile.user_id"
              :is-own-profile="isOwnProfile"
            />
          </template>

          <!-- Groups Tab -->
          <template v-if="activeTab === 'groups'">
            <GroupsList 
              :user-id="profile.reference_code || profile.user_id"
              :is-own-profile="isOwnProfile"
            />
          </template>

          <!-- Events Tab -->
          <template v-if="activeTab === 'events'">
            <EventsList 
              :user-id="profile.reference_code || profile.user_id"
              :is-own-profile="isOwnProfile"
            />
          </template>

          <!-- Blog Tab -->
          <template v-if="activeTab === 'blog'">
            <BaseCard class="bg-gray-800 border-gray-700 text-center py-12">
              <Icon icon="fluent:document-text-24-regular" class="w-16 h-16 text-gray-600 mx-auto mb-4" />
              <p class="text-gray-400">Blog posts coming soon</p>
            </BaseCard>
          </template>

          <!-- Forum Tab -->
          <template v-if="activeTab === 'forum'">
            <BaseCard class="bg-gray-800 border-gray-700 text-center py-12">
              <Icon icon="fluent:chat-multiple-24-regular" class="w-16 h-16 text-gray-600 mx-auto mb-4" />
              <p class="text-gray-400">Forum discussions coming soon</p>
            </BaseCard>
          </template>

          <!-- Marketplace Tab -->
          <template v-if="activeTab === 'marketplace'">
            <BaseCard class="bg-gray-800 border-gray-700 text-center py-12">
              <Icon icon="fluent:building-shop-24-regular" class="w-16 h-16 text-gray-600 mx-auto mb-4" />
              <p class="text-gray-400">Marketplace coming soon</p>
              <p class="text-gray-500 text-sm mt-2">Buy and sell items with other users</p>
            </BaseCard>
          </template>

          <!-- Cart Tab -->
          <template v-if="activeTab === 'cart'">
            <BaseCard class="bg-gray-800 border-gray-700 text-center py-12">
              <Icon icon="fluent:cart-24-regular" class="w-16 h-16 text-gray-600 mx-auto mb-4" />
              <p class="text-gray-400">Your cart is empty</p>
              <p class="text-gray-500 text-sm mt-2">Items you add to cart will appear here</p>
            </BaseCard>
          </template>
        </div>

        <!-- Right Sidebar (Photos, Groups) -->
        <div class="lg:col-span-3 space-y-6">
          <!-- Stats Card - Premium Gaming Style -->
          <div class="vikinger-card vikinger-card-hover relative overflow-hidden !rounded-2xl !p-0 dark:!bg-gradient-to-br dark:!from-gray-900 dark:!via-gray-800 dark:!to-gray-900">
            <!-- Animated Background -->
            <div class="absolute inset-0 pointer-events-none overflow-hidden">
              <div class="absolute -top-20 -right-20 w-40 h-40 bg-blue-500/10 rounded-full blur-3xl animate-pulse"></div>
              <div class="absolute -bottom-20 -left-20 w-40 h-40 bg-cyan-500/10 rounded-full blur-3xl animate-pulse delay-500"></div>
            </div>
            
            <!-- Header -->
            <div class="relative bg-gradient-to-r from-blue-600 via-indigo-600 to-cyan-500 p-4">
              <div class="absolute inset-0 bg-[url('/images/patterns/circuit.svg')] bg-repeat opacity-10"></div>
              <h3 class="relative text-lg font-black text-white flex items-center gap-2 drop-shadow-lg">
                <div class="w-8 h-8 rounded-lg bg-white/20 flex items-center justify-center">
                  <Icon icon="fluent:data-trending-24-filled" class="w-5 h-5" />
                </div>
                สถิติกิจกรรม
              </h3>
            </div>
            
            <div class="relative p-5 space-y-4">
              <!-- Stats Grid - Enhanced -->
              <div class="grid grid-cols-2 gap-3">
                <!-- Posts -->
                <div class="group relative overflow-hidden p-4 bg-gradient-to-br from-violet-900/40 to-purple-900/30 rounded-xl border border-violet-500/30 hover:border-violet-400/50 transition-all cursor-pointer hover:scale-[1.02]">
                  <div class="absolute inset-0 bg-gradient-to-r from-transparent via-violet-400/5 to-transparent -translate-x-full group-hover:translate-x-full transition-transform duration-1000"></div>
                  <div class="relative text-center">
                    <div class="w-12 h-12 mx-auto mb-3 rounded-xl bg-gradient-to-br from-violet-500 to-purple-600 flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform">
                      <Icon icon="fluent:document-24-filled" class="w-6 h-6 text-white" />
                    </div>
                    <p class="text-2xl font-black text-white">{{ profile.posts_count || 0 }}</p>
                    <p class="text-xs text-gray-400 font-medium uppercase tracking-wider">โพสต์</p>
                  </div>
                </div>
                
                <!-- Likes Received -->
                <div class="group relative overflow-hidden p-4 bg-gradient-to-br from-pink-900/40 to-rose-900/30 rounded-xl border border-pink-500/30 hover:border-pink-400/50 transition-all cursor-pointer hover:scale-[1.02]">
                  <div class="absolute inset-0 bg-gradient-to-r from-transparent via-pink-400/5 to-transparent -translate-x-full group-hover:translate-x-full transition-transform duration-1000"></div>
                  <div class="relative text-center">
                    <div class="w-12 h-12 mx-auto mb-3 rounded-xl bg-gradient-to-br from-pink-500 to-rose-600 flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform">
                      <Icon icon="fluent:heart-24-filled" class="w-6 h-6 text-white" />
                    </div>
                    <p class="text-2xl font-black text-white">{{ (profile.likes_received || 0).toLocaleString() }}</p>
                    <p class="text-xs text-gray-400 font-medium uppercase tracking-wider">ถูกใจ</p>
                  </div>
                </div>
                
                <!-- Comments -->
                <div class="group relative overflow-hidden p-4 bg-gradient-to-br from-blue-900/40 to-indigo-900/30 rounded-xl border border-blue-500/30 hover:border-blue-400/50 transition-all cursor-pointer hover:scale-[1.02]">
                  <div class="absolute inset-0 bg-gradient-to-r from-transparent via-blue-400/5 to-transparent -translate-x-full group-hover:translate-x-full transition-transform duration-1000"></div>
                  <div class="relative text-center">
                    <div class="w-12 h-12 mx-auto mb-3 rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform">
                      <Icon icon="fluent:chat-24-filled" class="w-6 h-6 text-white" />
                    </div>
                    <p class="text-2xl font-black text-white">{{ profile.comments_count || 0 }}</p>
                    <p class="text-xs text-gray-400 font-medium uppercase tracking-wider">คอมเมนต์</p>
                  </div>
                </div>
                
                <!-- Shares -->
                <div class="group relative overflow-hidden p-4 bg-gradient-to-br from-teal-900/40 to-emerald-900/30 rounded-xl border border-teal-500/30 hover:border-teal-400/50 transition-all cursor-pointer hover:scale-[1.02]">
                  <div class="absolute inset-0 bg-gradient-to-r from-transparent via-teal-400/5 to-transparent -translate-x-full group-hover:translate-x-full transition-transform duration-1000"></div>
                  <div class="relative text-center">
                    <div class="w-12 h-12 mx-auto mb-3 rounded-xl bg-gradient-to-br from-teal-500 to-emerald-600 flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform">
                      <Icon icon="fluent:share-24-filled" class="w-6 h-6 text-white" />
                    </div>
                    <p class="text-2xl font-black text-white">{{ profile.shares_count || 0 }}</p>
                    <p class="text-xs text-gray-400 font-medium uppercase tracking-wider">แชร์</p>
                  </div>
                </div>
              </div>

              <!-- Activity Streak - Enhanced -->
              <div class="relative overflow-hidden p-4 bg-gradient-to-r from-orange-900/40 via-red-900/30 to-orange-900/40 rounded-xl border border-orange-500/30">
                <!-- Fire Animation Background -->
                <div class="absolute inset-0 bg-gradient-to-t from-orange-600/10 to-transparent animate-pulse"></div>
                
                <div class="relative flex items-center justify-between">
                  <div class="flex items-center gap-4">
                    <div class="relative">
                      <div class="absolute inset-0 bg-orange-500 rounded-xl blur-md opacity-50 animate-pulse"></div>
                      <div class="relative w-14 h-14 rounded-xl bg-gradient-to-br from-orange-400 via-red-500 to-orange-600 flex items-center justify-center shadow-lg border border-orange-300/30">
                        <Icon icon="fluent:fire-24-filled" class="w-7 h-7 text-white animate-bounce" />
                      </div>
                    </div>
                    <div>
                      <p class="text-sm font-bold text-white">Activity Streak</p>
                      <p class="text-xs text-orange-400/80">กิจกรรมต่อเนื่อง</p>
                    </div>
                  </div>
                  <div class="text-right">
                    <p class="text-4xl font-black text-transparent bg-clip-text bg-gradient-to-r from-orange-400 to-red-400">{{ profile.streak || 7 }}</p>
                    <p class="text-xs text-gray-400 font-medium uppercase">วัน</p>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Achievements Widget - Premium Gaming Style -->
          <div class="vikinger-card vikinger-card-hover relative overflow-hidden !rounded-2xl !p-0 dark:!bg-gradient-to-br dark:!from-gray-900 dark:!via-gray-800 dark:!to-gray-900">
            <!-- Animated Background -->
            <div class="absolute inset-0 pointer-events-none overflow-hidden">
              <div class="absolute top-0 left-0 w-40 h-40 bg-yellow-500/10 rounded-full blur-3xl animate-pulse"></div>
            </div>
            
            <!-- Header -->
            <div class="relative bg-gradient-to-r from-yellow-500 via-amber-500 to-orange-500 p-4">
              <div class="absolute inset-0 bg-[url('/images/patterns/circuit.svg')] bg-repeat opacity-10"></div>
              <h3 class="relative text-lg font-black text-white flex items-center gap-2 drop-shadow-lg">
                <div class="w-8 h-8 rounded-lg bg-white/20 flex items-center justify-center">
                  <Icon icon="fluent:ribbon-star-24-filled" class="w-5 h-5" />
                </div>
                ความสำเร็จ
                <span class="ml-auto px-3 py-1 bg-white/20 rounded-full text-sm font-bold">3/10</span>
              </h3>
            </div>
            
            <div class="relative p-5 space-y-3">
              <!-- Achievement Items - Enhanced -->
              
              <!-- First Post - Unlocked -->
              <div class="group relative overflow-hidden flex items-center gap-4 p-3 bg-gradient-to-r from-green-900/30 to-emerald-900/20 rounded-xl border border-green-500/30 hover:border-green-400/50 transition-all cursor-pointer">
                <div class="absolute inset-0 bg-gradient-to-r from-transparent via-green-400/5 to-transparent -translate-x-full group-hover:translate-x-full transition-transform duration-1000"></div>
                <div class="relative w-12 h-12 rounded-xl bg-gradient-to-br from-green-400 to-emerald-500 flex items-center justify-center shadow-lg">
                  <Icon icon="fluent:document-checkmark-24-filled" class="w-6 h-6 text-white" />
                </div>
                <div class="flex-1 min-w-0">
                  <p class="text-sm font-bold text-white">โพสต์แรก</p>
                  <p class="text-xs text-gray-400 truncate">สร้างโพสต์แรกของคุณ</p>
                </div>
                <div class="flex items-center gap-2">
                  <span class="text-xs text-green-400 font-bold">+50 XP</span>
                  <Icon icon="fluent:checkmark-circle-24-filled" class="w-6 h-6 text-green-500" />
                </div>
              </div>
              
              <!-- 10 Friends - In Progress -->
              <div class="group relative overflow-hidden flex items-center gap-4 p-3 bg-gradient-to-r from-blue-900/30 to-indigo-900/20 rounded-xl border border-blue-500/30 hover:border-blue-400/50 transition-all cursor-pointer">
                <div class="absolute inset-0 bg-gradient-to-r from-transparent via-blue-400/5 to-transparent -translate-x-full group-hover:translate-x-full transition-transform duration-1000"></div>
                <div class="relative w-12 h-12 rounded-xl bg-gradient-to-br from-blue-400 to-indigo-500 flex items-center justify-center shadow-lg">
                  <Icon icon="fluent:people-24-filled" class="w-6 h-6 text-white" />
                </div>
                <div class="flex-1 min-w-0">
                  <p class="text-sm font-bold text-white">สังคมออนไลน์</p>
                  <p class="text-xs text-gray-400 truncate">มีเพื่อน 10 คน</p>
                  <!-- Progress Bar -->
                  <div class="mt-1.5 h-1.5 bg-gray-700 rounded-full overflow-hidden">
                    <div class="h-full bg-gradient-to-r from-blue-500 to-indigo-500 rounded-full" :style="{ width: Math.min((profile.friends_count || 0) * 10, 100) + '%' }"></div>
                  </div>
                </div>
                <div class="text-right">
                  <span class="text-xs text-blue-400 font-bold">{{ Math.min(profile.friends_count || 0, 10) }}/10</span>
                </div>
              </div>
              
              <!-- 100 Points - Unlocked/Locked based on points -->
              <div class="group relative overflow-hidden flex items-center gap-4 p-3 rounded-xl border transition-all cursor-pointer"
                :class="(profile.points || profile.pp || 0) >= 100 
                  ? 'bg-gradient-to-r from-amber-900/30 to-orange-900/20 border-amber-500/30 hover:border-amber-400/50' 
                  : 'bg-gray-800/30 border-gray-700 hover:border-gray-600'">
                <div class="absolute inset-0 bg-gradient-to-r from-transparent via-amber-400/5 to-transparent -translate-x-full group-hover:translate-x-full transition-transform duration-1000"></div>
                <div class="relative w-12 h-12 rounded-xl flex items-center justify-center shadow-lg"
                  :class="(profile.points || profile.pp || 0) >= 100 
                    ? 'bg-gradient-to-br from-amber-400 to-orange-500' 
                    : 'bg-gray-700'">
                  <Icon icon="fluent:star-24-filled" class="w-6 h-6" :class="(profile.points || profile.pp || 0) >= 100 ? 'text-white' : 'text-gray-500'" />
                </div>
                <div class="flex-1 min-w-0">
                  <p class="text-sm font-bold text-white">นักสะสมแต้ม</p>
                  <p class="text-xs text-gray-400 truncate">สะสม 100 แต้ม</p>
                </div>
                <div class="flex items-center gap-2">
                  <span class="text-xs font-bold" :class="(profile.points || profile.pp || 0) >= 100 ? 'text-amber-400' : 'text-gray-500'">+100 XP</span>
                  <Icon 
                    :icon="(profile.points || profile.pp || 0) >= 100 ? 'fluent:checkmark-circle-24-filled' : 'fluent:lock-closed-24-regular'" 
                    :class="(profile.points || profile.pp || 0) >= 100 ? 'w-6 h-6 text-amber-500' : 'w-5 h-5 text-gray-500'" 
                  />
                </div>
              </div>
              
              <!-- Level 5 - Locked -->
              <div class="group relative overflow-hidden flex items-center gap-4 p-3 rounded-xl border transition-all cursor-pointer"
                :class="(profile.level || 1) >= 5 
                  ? 'bg-gradient-to-r from-purple-900/30 to-violet-900/20 border-purple-500/30 hover:border-purple-400/50' 
                  : 'bg-gray-800/30 border-gray-700 hover:border-gray-600'">
                <div class="absolute inset-0 bg-gradient-to-r from-transparent via-purple-400/5 to-transparent -translate-x-full group-hover:translate-x-full transition-transform duration-1000"></div>
                <div class="relative w-12 h-12 rounded-xl flex items-center justify-center shadow-lg"
                  :class="(profile.level || 1) >= 5 
                    ? 'bg-gradient-to-br from-purple-400 to-violet-500' 
                    : 'bg-gray-700'">
                  <Icon icon="fluent:trophy-24-filled" class="w-6 h-6" :class="(profile.level || 1) >= 5 ? 'text-white' : 'text-gray-500'" />
                </div>
                <div class="flex-1 min-w-0">
                  <p class="text-sm font-bold text-white">ผู้ชำนาญ</p>
                  <p class="text-xs text-gray-400 truncate">ถึง Level 5</p>
                </div>
                <div class="flex items-center gap-2">
                  <span class="text-xs font-bold" :class="(profile.level || 1) >= 5 ? 'text-purple-400' : 'text-gray-500'">+200 XP</span>
                  <Icon 
                    :icon="(profile.level || 1) >= 5 ? 'fluent:checkmark-circle-24-filled' : 'fluent:lock-closed-24-regular'" 
                    :class="(profile.level || 1) >= 5 ? 'w-6 h-6 text-purple-500' : 'w-5 h-5 text-gray-500'" 
                  />
                </div>
              </div>
              
              <!-- View All Button -->
              <button class="w-full py-3 px-4 bg-gradient-to-r from-yellow-600 to-amber-600 hover:from-yellow-700 hover:to-amber-700 text-white rounded-xl text-sm font-bold flex items-center justify-center gap-2 transition-all mt-2">
                <Icon icon="fluent:trophy-24-regular" class="w-4 h-4" />
                <span>ดูความสำเร็จทั้งหมด</span>
              </button>
            </div>
          </div>

          <!-- Stream Box / Featured Content - Premium Style -->
          <div class="vikinger-card vikinger-card-hover relative overflow-hidden !rounded-2xl dark:!bg-gradient-to-br dark:!from-gray-900 dark:!via-gray-800 dark:!to-gray-900">
            <div class="relative">
              <!-- Header -->
              <div class="flex items-center justify-between mb-5">
                <h3 class="text-lg font-black text-white flex items-center gap-2">
                  <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-red-500 to-pink-600 flex items-center justify-center">
                    <Icon icon="fluent:video-24-filled" class="w-4 h-4 text-white" />
                  </div>
                  Stream Box
                </h3>
                <div class="flex items-center gap-2">
                  <span class="px-2 py-1 bg-red-500/20 text-red-400 text-xs font-bold rounded-full flex items-center gap-1">
                    <span class="w-2 h-2 bg-red-500 rounded-full animate-pulse"></span>
                    OFFLINE
                  </span>
                </div>
              </div>
              
              <!-- Stream Preview - Enhanced -->
              <div class="relative aspect-video bg-gradient-to-br from-gray-800 to-gray-900 rounded-xl overflow-hidden border border-gray-700">
                <!-- Placeholder Animation -->
                <div class="absolute inset-0 flex flex-col items-center justify-center">
                  <div class="relative mb-4">
                    <div class="absolute inset-0 bg-vikinger-purple/30 rounded-full blur-xl animate-pulse"></div>
                    <div class="relative w-20 h-20 rounded-full bg-gradient-to-br from-gray-700 to-gray-800 flex items-center justify-center border border-gray-600">
                      <Icon icon="fluent:video-off-24-regular" class="w-10 h-10 text-gray-500" />
                    </div>
                  </div>
                  <p class="text-gray-400 text-sm font-medium">ไม่มีการถ่ายทอดสด</p>
                  <p class="text-gray-500 text-xs mt-1">No active stream</p>
                </div>
                
                <!-- Go Live Button for Own Profile -->
                <button v-if="isOwnProfile" class="absolute bottom-4 left-1/2 -translate-x-1/2 px-6 py-2.5 bg-gradient-to-r from-red-500 to-pink-600 text-white rounded-xl font-bold text-sm hover:from-red-600 hover:to-pink-700 transition-all flex items-center gap-2 shadow-lg">
                  <Icon icon="fluent:video-24-filled" class="w-4 h-4" />
                  Go Live
                </button>
              </div>
            </div>
          </div>

          <!-- Photos Widget - Premium Style -->
          <div class="vikinger-card vikinger-card-hover relative overflow-hidden !rounded-2xl dark:!bg-gradient-to-br dark:!from-gray-900 dark:!via-gray-800 dark:!to-gray-900">
            <div class="relative">
              <!-- Header -->
              <div class="flex items-center justify-between mb-5">
                <h3 class="text-lg font-black text-white flex items-center gap-2">
                  <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-cyan-500 to-blue-600 flex items-center justify-center">
                    <Icon icon="fluent:image-multiple-24-filled" class="w-4 h-4 text-white" />
                  </div>
                  Photos
                  <span class="ml-1 px-2 py-0.5 bg-vikinger-cyan/20 text-vikinger-cyan text-xs font-bold rounded-full">{{ profile.photos_count || 0 }}</span>
                </h3>
                <button class="p-2 hover:bg-gray-700/50 rounded-lg transition-colors">
                  <Icon icon="fluent:more-horizontal-24-regular" class="w-5 h-5 text-gray-400 hover:text-white" />
                </button>
              </div>
              
              <!-- Photos Grid - Enhanced -->
              <div class="grid grid-cols-4 gap-1.5">
                <div 
                  v-for="i in 12" 
                  :key="i"
                  class="group relative aspect-square bg-gradient-to-br from-gray-800 to-gray-700 rounded-lg overflow-hidden cursor-pointer hover:scale-105 transition-transform"
                >
                  <!-- Placeholder -->
                  <div class="absolute inset-0 flex items-center justify-center">
                    <Icon icon="fluent:image-24-regular" class="w-5 h-5 text-gray-600 group-hover:text-gray-500 transition-colors" />
                  </div>
                  
                  <!-- Hover Overlay -->
                  <div class="absolute inset-0 bg-vikinger-purple/0 group-hover:bg-vikinger-purple/20 transition-colors"></div>
                </div>
              </div>
              
              <!-- View All Button -->
              <button 
                @click="activeTab = 'photos'"
                class="w-full mt-4 py-2.5 px-4 bg-gray-800 hover:bg-gray-700 text-white rounded-xl text-sm font-medium flex items-center justify-center gap-2 transition-all"
              >
                <Icon icon="fluent:image-multiple-24-regular" class="w-4 h-4 text-vikinger-cyan" />
                <span>ดูรูปภาพทั้งหมด</span>
              </button>
            </div>
          </div>

          <!-- Groups Widget - Premium Style -->
          <div class="vikinger-card vikinger-card-hover relative overflow-hidden !rounded-2xl dark:!bg-gradient-to-br dark:!from-gray-900 dark:!via-gray-800 dark:!to-gray-900">
            <div class="relative">
              <!-- Header -->
              <div class="flex items-center justify-between mb-5">
                <h3 class="text-lg font-black text-white flex items-center gap-2">
                  <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-green-500 to-emerald-600 flex items-center justify-center">
                    <Icon icon="fluent:people-community-24-filled" class="w-4 h-4 text-white" />
                  </div>
                  Groups
                  <span class="ml-1 px-2 py-0.5 bg-vikinger-cyan/20 text-vikinger-cyan text-xs font-bold rounded-full">{{ profile.groups_count || 0 }}</span>
                </h3>
                <button class="p-2 hover:bg-gray-700/50 rounded-lg transition-colors">
                  <Icon icon="fluent:more-horizontal-24-regular" class="w-5 h-5 text-gray-400 hover:text-white" />
                </button>
              </div>
              
              <!-- Empty State - Enhanced -->
              <div class="text-center py-8 px-4">
                <div class="relative w-20 h-20 mx-auto mb-4">
                  <div class="absolute inset-0 bg-green-500/20 rounded-full blur-xl animate-pulse"></div>
                  <div class="relative w-full h-full rounded-full bg-gradient-to-br from-gray-800 to-gray-700 flex items-center justify-center border border-gray-600">
                    <Icon icon="fluent:people-community-24-regular" class="w-10 h-10 text-gray-500" />
                  </div>
                </div>
                <p class="text-gray-400 text-sm font-medium">ยังไม่ได้เข้าร่วมกลุ่ม</p>
                <p class="text-gray-500 text-xs mt-1">No groups joined yet</p>
                
                <!-- Explore Groups Button -->
                <button class="mt-4 px-6 py-2.5 bg-gradient-to-r from-green-500 to-emerald-600 text-white rounded-xl font-medium text-sm hover:from-green-600 hover:to-emerald-700 transition-all flex items-center gap-2 mx-auto">
                  <Icon icon="fluent:search-24-regular" class="w-4 h-4" />
                  สำรวจกลุ่ม
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </template>

    <!-- Not Found -->
    <template v-else>
      <BaseCard class="bg-gray-800 border-gray-700 text-center py-12">
      <Icon icon="fluent:person-warning-24-regular" class="w-16 h-16 text-gray-600 mx-auto mb-4" />
      <p class="text-gray-400">Profile not found</p>
      <NuxtLink to="/play/newsfeed" class="mt-4 inline-block text-vikinger-purple hover:underline">
        Go back to newsfeed
      </NuxtLink>
    </BaseCard>
    </template>

    <!-- ========== COVER PHOTO MODAL ========== -->
    <Teleport to="body">
      <Transition name="modal">
        <div 
          v-if="showCoverModal"
          class="fixed inset-0 z-50 flex items-center justify-center p-4"
        >
          <!-- Backdrop -->
          <div 
            class="absolute inset-0 bg-black/70 backdrop-blur-sm"
            @click="closeCoverModal"
          />
          
          <!-- Modal Content -->
          <div class="relative bg-gray-800 rounded-xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-hidden border border-gray-700">
            <!-- Header -->
            <div class="flex items-center justify-between p-4 border-b border-gray-700">
              <h3 class="text-xl font-bold text-white flex items-center gap-2">
                <Icon icon="fluent:image-edit-24-regular" class="w-6 h-6 text-vikinger-cyan" />
                Edit Cover Photo
              </h3>
              <button 
                @click="closeCoverModal"
                class="p-2 text-gray-400 hover:text-white hover:bg-gray-700 rounded-lg transition-colors"
              >
                <Icon icon="fluent:dismiss-24-regular" class="w-5 h-5" />
              </button>
            </div>
            
            <!-- Body -->
            <div class="p-6">
              <!-- Preview Area -->
              <div class="relative aspect-[3/1] bg-gray-700 rounded-lg overflow-hidden mb-6">
                <img 
                  v-if="coverPreview || profile?.cover_photo"
                  :src="coverPreview || profile?.cover_photo"
                  alt="Cover Preview"
                  class="w-full h-full object-cover"
                />
                <div v-else class="w-full h-full flex items-center justify-center">
                  <div class="text-center">
                    <Icon icon="fluent:image-24-regular" class="w-16 h-16 text-gray-500 mx-auto mb-2" />
                    <p class="text-gray-500">No cover photo</p>
                  </div>
                </div>
                
                <!-- Change indicator when preview exists -->
                <div 
                  v-if="coverPreview"
                  class="absolute top-2 left-2 px-2 py-1 bg-green-500/80 text-white text-xs rounded-lg"
                >
                  New Photo Selected
                </div>
              </div>
              
              <!-- Upload Options -->
              <div class="space-y-4">
                <button
                  @click="triggerCoverUpload"
                  class="w-full py-3 px-4 bg-gray-700 hover:bg-gray-600 text-white rounded-lg transition-colors flex items-center justify-center gap-2"
                >
                  <Icon icon="fluent:arrow-upload-24-regular" class="w-5 h-5" />
                  Choose Image from Device
                </button>
                
                <p class="text-center text-gray-500 text-sm">
                  Recommended size: 1200 x 400 pixels. Max file size: 5MB
                </p>
              </div>
            </div>
            
            <!-- Footer -->
            <div class="flex items-center justify-end gap-3 p-4 border-t border-gray-700">
              <button
                @click="closeCoverModal"
                class="px-5 py-2.5 text-gray-400 hover:text-white transition-colors"
              >
                Cancel
              </button>
              <button
                @click="uploadCover"
                :disabled="!coverPreview || isUploadingCover"
                :class="[
                  'px-6 py-2.5 rounded-lg font-medium transition-all flex items-center gap-2',
                  coverPreview && !isUploadingCover
                    ? 'bg-vikinger-purple hover:bg-vikinger-purple/80 text-white'
                    : 'bg-gray-700 text-gray-500 cursor-not-allowed'
                ]"
              >
                <Icon 
                  v-if="isUploadingCover" 
                  icon="fluent:spinner-ios-20-regular" 
                  class="w-5 h-5 animate-spin" 
                />
                <Icon v-else icon="fluent:save-24-regular" class="w-5 h-5" />
                {{ isUploadingCover ? 'Saving...' : 'Save Cover' }}
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- ========== AVATAR MODAL ========== -->
    <Teleport to="body">
      <Transition name="modal">
        <div 
          v-if="showAvatarModal"
          class="fixed inset-0 z-50 flex items-center justify-center p-4"
        >
          <!-- Backdrop -->
          <div 
            class="absolute inset-0 bg-black/70 backdrop-blur-sm"
            @click="closeAvatarModal"
          />
          
          <!-- Modal Content -->
          <div class="relative bg-gray-800 rounded-xl shadow-2xl w-full max-w-md max-h-[90vh] overflow-hidden border border-gray-700">
            <!-- Header -->
            <div class="flex items-center justify-between p-4 border-b border-gray-700">
              <h3 class="text-xl font-bold text-white flex items-center gap-2">
                <Icon icon="fluent:person-edit-24-regular" class="w-6 h-6 text-vikinger-cyan" />
                Edit Profile Photo
              </h3>
              <button 
                @click="closeAvatarModal"
                class="p-2 text-gray-400 hover:text-white hover:bg-gray-700 rounded-lg transition-colors"
              >
                <Icon icon="fluent:dismiss-24-regular" class="w-5 h-5" />
              </button>
            </div>
            
            <!-- Body -->
            <div class="p-6">
              <!-- Preview Area -->
              <div class="flex justify-center mb-6">
                <div class="relative">
                  <div class="w-40 h-40 rounded-full overflow-hidden border-4 border-vikinger-cyan bg-gray-700">
                    <img 
                      v-if="avatarPreview || profile?.avatar"
                      :src="avatarPreview || profile?.avatar"
                      alt="Avatar Preview"
                      class="w-full h-full object-cover"
                    />
                    <div v-else class="w-full h-full flex items-center justify-center">
                      <Icon icon="fluent:person-24-regular" class="w-16 h-16 text-gray-500" />
                    </div>
                  </div>
                  
                  <!-- Change indicator -->
                  <div 
                    v-if="avatarPreview"
                    class="absolute -top-2 -right-2 px-2 py-1 bg-green-500 text-white text-xs rounded-full"
                  >
                    New
                  </div>
                </div>
              </div>
              
              <!-- Upload Options -->
              <div class="space-y-4">
                <button
                  @click="triggerAvatarUpload"
                  class="w-full py-3 px-4 bg-gray-700 hover:bg-gray-600 text-white rounded-lg transition-colors flex items-center justify-center gap-2"
                >
                  <Icon icon="fluent:arrow-upload-24-regular" class="w-5 h-5" />
                  Choose Image from Device
                </button>
                
                <p class="text-center text-gray-500 text-sm">
                  Recommended: Square image, at least 200x200 pixels. Max: 2MB
                </p>
              </div>
            </div>
            
            <!-- Footer -->
            <div class="flex items-center justify-end gap-3 p-4 border-t border-gray-700">
              <button
                @click="closeAvatarModal"
                class="px-5 py-2.5 text-gray-400 hover:text-white transition-colors"
              >
                Cancel
              </button>
              <button
                @click="uploadAvatar"
                :disabled="!avatarPreview || isUploadingAvatar"
                :class="[
                  'px-6 py-2.5 rounded-lg font-medium transition-all flex items-center gap-2',
                  avatarPreview && !isUploadingAvatar
                    ? 'bg-vikinger-purple hover:bg-vikinger-purple/80 text-white'
                    : 'bg-gray-700 text-gray-500 cursor-not-allowed'
                ]"
              >
                <Icon 
                  v-if="isUploadingAvatar" 
                  icon="fluent:spinner-ios-20-regular" 
                  class="w-5 h-5 animate-spin" 
                />
                <Icon v-else icon="fluent:save-24-regular" class="w-5 h-5" />
                {{ isUploadingAvatar ? 'Saving...' : 'Save Avatar' }}
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<style scoped>
/* Hide scrollbar but allow scrolling */
.scrollbar-hide {
  -ms-overflow-style: none;
  scrollbar-width: none;
}

.scrollbar-hide::-webkit-scrollbar {
  display: none;
}

/* Modal transitions */
.modal-enter-active,
.modal-leave-active {
  transition: all 0.3s ease;
}

.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}

.modal-enter-from .relative,
.modal-leave-to .relative {
  transform: scale(0.95) translateY(-20px);
}

/* Shimmer animation for progress bars */
@keyframes shimmer {
  0% {
    transform: translateX(-100%);
  }
  100% {
    transform: translateX(100%);
  }
}

.animate-shimmer {
  animation: shimmer 2s infinite;
}

/* Slow spin for avatar ring */
@keyframes spin-slow {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}

.animate-spin-slow {
  animation: spin-slow 8s linear infinite;
}

/* Pulse delay utilities */
.delay-500 {
  animation-delay: 500ms;
}

.delay-700 {
  animation-delay: 700ms;
}

.delay-1000 {
  animation-delay: 1000ms;
}

/* Card hover effects */
.stat-card {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.stat-card:hover {
  transform: translateY(-2px);
}

/* Gradient text */
.text-gradient {
  background: linear-gradient(90deg, #615dfa 0%, #23d2e2 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

/* Ring animations */
@keyframes ring-pulse {
  0%, 100% {
    opacity: 0.6;
    transform: scale(1);
  }
  50% {
    opacity: 0.8;
    transform: scale(1.05);
  }
}

.animate-ring-pulse {
  animation: ring-pulse 2s ease-in-out infinite;
}

/* Border width utility */
.border-3 {
  border-width: 3px;
}

/* Glow effects for cards */
.glow-cyan {
  box-shadow: 0 0 30px rgba(35, 210, 226, 0.3);
}

.glow-purple {
  box-shadow: 0 0 30px rgba(97, 93, 250, 0.3);
}

/* Floating animation */
@keyframes float {
  0%, 100% {
    transform: translateY(0);
  }
  50% {
    transform: translateY(-5px);
  }
}

.animate-float {
  animation: float 3s ease-in-out infinite;
}

/* Tab active indicator animation */
.tab-indicator {
  position: absolute;
  bottom: 0;
  left: 0;
  height: 3px;
  background: linear-gradient(90deg, #615dfa 0%, #23d2e2 100%);
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

/* Hexagon pattern background */
.hexagon-bg {
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='28' height='49' viewBox='0 0 28 49'%3E%3Cg fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.03'%3E%3Cpath d='M13.99 9.25l13 7.5v15l-13 7.5L1 31.75v-15l12.99-7.5zM3 17.9v12.7l10.99 6.34 11-6.35V17.9l-11-6.34L3 17.9zM0 15l12.98-7.5V0h-2v6.35L0 12.69v2.3zm0 18.5L12.98 41v8h-2v-6.85L0 35.81v-2.3zM15 0v7.5L27.99 15H28v-2.31h-.01L17 6.35V0h-2zm0 49v-8l12.99-7.5H28v2.31h-.01L17 42.15V49h-2z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
}

/* Smooth card entrance */
@keyframes card-enter {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.animate-card-enter {
  animation: card-enter 0.5s ease-out forwards;
}

/* Level progress glow */
.level-glow {
  filter: drop-shadow(0 0 8px rgba(97, 93, 250, 0.5));
}
</style>
