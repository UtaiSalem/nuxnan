<template>
  <Teleport to="body">
    <Transition name="modal">
      <div v-if="show" class="fixed inset-0 z-[100] overflow-y-auto" @click.self="closeModal">
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-black bg-opacity-90 transition-opacity" @click="closeModal"></div>

        <!-- Modal Content -->
        <div class="relative min-h-screen flex items-center justify-center p-4" @click.self="closeModal">
          <div class="relative bg-white dark:bg-gray-900 rounded-lg shadow-2xl max-w-6xl w-full max-h-[90vh] overflow-hidden flex flex-col" @click.stop>
            <!-- Header -->
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 relative flex justify-between items-center shrink-0">
              <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                <Icon icon="heroicons:photo-20-solid" class="w-6 h-6 text-indigo-600" />
                <span>{{ title || 'รูปภาพ' }}</span>
                <span class="text-sm font-normal text-gray-500 dark:text-gray-400 ml-2">
                    ({{ currentIndex + 1 }} / {{ images.length }})
                </span>
              </h3>
              
              <!-- Close Button -->
              <button
                @click="closeModal"
                class="w-8 h-8 rounded-full bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 flex items-center justify-center transition-colors text-gray-500 dark:text-gray-400"
              >
                <Icon icon="heroicons:x-mark" class="w-5 h-5" />
              </button>
            </div>

            <!-- Main Image Display -->
            <div class="relative bg-black flex-1 min-h-0 overflow-hidden flex flex-col">
              <div 
                ref="imageContainer"
                class="w-full h-full relative p-4 overflow-auto flex items-center justify-center transition-all duration-300"
                :class="{'cursor-zoom-in': !isZoomed, 'cursor-grab': isZoomed}"
                @click.self="toggleZoom"
              >
                <img
                  v-if="images.length > 0"
                  :src="currentImageUrl"
                  :alt="`รูปที่ ${currentIndex + 1}`"
                  class="transition-all duration-300"
                  :class="isZoomed ? 'max-w-none max-h-none' : 'max-h-full max-w-full object-contain'"
                  :style="isZoomed ? { transform: `scale(${zoomLevel})` } : {}"
                  @click="toggleZoom"
                  @error="handleImageError"
                >
                
                <!-- Navigation Arrows -->
                <button
                  v-if="images.length > 1 && !isZoomed"
                  @click.stop="previousImage"
                  class="absolute left-4 top-1/2 -translate-y-1/2 bg-white/10 hover:bg-white/20 text-white w-12 h-12 rounded-full flex items-center justify-center backdrop-blur-sm transition-all hover:scale-110 z-10"
                >
                  <Icon icon="heroicons:chevron-left-20-solid" class="w-8 h-8" />
                </button>
                
                <button
                  v-if="images.length > 1 && !isZoomed"
                  @click.stop="nextImage"
                  class="absolute right-4 top-1/2 -translate-y-1/2 bg-white/10 hover:bg-white/20 text-white w-12 h-12 rounded-full flex items-center justify-center backdrop-blur-sm transition-all hover:scale-110 z-10"
                >
                  <Icon icon="heroicons:chevron-right-20-solid" class="w-8 h-8" />
                </button>

                <!-- Tools Overlay -->
                <div class="absolute top-4 right-4 flex gap-2 z-20">
                    <button
                      @click.stop="toggleZoom"
                      class="bg-black/50 hover:bg-black/70 text-white p-2 rounded-lg backdrop-blur-sm transition-all hover:scale-105"
                      :title="isZoomed ? 'ย่อรูป' : 'ขยายรูป'"
                    >
                      <Icon :icon="isZoomed ? 'heroicons:magnifying-glass-minus-20-solid' : 'heroicons:magnifying-glass-plus-20-solid'" class="w-5 h-5" />
                    </button>
                    
                    <a
                      :href="currentImageUrl"
                      download
                      target="_blank"
                      class="bg-black/50 hover:bg-black/70 text-white p-2 rounded-lg backdrop-blur-sm transition-all hover:scale-105"
                      title="ดาวน์โหลดรูปนี้"
                      @click.stop
                    >
                      <Icon icon="heroicons:arrow-down-tray-20-solid" class="w-5 h-5" />
                    </a>
                </div>
              </div>

              <div v-if="images.length === 0" class="h-full flex items-center justify-center text-white/50">
                <div class="text-center">
                  <Icon icon="heroicons:photo" class="w-24 h-24 mb-4 mx-auto" />
                  <p class="text-lg">ไม่มีรูปภาพ</p>
                </div>
              </div>
            </div>

            <!-- Thumbnails -->
            <div v-if="images.length > 1" class="p-4 bg-white dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700 shrink-0">
              <div class="flex gap-3 overflow-x-auto pb-2 scrollbar-thin scrollbar-thumb-gray-300 dark:scrollbar-thumb-gray-600">
                <button
                  v-for="(image, index) in images"
                  :key="index"
                  @click="currentIndex = index"
                  class="flex-shrink-0 relative group transition-all"
                  :class="currentIndex === index ? 'opacity-100' : 'opacity-60 hover:opacity-100'"
                >
                  <img
                    :src="getImageUrl(image)"
                    class="w-16 h-16 object-cover rounded-lg border-2 transition-all"
                    :class="currentIndex === index ? 'border-indigo-500 ring-2 ring-indigo-500/20' : 'border-transparent group-hover:border-gray-300 dark:group-hover:border-gray-600'"
                    @error="handleImageError"
                  >
                </button>
              </div>
            </div>

            <!-- Footer / Description (Optional) -->
            <div v-if="currentImage && currentImage.description" class="px-6 py-3 bg-white dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700 shrink-0">
               <p class="text-sm text-gray-600 dark:text-gray-300">{{ currentImage.description }}</p>
            </div>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import { Icon } from '@iconify/vue'

const props = defineProps({
  show: {
    type: Boolean,
    default: false
  },
  images: {
    type: Array, // Array of strings (URLs) or Objects { url: '...', full_url: '...', description: '...' }
    default: () => []
  },
  startIndex: {
    type: Number,
    default: 0
  },
  title: {
      type: String,
      default: ''
  }
})

const emit = defineEmits(['close'])

const currentIndex = ref(0)

const currentImage = computed(() => {
    if (!props.images || props.images.length === 0) return null
    return props.images[currentIndex.value]
})

const currentImageUrl = computed(() => {
  if (!currentImage.value) return ''
  return getImageUrl(currentImage.value)
})

function getImageUrl(image) {
  if (!image) return ''
  if (typeof image === 'string') return image
  return image.full_url || image.image_url || image.url || ''
}

const isZoomed = ref(false)
const zoomLevel = ref(1)

function toggleZoom() {
    isZoomed.value = !isZoomed.value
    zoomLevel.value = isZoomed.value ? 1.5 : 1 // Adjusted zoom level
}

function closeModal() {
  emit('close')
  isZoomed.value = false // Reset zoom on close
}

function previousImage() {
  if (props.images.length <= 1) return
  currentIndex.value = currentIndex.value > 0 ? currentIndex.value - 1 : props.images.length - 1
  isZoomed.value = false // Reset zoom
}

function nextImage() {
  if (props.images.length <= 1) return
  currentIndex.value = currentIndex.value < props.images.length - 1 ? currentIndex.value + 1 : 0
  isZoomed.value = false // Reset zoom
}

function handleImageError(event) {
    // Basic placeholder or error handling
    // event.target.style.display = 'none' 
}

// Watchers
watch(() => props.show, (newVal) => {
  if (newVal) {
    currentIndex.value = props.startIndex
    document.body.style.overflow = 'hidden' // Prevent background scrolling
  } else {
    document.body.style.overflow = ''
  }
})

watch(() => props.startIndex, (newVal) => {
    if (props.show) {
        currentIndex.value = newVal
    }
})

// Keyboard navigation
function handleKeydown(event) {
  if (!props.show) return
  
  switch (event.key) {
    case 'ArrowLeft':
      previousImage()
      break
    case 'ArrowRight':
      nextImage()
      break
    case 'Escape':
      closeModal()
      break
  }
}

onMounted(() => {
    document.addEventListener('keydown', handleKeydown)
})

onUnmounted(() => {
    document.removeEventListener('keydown', handleKeydown)
    document.body.style.overflow = ''
})

</script>

<style scoped>
.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.3s ease;
}

.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}
</style>
