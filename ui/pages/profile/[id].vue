<script setup lang="ts">
import { Icon } from '@iconify/vue'
import BaseCard from '~/components/atoms/BaseCard.vue'
import FeedPost from '~/components/feed/FeedPost.vue'
import ProfileCompletionWidget from '~/components/organisms/ProfileCompletionWidget.vue'
import CreatePostBox from '~/components/feed/CreatePostBox.vue'
import type { UserProfile, FriendshipStatus } from '~/composables/useProfile'

definePageMeta({
  layout: 'main',
  middleware: 'auth'
})

const route = useRoute()
const { fetchUserProfile, fetchMyProfile } = useProfile()
const authStore = useAuthStore()

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
  try {
    const api = useApi()
    // API จะตรวจสอบ privacy settings ตาม:
    // - เจ้าของโปรไฟล์: เห็นได้ทุกโพสต์ (privacy 1, 2, 3)
    // - เพื่อน: เห็นโพสต์ Friends + Global (privacy 2, 3)
    // - คนอื่น: เห็นเฉพาะ Global (privacy 3)
    const response = await api.get(`/api/users/${referenceCode.value}/activities?page=${page}`)
    
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
    return { text: 'Add Friend', icon: 'fluent:person-add-24-regular', class: 'bg-vikinger-purple' }
  }
  
  switch (friendshipStatus.value) {
    case 'pending_sent':
      return { text: 'Request Sent', icon: 'fluent:clock-24-regular', class: 'bg-gray-500' }
    case 'pending_received':
      return { text: 'Accept Request', icon: 'fluent:checkmark-24-regular', class: 'bg-green-500' }
    case 'friends':
      return { text: 'Friends', icon: 'fluent:people-24-filled', class: 'bg-vikinger-cyan' }
    default:
      return { text: 'Add Friend', icon: 'fluent:person-add-24-regular', class: 'bg-vikinger-purple' }
  }
})

// Handle friend action
const handleFriendAction = () => {
  // TODO: Implement friend actions
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
              :class="[friendButtonConfig.class, 'px-6 py-3 text-white rounded-lg hover:opacity-90 transition-all flex items-center gap-2 font-semibold shadow-lg']"
            >
              <Icon :icon="friendButtonConfig.icon" class="w-5 h-5" />
              {{ friendButtonConfig.text }}
            </button>
            <button class="px-6 py-3 bg-primary text-white rounded-lg hover:opacity-90 transition-all flex items-center gap-2 font-semibold shadow-lg">
              <Icon icon="fluent:chat-24-regular" class="w-5 h-5" />
              Send Message
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
            <BaseCard class="bg-gray-800 border-gray-700 p-6">
              <h3 class="text-lg font-bold text-white mb-6">About {{ displayName }}</h3>
              
              <div class="space-y-6">
                <!-- Bio -->
                <div v-if="profile.bio">
                  <h4 class="text-sm font-medium text-gray-400 mb-2">Bio</h4>
                  <p class="text-white">{{ profile.bio }}</p>
                </div>

                <!-- Basic Info -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div v-if="profile.gender">
                    <h4 class="text-sm font-medium text-gray-400 mb-1">Gender</h4>
                    <p class="text-white capitalize">{{ profile.gender }}</p>
                  </div>
                  <div v-if="profile.birthdate">
                    <h4 class="text-sm font-medium text-gray-400 mb-1">Birthday</h4>
                    <p class="text-white">{{ new Date(profile.birthdate).toLocaleDateString('th-TH') }}</p>
                  </div>
                  <div v-if="profile.location">
                    <h4 class="text-sm font-medium text-gray-400 mb-1">Location</h4>
                    <p class="text-white">{{ profile.location }}</p>
                  </div>
                  <div v-if="profile.website">
                    <h4 class="text-sm font-medium text-gray-400 mb-1">Website</h4>
                    <a :href="profile.website" target="_blank" class="text-vikinger-cyan hover:underline">{{ profile.website }}</a>
                  </div>
                </div>

                <!-- Interests -->
                <div v-if="profile.interests">
                  <h4 class="text-sm font-medium text-gray-400 mb-2">Interests</h4>
                  <p class="text-white">{{ profile.interests }}</p>
                </div>
              </div>
            </BaseCard>
          </template>

          <!-- Friends Tab -->
          <template v-if="activeTab === 'friends'">
            <BaseCard class="bg-gray-800 border-gray-700 text-center py-12">
              <Icon icon="fluent:people-24-regular" class="w-16 h-16 text-gray-600 mx-auto mb-4" />
              <p class="text-gray-400">Friends list coming soon</p>
            </BaseCard>
          </template>

          <!-- Photos Tab -->
          <template v-if="activeTab === 'photos'">
            <BaseCard class="bg-gray-800 border-gray-700 text-center py-12">
              <Icon icon="fluent:image-24-regular" class="w-16 h-16 text-gray-600 mx-auto mb-4" />
              <p class="text-gray-400">Photos coming soon</p>
            </BaseCard>
          </template>

          <!-- Videos Tab -->
          <template v-if="activeTab === 'videos'">
            <BaseCard class="bg-gray-800 border-gray-700 text-center py-12">
              <Icon icon="fluent:video-24-regular" class="w-16 h-16 text-gray-600 mx-auto mb-4" />
              <p class="text-gray-400">Videos coming soon</p>
            </BaseCard>
          </template>

          <!-- Badges Tab -->
          <template v-if="activeTab === 'badges'">
            <BaseCard class="bg-gray-800 border-gray-700 text-center py-12">
              <Icon icon="fluent:trophy-24-regular" class="w-16 h-16 text-gray-600 mx-auto mb-4" />
              <p class="text-gray-400">Badges coming soon</p>
            </BaseCard>
          </template>

          <!-- Groups Tab -->
          <template v-if="activeTab === 'groups'">
            <BaseCard class="bg-gray-800 border-gray-700 text-center py-12">
              <Icon icon="fluent:people-community-24-regular" class="w-16 h-16 text-gray-600 mx-auto mb-4" />
              <p class="text-gray-400">Groups coming soon</p>
            </BaseCard>
          </template>

          <!-- Events Tab -->
          <template v-if="activeTab === 'events'">
            <BaseCard class="bg-gray-800 border-gray-700 text-center py-12">
              <Icon icon="fluent:calendar-24-regular" class="w-16 h-16 text-gray-600 mx-auto mb-4" />
              <p class="text-gray-400">Events coming soon</p>
            </BaseCard>
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
      <NuxtLink to="/newsfeed" class="mt-4 inline-block text-vikinger-purple hover:underline">
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
</style>
