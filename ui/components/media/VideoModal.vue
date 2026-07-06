<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { Icon } from '@iconify/vue'

interface Props {
  youtubeUrl?: string
  vimeoUrl?: string
  videoSrc?: string
  title?: string
}

const props = defineProps<Props>()

const emit = defineEmits<{
  close: []
}>()

const isVisible = ref(false)
const isAnimating = ref(true)
const iframeLoaded = ref(false)

// Extract YouTube video ID from URL
const getYoutubeVideoId = (url: string): string | null => {
  if (!url) return null

  if (/^[a-zA-Z0-9_-]{11}$/.test(url)) {
    return url
  }

  const patterns = [
    /(?:youtube\.com\/watch\?v=|youtube\.com\/watch\?.+&v=)([a-zA-Z0-9_-]{11})/,
    /youtu\.be\/([a-zA-Z0-9_-]{11})/,
    /youtube\.com\/embed\/([a-zA-Z0-9_-]{11})/,
    /youtube\.com\/v\/([a-zA-Z0-9_-]{11})/,
    /youtube\.com\/shorts\/([a-zA-Z0-9_-]{11})/,
  ]

  for (const pattern of patterns) {
    const match = url.match(pattern)
    if (match && match[1]) {
      return match[1]
    }
  }

  return null
}

const youtubeVideoId = computed(() =>
  props.youtubeUrl ? getYoutubeVideoId(props.youtubeUrl) : null
)

// YouTube embed URL with autoplay
const youtubeEmbedUrl = computed(() => {
  if (!youtubeVideoId.value) return null
  const params = new URLSearchParams({
    autoplay: '1',
    rel: '0',
    modestbranding: '1',
    playsinline: '1',
  })
  return `https://www.youtube-nocookie.com/embed/${youtubeVideoId.value}?${params.toString()}`
})

// Close modal
const closeModal = () => {
  isAnimating.value = true
  isVisible.value = false
  setTimeout(() => {
    emit('close')
  }, 300)
}

// Handle escape key
const handleKeydown = (e: KeyboardEvent) => {
  if (e.key === 'Escape') {
    closeModal()
  }
}

// Handle backdrop click
const handleBackdropClick = (e: MouseEvent) => {
  if ((e.target as HTMLElement).classList.contains('modal-backdrop')) {
    closeModal()
  }
}

const handleIframeLoad = () => {
  iframeLoaded.value = true
}

// Prevent body scroll when modal is open
onMounted(() => {
  document.addEventListener('keydown', handleKeydown)
  document.body.style.overflow = 'hidden'

  // Trigger enter animation
  requestAnimationFrame(() => {
    isVisible.value = true
    setTimeout(() => {
      isAnimating.value = false
    }, 300)
  })
})

onUnmounted(() => {
  document.removeEventListener('keydown', handleKeydown)
  document.body.style.overflow = ''
})
</script>

<template>
  <Teleport to="body">
    <div
      class="modal-backdrop fixed inset-0 z-[9999] flex items-center justify-center p-4 md:p-8 transition-all duration-300"
      :class="[isVisible ? 'bg-black/80 backdrop-blur-md' : 'bg-black/0 backdrop-blur-0']"
      @click="handleBackdropClick"
    >
      <!-- Modal Content -->
      <div
        class="relative w-full max-w-5xl transition-all duration-300 transform"
        :class="[isVisible ? 'opacity-100 scale-100' : 'opacity-0 scale-95']"
      >
        <!-- Close Button -->
        <button
          @click="closeModal"
          class="absolute -top-12 right-0 md:-right-12 md:top-0 p-3 text-white/80 hover:text-white hover:bg-white/10 rounded-full transition-all duration-200 z-10 group"
          title="ปิด (Esc)"
        >
          <Icon
            icon="fluent:dismiss-24-filled"
            class="w-8 h-8 transform group-hover:rotate-90 transition-transform duration-200"
          />
        </button>

        <!-- Title -->
        <h3 v-if="title" class="text-white text-lg font-semibold mb-4 truncate pr-12 md:pr-0">
          {{ title }}
        </h3>

        <!-- Video Container -->
        <div
          class="relative rounded-2xl overflow-hidden shadow-2xl ring-1 ring-white/20 bg-black aspect-video"
        >
          <!-- YouTube iframe -->
          <template v-if="youtubeEmbedUrl">
            <!-- Loading state -->
            <div
              v-if="!iframeLoaded"
              class="absolute inset-0 flex items-center justify-center bg-gray-900"
            >
              <div class="text-center">
                <div
                  class="w-20 h-20 mx-auto mb-4 bg-gray-800 rounded-full flex items-center justify-center animate-pulse"
                >
                  <Icon icon="fluent:play-24-filled" class="w-10 h-10 text-gray-600" />
                </div>
                <span class="text-gray-500">กำลังโหลดวิดีโอ...</span>
              </div>
            </div>

            <!-- YouTube iframe player -->
            <iframe
              :src="youtubeEmbedUrl"
              class="absolute inset-0 w-full h-full"
              frameborder="0"
              allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
              allowfullscreen
              sandbox="allow-forms allow-scripts allow-same-origin allow-popups allow-popups-to-escape-sandbox allow-presentation"
              @load="handleIframeLoad"
            />
          </template>

          <!-- No video -->
          <div
            v-else
            class="w-full h-full flex items-center justify-center bg-gray-900"
          >
            <div class="text-center">
              <Icon icon="fluent:video-off-24-filled" class="w-16 h-16 text-gray-600 mx-auto mb-4" />
              <span class="text-gray-500">ไม่พบวิดีโอ</span>
            </div>
          </div>
        </div>

        <!-- Hints -->
        <p class="text-white/50 text-center text-sm mt-4 hidden md:block">
          กด
          <kbd class="px-2 py-1 bg-white/10 rounded text-white/70">Esc</kbd> หรือคลิกด้านนอกเพื่อปิด
        </p>
      </div>
    </div>
  </Teleport>
</template>

<style scoped>
kbd {
  font-family: ui-monospace, SFMono-Regular, 'SF Mono', Menlo, Consolas, 'Liberation Mono',
    monospace;
}
</style>
