<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { Icon } from '@iconify/vue'
import TimeClockWidget from '~/components/widgets/TimeClockWidget.vue'
import CreatePostBox from '~/components/play/feed/CreatePostBox.vue'
import FeedPost from '~/components/play/feed/FeedPost.vue'
import ProfileCompletionWidget from '~/components/organisms/ProfileCompletionWidget.vue'
import StatsBoxWidget from '~/components/organisms/StatsBoxWidget.vue'
import ReactionsWidget from '~/components/organisms/ReactionsWidget.vue'
import PeopleMayKnowWidget from '~/components/widgets/PeopleMayKnowWidget.vue'
import PendingFriendsWidget from '~/components/widgets/PendingFriendsWidget.vue'
import PendingAcademyInvitationsWidget from '~/components/widgets/PendingAcademyInvitationsWidget.vue'
import DonatesWidget from '~/components/widgets/DonatesWidget.vue'
import AdvertisesWidget from '~/components/widgets/AdvertisesWidget.vue'
import RecentlyViewedCoursesWidget from '~/components/widgets/RecentlyViewedCoursesWidget.vue'
import PopularCoursesWidget from '~/components/widgets/PopularCoursesWidget.vue'
import MemberedCoursesWidget from '~/components/widgets/MemberedCoursesWidget.vue'
import MemberedAcademiesWidget from '~/components/widgets/MemberedAcademiesWidget.vue'
import AllAcademiesWidget from '~/components/widgets/AllAcademiesWidget.vue'

definePageMeta({
  layout: 'main',
  middleware: 'auth',
})

useHead({
  title: 'Newsfeed - Plearnd',
})

const api = useApi()
const authStore = useAuthStore()

// Data - Activities are loaded here, sidebar widgets load their own data
const activities = ref([])
const isLoading = ref(true)
const isLoadingMore = ref(false)
const error = ref(null)

// Pagination for activities
const pagination = ref({
  currentPage: 1,
  lastPage: 1,
  total: 0,
  perPage: 15,
})
const hasMorePages = computed(() => pagination.value.currentPage < pagination.value.lastPage)

// Infinite scroll observer
const loadMoreTrigger = ref(null)
let observer = null

// Stats
const userStats = computed(() => ({
  posts: 294,
  postsGrowth: 0.4,
  likes: 12642,
  loves: 8913,
  dislikes: 945,
  happy: 7034,
}))

// Profile completion
const profileCompletion = computed(() => 59)
const quests = ref({ completed: 11, total: 30 })
const badges = ref({ unlocked: 22, total: 46 })

// Recent Stories
const recentStories = ref([
  {
    id: 1,
    user: 'Sarah Diamond',
    avatar: 'https://i.pravatar.cc/150?img=1',
    preview: 'https://picsum.photos/200/300?random=1',
  },
  {
    id: 2,
    user: 'James Murdock',
    avatar: 'https://i.pravatar.cc/150?img=2',
    preview: 'https://picsum.photos/200/300?random=2',
  },
])

// Featured Badges
const featuredBadges = ref([
  { id: 1, name: 'Gold Star', icon: '⭐', color: 'from-yellow-400 to-yellow-600' },
  { id: 2, name: 'Shield', icon: '🛡️', color: 'from-blue-400 to-blue-600' },
])



// Note: Sidebar widgets (PeopleMayKnow, PendingFriends, Donates, Advertises)
// now load their own data independently for better UX and performance

// Fetch activities with pagination
const fetchActivities = async (page = 1, append = false) => {
  if (page === 1) {
    isLoading.value = true
  } else {
    isLoadingMore.value = true
  }
  error.value = null

  try {
    const data = await api.get(`/api/newsfeed/activities?page=${page}&per_page=${pagination.value.perPage}`)

    if (data && data.activities) {
      const newActivities = Array.isArray(data.activities) ? data.activities : (data.activities.data || [])
      
      if (append) {
        activities.value = [...activities.value, ...newActivities]
      } else {
        activities.value = newActivities
      }

      // Update pagination info from response
      if (data.activities.current_page !== undefined) {
        pagination.value = {
          currentPage: data.activities.current_page || page,
          lastPage: data.activities.last_page || 1,
          total: data.activities.total || 0,
          perPage: data.activities.per_page || 15,
        }
      } else {
        // If using ActivityResource::collection, pagination is in wrapper
        pagination.value.currentPage = page
        // Check if there are more items
        if (newActivities.length < pagination.value.perPage) {
          pagination.value.lastPage = page
        } else {
          pagination.value.lastPage = page + 1
        }
      }
    }
  } catch (err) {
    console.error('Error fetching activities:', err)
    console.error('Error details:', {
      message: err?.message,
      status: err?.statusCode || err?.response?.status,
      data: err?.data,
    })
    
    // Show more specific error message
    if (err?.statusCode === 401 || err?.message?.includes('401') || err?.message?.includes('authentication')) {
      error.value = 'กรุณาเข้าสู่ระบบใหม่อีกครั้ง'
    } else {
      error.value = 'ไม่สามารถโหลดข้อมูลได้ กรุณาลองใหม่อีกครั้ง'
    }
  } finally {
    isLoading.value = false
    isLoadingMore.value = false
  }
}

// Load more activities (for infinite scroll)
const loadMore = async () => {
  if (hasMorePages.value && !isLoadingMore.value && !isLoading.value) {
    await fetchActivities(pagination.value.currentPage + 1, true)
  }
}

// Refresh feed
const refreshFeed = async () => {
  pagination.value.currentPage = 1
  await fetchActivities(1, false)
}

const handlePostCreated = (activity) => {
  // Add activity from API response directly to the feed
  if (activity && activity.id) {
    activities.value.unshift(activity)
  }
}

// Handle activity deletion
const handleDeleteActivity = (activityId) => {
  // Remove activity from list
  activities.value = activities.value.filter(a => a.id !== activityId)
  
  // Update total count
  if (pagination.value.total > 0) {
    pagination.value.total--
  }
}

// Handle post updated
const handlePostUpdated = (updatedPost) => {
  const index = activities.value.findIndex(a => {
    // Handle activity-wrapped posts
    if (a.target_resource && a.target_resource.id === updatedPost.id) return true
    return a.id === updatedPost.id
  })
  
  if (index !== -1) {
    // If it's an activity-wrapped post, update target_resource
    if (activities.value[index].target_resource) {
      activities.value[index] = {
        ...activities.value[index],
        target_resource: { ...activities.value[index].target_resource, ...updatedPost }
      }
    } else {
      activities.value[index] = { ...activities.value[index], ...updatedPost }
    }
  }
}

// Setup IntersectionObserver for infinite scroll
const setupIntersectionObserver = () => {
  observer?.disconnect()
  if (!loadMoreTrigger.value) return

  observer = new IntersectionObserver(
    (entries) => {
      const target = entries[0]
      if (target?.isIntersecting && hasMorePages.value && !isLoadingMore.value && !isLoading.value) {
        loadMore()
      }
    },
    {
      root: null,
      rootMargin: '200px',
      threshold: 0,
    }
  )

  observer.observe(loadMoreTrigger.value)
}

// sentinel มี v-if จึง unmount/mount ใหม่ทุกครั้งที่ isLoading หรือ hasMorePages เปลี่ยน
// ต้องผูก observer กับตัว element จริง ไม่ใช่ตั้งครั้งเดียวตอน mount
watch(loadMoreTrigger, (el) => {
  if (el) {
    setupIntersectionObserver()
  } else {
    observer?.disconnect()
  }
}, { flush: 'post' })

usePageLayoutWidgets({ left: true, right: true })

onMounted(async () => {
  // Fetch activities - sidebar widgets load their own data independently
  await fetchActivities(1, false)
})

onUnmounted(() => {
  if (observer) {
    observer.disconnect()
  }
})

// Back to top
const showScrollButton = ref(false)

const scrollToTop = () => {
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

const handleScroll = () => {
  showScrollButton.value = window.scrollY > 300
}

onMounted(() => {
  window.addEventListener('scroll', handleScroll, { passive: true })
  handleScroll()
})

onUnmounted(() => {
  window.removeEventListener('scroll', handleScroll)
})
</script>

<template>
  <div class="space-y-6">
    <!-- Left Widgets Column -->
    <ClientOnly><Teleport to="#left-widgets-slot">
      <div class="space-y-6">
        <RecentlyViewedCoursesWidget />
        <MemberedCoursesWidget />
        <PopularCoursesWidget />
        <ProfileCompletionWidget
          :completion="profileCompletion"
          :quests="quests"
          :badges="badges"
        />
        <StatsBoxWidget :stats="userStats" />
      </div>
    </Teleport></ClientOnly>

    <!-- Main Feed Content (Default Slot) -->
    <div class="space-y-6">
      <!-- Create Post Box -->
      <CreatePostBox @post-created="handlePostCreated" />

      <!-- Error State -->
      <div v-if="error && !isLoading" class="bg-red-50 dark:bg-red-900/20 rounded-xl p-4 sm:p-6 text-center">
        <Icon icon="fluent:error-circle-24-regular" class="w-12 h-12 mx-auto mb-3 text-red-500" />
        <p class="text-red-600 dark:text-red-400 mb-4">{{ error }}</p>
        <button 
          @click="refreshFeed" 
          class="min-h-[44px] sm:min-h-0 px-6 py-2 bg-vikinger-purple text-white rounded-full hover:bg-vikinger-purple/90 transition-colors"
        >
          <Icon icon="fluent:arrow-sync-24-regular" class="w-4 h-4 inline mr-2" />
          ลองใหม่อีกครั้ง
        </button>
      </div>

      <!-- Loading State -->
      <div v-else-if="isLoading" class="space-y-4">
        <div
          v-for="i in 3"
          :key="i"
          class="bg-white dark:bg-vikinger-dark-200 rounded-xl p-4 sm:p-6 animate-pulse"
        >
          <div class="flex items-center gap-4 mb-4">
            <div class="w-12 h-12 bg-gray-200 dark:bg-vikinger-dark-100 rounded-full"></div>
            <div class="flex-1 space-y-2">
              <div class="h-4 bg-gray-200 dark:bg-vikinger-dark-100 rounded w-1/4"></div>
              <div class="h-3 bg-gray-200 dark:bg-vikinger-dark-100 rounded w-1/6"></div>
            </div>
          </div>
          <div class="space-y-2">
            <div class="h-4 bg-gray-200 dark:bg-vikinger-dark-100 rounded w-full"></div>
            <div class="h-4 bg-gray-200 dark:bg-vikinger-dark-100 rounded w-3/4"></div>
          </div>
        </div>
      </div>

      <!-- Feed Posts -->
      <div v-else class="space-y-1.5 sm:space-y-2 md:space-y-3">
        <!-- Refresh Button -->
        <div class="flex justify-center">
          <button 
            @click="refreshFeed" 
            class="min-h-[44px] sm:min-h-0 flex items-center gap-2 px-4 py-2 text-sm text-vikinger-purple hover:bg-vikinger-purple/10 rounded-full transition-colors"
          >
            <Icon icon="fluent:arrow-sync-24-regular" class="w-4 h-4" />
            รีเฟรชฟีด
          </button>
        </div>

        <FeedPost
          v-for="activity in activities"
          :key="activity.id"
          :post="activity"
          @delete-success="handleDeleteActivity"
          @post-updated="handlePostUpdated"
        />

        <!-- Infinite Scroll Trigger -->
        <div 
          ref="loadMoreTrigger"
          v-if="hasMorePages"
          class="flex justify-center py-6"
        >
          <div 
            v-if="isLoadingMore" 
            class="flex items-center gap-2 px-4 sm:px-6 py-3 bg-white dark:bg-vikinger-dark-200 text-vikinger-purple rounded-full shadow-sm"
          >
            <Icon icon="fluent:spinner-ios-20-regular" class="w-5 h-5 animate-spin" />
            กำลังโหลด...
          </div>
          <div v-else class="h-4 w-full"></div>
        </div>

        <!-- End of Feed -->
        <div v-else-if="activities.length > 0" class="text-center py-6 text-gray-500 dark:text-gray-400">
          <Icon icon="fluent:checkmark-circle-24-regular" class="w-8 h-8 mx-auto mb-2 text-green-500" />
          <p>คุณได้ดูโพสต์ทั้งหมดแล้ว!</p>
        </div>

        <!-- Empty State -->
        <div
          v-if="activities.length === 0 && !error"
          class="bg-white dark:bg-vikinger-dark-200 rounded-xl p-4 sm:p-8 text-center"
        >
          <Icon icon="fluent:document-text-24-regular" class="w-16 h-16 mx-auto mb-4 text-gray-400" />
          <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-2">
            ยังไม่มีโพสต์
          </h3>
          <p class="text-gray-500 dark:text-gray-400">
            เริ่มต้นแชร์สิ่งดีๆ กับเพื่อนๆ ของคุณวันนี้!
          </p>
        </div>
      </div>
    </div>

    <!-- Right Widgets Column -->
    <ClientOnly><Teleport to="#right-widgets-slot">
      <div class="space-y-6">
        <!-- Academy Widgets -->
        <MemberedAcademiesWidget />
        <AllAcademiesWidget />

        <!-- Self-loading sidebar widgets -->
        <PeopleMayKnowWidget />
        <PendingFriendsWidget />
        <PendingAcademyInvitationsWidget />
        <DonatesWidget />
        <AdvertisesWidget />

        <!-- Recent Stories Widget -->
        <div class="bg-white dark:bg-vikinger-dark-200 rounded-xl p-4 shadow-sm">
          <h3 class="font-semibold text-gray-900 dark:text-white mb-4">Recent Stories</h3>
          <div class="flex gap-2 overflow-x-auto">
            <div
              v-for="story in recentStories"
              :key="story.id"
              class="flex-shrink-0 w-16 cursor-pointer group"
            >
              <div class="relative">
                <img
                  :src="story.preview"
                  :alt="story.user"
                  class="w-16 h-24 object-cover rounded-lg ring-2 ring-vikinger-purple"
                />
                <img
                  :src="story.avatar"
                  :alt="story.user"
                  class="absolute -bottom-2 left-1/2 -translate-x-1/2 w-8 h-8 rounded-full border-2 border-white dark:border-vikinger-dark-200"
                />
              </div>
            </div>
          </div>
        </div>

        <!-- Featured Badges Widget -->
        <div class="bg-white dark:bg-vikinger-dark-200 rounded-xl p-4 shadow-sm">
          <h3 class="font-semibold text-gray-900 dark:text-white mb-4">Featured Badges</h3>
          <div class="flex gap-3">
            <div
              v-for="badge in featuredBadges"
              :key="badge.id"
              class="w-12 h-12 rounded-lg flex items-center justify-center text-2xl bg-gradient-to-br shadow-lg"
              :class="badge.color"
            >
              {{ badge.icon }}
            </div>
          </div>
        </div>
      </div>
    </Teleport></ClientOnly>

    <!-- Scroll to Top Button -->
    <transition
      enter-active-class="transition ease-out duration-300"
      enter-from-class="opacity-0 translate-y-10"
      enter-to-class="opacity-100 translate-y-0"
      leave-active-class="transition ease-in duration-300"
      leave-from-class="opacity-100 translate-y-0"
      leave-to-class="opacity-0 translate-y-10"
    >
      <button
        v-if="showScrollButton"
        @click="scrollToTop"
        class="fixed bottom-4 right-4 sm:bottom-8 sm:right-8 z-[999] p-3 min-h-[44px] min-w-[44px] flex items-center justify-center bg-white dark:bg-gray-800 text-gray-900 dark:text-white rounded-full shadow-lg border border-gray-200 dark:border-gray-700 hover:shadow-xl hover:-translate-y-1 transition-all"
        title="เลื่อนขึ้นด้านบน"
        aria-label="เลื่อนขึ้นด้านบน"
      >
        <Icon icon="fluent:arrow-up-24-filled" class="w-6 h-6" />
      </button>
    </transition>
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

/* Feed item animations */
.feed-item-enter-active {
  transition: all 0.4s ease-out;
}

.feed-item-leave-active {
  transition: all 0.3s ease-in;
}

.feed-item-enter-from {
  opacity: 0;
  transform: translateY(20px);
}

.feed-item-leave-to {
  opacity: 0;
  transform: translateY(-20px);
}

/* Skeleton loading animation */
.skeleton {
  @apply bg-vikinger-dark-200;
  background: linear-gradient(
    90deg,
    var(--vikinger-dark-200) 0%,
    var(--vikinger-dark-100) 50%,
    var(--vikinger-dark-200) 100%
  );
  background-size: 200% 100%;
  animation: skeleton-loading 1.5s ease-in-out infinite;
}

@keyframes skeleton-loading {
  0% {
    background-position: 200% 0;
  }
  100% {
    background-position: -200% 0;
  }
}

/* Float animation for hero elements */
@keyframes float {
  0%,
  100% {
    transform: translateY(0) rotate(0deg);
  }
  33% {
    transform: translateY(-20px) rotate(5deg);
  }
  66% {
    transform: translateY(-10px) rotate(-5deg);
  }
}

.animate-float {
  animation: float 6s ease-in-out infinite;
}

/* Smooth scale transitions */
.scale-102 {
  transform: scale(1.02);
}

.scale-105 {
  transform: scale(1.05);
}

/* Responsive adjustments */
@media (max-width: 640px) {
  .vikinger-card {
    @apply rounded-lg;
  }
}
</style>
