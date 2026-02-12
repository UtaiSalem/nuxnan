<template>
  <Teleport to="body">
    <Transition name="modal">
      <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto" @click.self="closeModal">
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-black bg-opacity-75 transition-opacity" @click="closeModal"></div>

        <!-- Modal Content -->
        <div class="relative min-h-screen flex items-center justify-center p-4" @click.self="closeModal">
          <div class="relative bg-white rounded-lg shadow-2xl max-w-6xl w-full max-h-[90vh] overflow-hidden" @click.stop>
            <!-- Header -->
            <div class="px-6 py-4 border-b border-gray-200 bg-white relative">
              <h3 class="text-lg font-semibold text-gray-900 flex items-center gap-2 pr-12">
                <Icon icon="heroicons:photo-20-solid" class="w-6 h-6 text-green-600" />
                รูปภาพการเยี่ยมบ้าน
              </h3>
              <p class="text-sm text-gray-600 mt-1">
                วันที่ {{ formatDate(visit?.visit_date) }} - {{ images.length }} รูป
              </p>
              
              <!-- Close Button -->
              <button
                @click="closeModal"
                class="absolute top-4 right-4 w-10 h-10 bg-red-600 hover:bg-red-700 text-white rounded-full flex items-center justify-center transition-all hover:scale-110 shadow-lg"
              >
                <Icon icon="heroicons:x-mark" class="w-6 h-6" />
              </button>
            </div>

            <!-- Main Image Display -->
            <div class="relative bg-black" style="height: 60vh;">
              <div v-if="images.length > 0" class="h-full flex items-center justify-center">
                <img
                  :src="currentImageUrl"
                  :alt="`รูปที่ ${currentIndex + 1}`"
                  class="max-h-full max-w-full object-contain"
                  @error="handleImageError"
                >
                
                <!-- Navigation Arrows -->
                <button
                  v-if="images.length > 1"
                  @click="previousImage"
                  class="absolute left-4 top-1/2 -translate-y-1/2 bg-white bg-opacity-90 hover:bg-opacity-100 text-gray-800 w-14 h-14 rounded-full flex items-center justify-center shadow-lg transition-all hover:scale-110"
                >
                  <Icon icon="heroicons:chevron-left-20-solid" class="w-8 h-8" />
                </button>
                
                <button
                  v-if="images.length > 1"
                  @click="nextImage"
                  class="absolute right-4 top-1/2 -translate-y-1/2 bg-white bg-opacity-90 hover:bg-opacity-100 text-gray-800 w-14 h-14 rounded-full flex items-center justify-center shadow-lg transition-all hover:scale-110"
                >
                  <Icon icon="heroicons:chevron-right-20-solid" class="w-8 h-8" />
                </button>

                <!-- Image Counter -->
                <div class="absolute bottom-4 left-1/2 -translate-x-1/2 bg-black bg-opacity-75 text-white px-4 py-2 rounded-full text-sm">
                  {{ currentIndex + 1 }} / {{ images.length }}
                </div>

                <!-- Download Button -->
                <a
                  :href="currentImageUrl"
                  :download="`home_visit_${visit?.id}_${currentIndex + 1}.jpg`"
                  class="absolute top-4 right-4 bg-blue-600 hover:bg-blue-700 text-white px-5 py-3 rounded-lg flex items-center gap-2 shadow-xl transition-all hover:scale-105 font-bold"
                >
                  <Icon icon="heroicons:arrow-down-tray-20-solid" class="w-6 h-6" />
                  <span class="text-base">ดาวน์โหลด</span>
                </a>
              </div>

              <div v-else class="h-full flex items-center justify-center text-white">
                <div class="text-center">
                  <Icon icon="heroicons:photo" class="w-24 h-24 mb-4 opacity-50" />
                  <p class="text-lg">ไม่มีรูปภาพ</p>
                </div>
              </div>
            </div>

            <!-- Thumbnails -->
            <div v-if="images.length > 1" class="p-4 bg-gray-100 border-t border-gray-200">
              <div class="flex gap-3 overflow-x-auto pb-2 scrollbar-thin scrollbar-thumb-gray-300 scrollbar-track-gray-100">
                <button
                  v-for="(image, index) in images"
                  :key="index"
                  @click="currentIndex = index"
                  class="flex-shrink-0 relative group transition-all"
                  :class="currentIndex === index ? 'ring-4 ring-green-500 rounded-lg scale-105' : 'opacity-60 hover:opacity-100'"
                >
                  <img
                    :src="getImageUrl(image)"
                    :alt="`ภาพย่อที่ ${index + 1}`"
                    class="w-28 h-28 object-cover rounded-lg cursor-pointer transition-all border-2"
                    :class="currentIndex === index ? 'border-green-500' : 'border-gray-300 group-hover:border-green-400'"
                    @error="handleImageError"
                  >
                  
                  <!-- Selected Indicator -->
                  <div
                    v-if="currentIndex === index"
                    class="absolute inset-0 bg-green-500 bg-opacity-30 rounded-lg flex items-center justify-center pointer-events-none"
                  >
                    <div class="bg-green-600 rounded-full p-1">
                      <Icon icon="heroicons:check-20-solid" class="w-8 h-8 text-white" />
                    </div>
                  </div>
                  
                  <!-- Image Number Badge -->
                  <div class="absolute top-1 left-1 bg-black bg-opacity-60 text-white text-xs px-2 py-0.5 rounded-full font-medium">
                    {{ index + 1 }}
                  </div>
                </button>
              </div>
            </div>

            <!-- Image Info + Edit Description -->
            <div v-if="images.length > 0 && currentImage" class="px-6 py-4 bg-white border-t border-gray-200">
              <div class="flex items-center justify-between">
                <div class="flex-1">
                  <div class="flex items-center gap-2">
                    <Icon icon="heroicons:information-circle-20-solid" class="w-5 h-5" />
                    <span v-if="!editingDescription">{{ currentImage.description || 'รูปภาพหลักฐานการเยี่ยมบ้าน' }}</span>
                    <button v-if="!editingDescription" @click="startEditDescription" class="ml-2 px-2 py-1 text-xs bg-yellow-100 text-yellow-700 rounded hover:bg-yellow-200">แก้ไข</button>
                    <div v-else class="flex items-center gap-2">
                      <input v-model="editDescriptionText" class="border px-2 py-1 rounded text-sm" placeholder="แก้ไขคำอธิบาย..." />
                      <button @click="saveDescription" :disabled="savingDescription" class="px-2 py-1 text-xs bg-green-600 text-white rounded hover:bg-green-700">บันทึก</button>
                      <button @click="cancelEditDescription" :disabled="savingDescription" class="px-2 py-1 text-xs bg-gray-300 text-gray-700 rounded hover:bg-gray-400">ยกเลิก</button>
                    </div>
                  </div>
                  <p class="text-xs text-gray-500 mt-1">
                    อัปโหลดเมื่อ: {{ formatDateTime(currentImage.created_at) }}
                  </p>
                </div>
                <div class="flex items-center gap-2">
                  <button
                    @click="downloadAllImages"
                    class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-bold rounded-lg transition-colors flex items-center gap-2"
                  >
                    <Icon icon="heroicons:arrow-down-tray-20-solid" class="w-5 h-5" />
                    ดาวน์โหลดทั้งหมด
                  </button>
                  <button
                    @click="confirmDeleteImage"
                    :disabled="deletingImage"
                    class="px-5 py-2.5 bg-red-600 hover:bg-red-700 text-white text-sm font-bold rounded-lg transition-colors flex items-center gap-2"
                  >
                    <Icon icon="heroicons:trash-20-solid" class="w-5 h-5" />
                    ลบรูปนี้
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>

</template>

<script setup>
import { ref, computed, watch } from 'vue'
import Swal from 'sweetalert2'
import { Icon } from '@iconify/vue'

const props = defineProps({
  show: {
    type: Boolean,
    default: false
  },
  visit: {
    type: Object,
    default: null
  },
  images: {
    type: Array,
    default: () => []
  }
})

const emit = defineEmits(['close'])

const currentIndex = ref(0)

const currentImage = computed(() => props.images[currentIndex.value])

const currentImageUrl = computed(() => {
  if (!currentImage.value) return ''
  return getImageUrl(currentImage.value)
})

function getImageUrl(image) {
  if (!image) return ''
  
  // Handle different image path formats
  if (image.image_url) return image.image_url
  if (image.image_path) {
    // Remove 'public/' prefix if exists
    const path = image.image_path.replace(/^public\//, '')
    return `/storage/${path}`
  }
  return ''
}

function closeModal() {
  currentIndex.value = 0
  emit('close')
}

function previousImage() {
  currentIndex.value = currentIndex.value > 0 ? currentIndex.value - 1 : props.images.length - 1
}

function nextImage() {
  currentIndex.value = currentIndex.value < props.images.length - 1 ? currentIndex.value + 1 : 0
}

function handleImageError(event) {
  event.target.src = '/images/placeholder.png'
}

function formatDate(dateString) {
  if (!dateString) return ''
  const date = new Date(dateString)
  return date.toLocaleDateString('th-TH', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}

function formatDateTime(dateString) {
  if (!dateString) return ''
  const date = new Date(dateString)
  return date.toLocaleDateString('th-TH', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

async function downloadAllImages() {
  for (let i = 0; i < props.images.length; i++) {
    const image = props.images[i]
    const url = getImageUrl(image)
    const link = document.createElement('a')
    link.href = url
    link.download = `home_visit_${props.visit?.id}_${i + 1}.jpg`
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    
    // Add delay between downloads
    await new Promise(resolve => setTimeout(resolve, 500))
  }
}

// Reset index when modal opens
watch(() => props.show, (newVal) => {
  if (newVal) {
    currentIndex.value = 0
  }
})

// Keyboard navigation
watch(() => props.show, (newVal) => {
  if (newVal) {
    document.addEventListener('keydown', handleKeydown)
  } else {
    document.removeEventListener('keydown', handleKeydown)
  }
})

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
// Edit description state
const editingDescription = ref(false)
const editDescriptionText = ref('')
const savingDescription = ref(false)


function startEditDescription() {
  editDescriptionText.value = currentImage.value?.description || ''
  editingDescription.value = true
}

function cancelEditDescription() {
  editingDescription.value = false
  editDescriptionText.value = ''
}

async function saveDescription() {
  if (!currentImage.value) return
  savingDescription.value = true
  try {
    // สมมติ API endpoint: /api/home-visit/image/:id/update-description
    const imageId = currentImage.value.id
    const response = await fetch(`/api/home-visit/image/${imageId}/update-description`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
      body: JSON.stringify({ description: editDescriptionText.value })
    })
    const result = await response.json()
    if (result.success) {
      currentImage.value.description = editDescriptionText.value
      await Swal.fire({
        title: 'สำเร็จ',
        text: 'อัปเดตคำอธิบายรูปภาพเรียบร้อย',
        icon: 'success',
        timer: 1500,
        showConfirmButton: false
      })
      editingDescription.value = false
    } else {
      await Swal.fire({
        title: 'เกิดข้อผิดพลาด',
        text: result.message || 'ไม่สามารถอัปเดตคำอธิบายได้',
        icon: 'error',
      })
    }
  } catch (err) {
    await Swal.fire({
      title: 'เกิดข้อผิดพลาด',
      text: err.message || 'ไม่สามารถอัปเดตคำอธิบายได้',
      icon: 'error',
    })
  } finally {
    savingDescription.value = false
  }
}

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
