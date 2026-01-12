<script setup lang="ts">
import { Icon } from '@iconify/vue'
import BaseCard from '~/components/atoms/BaseCard.vue'

const props = defineProps<{
  userId?: string | number
  isOwnProfile?: boolean
}>()

const api = useApi()
const toast = useToast()

// Types
interface Photo {
  id: number
  url: string
  thumbnail_url?: string
  caption?: string
  created_at: string
  likes_count: number
  comments_count: number
  is_liked: boolean
}

// State
const photos = ref<Photo[]>([])
const isLoading = ref(false)
const currentPage = ref(1)
const lastPage = ref(1)
const hasMore = computed(() => currentPage.value < lastPage.value)
const selectedPhoto = ref<Photo | null>(null)
const showLightbox = ref(false)
const isUploading = ref(false)
const uploadProgress = ref(0)
const fileInput = ref<HTMLInputElement | null>(null)

// Fetch photos
const fetchPhotos = async (page: number = 1) => {
  isLoading.value = true
  
  try {
    const endpoint = props.userId 
      ? `/api/users/${props.userId}/photos?page=${page}`
      : `/api/profile/photos?page=${page}`
    
    const response = await api.get(endpoint) as {
      success: boolean
      data?: Photo[]
      photos?: Photo[]
      meta?: { current_page: number; last_page: number }
    }
    
    if (response.success) {
      const photosList = response.data || response.photos || []
      
      if (page === 1) {
        photos.value = photosList
      } else {
        photos.value = [...photos.value, ...photosList]
      }
      
      if (response.meta) {
        currentPage.value = response.meta.current_page
        lastPage.value = response.meta.last_page
      }
    }
  } catch (error) {
    console.error('Error fetching photos:', error)
  } finally {
    isLoading.value = false
  }
}

// Upload photos
const handleFileSelect = async (event: Event) => {
  const input = event.target as HTMLInputElement
  const files = input.files
  
  if (!files || files.length === 0) return
  
  isUploading.value = true
  uploadProgress.value = 0
  
  const formData = new FormData()
  Array.from(files).forEach((file, index) => {
    formData.append(`photos[${index}]`, file)
  })
  
  try {
    const response = await api.post('/api/profile/photos', formData, {
      onUploadProgress: (progressEvent: any) => {
        uploadProgress.value = Math.round((progressEvent.loaded * 100) / progressEvent.total)
      }
    }) as { success: boolean; photos?: Photo[] }
    
    if (response.success && response.photos) {
      photos.value = [...response.photos, ...photos.value]
      toast.success('อัพโหลดรูปภาพสำเร็จ')
    }
  } catch (error: any) {
    toast.error(error.message || 'ไม่สามารถอัพโหลดรูปภาพได้')
  } finally {
    isUploading.value = false
    uploadProgress.value = 0
    if (fileInput.value) fileInput.value.value = ''
  }
}

// Delete photo
const deletePhoto = async (photoId: number) => {
  try {
    const response = await api.delete(`/api/profile/photos/${photoId}`) as { success: boolean }
    
    if (response.success) {
      photos.value = photos.value.filter(p => p.id !== photoId)
      toast.success('ลบรูปภาพสำเร็จ')
      if (selectedPhoto.value?.id === photoId) {
        closeLightbox()
      }
    }
  } catch (error: any) {
    toast.error(error.message || 'ไม่สามารถลบรูปภาพได้')
  }
}

// Like photo
const toggleLike = async (photo: Photo) => {
  try {
    const endpoint = photo.is_liked 
      ? `/api/photos/${photo.id}/unlike`
      : `/api/photos/${photo.id}/like`
    
    const response = await api.post(endpoint) as { success: boolean }
    
    if (response.success) {
      photo.is_liked = !photo.is_liked
      photo.likes_count += photo.is_liked ? 1 : -1
    }
  } catch (error) {
    console.error('Error toggling like:', error)
  }
}

// Lightbox functions
const openLightbox = (photo: Photo) => {
  selectedPhoto.value = photo
  showLightbox.value = true
  document.body.style.overflow = 'hidden'
}

const closeLightbox = () => {
  showLightbox.value = false
  selectedPhoto.value = null
  document.body.style.overflow = ''
}

const navigatePhoto = (direction: 'prev' | 'next') => {
  if (!selectedPhoto.value) return
  
  const currentIndex = photos.value.findIndex(p => p.id === selectedPhoto.value?.id)
  let newIndex = direction === 'prev' ? currentIndex - 1 : currentIndex + 1
  
  if (newIndex < 0) newIndex = photos.value.length - 1
  if (newIndex >= photos.value.length) newIndex = 0
  
  selectedPhoto.value = photos.value[newIndex]
}

// Keyboard navigation
const handleKeydown = (event: KeyboardEvent) => {
  if (!showLightbox.value) return
  
  switch (event.key) {
    case 'Escape':
      closeLightbox()
      break
    case 'ArrowLeft':
      navigatePhoto('prev')
      break
    case 'ArrowRight':
      navigatePhoto('next')
      break
  }
}

// Load more
const loadMore = async () => {
  if (!hasMore.value || isLoading.value) return
  await fetchPhotos(currentPage.value + 1)
}

// Initialize
onMounted(() => {
  fetchPhotos()
  window.addEventListener('keydown', handleKeydown)
})

onUnmounted(() => {
  window.removeEventListener('keydown', handleKeydown)
})
</script>

<template>
  <div class="space-y-6">
    <!-- Header with Upload Button -->
    <BaseCard class="bg-gray-800 border-gray-700 p-5">
      <div class="flex items-center justify-between">
        <div>
          <h3 class="text-xl font-bold text-white flex items-center gap-2">
            <Icon icon="fluent:image-24-filled" class="w-6 h-6 text-vikinger-cyan" />
            รูปภาพ
            <span class="text-vikinger-cyan font-normal ml-1">({{ photos.length }})</span>
          </h3>
          <p class="text-sm text-gray-400 mt-1">คลังรูปภาพทั้งหมด</p>
        </div>
        
        <!-- Upload Button -->
        <button
          v-if="isOwnProfile"
          @click="fileInput?.click()"
          :disabled="isUploading"
          class="px-4 py-2 bg-vikinger-purple text-white rounded-lg hover:bg-vikinger-purple/80 transition-colors flex items-center gap-2 disabled:opacity-50"
        >
          <Icon 
            :icon="isUploading ? 'fluent:spinner-ios-20-regular' : 'fluent:arrow-upload-24-regular'" 
            :class="['w-5 h-5', isUploading && 'animate-spin']" 
          />
          {{ isUploading ? `กำลังอัพโหลด ${uploadProgress}%` : 'อัพโหลดรูป' }}
        </button>
        <input
          ref="fileInput"
          type="file"
          accept="image/*"
          multiple
          class="hidden"
          @change="handleFileSelect"
        />
      </div>
      
      <!-- Upload Progress -->
      <div v-if="isUploading" class="mt-4">
        <div class="h-2 bg-gray-700 rounded-full overflow-hidden">
          <div 
            class="h-full bg-gradient-to-r from-vikinger-purple to-vikinger-cyan transition-all duration-300"
            :style="{ width: uploadProgress + '%' }"
          />
        </div>
      </div>
    </BaseCard>

    <!-- Loading State -->
    <div v-if="isLoading && photos.length === 0" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
      <div v-for="i in 8" :key="i" class="aspect-square bg-gray-800 rounded-lg animate-pulse" />
    </div>

    <!-- Photos Grid -->
    <div v-else-if="photos.length > 0" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
      <div
        v-for="photo in photos"
        :key="photo.id"
        class="aspect-square bg-gray-800 rounded-lg overflow-hidden relative group cursor-pointer"
        @click="openLightbox(photo)"
      >
        <img
          :src="photo.thumbnail_url || photo.url"
          :alt="photo.caption || 'Photo'"
          class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-110"
          loading="lazy"
        />
        
        <!-- Overlay -->
        <div class="absolute inset-0 bg-black/60 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-4">
          <div class="flex items-center gap-1 text-white">
            <Icon icon="fluent:heart-24-filled" class="w-5 h-5" />
            <span class="font-medium">{{ photo.likes_count }}</span>
          </div>
          <div class="flex items-center gap-1 text-white">
            <Icon icon="fluent:chat-24-filled" class="w-5 h-5" />
            <span class="font-medium">{{ photo.comments_count }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <BaseCard v-else class="bg-gray-800 border-gray-700 text-center py-12">
      <Icon icon="fluent:image-24-regular" class="w-16 h-16 text-gray-600 mx-auto mb-4" />
      <p class="text-gray-400">ยังไม่มีรูปภาพ</p>
      <p v-if="isOwnProfile" class="text-sm text-gray-500 mt-2">
        เริ่มอัพโหลดรูปภาพเพื่อแชร์ช่วงเวลาดีๆ!
      </p>
    </BaseCard>

    <!-- Load More -->
    <div v-if="hasMore" class="text-center">
      <button
        @click="loadMore"
        :disabled="isLoading"
        class="px-6 py-3 bg-gray-700 text-white rounded-lg hover:bg-gray-600 transition-colors disabled:opacity-50 flex items-center gap-2 mx-auto"
      >
        <Icon v-if="isLoading" icon="fluent:spinner-ios-20-regular" class="w-5 h-5 animate-spin" />
        <span>{{ isLoading ? 'กำลังโหลด...' : 'โหลดเพิ่มเติม' }}</span>
      </button>
    </div>

    <!-- Lightbox Modal -->
    <Teleport to="body">
      <Transition name="lightbox">
        <div 
          v-if="showLightbox && selectedPhoto"
          class="fixed inset-0 z-50 bg-black/95 flex items-center justify-center"
        >
          <!-- Close Button -->
          <button
            @click="closeLightbox"
            class="absolute top-4 right-4 z-10 p-3 bg-gray-800/50 rounded-full text-white hover:bg-gray-700 transition-colors"
          >
            <Icon icon="fluent:dismiss-24-regular" class="w-6 h-6" />
          </button>

          <!-- Navigation -->
          <button
            v-if="photos.length > 1"
            @click="navigatePhoto('prev')"
            class="absolute left-4 z-10 p-3 bg-gray-800/50 rounded-full text-white hover:bg-gray-700 transition-colors"
          >
            <Icon icon="fluent:chevron-left-24-regular" class="w-6 h-6" />
          </button>
          <button
            v-if="photos.length > 1"
            @click="navigatePhoto('next')"
            class="absolute right-4 z-10 p-3 bg-gray-800/50 rounded-full text-white hover:bg-gray-700 transition-colors"
          >
            <Icon icon="fluent:chevron-right-24-regular" class="w-6 h-6" />
          </button>

          <!-- Image Container -->
          <div class="max-w-5xl max-h-[85vh] mx-4">
            <img
              :src="selectedPhoto.url"
              :alt="selectedPhoto.caption || 'Photo'"
              class="max-w-full max-h-[85vh] object-contain rounded-lg"
            />
          </div>

          <!-- Photo Info -->
          <div class="absolute bottom-0 left-0 right-0 p-6 bg-gradient-to-t from-black/80 to-transparent">
            <div class="max-w-5xl mx-auto flex items-center justify-between">
              <div>
                <p v-if="selectedPhoto.caption" class="text-white mb-2">{{ selectedPhoto.caption }}</p>
                <p class="text-gray-400 text-sm">
                  {{ new Date(selectedPhoto.created_at).toLocaleDateString('th-TH', { year: 'numeric', month: 'long', day: 'numeric' }) }}
                </p>
              </div>
              
              <div class="flex items-center gap-4">
                <button
                  @click.stop="toggleLike(selectedPhoto)"
                  :class="[
                    'flex items-center gap-2 px-4 py-2 rounded-lg transition-colors',
                    selectedPhoto.is_liked 
                      ? 'bg-pink-500/20 text-pink-400' 
                      : 'bg-gray-800 text-white hover:bg-gray-700'
                  ]"
                >
                  <Icon :icon="selectedPhoto.is_liked ? 'fluent:heart-24-filled' : 'fluent:heart-24-regular'" class="w-5 h-5" />
                  {{ selectedPhoto.likes_count }}
                </button>
                
                <button
                  v-if="isOwnProfile"
                  @click.stop="deletePhoto(selectedPhoto.id)"
                  class="flex items-center gap-2 px-4 py-2 bg-red-500/20 text-red-400 rounded-lg hover:bg-red-500/30 transition-colors"
                >
                  <Icon icon="fluent:delete-24-regular" class="w-5 h-5" />
                  ลบ
                </button>
              </div>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<style scoped>
.lightbox-enter-active,
.lightbox-leave-active {
  transition: all 0.3s ease;
}

.lightbox-enter-from,
.lightbox-leave-to {
  opacity: 0;
}
</style>
