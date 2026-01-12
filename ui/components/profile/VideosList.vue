<script setup lang="ts">
import { Icon } from '@iconify/vue'
import BaseCard from '~/components/atoms/BaseCard.vue'

const props = defineProps<{
  userId?: string | number
  isOwnProfile?: boolean
}>()

const api = useApi()

// Types
interface Video {
  id: number
  title: string
  description?: string
  thumbnail: string
  video_url: string
  duration: number // in seconds
  views_count: number
  likes_count: number
  created_at: string
  privacy: 'public' | 'friends' | 'private'
}

// State
const videos = ref<Video[]>([])
const isLoading = ref(false)
const selectedVideo = ref<Video | null>(null)
const showUploadModal = ref(false)
const sortBy = ref<'recent' | 'popular' | 'oldest'>('recent')

// Computed
const sortedVideos = computed(() => {
  const sorted = [...videos.value]
  
  switch (sortBy.value) {
    case 'recent':
      return sorted.sort((a, b) => new Date(b.created_at).getTime() - new Date(a.created_at).getTime())
    case 'popular':
      return sorted.sort((a, b) => b.views_count - a.views_count)
    case 'oldest':
      return sorted.sort((a, b) => new Date(a.created_at).getTime() - new Date(b.created_at).getTime())
    default:
      return sorted
  }
})

// Fetch videos
const fetchVideos = async () => {
  isLoading.value = true
  
  try {
    const endpoint = props.userId 
      ? `/api/users/${props.userId}/videos`
      : `/api/profile/videos`
    
    const response = await api.get(endpoint) as {
      success: boolean
      data?: Video[]
      videos?: Video[]
    }
    
    if (response.success) {
      videos.value = response.data || response.videos || []
    } else {
      videos.value = getMockVideos()
    }
  } catch (error) {
    console.error('Error fetching videos:', error)
    videos.value = getMockVideos()
  } finally {
    isLoading.value = false
  }
}

// Mock data
const getMockVideos = (): Video[] => [
  {
    id: 1,
    title: 'How to Build a Nuxt 3 App from Scratch',
    description: 'เรียนรู้การสร้าง Web Application ด้วย Nuxt 3 ตั้งแต่ต้นจนจบ',
    thumbnail: 'https://picsum.photos/640/360?random=20',
    video_url: 'https://example.com/video1.mp4',
    duration: 1845, // 30:45
    views_count: 15420,
    likes_count: 892,
    created_at: new Date(Date.now() - 7 * 24 * 60 * 60 * 1000).toISOString(),
    privacy: 'public'
  },
  {
    id: 2,
    title: 'Vue 3 Composition API Tips & Tricks',
    description: 'เทคนิคการใช้งาน Composition API อย่างมีประสิทธิภาพ',
    thumbnail: 'https://picsum.photos/640/360?random=21',
    video_url: 'https://example.com/video2.mp4',
    duration: 925, // 15:25
    views_count: 8756,
    likes_count: 456,
    created_at: new Date(Date.now() - 14 * 24 * 60 * 60 * 1000).toISOString(),
    privacy: 'public'
  },
  {
    id: 3,
    title: 'การใช้งาน Tailwind CSS กับ Vue',
    description: 'วิธีการ Setup และ Best Practices ในการใช้ Tailwind CSS',
    thumbnail: 'https://picsum.photos/640/360?random=22',
    video_url: 'https://example.com/video3.mp4',
    duration: 2234, // 37:14
    views_count: 12340,
    likes_count: 678,
    created_at: new Date(Date.now() - 30 * 24 * 60 * 60 * 1000).toISOString(),
    privacy: 'friends'
  }
]

// Format duration
const formatDuration = (seconds: number): string => {
  const hrs = Math.floor(seconds / 3600)
  const mins = Math.floor((seconds % 3600) / 60)
  const secs = seconds % 60
  
  if (hrs > 0) {
    return `${hrs}:${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`
  }
  return `${mins}:${secs.toString().padStart(2, '0')}`
}

// Format view count
const formatViews = (count: number): string => {
  if (count >= 1000000) {
    return `${(count / 1000000).toFixed(1)}M`
  }
  if (count >= 1000) {
    return `${(count / 1000).toFixed(1)}K`
  }
  return count.toString()
}

// Format relative time
const formatRelativeTime = (dateStr: string): string => {
  const date = new Date(dateStr)
  const now = new Date()
  const diffMs = now.getTime() - date.getTime()
  const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24))
  
  if (diffDays === 0) return 'วันนี้'
  if (diffDays === 1) return 'เมื่อวาน'
  if (diffDays < 7) return `${diffDays} วันที่แล้ว`
  if (diffDays < 30) return `${Math.floor(diffDays / 7)} สัปดาห์ที่แล้ว`
  if (diffDays < 365) return `${Math.floor(diffDays / 30)} เดือนที่แล้ว`
  return `${Math.floor(diffDays / 365)} ปีที่แล้ว`
}

// Privacy icons
const privacyConfig = {
  public: { icon: 'fluent:globe-24-regular', label: 'สาธารณะ' },
  friends: { icon: 'fluent:people-24-regular', label: 'เพื่อน' },
  private: { icon: 'fluent:lock-closed-24-regular', label: 'ส่วนตัว' }
}

// Play video
const playVideo = (video: Video) => {
  selectedVideo.value = video
}

// Close video modal
const closeVideoModal = () => {
  selectedVideo.value = null
}

// Delete video
const deleteVideo = async (video: Video) => {
  if (!confirm(`ต้องการลบวิดีโอ "${video.title}" ใช่ไหม?`)) return
  
  try {
    await api.delete(`/api/profile/videos/${video.id}`)
    videos.value = videos.value.filter(v => v.id !== video.id)
  } catch (error) {
    console.error('Error deleting video:', error)
    alert('ไม่สามารถลบวิดีโอได้')
  }
}

// Initialize
onMounted(() => {
  fetchVideos()
})
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <BaseCard class="bg-gray-800 border-gray-700 p-5">
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-4">
        <div>
          <h3 class="text-xl font-bold text-white flex items-center gap-2">
            <Icon icon="fluent:video-24-filled" class="w-6 h-6 text-vikinger-cyan" />
            วิดีโอ
            <span class="text-vikinger-cyan font-normal ml-1">({{ videos.length }})</span>
          </h3>
          <p class="text-sm text-gray-400 mt-1">วิดีโอที่อัปโหลด</p>
        </div>
        
        <!-- Upload Button (Own Profile) -->
        <button
          v-if="isOwnProfile"
          @click="showUploadModal = true"
          class="px-4 py-2 bg-vikinger-purple text-white rounded-lg hover:bg-vikinger-purple/80 transition-colors flex items-center gap-2"
        >
          <Icon icon="fluent:arrow-upload-24-regular" class="w-5 h-5" />
          อัปโหลดวิดีโอ
        </button>
      </div>

      <!-- Sort Options -->
      <div class="flex items-center gap-4">
        <span class="text-sm text-gray-400">เรียงตาม:</span>
        <div class="flex gap-2">
          <button
            v-for="sort in [
              { key: 'recent', label: 'ล่าสุด' },
              { key: 'popular', label: 'ยอดนิยม' },
              { key: 'oldest', label: 'เก่าสุด' }
            ]"
            :key="sort.key"
            @click="sortBy = sort.key as typeof sortBy"
            :class="[
              'px-3 py-1.5 rounded-full text-sm transition-all',
              sortBy === sort.key
                ? 'bg-vikinger-purple text-white'
                : 'bg-gray-700 text-gray-400 hover:text-white'
            ]"
          >
            {{ sort.label }}
          </button>
        </div>
      </div>
    </BaseCard>

    <!-- Loading State -->
    <div v-if="isLoading" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
      <div v-for="i in 6" :key="i" class="animate-pulse">
        <BaseCard class="bg-gray-800 border-gray-700 overflow-hidden">
          <div class="aspect-video bg-gray-700"></div>
          <div class="p-4 space-y-3">
            <div class="h-4 bg-gray-700 rounded w-3/4"></div>
            <div class="h-3 bg-gray-700 rounded w-1/2"></div>
          </div>
        </BaseCard>
      </div>
    </div>

    <!-- Videos Grid -->
    <div v-else-if="sortedVideos.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
      <BaseCard 
        v-for="video in sortedVideos" 
        :key="video.id"
        class="bg-gray-800 border-gray-700 overflow-hidden group"
      >
        <!-- Thumbnail -->
        <div 
          class="aspect-video bg-gray-900 relative cursor-pointer"
          @click="playVideo(video)"
        >
          <img 
            :src="video.thumbnail" 
            :alt="video.title"
            class="w-full h-full object-cover group-hover:opacity-80 transition-opacity"
          />
          
          <!-- Play Button Overlay -->
          <div class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
            <div class="w-16 h-16 bg-vikinger-purple/90 rounded-full flex items-center justify-center">
              <Icon icon="fluent:play-24-filled" class="w-8 h-8 text-white ml-1" />
            </div>
          </div>
          
          <!-- Duration Badge -->
          <div class="absolute bottom-2 right-2 bg-black/80 text-white text-xs px-2 py-1 rounded">
            {{ formatDuration(video.duration) }}
          </div>

          <!-- Privacy Badge -->
          <div 
            v-if="video.privacy !== 'public'"
            class="absolute top-2 right-2 bg-black/70 text-white text-xs px-2 py-1 rounded-full flex items-center gap-1"
          >
            <Icon :icon="privacyConfig[video.privacy].icon" class="w-3 h-3" />
            {{ privacyConfig[video.privacy].label }}
          </div>
        </div>

        <!-- Content -->
        <div class="p-4">
          <h4 class="font-semibold text-white line-clamp-2 group-hover:text-vikinger-cyan transition-colors">
            {{ video.title }}
          </h4>
          
          <div class="flex items-center gap-3 mt-2 text-sm text-gray-500">
            <span class="flex items-center gap-1">
              <Icon icon="fluent:eye-24-regular" class="w-4 h-4" />
              {{ formatViews(video.views_count) }} views
            </span>
            <span>•</span>
            <span>{{ formatRelativeTime(video.created_at) }}</span>
          </div>

          <!-- Actions (Own Profile) -->
          <div v-if="isOwnProfile" class="flex items-center gap-2 mt-3 pt-3 border-t border-gray-700">
            <button
              class="flex-1 px-3 py-1.5 bg-gray-700 text-gray-300 text-sm rounded-lg hover:bg-gray-600 transition-colors flex items-center justify-center gap-1"
            >
              <Icon icon="fluent:edit-24-regular" class="w-4 h-4" />
              แก้ไข
            </button>
            <button
              @click="deleteVideo(video)"
              class="px-3 py-1.5 bg-red-500/20 text-red-400 text-sm rounded-lg hover:bg-red-500/30 transition-colors flex items-center justify-center gap-1"
            >
              <Icon icon="fluent:delete-24-regular" class="w-4 h-4" />
            </button>
          </div>
        </div>
      </BaseCard>
    </div>

    <!-- Empty State -->
    <BaseCard v-else class="bg-gray-800 border-gray-700 text-center py-12">
      <Icon icon="fluent:video-24-regular" class="w-16 h-16 text-gray-600 mx-auto mb-4" />
      <p class="text-gray-400">ยังไม่มีวิดีโอ</p>
      <p v-if="isOwnProfile" class="text-sm text-gray-500 mt-2">
        อัปโหลดวิดีโอเพื่อแชร์กับเพื่อนของคุณ!
      </p>
      <button 
        v-if="isOwnProfile" 
        @click="showUploadModal = true"
        class="inline-flex items-center gap-2 mt-4 px-4 py-2 bg-vikinger-purple text-white rounded-lg hover:bg-vikinger-purple/80 transition-colors"
      >
        <Icon icon="fluent:arrow-upload-24-regular" class="w-5 h-5" />
        อัปโหลดวิดีโอแรก
      </button>
    </BaseCard>

    <!-- Video Player Modal -->
    <Teleport to="body">
      <div 
        v-if="selectedVideo"
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
        @click="closeVideoModal"
      >
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-black/90"></div>
        
        <!-- Modal Content -->
        <div 
          class="relative w-full max-w-5xl z-10"
          @click.stop
        >
          <!-- Close Button -->
          <button 
            @click="closeVideoModal"
            class="absolute -top-12 right-0 text-white hover:text-vikinger-cyan transition-colors"
          >
            <Icon icon="fluent:dismiss-24-regular" class="w-8 h-8" />
          </button>

          <!-- Video Container -->
          <div class="aspect-video bg-black rounded-lg overflow-hidden shadow-2xl">
            <video
              :src="selectedVideo.video_url"
              controls
              autoplay
              class="w-full h-full"
            >
              Your browser does not support the video tag.
            </video>
          </div>

          <!-- Video Info -->
          <div class="mt-4 p-4 bg-gray-800 rounded-lg">
            <h3 class="text-xl font-bold text-white">{{ selectedVideo.title }}</h3>
            <p v-if="selectedVideo.description" class="text-gray-400 mt-2">
              {{ selectedVideo.description }}
            </p>
            <div class="flex items-center gap-4 mt-3 text-sm text-gray-500">
              <span class="flex items-center gap-1">
                <Icon icon="fluent:eye-24-regular" class="w-4 h-4" />
                {{ formatViews(selectedVideo.views_count) }} views
              </span>
              <span class="flex items-center gap-1">
                <Icon icon="fluent:thumb-like-24-regular" class="w-4 h-4" />
                {{ formatViews(selectedVideo.likes_count) }} likes
              </span>
              <span>{{ formatRelativeTime(selectedVideo.created_at) }}</span>
            </div>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- Upload Modal Placeholder -->
    <Teleport to="body">
      <div 
        v-if="showUploadModal"
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
        @click="showUploadModal = false"
      >
        <div class="absolute inset-0 bg-black/80"></div>
        
        <div 
          class="relative bg-gray-800 rounded-xl p-6 max-w-lg w-full z-10"
          @click.stop
        >
          <h3 class="text-xl font-bold text-white mb-4">อัปโหลดวิดีโอ</h3>
          
          <!-- Upload Area -->
          <div class="border-2 border-dashed border-gray-600 rounded-lg p-8 text-center hover:border-vikinger-purple transition-colors cursor-pointer">
            <Icon icon="fluent:cloud-arrow-up-24-regular" class="w-12 h-12 text-gray-500 mx-auto mb-3" />
            <p class="text-gray-400">คลิกหรือลากไฟล์มาวาง</p>
            <p class="text-sm text-gray-500 mt-1">MP4, WebM, MOV (สูงสุด 500MB)</p>
          </div>

          <div class="flex justify-end gap-3 mt-6">
            <button
              @click="showUploadModal = false"
              class="px-4 py-2 bg-gray-700 text-gray-300 rounded-lg hover:bg-gray-600 transition-colors"
            >
              ยกเลิก
            </button>
            <button
              class="px-4 py-2 bg-vikinger-purple text-white rounded-lg hover:bg-vikinger-purple/80 transition-colors"
              disabled
            >
              อัปโหลด
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>
