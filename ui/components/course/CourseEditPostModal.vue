<script setup lang="ts">
/**
 * CourseEditPostModal - Modal for editing course posts
 */
import { Icon } from '@iconify/vue'
import { ref, computed, watch } from 'vue'

const props = defineProps<{
  show: boolean
  post: any
  courseId: string | number
}>()

const emit = defineEmits<{
  'close': []
  'post-updated': [post: any]
}>()

const { user } = useAuth()
const api = useApi()
const swal = useSweetAlert()

// Form state
const postContent = ref(props.post?.content || '')
const isSubmitting = ref(false)

// Watch for post changes
watch(() => props.post, (newPost) => {
  if (newPost) {
    postContent.value = newPost.content || ''
  }
}, { immediate: true })

// Close modal
const closeModal = () => {
  emit('close')
}

// Update post
const updatePost = async () => {
  if (!postContent.value.trim()) {
    swal.warning('กรุณาใส่เนื้อหาโพสต์')
    return
  }
  
  if (isSubmitting.value) return
  
  isSubmitting.value = true
  
  try {
    const response = await api.put(`/api/courses/${props.courseId}/posts/${props.post.id}`, {
      content: postContent.value
    })
    
    if (response.success || response.data) {
      const updatedPost = response.data || response.post || {
        ...props.post,
        content: postContent.value,
        is_edited: true
      }
      emit('post-updated', updatedPost)
      swal.toast('แก้ไขโพสต์สำเร็จ!', 'success')
    } else {
      swal.error(response.message || 'ไม่สามารถแก้ไขโพสต์ได้')
    }
  } catch (error: any) {
    console.error('Error updating post:', error)
    let errorMessage = 'เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง'
    if (error?.data?.message) errorMessage = error.data.message
    swal.error(errorMessage)
  } finally {
    isSubmitting.value = false
  }
}
</script>

<template>
  <Teleport to="body">
    <Transition name="modal">
      <div 
        v-if="show" 
        class="fixed inset-0 bg-black/50 z-50 flex items-start justify-center pt-10 md:pt-16 overflow-y-auto backdrop-blur-sm"
        @click.self="closeModal"
      >
        <div class="w-full max-w-2xl mx-4 mb-10 modal-content">
          <div class="bg-white dark:bg-vikinger-dark-300 rounded-xl shadow-2xl">
            <!-- Header -->
            <div class="flex items-center justify-between p-4 border-b border-gray-200 dark:border-vikinger-dark-50/30">
              <div class="flex items-center gap-2">
                <Icon icon="fluent:edit-24-regular" class="w-5 h-5 text-blue-500" />
                <h2 class="text-xl font-bold text-gray-800 dark:text-white">แก้ไขโพสต์</h2>
              </div>
              <button @click="closeModal" class="p-2 hover:bg-gray-100 dark:hover:bg-vikinger-dark-200 rounded-full">
                <Icon icon="fluent:dismiss-24-regular" class="w-6 h-6 text-gray-500" />
              </button>
            </div>

            <!-- Body -->
            <div class="p-4">
              <!-- User Info -->
              <div class="flex items-center gap-3 mb-4">
                <img 
                  :src="post.user?.avatar || post.author?.avatar || user?.avatar || '/images/default-avatar.png'" 
                  class="w-10 h-10 rounded-full object-cover" 
                />
                <div>
                  <div class="font-medium text-gray-800 dark:text-white">
                    {{ post.user?.name || post.author?.name || user?.name }}
                  </div>
                  <div class="flex items-center gap-1 text-xs text-gray-500">
                    <Icon icon="fluent:book-24-regular" class="w-3 h-3" />
                    <span>โพสต์ในรายวิชา</span>
                  </div>
                </div>
              </div>

              <!-- Post Input -->
              <div class="rounded-lg mb-4 min-h-[150px] p-4 bg-gray-50 dark:bg-vikinger-dark-200">
                <textarea 
                  v-model="postContent" 
                  placeholder="แก้ไขเนื้อหาโพสต์..." 
                  rows="6"
                  class="w-full bg-transparent border-none outline-none resize-none text-gray-800 dark:text-white placeholder-gray-400" 
                  @keydown.ctrl.enter="updatePost" 
                  :disabled="isSubmitting" 
                />
              </div>

              <!-- Existing Images (read-only) -->
              <div v-if="post.images?.length || post.media?.length" class="mb-4">
                <p class="text-sm text-gray-500 mb-2">รูปภาพในโพสต์ (ไม่สามารถแก้ไขได้)</p>
                <div class="flex flex-wrap gap-2">
                  <img 
                    v-for="(image, index) in (post.images || post.media || post.imagesResources || [])" 
                    :key="index"
                    :src="image.url || image"
                    class="w-20 h-20 object-cover rounded-lg opacity-70"
                  />
                </div>
              </div>
            </div>

            <!-- Footer -->
            <div class="p-4 border-t border-gray-200 dark:border-vikinger-dark-50/30 flex gap-3">
              <button 
                @click="closeModal"
                class="flex-1 py-3 px-4 bg-gray-100 dark:bg-vikinger-dark-200 text-gray-700 dark:text-gray-300 font-semibold rounded-lg hover:bg-gray-200 dark:hover:bg-vikinger-dark-100 transition-all"
              >
                ยกเลิก
              </button>
              <button 
                @click="updatePost" 
                :disabled="isSubmitting || !postContent.trim()" 
                class="flex-1 py-3 px-4 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
              >
                <Icon v-if="isSubmitting" icon="fluent:spinner-ios-20-regular" class="w-5 h-5 animate-spin" />
                <span>{{ isSubmitting ? 'กำลังบันทึก...' : 'บันทึก' }}</span>
              </button>
            </div>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.modal-enter-active {
  transition: opacity 0.3s ease;
}

.modal-leave-active {
  transition: opacity 0.2s ease;
}

.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}

.modal-enter-active .modal-content {
  animation: modal-in 0.35s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.modal-leave-active .modal-content {
  animation: modal-out 0.2s ease-in forwards;
}

@keyframes modal-in {
  from {
    opacity: 0;
    transform: scale(0.9) translateY(-20px);
  }
  to {
    opacity: 1;
    transform: scale(1) translateY(0);
  }
}

@keyframes modal-out {
  from {
    opacity: 1;
    transform: scale(1) translateY(0);
  }
  to {
    opacity: 0;
    transform: scale(0.95) translateY(10px);
  }
}
</style>
