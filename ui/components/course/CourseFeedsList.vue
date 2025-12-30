<script setup lang="ts">
/**
 * CourseFeedsList - Complete feed list for course posts
 * Features: Create/View/Edit posts, Comments, Replies, Like/Dislike, Share, Infinite Scroll
 */
import { Icon } from '@iconify/vue'
import { ref, computed, onMounted, onUnmounted } from 'vue'
import CourseCreatePostBox from './CourseCreatePostBox.vue'
import CourseFeedPost from './CourseFeedPost.vue'
import CourseEditPostModal from './CourseEditPostModal.vue'

interface Props {
  courseId: string | number
  isCourseAdmin?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  isCourseAdmin: false
})

const api = useApi()
const swal = useSweetAlert()

// Get current user
const { user } = useAuth()

// State
const posts = ref<any[]>([])
const loading = ref(true)
const loadingMore = ref(false)
const hasMore = ref(true)
const page = ref(1)
const perPage = ref(10)
const error = ref<string | null>(null)

// Edit modal state
const showEditModal = ref(false)
const editingPost = ref<any>(null)

// Active tab
const activeTab = ref('all')

// Tabs
const tabs = [
  { id: 'all', label: 'ทั้งหมด', icon: 'fluent:feed-24-regular' },
  { id: 'discussions', label: 'พูดคุย', icon: 'fluent:chat-multiple-24-regular' },
  { id: 'questions', label: 'คำถาม', icon: 'fluent:question-circle-24-regular' },
  { id: 'materials', label: 'เอกสาร', icon: 'fluent:document-24-regular' },
  { id: 'announcements', label: 'ประกาศ', icon: 'fluent:megaphone-24-regular' },
]

// Infinite scroll
const loadMoreTrigger = ref<HTMLElement | null>(null)
let observer: IntersectionObserver | null = null

// Fetch posts
const fetchPosts = async (reset = false) => {
  if (reset) {
    page.value = 1
    hasMore.value = true
    error.value = null
  }
  
  loading.value = reset
  loadingMore.value = !reset
  
  try {
    const params: any = { 
      page: page.value, 
      per_page: perPage.value 
    }
    
    // Add tab filter if not 'all'
    if (activeTab.value !== 'all') {
      params.type = activeTab.value
    }
    
    const response = await api.get(`/api/courses/${props.courseId}/posts`, { params })
    
    // Handle different response formats
    const data = response.data?.data || response.data || response.posts || []
    const pagination = response.pagination || response.meta || {}
    
    if (reset) {
      posts.value = Array.isArray(data) ? data : []
    } else {
      const newPosts = Array.isArray(data) ? data : []
      posts.value = [...posts.value, ...newPosts]
    }
    
    // Update hasMore based on response
    if (pagination.has_more !== undefined) {
      hasMore.value = pagination.has_more
    } else if (pagination.last_page !== undefined) {
      hasMore.value = page.value < pagination.last_page
    } else {
      hasMore.value = Array.isArray(data) && data.length === perPage.value
    }
  } catch (err: any) {
    console.error('Error fetching posts:', err)
    error.value = 'ไม่สามารถโหลดโพสต์ได้'
    if (!reset) {
      // Don't show error for load more failures
      error.value = null
    }
  } finally {
    loading.value = false
    loadingMore.value = false
  }
}

// Load more
const loadMore = async () => {
  if (loadingMore.value || !hasMore.value) return
  page.value++
  await fetchPosts(false)
}

// Refresh
const refreshFeed = async () => {
  await fetchPosts(true)
}

// Handle new post created
const handlePostCreated = (newPost: any) => {
  if (newPost) {
    posts.value.unshift(newPost)
  }
}

// Handle post updated
const handlePostUpdated = (updatedPost: any) => {
  const index = posts.value.findIndex(p => p.id === updatedPost.id)
  if (index !== -1) {
    posts.value[index] = { ...posts.value[index], ...updatedPost }
  }
  showEditModal.value = false
  editingPost.value = null
}

// Handle post deleted
const handlePostDeleted = (postId: number) => {
  posts.value = posts.value.filter(p => p.id !== postId)
}

// Open edit modal
const openEditModal = (post: any) => {
  editingPost.value = { ...post }
  showEditModal.value = true
}

// Handle tab change
const changeTab = (tabId: string) => {
  if (activeTab.value === tabId) return
  activeTab.value = tabId
  fetchPosts(true)
}

// Setup IntersectionObserver for infinite scroll
const setupObserver = () => {
  if (observer) observer.disconnect()
  
  observer = new IntersectionObserver(
    (entries) => {
      const target = entries[0]
      if (target.isIntersecting && hasMore.value && !loadingMore.value && !loading.value) {
        loadMore()
      }
    },
    {
      root: null,
      rootMargin: '200px',
      threshold: 0.1,
    }
  )
  
  if (loadMoreTrigger.value) {
    observer.observe(loadMoreTrigger.value)
  }
}

// Init
onMounted(() => {
  fetchPosts(true)
  setupObserver()
})

onUnmounted(() => {
  if (observer) {
    observer.disconnect()
  }
})
</script>

<template>
  <div class="space-y-4">
    <!-- Create Post Box -->
    <CourseCreatePostBox 
      :course-id="courseId" 
      @post-created="handlePostCreated" 
    />
    
    <!-- Feed Tabs -->
    <div class="flex items-center gap-2 overflow-x-auto pb-2 scrollbar-hide">
      <button
        v-for="tab in tabs"
        :key="tab.id"
        @click="changeTab(tab.id)"
        class="flex items-center gap-2 px-4 py-2 rounded-full text-sm font-medium whitespace-nowrap transition-all duration-300"
        :class="
          activeTab === tab.id
            ? 'bg-gradient-to-r from-blue-600 to-blue-700 text-white shadow-lg'
            : 'bg-white dark:bg-vikinger-dark-200 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-vikinger-dark-100'
        "
      >
        <Icon :icon="tab.icon" class="w-4 h-4" />
        {{ tab.label }}
      </button>
    </div>
    
    <!-- Error State -->
    <div v-if="error && !loading" class="bg-red-50 dark:bg-red-900/20 rounded-xl p-6 text-center">
      <Icon icon="fluent:error-circle-24-regular" class="w-12 h-12 mx-auto mb-3 text-red-500" />
      <p class="text-red-600 dark:text-red-400 mb-4">{{ error }}</p>
      <button 
        @click="refreshFeed" 
        class="px-6 py-2 bg-blue-600 text-white rounded-full hover:bg-blue-700 transition-colors"
      >
        <Icon icon="fluent:arrow-sync-24-regular" class="w-4 h-4 inline mr-2" />
        ลองใหม่อีกครั้ง
      </button>
    </div>
    
    <!-- Loading State -->
    <div v-else-if="loading" class="space-y-4">
      <div
        v-for="i in 3"
        :key="i"
        class="bg-white dark:bg-vikinger-dark-200 rounded-xl p-6 animate-pulse"
      >
        <div class="flex items-center gap-4 mb-4">
          <div class="w-10 h-10 bg-gray-200 dark:bg-vikinger-dark-100 rounded-full"></div>
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
    
    <!-- Posts Feed -->
    <template v-else>
      <!-- Refresh Button -->
      <div class="flex justify-center">
        <button 
          @click="refreshFeed" 
          class="flex items-center gap-2 px-4 py-2 text-sm text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-full transition-colors"
        >
          <Icon icon="fluent:arrow-sync-24-regular" class="w-4 h-4" />
          รีเฟรชฟีด
        </button>
      </div>
      
      <!-- Posts List -->
      <TransitionGroup 
        v-if="posts.length > 0"
        tag="div" 
        name="feed"
        class="space-y-4"
      >
        <CourseFeedPost
          v-for="post in posts"
          :key="post.id"
          :post="post"
          :course-id="courseId"
          :current-user-id="user?.id"
          :is-course-admin="isCourseAdmin"
          @edit="openEditModal"
          @delete="handlePostDeleted"
          @post-updated="handlePostUpdated"
        />
      </TransitionGroup>
      
      <!-- Empty State -->
      <div 
        v-else 
        class="bg-white dark:bg-vikinger-dark-200 rounded-xl p-8 text-center"
      >
        <Icon icon="fluent:chat-24-regular" class="w-16 h-16 mx-auto mb-4 text-gray-300 dark:text-gray-600" />
        <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-2">
          ยังไม่มีโพสต์
        </h3>
        <p class="text-gray-500 dark:text-gray-400">
          เป็นคนแรกที่โพสต์ในรายวิชานี้!
        </p>
      </div>
      
      <!-- Infinite Scroll Trigger -->
      <div 
        ref="loadMoreTrigger"
        v-if="hasMore && posts.length > 0"
        class="flex justify-center py-6"
      >
        <div 
          v-if="loadingMore" 
          class="flex items-center gap-2 px-6 py-3 bg-white dark:bg-vikinger-dark-200 text-blue-600 rounded-full shadow-sm"
        >
          <Icon icon="fluent:spinner-ios-20-regular" class="w-5 h-5 animate-spin" />
          กำลังโหลด...
        </div>
        <div v-else class="h-4 w-full"></div>
      </div>
      
      <!-- End of Feed -->
      <div 
        v-if="!hasMore && posts.length > 0" 
        class="text-center py-6 text-gray-500 dark:text-gray-400"
      >
        <Icon icon="fluent:checkmark-circle-24-regular" class="w-8 h-8 mx-auto mb-2 text-green-500" />
        <p>คุณได้ดูโพสต์ทั้งหมดแล้ว!</p>
      </div>
    </template>
    
    <!-- Edit Post Modal -->
    <CourseEditPostModal
      v-if="showEditModal && editingPost"
      :show="showEditModal"
      :post="editingPost"
      :course-id="courseId"
      @close="showEditModal = false; editingPost = null"
      @post-updated="handlePostUpdated"
    />
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

/* Feed animations */
.feed-enter-active {
  transition: all 0.4s ease-out;
}

.feed-leave-active {
  transition: all 0.3s ease-in;
}

.feed-enter-from {
  opacity: 0;
  transform: translateY(20px);
}

.feed-leave-to {
  opacity: 0;
  transform: translateY(-20px);
}
</style>
