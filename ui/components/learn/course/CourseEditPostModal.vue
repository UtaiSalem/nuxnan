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
const selectedPrivacy = ref(props.post?.privacy_settings || 3)

// Image handling
const selectedImages = ref<File[]>([])
const imageInput = ref<HTMLInputElement | null>(null)
const imagesToRemove = ref<number[]>([])

// Watch for post changes
watch(() => props.post, (newPost) => {
  if (newPost) {
    postContent.value = newPost.content || ''
    selectedPrivacy.value = newPost.privacy_settings || 3
    selectedImages.value = []
    imagesToRemove.value = []
  }
}, { immediate: true })

// Existing images (from post data)
const existingImages = computed(() => {
  if (!props.post) return []
  const images = props.post.images || props.post.media || props.post.imagesResources || []
  return images.filter((img: any) => !imagesToRemove.value.includes(img.id))
})

// New image previews
const imagePreviews = computed(() => {
  if (typeof window === 'undefined') return []
  return selectedImages.value.map(file => ({
    file,
    url: URL.createObjectURL(file)
  }))
})

// Total image count
const totalImageCount = computed(() => {
  return existingImages.value.length + imagePreviews.value.length
})

// Close modal
const closeModal = () => {
  emit('close')
}

// Image handling
const handleImageSelect = (event: Event) => {
  const target = event.target as HTMLInputElement
  if (!target.files) return
  
  const files = Array.from(target.files)
  const remaining = 10 - totalImageCount.value
  
  if (files.length > remaining) {
    swal.warning(`สามารถเพิ่ม ได้อีก ${remaining} รูปเท่านั้น (สูงสุด 10 รูป)`)
    return
  }
  
  selectedImages.value = [...selectedImages.value, ...files]
  target.value = ''
}

const removeNewImage = (index: number) => {
  selectedImages.value.splice(index, 1)
}

const removeExistingImage = (imageId: number) => {
  if (!imagesToRemove.value.includes(imageId)) {
    imagesToRemove.value.push(imageId)
  }
}

const triggerImageInput = () => {
  imageInput.value?.click()
}

// Privacy options
const privacyOptions = [
  { value: 1, label: 'สาธารณะ', icon: 'fluent:globe-24-regular' },
  { value: 2, label: 'เฉพาะสมาชิกคอร์ส', icon: 'fluent:people-24-regular' },
  { value: 3, label: 'เฉพาะครูและผู้ดูแล', icon: 'fluent:lock-closed-24-regular' }
]

// Update post
const updatePost = async () => {
  if (!postContent.value.trim()) {
    swal.warning('กรุณาใส่เนื้อหาโพสต์')
    return
  }
  
  if (isSubmitting.value) return
  
  isSubmitting.value = true
  
  try {
    const formData = new FormData()
    formData.append('content', postContent.value)
    formData.append('privacy_settings', selectedPrivacy.value.toString())
    
    // Add new images
    selectedImages.value.forEach((file, index) => {
      formData.append(`images[${index}]`, file)
    })
    
    // Add images to remove
    imagesToRemove.value.forEach((imageId, index) => {
      formData.append(`remove_images[${index}]`, imageId.toString())
    })
    
    const response = await api.post(`/api/courses/${props.courseId}/posts/${props.post.id}?_method=PATCH`, formData)
    
    if (response.success || response.data) {
      const updatedPost = response.data || response.post || {
        ...props.post,
        content: postContent.value,
        privacy_settings: selectedPrivacy.value,
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
            <div class="p-4 max-h-[70vh] overflow-y-auto">
              <!-- Hidden file input -->
              <input type="file" ref="imageInput" class="hidden" accept="image/*" multiple @change="handleImageSelect" />

              <!-- User Info -->
              <div class="flex items-center gap-3 mb-4">
                <img 
                  :src="post.user?.avatar || post.author?.avatar || user?.avatar || '/images/default-avatar.png'" 
                  class="w-10 h-10 rounded-full object-cover" 
                />
                <div class="flex-1">
                  <div class="font-medium text-gray-800 dark:text-white">
                    {{ post.user?.name || post.author?.name || user?.name }}
                  </div>
                  <div class="flex items-center gap-2">
                    <Icon icon="fluent:book-24-regular" class="w-3 h-3 text-gray-500" />
                    <span class="text-xs text-gray-500">โพสต์ในรายวิชา</span>
                    <!-- Privacy Selector -->
                    <select 
                      v-model="selectedPrivacy"
                      class="text-xs px-2 py-0.5 rounded border border-gray-200 dark:border-vikinger-dark-50/30 bg-white dark:bg-vikinger-dark-100"
                    >
                      <option v-for="option in privacyOptions" :key="option.value" :value="option.value">
                        {{ option.label }}
                      </option>
                    </select>
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

              <!-- Existing Images -->
              <div v-if="existingImages.length > 0" class="mb-4">
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-2">รูปภาพปัจจุบัน</p>
                <div class="flex flex-wrap gap-2">
                  <div v-for="(image, index) in existingImages" :key="image.id || index" class="relative group">
                    <img 
                      :src="image.url || image"
                      class="w-24 h-24 object-cover rounded-lg border border-gray-200 dark:border-vikinger-dark-50/30"
                    />
                    <button 
                      @click="removeExistingImage(image.id)"
                      class="absolute -top-1 -right-1 w-6 h-6 bg-red-500 text-white rounded-full flex items-center justify-center shadow-sm md:opacity-0 md:group-hover:opacity-100 transition-opacity"
                    >
                      <Icon icon="fluent:dismiss-24-regular" class="w-4 h-4" />
                    </button>
                  </div>
                </div>
              </div>

              <!-- New Images Preview -->
              <div v-if="imagePreviews.length > 0" class="mb-4">
                <p class="text-sm font-medium text-green-600 dark:text-green-400 mb-2">รูปภาพใหม่</p>
                <div class="flex flex-wrap gap-2">
                  <div v-for="(preview, index) in imagePreviews" :key="index" class="relative group">
                    <img 
                      :src="preview.url" 
                      class="w-24 h-24 object-cover rounded-lg border-2 border-green-400 dark:border-green-500"
                    />
                    <button 
                      @click="removeNewImage(index)" 
                      class="absolute -top-1 -right-1 w-6 h-6 bg-red-500 text-white rounded-full flex items-center justify-center shadow-sm md:opacity-0 md:group-hover:opacity-100 transition-opacity"
                    >
                      <Icon icon="fluent:dismiss-24-regular" class="w-4 h-4" />
                    </button>
                  </div>
                  <button 
                    v-if="totalImageCount < 10" 
                    @click="triggerImageInput" 
                    class="w-24 h-24 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg flex items-center justify-center hover:border-vikinger-purple hover:bg-vikinger-purple/5 transition-all"
                  >
                    <Icon icon="fluent:add-24-regular" class="w-8 h-8 text-gray-400" />
                  </button>
                </div>
                <p class="text-xs text-gray-500 mt-1">{{ totalImageCount }}/10 รูป</p>
              </div>

              <!-- Add Images Button (if no images yet) -->
              <div v-if="imagePreviews.length === 0" class="mb-4">
                <button 
                  @click="triggerImageInput" 
                  class="flex items-center gap-2 px-3 py-2 rounded-lg bg-white dark:bg-vikinger-dark-200 border border-gray-200 dark:border-vikinger-dark-50/30 hover:border-green-400 hover:bg-green-50 dark:hover:bg-green-900/20 transition-all"
                  :disabled="totalImageCount >= 10"
                >
                  <Icon icon="fluent:image-24-regular" class="w-5 h-5 text-green-500" />
                  <span class="text-sm text-gray-700 dark:text-gray-300">เพิ่มรูปภาพ</span>
                </button>
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
