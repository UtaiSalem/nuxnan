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

      <!-- Profile Header Card -->
      <BaseCard class="bg-gray-800 border-gray-700 overflow-hidden mb-6" no-padding>
        <!-- Cover Photo -->
        <div class="relative h-52 md:h-72 bg-gradient-to-r from-vikinger-purple to-vikinger-cyan">
          <img 
            v-if="profile.cover_image"
            :src="profile.cover_image" 
            :alt="`${displayName}'s cover`"
            class="w-full h-full object-cover"
          />
          
          <!-- Edit Cover Button - Opens Modal -->
          <button 
            v-if="isOwnProfile"
            @click="openCoverModal"
            class="absolute top-4 right-4 px-4 py-2 bg-gray-900/70 text-white rounded-lg hover:bg-gray-900/90 transition-colors flex items-center gap-2 backdrop-blur-sm"
          >
            <Icon icon="fluent:camera-24-regular" class="w-5 h-5" />
            <span class="hidden sm:inline">Edit Cover</span>
          </button>

          <!-- Circle Avatar - Positioned at bottom left -->
          <div class="absolute left-8 md:left-12 bottom-0 translate-y-1/2 z-10">
            <div class="relative group">
              <CircleAvatar
                :src="profile.avatar || '/images/default-avatar.png'"
                :alt="displayName"
                size="xl"
                :border-width="5"
                border-color="#23d2e2"
                :show-online-status="true"
                :is-online="true"
              />
              
              <!-- Level Badge -->
              <div class="absolute -bottom-2 left-1/2 -translate-x-1/2 bg-gradient-to-r from-vikinger-purple to-vikinger-cyan text-white text-sm font-bold px-3 py-1 rounded-full shadow-lg">
                {{ profile.level || 1 }}
              </div>
              
              <!-- Edit Avatar Button - Opens Modal -->
              <button 
                v-if="isOwnProfile"
                @click="openAvatarModal"
                class="absolute top-0 right-0 p-1.5 bg-vikinger-purple text-white rounded-full shadow-lg hover:bg-vikinger-purple/80 transition-colors"
              >
                <Icon icon="fluent:camera-24-filled" class="w-4 h-4" />
              </button>
            </div>
          </div>

          <!-- Name & Website - Positioned at bottom left, next to avatar -->
          <div class="absolute left-[192px] md:left-[224px] bottom-[-32px] z-10">
            <h1 class="text-4xl md:text-2xl font-bold text-white drop-shadow-lg">{{ displayName }}</h1>
            <a 
              v-if="profile.website" 
              :href="profile.website" 
              target="_blank"
              class="text-vikinger-cyan hover:underline text-sm drop-shadow-md"
            >
              {{ profile.website.replace(/^https?:\/\//, '') }}
            </a>
            <p v-else-if="profile.bio" class="text-gray-200 text-sm max-w-xs drop-shadow-md">{{ profile.bio }}</p>
          </div>

          <!-- Action Buttons (For other users) - Positioned bottom right on cover -->
          <div v-if="!isOwnProfile" class="absolute bottom-4 right-4 flex gap-3">
            <button 
              @click="handleFriendAction"
              :disabled="isProcessingFriend"
              :class="[friendButtonConfig.class, 'px-6 py-3 text-white rounded-lg hover:opacity-90 transition-all flex items-center gap-2 font-semibold shadow-lg disabled:opacity-70']"
            >
              <Icon 
                v-if="isProcessingFriend" 
                icon="fluent:spinner-ios-20-regular" 
                class="w-5 h-5 animate-spin" 
              />
              <Icon v-else :icon="friendButtonConfig.icon" class="w-5 h-5" />
              {{ friendButtonConfig.text }}
            </button>
            <button class="px-6 py-3 bg-primary text-white rounded-lg hover:opacity-90 transition-all flex items-center gap-2 font-semibold shadow-lg">
              <Icon icon="fluent:chat-24-regular" class="w-5 h-5" />
              ส่งข้อความ
            </button>
          </div>
        </div>

        <!-- Profile Info Section - Vikinger Style Layout -->
        <div class="pt-16 pb-6 px-6">
          <!-- Main Info Row: Stats | Social -->
          <div class="flex flex-col lg:flex-row items-center justify-between gap-6">
            
            <!-- Left: Spacer for avatar -->
            <div class="hidden lg:block lg:w-44 xl:w-52"></div>

            <!-- Center: Stats -->
            <div class="flex items-center gap-6 order-1 lg:order-2">
              <div class="text-center px-4 border-r border-gray-700">
                <span class="block text-2xl font-bold text-white">{{ profile.posts_count || 0 }}</span>
                <span class="text-xs text-gray-400 uppercase tracking-wider">Posts</span>
              </div>
              <div class="text-center px-4 border-r border-gray-700">
                <span class="block text-2xl font-bold text-white">{{ profile.friends_count || 0 }}</span>
                <span class="text-xs text-gray-400 uppercase tracking-wider">Friends</span>
              </div>
              <div class="text-center px-4">
                <span class="block text-2xl font-bold text-white">{{ profile.visits_count || 0 }}</span>
                <span class="text-xs text-gray-400 uppercase tracking-wider">Visits</span>
              </div>
              <!-- Country Flag -->
              <div v-if="countryFlag" class="text-center px-4 border-l border-gray-700">
                <span class="block text-2xl">{{ countryFlag }}</span>
                <span class="text-xs text-gray-400 uppercase tracking-wider">{{ profile.location?.split(',')[0] || 'Location' }}</span>
              </div>
            </div>

            <!-- Right: Social Media Icons -->
            <div class="flex items-center gap-2 order-3">
              <template v-if="profile.social_media_links">
                <a 
                  v-for="(url, platform) in profile.social_media_links"
                  :key="platform"
                  v-show="url"
                  :href="url"
                  target="_blank"
                  rel="noopener noreferrer"
                  class="w-10 h-10 rounded-full flex items-center justify-center transition-transform hover:scale-110"
                  :style="{ backgroundColor: socialIcons[platform]?.color || '#6b7280' }"
                >
                  <Icon :icon="socialIcons[platform]?.icon || 'fluent:link-24-regular'" class="w-5 h-5 text-white" />
                </a>
              </template>
              
              <!-- Edit Profile Button (Own Profile) -->
              <button 
                v-if="isOwnProfile"
                @click="goToEditProfile"
                class="ml-2 px-4 py-2 bg-vikinger-purple text-white rounded-lg hover:bg-vikinger-purple/80 transition-colors flex items-center gap-2 text-sm"
              >
                <Icon icon="fluent:edit-24-regular" class="w-4 h-4" />
                Edit Profile
              </button>
            </div>
          </div>
        </div>
      </BaseCard>

      <!-- Tabs Navigation Bar - Carousel Style -->
      <BaseCard class="bg-gray-800 border-gray-700 mb-6 overflow-hidden" no-padding>
        <div class="flex items-center">
          <!-- Left Arrow -->
          <button 
            @click="scrollTabs('left')"
            :class="[
              'p-4 transition-all duration-300 flex-shrink-0',
              canScrollLeft 
                ? 'text-gray-400 hover:text-white hover:bg-gray-700/50 cursor-pointer' 
                : 'text-gray-700 cursor-not-allowed'
            ]"
            :disabled="!canScrollLeft"
          >
            <Icon icon="fluent:chevron-left-24-regular" class="w-5 h-5" />
          </button>
          
          <!-- Tab Items - Scrollable Container -->
          <div 
            ref="tabsContainer"
            @scroll="updateScrollButtons"
            class="flex-1 flex overflow-x-auto scrollbar-hide scroll-smooth"
          >
            <button
              v-for="tab in tabs"
              :key="tab.key"
              @click="activeTab = tab.key"
              :class="[
                'relative flex-1 min-w-[80px] lg:min-w-[100px] flex flex-col items-center justify-center gap-1.5 py-4 px-4 transition-all duration-300 group border-b-2',
                activeTab === tab.key 
                  ? 'text-vikinger-cyan border-vikinger-cyan bg-gray-700/20' 
                  : 'text-gray-500 border-transparent hover:text-white hover:bg-gray-700/30'
              ]"
            >
              <!-- Icon with animation -->
              <div class="relative">
                <Icon 
                  :icon="tab.icon" 
                  :class="[
                    'w-6 h-6 transition-all duration-300',
                    activeTab === tab.key ? 'scale-110 text-vikinger-cyan' : 'group-hover:scale-110 group-hover:text-white'
                  ]" 
                />
                <!-- Glow effect on active -->
                <div 
                  v-if="activeTab === tab.key"
                  class="absolute inset-0 bg-vikinger-cyan/30 blur-md rounded-full animate-pulse"
                />
              </div>
              
              <!-- Label - visible on larger screens -->
              <span 
                :class="[
                  'text-xs font-medium transition-all duration-300 hidden xl:block whitespace-nowrap',
                  activeTab === tab.key ? 'opacity-100 text-vikinger-cyan' : 'opacity-60 group-hover:opacity-100'
                ]"
              >
                {{ tab.label }}
              </span>
              
              <!-- Tooltip for smaller screens -->
              <span class="xl:hidden absolute -bottom-10 left-1/2 -translate-x-1/2 bg-gray-900 text-white text-xs px-3 py-1.5 rounded-lg opacity-0 group-hover:opacity-100 transition-all duration-200 whitespace-nowrap pointer-events-none z-20 shadow-lg">
                {{ tab.label }}
              </span>
            </button>
          </div>
          
          <!-- Right Arrow -->
          <button 
            @click="scrollTabs('right')"
            :class="[
              'p-4 transition-all duration-300 flex-shrink-0',
              canScrollRight 
                ? 'text-gray-400 hover:text-white hover:bg-gray-700/50 cursor-pointer' 
                : 'text-gray-700 cursor-not-allowed'
            ]"
            :disabled="!canScrollRight"
          >
            <Icon icon="fluent:chevron-right-24-regular" class="w-5 h-5" />
          </button>
        </div>
      </BaseCard>

      <!-- Tab Content - 3 Column Layout like Vikinger -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        
        <!-- Left Sidebar (About, Badges, Friends) -->
        <div class="lg:col-span-3 space-y-6">
          <!-- Profile Completion Widget (Own Profile) -->
          <ProfileCompletionWidget v-if="isOwnProfile" />

          <!-- Friend Requests Widget (Own Profile) -->
          <FriendRequestsWidget v-if="isOwnProfile" />

          <!-- Points & Wallet Card - Gaming Style -->
          <BaseCard class="bg-gray-800 border-gray-700 overflow-hidden">
            <!-- Header with gradient -->
            <div class="bg-gradient-to-r from-amber-500 via-orange-500 to-red-500 p-4">
              <h3 class="text-lg font-bold text-white flex items-center gap-2">
                <Icon icon="fluent:wallet-24-filled" class="w-5 h-5" />
                แต้ม & กระเป๋าเงิน
              </h3>
            </div>
            <div class="p-5 space-y-4">
              <!-- Points -->
              <div class="flex items-center justify-between p-3 bg-gradient-to-r from-amber-900/30 to-orange-900/30 rounded-xl border border-amber-600/30">
                <div class="flex items-center gap-3">
                  <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-amber-400 to-orange-500 flex items-center justify-center shadow-lg">
                    <Icon icon="fluent:star-24-filled" class="w-6 h-6 text-white" />
                  </div>
                  <div>
                    <p class="text-xs text-amber-400 uppercase tracking-wider font-medium">แต้มสะสม</p>
                    <p class="text-2xl font-black text-white">{{ (profile.points || profile.pp || 0).toLocaleString() }}</p>
                  </div>
                </div>
                <Icon icon="fluent:sparkle-24-filled" class="w-8 h-8 text-amber-400 animate-pulse" />
              </div>
              
              <!-- Wallet -->
              <div class="flex items-center justify-between p-3 bg-gradient-to-r from-green-900/30 to-emerald-900/30 rounded-xl border border-green-600/30">
                <div class="flex items-center gap-3">
                  <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-green-400 to-emerald-500 flex items-center justify-center shadow-lg">
                    <Icon icon="fluent:money-24-filled" class="w-6 h-6 text-white" />
                  </div>
                  <div>
                    <p class="text-xs text-green-400 uppercase tracking-wider font-medium">Wallet</p>
                    <p class="text-2xl font-black text-white">฿{{ (profile.wallet || 0).toLocaleString() }}</p>
                  </div>
                </div>
                <Icon icon="fluent:wallet-credit-card-24-filled" class="w-8 h-8 text-green-400" />
              </div>
            </div>
          </BaseCard>

          <!-- Level & XP Progress Card - Gaming Style -->
          <BaseCard class="bg-gray-800 border-gray-700 overflow-hidden">
            <!-- Header with gradient -->
            <div class="bg-gradient-to-r from-vikinger-purple via-purple-600 to-vikinger-cyan p-4">
              <div class="flex items-center justify-between">
                <h3 class="text-lg font-bold text-white flex items-center gap-2">
                  <Icon icon="fluent:trophy-24-filled" class="w-5 h-5" />
                  Level & Experience
                </h3>
                <div class="flex items-center gap-1 px-3 py-1 bg-white/20 rounded-full">
                  <Icon icon="fluent:star-24-filled" class="w-4 h-4 text-yellow-300" />
                  <span class="text-white font-bold text-sm">LV.{{ profile.level || 1 }}</span>
                </div>
              </div>
            </div>
            <div class="p-5 space-y-4">
              <!-- Level Circle Display -->
              <div class="flex items-center justify-center">
                <div class="relative">
                  <!-- Outer ring -->
                  <svg class="w-28 h-28 transform -rotate-90" viewBox="0 0 100 100">
                    <!-- Background circle -->
                    <circle 
                      cx="50" cy="50" r="45" 
                      stroke="currentColor" 
                      stroke-width="8" 
                      fill="none" 
                      class="text-gray-700"
                    />
                    <!-- Progress circle -->
                    <circle 
                      cx="50" cy="50" r="45" 
                      stroke="url(#levelGradient)" 
                      stroke-width="8" 
                      fill="none" 
                      stroke-linecap="round"
                      :stroke-dasharray="`${(profile.level_progress || 65) * 2.83} 283`"
                      class="transition-all duration-1000"
                    />
                    <defs>
                      <linearGradient id="levelGradient" x1="0%" y1="0%" x2="100%" y2="0%">
                        <stop offset="0%" style="stop-color:#9333ea" />
                        <stop offset="100%" style="stop-color:#23d2e2" />
                      </linearGradient>
                    </defs>
                  </svg>
                  <!-- Level number in center -->
                  <div class="absolute inset-0 flex items-center justify-center">
                    <div class="text-center">
                      <span class="text-3xl font-black text-white">{{ profile.level || 1 }}</span>
                      <p class="text-xs text-gray-400 uppercase">LEVEL</p>
                    </div>
                  </div>
                </div>
              </div>

              <!-- XP Progress Bar -->
              <div class="space-y-2">
                <div class="flex justify-between text-sm">
                  <span class="text-gray-400">Experience</span>
                  <span class="text-vikinger-cyan font-medium">{{ (profile.level_progress || 65) }}%</span>
                </div>
                <div class="h-3 bg-gray-700 rounded-full overflow-hidden">
                  <div 
                    class="h-full bg-gradient-to-r from-vikinger-purple to-vikinger-cyan rounded-full transition-all duration-1000 relative overflow-hidden"
                    :style="{ width: (profile.level_progress || 65) + '%' }"
                  >
                    <!-- Shimmer effect -->
                    <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/30 to-transparent animate-shimmer" />
                  </div>
                </div>
                <div class="flex justify-between text-xs text-gray-500">
                  <span>{{ ((profile.points || profile.pp || 0) * 0.65).toLocaleString() }} XP</span>
                  <span>{{ ((profile.points || profile.pp || 0) * 0.65 + 1000).toLocaleString() }} XP</span>
                </div>
              </div>

              <!-- Next Level Reward -->
              <div class="p-3 bg-gray-700/50 rounded-xl border border-gray-600">
                <div class="flex items-center gap-3">
                  <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-yellow-400 to-amber-500 flex items-center justify-center">
                    <Icon icon="fluent:gift-24-filled" class="w-5 h-5 text-white" />
                  </div>
                  <div class="flex-1">
                    <p class="text-sm font-medium text-white">Next Level Reward</p>
                    <p class="text-xs text-gray-400">+500 points bonus</p>
                  </div>
                </div>
              </div>
            </div>
          </BaseCard>

          <!-- About Me Card - Vikinger Style -->
          <BaseCard class="bg-gray-800 border-gray-700 p-5">
            <div class="flex items-center justify-between mb-4">
              <h3 class="text-lg font-bold text-white">About Me</h3>
              <button class="text-gray-500 hover:text-white">
                <Icon icon="fluent:more-horizontal-24-regular" class="w-5 h-5" />
              </button>
            </div>
            
            <!-- Bio Text -->
            <p v-if="profile.bio" class="text-gray-300 text-sm mb-6 leading-relaxed">
              {{ profile.bio }}
            </p>
            <p v-else class="text-gray-500 text-sm mb-6 italic">
              No bio yet...
            </p>

            <!-- Info List -->
            <div class="space-y-4">
              <div v-if="memberSince" class="flex items-center gap-3">
                <span class="text-gray-500 text-sm w-16">Joined</span>
                <span class="text-white text-sm font-medium">{{ memberSince }}</span>
              </div>
              <div v-if="profile.location" class="flex items-center gap-3">
                <span class="text-gray-500 text-sm w-16">City</span>
                <span class="text-white text-sm font-medium">{{ profile.location }}</span>
              </div>
              <div class="flex items-center gap-3">
                <span class="text-gray-500 text-sm w-16">Country</span>
                <span class="text-white text-sm font-medium">Thailand {{ countryFlag }}</span>
              </div>
              <div v-if="profile.birthdate" class="flex items-center gap-3">
                <span class="text-gray-500 text-sm w-16">Age</span>
                <span class="text-white text-sm font-medium">{{ calculateAge(profile.birthdate) }} Years</span>
              </div>
              <div v-if="profile.website" class="flex items-center gap-3">
                <span class="text-gray-500 text-sm w-16">Web</span>
                <a :href="profile.website" target="_blank" class="text-vikinger-cyan text-sm hover:underline">
                  {{ profile.website.replace(/^https?:\/\//, '') }}
                </a>
              </div>
            </div>
          </BaseCard>

          <!-- Badges Card - Vikinger Style -->
          <BaseCard class="bg-gray-800 border-gray-700 p-5">
            <div class="flex items-center justify-between mb-4">
              <h3 class="text-lg font-bold text-white">
                Badges 
                <span class="text-vikinger-cyan font-normal ml-1">{{ profile.badges_count || 0 }}</span>
              </h3>
              <button class="text-gray-500 hover:text-white">
                <Icon icon="fluent:more-horizontal-24-regular" class="w-5 h-5" />
              </button>
            </div>
            
            <!-- Badges Grid -->
            <div class="grid grid-cols-5 gap-2">
              <div 
                v-for="i in 10" 
                :key="i"
                class="w-10 h-10 rounded-lg bg-gray-700 flex items-center justify-center"
              >
                <Icon icon="fluent:trophy-24-regular" class="w-6 h-6 text-gray-500" />
              </div>
            </div>
            <p class="text-gray-500 text-xs mt-3 text-center">Badges coming soon...</p>
          </BaseCard>

          <!-- Friends Preview Card -->
          <BaseCard class="bg-gray-800 border-gray-700 p-5">
            <div class="flex items-center justify-between mb-4">
              <h3 class="text-lg font-bold text-white">
                Friends 
                <span class="text-vikinger-cyan font-normal ml-1">{{ profile.friends_count || 0 }}</span>
              </h3>
              <button class="text-gray-500 hover:text-white">
                <Icon icon="fluent:more-horizontal-24-regular" class="w-5 h-5" />
              </button>
            </div>
            
            <!-- Friends List Preview -->
            <div class="space-y-3">
              <p class="text-gray-500 text-sm text-center py-4">Friends list coming soon...</p>
            </div>
          </BaseCard>
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
          <!-- Stats Card - Gaming Style -->
          <BaseCard class="bg-gray-800 border-gray-700 overflow-hidden">
            <div class="bg-gradient-to-r from-blue-600 to-cyan-500 p-4">
              <h3 class="text-lg font-bold text-white flex items-center gap-2">
                <Icon icon="fluent:data-trending-24-filled" class="w-5 h-5" />
                สถิติ
              </h3>
            </div>
            <div class="p-4 space-y-3">
              <!-- Stats Grid -->
              <div class="grid grid-cols-2 gap-3">
                <!-- Posts -->
                <div class="p-3 bg-gray-700/50 rounded-xl text-center hover:bg-gray-700 transition-colors">
                  <div class="w-10 h-10 mx-auto mb-2 rounded-lg bg-gradient-to-br from-violet-500 to-purple-600 flex items-center justify-center">
                    <Icon icon="fluent:document-24-filled" class="w-5 h-5 text-white" />
                  </div>
                  <p class="text-xl font-bold text-white">{{ profile.posts_count || 0 }}</p>
                  <p class="text-xs text-gray-400">โพสต์</p>
                </div>
                <!-- Likes Received -->
                <div class="p-3 bg-gray-700/50 rounded-xl text-center hover:bg-gray-700 transition-colors">
                  <div class="w-10 h-10 mx-auto mb-2 rounded-lg bg-gradient-to-br from-pink-500 to-rose-600 flex items-center justify-center">
                    <Icon icon="fluent:heart-24-filled" class="w-5 h-5 text-white" />
                  </div>
                  <p class="text-xl font-bold text-white">{{ (profile.likes_received || 0).toLocaleString() }}</p>
                  <p class="text-xs text-gray-400">ถูกใจ</p>
                </div>
                <!-- Comments -->
                <div class="p-3 bg-gray-700/50 rounded-xl text-center hover:bg-gray-700 transition-colors">
                  <div class="w-10 h-10 mx-auto mb-2 rounded-lg bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center">
                    <Icon icon="fluent:chat-24-filled" class="w-5 h-5 text-white" />
                  </div>
                  <p class="text-xl font-bold text-white">{{ profile.comments_count || 0 }}</p>
                  <p class="text-xs text-gray-400">ความคิดเห็น</p>
                </div>
                <!-- Shares -->
                <div class="p-3 bg-gray-700/50 rounded-xl text-center hover:bg-gray-700 transition-colors">
                  <div class="w-10 h-10 mx-auto mb-2 rounded-lg bg-gradient-to-br from-teal-500 to-emerald-600 flex items-center justify-center">
                    <Icon icon="fluent:share-24-filled" class="w-5 h-5 text-white" />
                  </div>
                  <p class="text-xl font-bold text-white">{{ profile.shares_count || 0 }}</p>
                  <p class="text-xs text-gray-400">แชร์</p>
                </div>
              </div>

              <!-- Activity Streak -->
              <div class="p-3 bg-gradient-to-r from-orange-900/30 to-red-900/30 rounded-xl border border-orange-600/30">
                <div class="flex items-center justify-between">
                  <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-orange-400 to-red-500 flex items-center justify-center">
                      <Icon icon="fluent:fire-24-filled" class="w-5 h-5 text-white" />
                    </div>
                    <div>
                      <p class="text-sm font-medium text-white">Activity Streak</p>
                      <p class="text-xs text-gray-400">Active days in a row</p>
                    </div>
                  </div>
                  <div class="text-right">
                    <p class="text-2xl font-black text-orange-400">{{ profile.streak || 7 }}</p>
                    <p class="text-xs text-gray-500">days</p>
                  </div>
                </div>
              </div>
            </div>
          </BaseCard>

          <!-- Achievements Widget - Gaming Style -->
          <BaseCard class="bg-gray-800 border-gray-700 overflow-hidden">
            <div class="bg-gradient-to-r from-yellow-500 to-amber-500 p-4">
              <h3 class="text-lg font-bold text-white flex items-center gap-2">
                <Icon icon="fluent:ribbon-star-24-filled" class="w-5 h-5" />
                ความสำเร็จ
              </h3>
            </div>
            <div class="p-4 space-y-3">
              <!-- Achievement Items -->
              <div class="space-y-2">
                <!-- First Post -->
                <div class="flex items-center gap-3 p-2 bg-gray-700/50 rounded-lg hover:bg-gray-700 transition-colors">
                  <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-green-400 to-emerald-500 flex items-center justify-center">
                    <Icon icon="fluent:document-checkmark-24-filled" class="w-5 h-5 text-white" />
                  </div>
                  <div class="flex-1">
                    <p class="text-sm font-medium text-white">โพสต์แรก</p>
                    <p class="text-xs text-gray-400">สร้างโพสต์แรกของคุณ</p>
                  </div>
                  <Icon icon="fluent:checkmark-circle-24-filled" class="w-5 h-5 text-green-500" />
                </div>
                
                <!-- 10 Friends -->
                <div class="flex items-center gap-3 p-2 bg-gray-700/50 rounded-lg hover:bg-gray-700 transition-colors">
                  <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-blue-400 to-indigo-500 flex items-center justify-center">
                    <Icon icon="fluent:people-24-filled" class="w-5 h-5 text-white" />
                  </div>
                  <div class="flex-1">
                    <p class="text-sm font-medium text-white">สังคมออนไลน์</p>
                    <p class="text-xs text-gray-400">มีเพื่อน 10 คน</p>
                  </div>
                  <div class="text-xs text-gray-400">{{ Math.min(profile.friends_count || 0, 10) }}/10</div>
                </div>
                
                <!-- 100 Points -->
                <div class="flex items-center gap-3 p-2 bg-gray-700/50 rounded-lg hover:bg-gray-700 transition-colors">
                  <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-amber-400 to-orange-500 flex items-center justify-center">
                    <Icon icon="fluent:star-24-filled" class="w-5 h-5 text-white" />
                  </div>
                  <div class="flex-1">
                    <p class="text-sm font-medium text-white">นักสะสมแต้ม</p>
                    <p class="text-xs text-gray-400">สะสม 100 แต้ม</p>
                  </div>
                  <Icon 
                    :icon="(profile.points || profile.pp || 0) >= 100 ? 'fluent:checkmark-circle-24-filled' : 'fluent:circle-24-regular'" 
                    :class="(profile.points || profile.pp || 0) >= 100 ? 'w-5 h-5 text-green-500' : 'w-5 h-5 text-gray-500'" 
                  />
                </div>
                
                <!-- Level 5 -->
                <div class="flex items-center gap-3 p-2 bg-gray-700/50 rounded-lg hover:bg-gray-700 transition-colors">
                  <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-purple-400 to-violet-500 flex items-center justify-center">
                    <Icon icon="fluent:trophy-24-filled" class="w-5 h-5 text-white" />
                  </div>
                  <div class="flex-1">
                    <p class="text-sm font-medium text-white">ผู้ชำนาญ</p>
                    <p class="text-xs text-gray-400">ถึง Level 5</p>
                  </div>
                  <Icon 
                    :icon="(profile.level || 1) >= 5 ? 'fluent:checkmark-circle-24-filled' : 'fluent:circle-24-regular'" 
                    :class="(profile.level || 1) >= 5 ? 'w-5 h-5 text-green-500' : 'w-5 h-5 text-gray-500'" 
                  />
                </div>
              </div>
              
              <!-- View All Button -->
              <button class="w-full py-2 text-vikinger-cyan text-sm hover:underline flex items-center justify-center gap-1">
                <span>ดูทั้งหมด</span>
                <Icon icon="fluent:chevron-right-24-regular" class="w-4 h-4" />
              </button>
            </div>
          </BaseCard>

          <!-- Stream Box / Featured Content -->
          <BaseCard class="bg-gray-800 border-gray-700 p-5">
            <div class="flex items-center justify-between mb-4">
              <h3 class="text-lg font-bold text-white">Stream Box</h3>
              <button class="text-gray-500 hover:text-white">
                <Icon icon="fluent:more-horizontal-24-regular" class="w-5 h-5" />
              </button>
            </div>
            <div class="aspect-video bg-gray-700 rounded-lg flex items-center justify-center">
              <div class="text-center">
                <Icon icon="fluent:video-24-regular" class="w-12 h-12 text-gray-500 mx-auto mb-2" />
                <p class="text-gray-500 text-sm">No stream active</p>
              </div>
            </div>
          </BaseCard>

          <!-- Photos Widget -->
          <BaseCard class="bg-gray-800 border-gray-700 p-5">
            <div class="flex items-center justify-between mb-4">
              <h3 class="text-lg font-bold text-white">
                Photos 
                <span class="text-vikinger-cyan font-normal ml-1">{{ profile.photos_count || 0 }}</span>
              </h3>
              <button class="text-gray-500 hover:text-white">
                <Icon icon="fluent:more-horizontal-24-regular" class="w-5 h-5" />
              </button>
            </div>
            
            <!-- Photos Grid -->
            <div class="grid grid-cols-4 gap-1">
              <div 
                v-for="i in 12" 
                :key="i"
                class="aspect-square bg-gray-700 rounded overflow-hidden"
              >
                <div class="w-full h-full flex items-center justify-center">
                  <Icon icon="fluent:image-24-regular" class="w-6 h-6 text-gray-600" />
                </div>
              </div>
            </div>
            <button class="w-full mt-3 py-2 text-vikinger-cyan text-sm hover:underline">
              View all photos
            </button>
          </BaseCard>

          <!-- Groups Widget -->
          <BaseCard class="bg-gray-800 border-gray-700 p-5">
            <div class="flex items-center justify-between mb-4">
              <h3 class="text-lg font-bold text-white">
                Groups 
                <span class="text-vikinger-cyan font-normal ml-1">{{ profile.groups_count || 0 }}</span>
              </h3>
              <button class="text-gray-500 hover:text-white">
                <Icon icon="fluent:more-horizontal-24-regular" class="w-5 h-5" />
              </button>
            </div>
            
            <div class="text-center py-4">
              <Icon icon="fluent:people-community-24-regular" class="w-12 h-12 text-gray-600 mx-auto mb-2" />
              <p class="text-gray-500 text-sm">No groups joined yet</p>
            </div>
          </BaseCard>
        </div>
      </div>
    </template>

    <!-- Not Found -->
    <BaseCard v-else class="bg-gray-800 border-gray-700 text-center py-12">
      <Icon icon="fluent:person-warning-24-regular" class="w-16 h-16 text-gray-600 mx-auto mb-4" />
      <p class="text-gray-400">Profile not found</p>
      <NuxtLink to="/play/newsfeed" class="mt-4 inline-block text-vikinger-purple hover:underline">
        Go back to newsfeed
      </NuxtLink>
    </BaseCard>

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
</style>
