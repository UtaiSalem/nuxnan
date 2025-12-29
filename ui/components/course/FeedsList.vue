<script setup lang="ts">
import { Icon } from '@iconify/vue'
import { useApi } from '~/composables/useApi'
import FeedPost from '~/components/course/FeedPost.vue'

interface Props {
  courseId: string | number
  isCourseAdmin?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  isCourseAdmin: false
})

const api = useApi()

// Get current user
const { user } = useAuth()

// State
const posts = ref<any[]>([])
const loading = ref(true)
const loadingMore = ref(false)
const hasMore = ref(true)
const page = ref(1)
const showCreateModal = ref(false)
const showEditModal = ref(false)
const editingPost = ref<any>(null)

// New post form
const newPost = ref({
  content: '',
  media: [] as File[],
  attachments: [] as File[]
})

// Fetch posts
const fetchPosts = async (reset = false) => {
  if (reset) {
    page.value = 1
    hasMore.value = true
  }
  
  loading.value = reset
  loadingMore.value = !reset
  
  try {
    const response = await api.get(`/courses/${props.courseId}/posts`, {
      params: { page: page.value, per_page: 10 }
    })
    
    const data = response.data.data || response.data
    
    if (reset) {
      posts.value = data
    } else {
      posts.value = [...posts.value, ...data]
    }
    
    hasMore.value = data.length === 10
  } catch (error) {
    console.error('Error fetching posts:', error)
  } finally {
    loading.value = false
    loadingMore.value = false
  }
}

// Load more
const loadMore = () => {
  if (loadingMore.value || !hasMore.value) return
  page.value++
  fetchPosts()
}

// Create post
const createPost = async () => {
  if (!newPost.value.content.trim() && newPost.value.media.length === 0) return
  
  const formData = new FormData()
  formData.append('content', newPost.value.content)
  
  newPost.value.media.forEach((file, index) => {
    formData.append(`media[${index}]`, file)
  })
  
  newPost.value.attachments.forEach((file, index) => {
    formData.append(`attachments[${index}]`, file)
  })
  
  try {
    await api.post(`/courses/${props.courseId}/posts`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
    
    showCreateModal.value = false
    resetForm()
    fetchPosts(true)
  } catch (error) {
    console.error('Error creating post:', error)
  }
}

// Edit post
const openEditModal = (post: any) => {
  editingPost.value = { ...post }
  showEditModal.value = true
}

// Update post
const updatePost = async () => {
  if (!editingPost.value) return
  
  try {
    await api.put(`/courses/${props.courseId}/posts/${editingPost.value.id}`, {
      content: editingPost.value.content
    })
    
    showEditModal.value = false
    fetchPosts(true)
  } catch (error) {
    console.error('Error updating post:', error)
  }
}

// Delete post
const deletePost = async (postId: number) => {
  if (!confirm('คุณต้องการลบโพสต์นี้หรือไม่?')) return
  
  try {
    await api.delete(`/courses/${props.courseId}/posts/${postId}`)
    posts.value = posts.value.filter(p => p.id !== postId)
  } catch (error) {
    console.error('Error deleting post:', error)
  }
}

// Like post
const likePost = async (post: any) => {
  try {
    if (post.is_liked) {
      await api.delete(`/courses/${props.courseId}/posts/${post.id}/like`)
      post.is_liked = false
      post.likes_count--
    } else {
      await api.post(`/courses/${props.courseId}/posts/${post.id}/like`)
      post.is_liked = true
      post.likes_count++
    }
  } catch (error) {
    console.error('Error liking post:', error)
  }
}

// Comment on post
const commentOnPost = (post: any) => {
  // Open comment input or modal
  // This could navigate to post detail or open inline comment
}

// Share post
const sharePost = async (post: any) => {
  // Implement share functionality
  if (navigator.share) {
    try {
      await navigator.share({
        title: 'แชร์โพสต์',
        url: `${window.location.origin}/courses/${props.courseId}/posts/${post.id}`
      })
    } catch (error) {
      console.log('Share cancelled')
    }
  }
}

// Handle file upload
const handleMediaUpload = (event: Event) => {
  const target = event.target as HTMLInputElement
  if (target.files) {
    newPost.value.media = [...newPost.value.media, ...Array.from(target.files)]
  }
}

const handleAttachmentUpload = (event: Event) => {
  const target = event.target as HTMLInputElement
  if (target.files) {
    newPost.value.attachments = [...newPost.value.attachments, ...Array.from(target.files)]
  }
}

// Remove media
const removeMedia = (index: number) => {
  newPost.value.media.splice(index, 1)
}

// Remove attachment
const removeAttachment = (index: number) => {
  newPost.value.attachments.splice(index, 1)
}

// Reset form
const resetForm = () => {
  newPost.value = {
    content: '',
    media: [],
    attachments: []
  }
}

// Infinite scroll
const handleScroll = () => {
  const scrollBottom = window.innerHeight + window.scrollY >= document.body.offsetHeight - 500
  if (scrollBottom && !loadingMore.value && hasMore.value) {
    loadMore()
  }
}

// Init
onMounted(() => {
  fetchPosts()
  window.addEventListener('scroll', handleScroll)
})

onUnmounted(() => {
  window.removeEventListener('scroll', handleScroll)
})
</script>

<template>
  <div class="space-y-4">
    <!-- Create Post Card -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4">
      <div class="flex gap-3">
        <img
          :src="user?.avatar || '/images/default-avatar.png'"
          :alt="user?.name"
          class="w-10 h-10 rounded-full"
        />
        <button
          @click="showCreateModal = true"
          class="flex-1 text-left px-4 py-2 bg-gray-100 dark:bg-gray-700 rounded-full text-gray-500 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
        >
          คุณกำลังคิดอะไรอยู่?
        </button>
      </div>
      
      <div class="flex items-center justify-end gap-2 mt-3 pt-3 border-t border-gray-100 dark:border-gray-700">
        <button
          @click="showCreateModal = true"
          class="flex items-center gap-2 px-3 py-1.5 text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
        >
          <Icon icon="fluent:image-24-regular" class="w-5 h-5 text-green-500" />
          <span>รูปภาพ</span>
        </button>
        <button
          @click="showCreateModal = true"
          class="flex items-center gap-2 px-3 py-1.5 text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
        >
          <Icon icon="fluent:attach-24-regular" class="w-5 h-5 text-blue-500" />
          <span>ไฟล์แนบ</span>
        </button>
      </div>
    </div>
    
    <!-- Loading -->
    <div v-if="loading" class="flex justify-center py-12">
      <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
    </div>
    
    <!-- Posts Feed -->
    <TransitionGroup 
      v-else-if="posts.length > 0"
      tag="div" 
      name="feed"
      class="space-y-4"
    >
      <FeedPost
        v-for="post in posts"
        :key="post.id"
        :post="post"
        :current-user-id="user?.id"
        :is-course-admin="isCourseAdmin"
        @edit="openEditModal"
        @delete="deletePost"
        @like="likePost"
        @comment="commentOnPost"
        @share="sharePost"
      />
    </TransitionGroup>
    
    <!-- Empty State -->
    <div v-else class="text-center py-12 bg-white dark:bg-gray-800 rounded-xl">
      <Icon icon="fluent:chat-24-regular" class="w-16 h-16 mx-auto text-gray-300 dark:text-gray-600" />
      <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">
        ยังไม่มีโพสต์
      </h3>
      <p class="mt-2 text-gray-500 dark:text-gray-400">
        เป็นคนแรกที่โพสต์ในคอร์สนี้!
      </p>
    </div>
    
    <!-- Load More -->
    <div v-if="loadingMore" class="flex justify-center py-4">
      <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-600"></div>
    </div>
    
    <!-- Create Post Modal -->
    <DialogModal :show="showCreateModal" @close="showCreateModal = false">
      <template #title>สร้างโพสต์</template>
      
      <template #content>
        <div class="space-y-4">
          <!-- User info -->
          <div class="flex items-center gap-3">
            <img
              :src="user?.avatar || '/images/default-avatar.png'"
              :alt="user?.name"
              class="w-10 h-10 rounded-full"
            />
            <div>
              <p class="font-medium text-gray-900 dark:text-white">{{ user?.name }}</p>
              <p class="text-xs text-gray-500">สาธารณะ</p>
            </div>
          </div>
          
          <!-- Content -->
          <textarea
            v-model="newPost.content"
            rows="4"
            placeholder="คุณกำลังคิดอะไรอยู่?"
            class="w-full px-4 py-3 border-0 bg-gray-50 dark:bg-gray-700 rounded-lg text-gray-900 dark:text-white focus:ring-0 resize-none"
          ></textarea>
          
          <!-- Media Preview -->
          <div v-if="newPost.media.length > 0" class="grid grid-cols-3 gap-2">
            <div 
              v-for="(file, index) in newPost.media" 
              :key="index"
              class="relative aspect-square rounded-lg overflow-hidden bg-gray-100 dark:bg-gray-700"
            >
              <img
                :src="URL.createObjectURL(file)"
                class="w-full h-full object-cover"
              />
              <button
                @click="removeMedia(index)"
                class="absolute top-1 right-1 w-6 h-6 rounded-full bg-black/50 text-white flex items-center justify-center hover:bg-black/70"
              >
                <Icon icon="fluent:dismiss-24-regular" class="w-4 h-4" />
              </button>
            </div>
          </div>
          
          <!-- Attachments Preview -->
          <div v-if="newPost.attachments.length > 0" class="space-y-2">
            <div
              v-for="(file, index) in newPost.attachments"
              :key="index"
              class="flex items-center justify-between p-2 bg-gray-50 dark:bg-gray-700 rounded-lg"
            >
              <div class="flex items-center gap-2">
                <Icon icon="fluent:document-24-regular" class="w-5 h-5 text-blue-500" />
                <span class="text-sm text-gray-700 dark:text-gray-300 truncate">{{ file.name }}</span>
              </div>
              <button
                @click="removeAttachment(index)"
                class="text-gray-400 hover:text-red-500"
              >
                <Icon icon="fluent:dismiss-24-regular" class="w-4 h-4" />
              </button>
            </div>
          </div>
          
          <!-- Upload buttons -->
          <div class="flex items-center gap-2 pt-2 border-t border-gray-100 dark:border-gray-700">
            <label class="flex items-center gap-2 px-3 py-2 text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg cursor-pointer transition-colors">
              <Icon icon="fluent:image-24-regular" class="w-5 h-5 text-green-500" />
              <span>รูปภาพ</span>
              <input
                type="file"
                accept="image/*"
                multiple
                class="hidden"
                @change="handleMediaUpload"
              />
            </label>
            
            <label class="flex items-center gap-2 px-3 py-2 text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg cursor-pointer transition-colors">
              <Icon icon="fluent:attach-24-regular" class="w-5 h-5 text-blue-500" />
              <span>ไฟล์แนบ</span>
              <input
                type="file"
                multiple
                class="hidden"
                @change="handleAttachmentUpload"
              />
            </label>
          </div>
        </div>
      </template>
      
      <template #footer>
        <button
          @click="showCreateModal = false"
          class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg"
        >
          ยกเลิก
        </button>
        <button
          @click="createPost"
          :disabled="!newPost.content.trim() && newPost.media.length === 0"
          class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          โพสต์
        </button>
      </template>
    </DialogModal>
    
    <!-- Edit Post Modal -->
    <DialogModal :show="showEditModal" @close="showEditModal = false">
      <template #title>แก้ไขโพสต์</template>
      
      <template #content>
        <div v-if="editingPost">
          <textarea
            v-model="editingPost.content"
            rows="4"
            class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white"
          ></textarea>
        </div>
      </template>
      
      <template #footer>
        <button
          @click="showEditModal = false"
          class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg"
        >
          ยกเลิก
        </button>
        <button
          @click="updatePost"
          class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
        >
          บันทึก
        </button>
      </template>
    </DialogModal>
  </div>
</template>

<style scoped>
.feed-enter-active,
.feed-leave-active {
  transition: all 0.3s ease;
}

.feed-enter-from,
.feed-leave-to {
  opacity: 0;
  transform: translateY(-20px);
}
</style>
