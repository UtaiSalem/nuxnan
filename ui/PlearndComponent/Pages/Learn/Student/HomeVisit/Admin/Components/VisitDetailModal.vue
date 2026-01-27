<template>
  <div
    class="fixed inset-0 z-50 overflow-y-auto"
    @click.self="$emit('close')"
  >
    <!-- Backdrop -->
    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>

    <!-- Modal -->
    <div class="flex min-h-screen items-center justify-center p-4">
      <div
        class="relative bg-white rounded-lg shadow-xl max-w-4xl w-full max-h-[90vh] overflow-hidden"
        @click.stop
      >
        <!-- Header -->
        <div class="bg-gradient-to-r from-indigo-600 to-purple-600 px-6 py-4">
          <div class="flex items-center justify-between">
            <div class="flex items-center text-white">
              <i class="fas fa-home text-2xl mr-3"></i>
              <div>
                <h3 class="text-xl font-bold">รายละเอียดการเยี่ยมบ้าน</h3>
                <p class="text-indigo-100 text-sm mt-1">
                  รหัส: #{{ visit.id }}
                </p>
              </div>
            </div>
            <button
              @click="$emit('close')"
              class="text-white hover:text-gray-200 transition-colors rounded-full hover:bg-white/20 p-2"
              title="ปิดหน้าต่าง"
            >
              <i class="fas fa-times text-2xl"></i>
            </button>
          </div>
        </div>

        <!-- Content -->
        <div class="overflow-y-auto max-h-[calc(90vh-80px)]">
          <div class="p-6 space-y-6">
            <!-- Status and Quick Info -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
              <div class="bg-blue-50 rounded-lg p-4">
                <div class="text-sm font-medium text-blue-600 mb-1">วันที่เยี่ยม</div>
                <div class="text-lg font-bold text-gray-900">
                  {{ formatDate(visit.visit_date) }}
                </div>
                <div v-if="visit.visit_time" class="text-sm text-gray-600 mt-1">
                  เวลา {{ formatTime(visit.visit_time) }}
                </div>
              </div>

              <div class="bg-green-50 rounded-lg p-4">
                <div class="text-sm font-medium text-green-600 mb-1">สถานะ</div>
                <div>
                  <span :class="getStatusBadgeClass(visit.visit_status)">
                    {{ getStatusText(visit.visit_status) }}
                  </span>
                </div>
              </div>

              <div class="bg-purple-50 rounded-lg p-4">
                <div class="text-sm font-medium text-purple-600 mb-1">โซน</div>
                <div class="text-sm font-semibold text-gray-900">
                  <i class="fas fa-map-marker-alt mr-1"></i>
                  {{ visit.zone?.zone_name || 'ไม่ระบุ' }}
                </div>
              </div>

              <div class="bg-orange-50 rounded-lg p-4">
                <div class="text-sm font-medium text-orange-600 mb-1">รูปภาพ</div>
                <div class="text-lg font-bold text-gray-900">
                  <i class="fas fa-images mr-2"></i>
                  {{ visit.images?.length || 0 }} รูป
                </div>
              </div>
            </div>

            <!-- Student Information -->
            <div class="bg-white border border-gray-200 rounded-lg p-6">
              <h4 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                <i class="fas fa-user-graduate mr-2 text-indigo-600"></i>
                ข้อมูลนักเรียน
              </h4>
              <div v-if="visit.student" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="flex items-center space-x-4">
                  <div class="flex-shrink-0">
                    <div class="w-16 h-16 rounded-full bg-indigo-100 flex items-center justify-center">
                      <span class="text-indigo-600 font-bold text-xl">
                        {{ getInitials(visit.student) }}
                      </span>
                    </div>
                  </div>
                  <div>
                    <div class="text-lg font-semibold text-gray-900">
                      {{ getStudentFullName(visit.student) }}
                    </div>
                    <div v-if="visit.student.nickname" class="text-sm text-gray-500">
                      ชื่อเล่น: {{ visit.student.nickname }}
                    </div>
                    <div v-if="visit.student.student_code" class="text-sm text-gray-600 mt-1">
                      รหัสนักเรียน: {{ visit.student.student_code }}
                    </div>
                  </div>
                </div>

                <div class="space-y-2">
                  <div v-if="visit.student.email" class="text-sm">
                    <span class="text-gray-500">อีเมล:</span>
                    <span class="text-gray-900 ml-2">{{ visit.student.email }}</span>
                  </div>
                  <div v-if="visit.student.phone" class="text-sm">
                    <span class="text-gray-500">เบอร์โทร:</span>
                    <span class="text-gray-900 ml-2">{{ visit.student.phone }}</span>
                  </div>
                  <div v-if="visit.student.classroom" class="text-sm">
                    <span class="text-gray-500">ห้องเรียน:</span>
                    <span class="text-gray-900 ml-2">{{ visit.student.classroom }}</span>
                  </div>
                </div>
              </div>
              <div v-else class="text-gray-500 italic">
                ไม่มีข้อมูลนักเรียน
              </div>
            </div>

            <!-- Participants -->
            <div class="bg-white border border-gray-200 rounded-lg p-6">
              <h4 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                <i class="fas fa-users mr-2 text-indigo-600"></i>
                ครูผู้เข้าร่วมการเยี่ยมบ้าน
              </h4>
              
              <div v-if="visit.participants && visit.participants.length > 0" class="space-y-3">
                <div
                  v-for="participant in visit.participants"
                  :key="participant.id"
                  class="flex items-center justify-between p-3 bg-gray-50 rounded-lg"
                >
                  <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-full bg-indigo-100 flex items-center justify-center">
                      <i class="fas fa-user text-indigo-600"></i>
                    </div>
                    <div>
                      <div class="font-medium text-gray-900">
                        {{ participant.participant_name }}
                      </div>
                      <div v-if="participant.participant_position" class="text-sm text-gray-500">
                        {{ participant.participant_position }}
                      </div>
                    </div>
                  </div>
                  <div v-if="participant.participant_role" class="text-sm">
                    <span class="px-2 py-1 bg-indigo-100 text-indigo-800 rounded-full text-xs font-medium">
                      {{ participant.participant_role }}
                    </span>
                  </div>
                </div>
              </div>

              <div v-else-if="visit.visitor_name" class="flex items-center space-x-3 p-3 bg-gray-50 rounded-lg">
                <div class="w-10 h-10 rounded-full bg-indigo-100 flex items-center justify-center">
                  <i class="fas fa-user text-indigo-600"></i>
                </div>
                <div>
                  <div class="font-medium text-gray-900">
                    {{ visit.visitor_name }}
                  </div>
                  <div v-if="visit.visitor_position" class="text-sm text-gray-500">
                    {{ visit.visitor_position }}
                  </div>
                </div>
              </div>

              <div v-else class="text-gray-500 italic text-center py-4">
                ไม่มีข้อมูลครูผู้เข้าร่วม
              </div>
            </div>

            <!-- Observations -->
            <div v-if="visit.observations" class="bg-white border border-gray-200 rounded-lg p-6">
              <h4 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                <i class="fas fa-clipboard-list mr-2 text-indigo-600"></i>
                ผลการสังเกต
              </h4>
              <div class="prose max-w-none">
                <p class="text-gray-700 whitespace-pre-wrap">{{ visit.observations }}</p>
              </div>
            </div>

            <!-- Notes -->
            <div v-if="visit.notes" class="bg-white border border-gray-200 rounded-lg p-6">
              <h4 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                <i class="fas fa-sticky-note mr-2 text-indigo-600"></i>
                บันทึกเพิ่มเติม
              </h4>
              <div class="prose max-w-none">
                <p class="text-gray-700 whitespace-pre-wrap">{{ visit.notes }}</p>
              </div>
            </div>

            <!-- Recommendations -->
            <div v-if="visit.recommendations" class="bg-white border border-gray-200 rounded-lg p-6">
              <h4 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                <i class="fas fa-lightbulb mr-2 text-yellow-600"></i>
                ข้อเสนอแนะ
              </h4>
              <div class="prose max-w-none">
                <p class="text-gray-700 whitespace-pre-wrap">{{ visit.recommendations }}</p>
              </div>
            </div>

            <!-- Follow Up -->
            <div v-if="visit.follow_up" class="bg-white border border-gray-200 rounded-lg p-6">
              <h4 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                <i class="fas fa-tasks mr-2 text-indigo-600"></i>
                การติดตามผล
              </h4>
              <div class="prose max-w-none">
                <p class="text-gray-700 whitespace-pre-wrap">{{ visit.follow_up }}</p>
              </div>
              <div v-if="visit.next_visit" class="mt-4 p-3 bg-blue-50 rounded-lg">
                <div class="text-sm font-medium text-blue-900">
                  <i class="fas fa-calendar-alt mr-2"></i>
                  กำหนดการเยี่ยมครั้งถัดไป: {{ formatDate(visit.next_visit) }}
                </div>
              </div>
            </div>

            <!-- Images Gallery -->
            <div v-if="visit.images && visit.images.length > 0" class="bg-white border border-gray-200 rounded-lg p-6">
              <h4 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                <i class="fas fa-images mr-2 text-indigo-600"></i>
                รูปภาพประกอบ ({{ visit.images.length }} รูป)
              </h4>

              <!-- Image Type Tabs -->
              <div class="mb-4 flex space-x-2 border-b border-gray-200">
                <button
                  @click="imageTab = 'all'"
                  :class="[
                    'px-4 py-2 text-sm font-medium border-b-2 transition-colors',
                    imageTab === 'all'
                      ? 'border-indigo-500 text-indigo-600'
                      : 'border-transparent text-gray-500 hover:text-gray-700'
                  ]"
                >
                  ทั้งหมด ({{ visit.images.length }})
                </button>
                <button
                  @click="imageTab = 'evidence'"
                  :class="[
                    'px-4 py-2 text-sm font-medium border-b-2 transition-colors',
                    imageTab === 'evidence'
                      ? 'border-indigo-500 text-indigo-600'
                      : 'border-transparent text-gray-500 hover:text-gray-700'
                  ]"
                >
                  หลักฐาน ({{ evidenceImages.length }})
                </button>
                <button
                  @click="imageTab = 'activity'"
                  :class="[
                    'px-4 py-2 text-sm font-medium border-b-2 transition-colors',
                    imageTab === 'activity'
                      ? 'border-indigo-500 text-indigo-600'
                      : 'border-transparent text-gray-500 hover:text-gray-700'
                  ]"
                >
                  กิจกรรม ({{ activityImages.length }})
                </button>
              </div>

              <!-- Images Grid -->
              <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                <div
                  v-for="image in filteredImages"
                  :key="image.id"
                  class="relative group cursor-pointer"
                  @click="openImageModal(image)"
                >
                  <div class="aspect-square rounded-lg overflow-hidden bg-gray-100">
                    <img
                      :src="getImageUrl(image.image_path)"
                      :alt="image.image_description || 'รูปภาพการเยี่ยมบ้าน'"
                      class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-200"
                    />
                  </div>
                  <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-40 transition-opacity rounded-lg flex items-center justify-center">
                    <i class="fas fa-search-plus text-white text-2xl opacity-0 group-hover:opacity-100 transition-opacity"></i>
                  </div>
                  <div v-if="image.image_type" class="absolute top-2 right-2">
                    <span :class="[
                      'px-2 py-1 rounded-full text-xs font-medium',
                      image.image_type === 'evidence' ? 'bg-blue-500 text-white' : 'bg-green-500 text-white'
                    ]">
                      {{ image.image_type === 'evidence' ? 'หลักฐาน' : 'กิจกรรม' }}
                    </span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Metadata -->
            <div class="bg-gray-50 border border-gray-200 rounded-lg p-6">
              <h4 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                <i class="fas fa-info-circle mr-2 text-gray-600"></i>
                ข้อมูลเพิ่มเติม
              </h4>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <div>
                  <span class="text-gray-500">สร้างเมื่อ:</span>
                  <span class="text-gray-900 ml-2">{{ formatDateTime(visit.created_at) }}</span>
                </div>
                <div>
                  <span class="text-gray-500">แก้ไขล่าสุด:</span>
                  <span class="text-gray-900 ml-2">{{ formatDateTime(visit.updated_at) }}</span>
                </div>
                <div v-if="visit.creator">
                  <span class="text-gray-500">สร้างโดย:</span>
                  <span class="text-gray-900 ml-2">{{ visit.creator.name }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Footer Actions -->
        <div class="border-t border-gray-200 px-6 py-4 bg-gray-50 sticky bottom-0">
          <div class="flex items-center justify-between">
            <button
              @click="printReport"
              class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors"
            >
              <i class="fas fa-print mr-2"></i>
              พิมพ์รายงาน
            </button>
            <div class="flex space-x-3">
              <button
                @click="downloadReport"
                class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition-colors"
              >
                <i class="fas fa-download mr-2"></i>
                ดาวน์โหลด PDF
              </button>
              <button
                @click="$emit('close')"
                class="inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg transition-colors shadow-lg"
              >
                <i class="fas fa-times mr-2"></i>
                ปิด
              </button>
            </div>
          </div>
        </div>

        <!-- Floating Close Button (Always Visible) -->
        <button
          @click="$emit('close')"
          class="fixed bottom-6 right-6 z-10 w-14 h-14 bg-red-600 hover:bg-red-700 text-white rounded-full shadow-2xl flex items-center justify-center transition-all hover:scale-110"
          title="ปิดหน้าต่าง"
        >
          <i class="fas fa-times text-xl"></i>
        </button>
      </div>
    </div>

    <!-- Image Lightbox Modal -->
    <div
      v-if="showImageModal"
      class="fixed inset-0 z-[60] bg-black bg-opacity-90 flex items-center justify-center p-4"
      @click="closeImageModal"
    >
      <button
        @click="closeImageModal"
        class="absolute top-4 right-4 text-white hover:text-gray-300 text-3xl"
      >
        <i class="fas fa-times"></i>
      </button>
      
      <div class="max-w-5xl max-h-full" @click.stop>
        <img
          :src="getImageUrl(selectedImage?.image_path)"
          :alt="selectedImage?.image_description"
          class="max-w-full max-h-[80vh] object-contain mx-auto"
        />
        <div v-if="selectedImage?.image_description" class="text-white text-center mt-4">
          {{ selectedImage.image_description }}
        </div>
      </div>

      <!-- Navigation Arrows -->
      <button
        v-if="currentImageIndex > 0"
        @click.stop="previousImage"
        class="absolute left-4 top-1/2 transform -translate-y-1/2 text-white hover:text-gray-300 text-4xl"
      >
        <i class="fas fa-chevron-left"></i>
      </button>
      <button
        v-if="currentImageIndex < filteredImages.length - 1"
        @click.stop="nextImage"
        class="absolute right-4 top-1/2 transform -translate-y-1/2 text-white hover:text-gray-300 text-4xl"
      >
        <i class="fas fa-chevron-right"></i>
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'

const props = defineProps({
  visit: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['close'])

const imageTab = ref('all')
const showImageModal = ref(false)
const selectedImage = ref(null)
const currentImageIndex = ref(0)

const evidenceImages = computed(() => {
  return props.visit.images?.filter(img => img.image_type === 'evidence') || []
})

const activityImages = computed(() => {
  return props.visit.images?.filter(img => img.image_type === 'activity') || []
})

const filteredImages = computed(() => {
  if (imageTab.value === 'evidence') return evidenceImages.value
  if (imageTab.value === 'activity') return activityImages.value
  return props.visit.images || []
})

const openImageModal = (image) => {
  selectedImage.value = image
  currentImageIndex.value = filteredImages.value.findIndex(img => img.id === image.id)
  showImageModal.value = true
}

const closeImageModal = () => {
  showImageModal.value = false
  selectedImage.value = null
}

const previousImage = () => {
  if (currentImageIndex.value > 0) {
    currentImageIndex.value--
    selectedImage.value = filteredImages.value[currentImageIndex.value]
  }
}

const nextImage = () => {
  if (currentImageIndex.value < filteredImages.value.length - 1) {
    currentImageIndex.value++
    selectedImage.value = filteredImages.value[currentImageIndex.value]
  }
}

const getImageUrl = (path) => {
  if (!path) return ''
  if (path.startsWith('http')) return path
  return `/storage/${path}`
}

const formatDate = (dateString) => {
  if (!dateString) return '-'
  const date = new Date(dateString)
  return date.toLocaleDateString('th-TH', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}

const formatTime = (timeString) => {
  if (!timeString) return '-'
  try {
    return new Date(`2000-01-01 ${timeString}`).toLocaleTimeString('th-TH', {
      hour: '2-digit',
      minute: '2-digit'
    })
  } catch {
    return timeString
  }
}

const formatDateTime = (dateTimeString) => {
  if (!dateTimeString) return '-'
  const date = new Date(dateTimeString)
  return date.toLocaleString('th-TH', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const getStudentFullName = (student) => {
  if (!student) return 'ไม่ระบุ'
  return `${student.first_name || ''} ${student.last_name || ''}`.trim() || 'ไม่ระบุชื่อ'
}

const getInitials = (student) => {
  if (!student || !student.first_name) return 'N'
  return student.first_name.charAt(0).toUpperCase()
}

const getStatusText = (status) => {
  const statusMap = {
    'pending': 'รอดำเนินการ',
    'in-progress': 'กำลังดำเนินการ',
    'completed': 'เสร็จสิ้น',
    'cancelled': 'ยกเลิก'
  }
  return statusMap[status] || status
}

const getStatusBadgeClass = (status) => {
  const baseClass = 'px-3 py-1 inline-flex text-sm leading-5 font-semibold rounded-full'
  const statusClasses = {
    'pending': 'bg-orange-100 text-orange-800',
    'in-progress': 'bg-yellow-100 text-yellow-800',
    'completed': 'bg-green-100 text-green-800',
    'cancelled': 'bg-gray-100 text-gray-800'
  }
  return `${baseClass} ${statusClasses[status] || 'bg-gray-100 text-gray-800'}`
}

const printReport = () => {
  window.print()
}

const downloadReport = async () => {
  try {
    const response = await axios.get(`/api/home-visit/admin/visits/${props.visit.id}/report`, {
      responseType: 'blob'
    })
    
    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `home-visit-report-${props.visit.id}.pdf`)
    document.body.appendChild(link)
    link.click()
    link.remove()
  } catch (error) {
    console.error('Download failed:', error)
    alert('เกิดข้อผิดพลาดในการดาวน์โหลด')
  }
}
</script>

<style scoped>
@media print {
  .fixed {
    position: relative;
  }
  
  button {
    display: none;
  }
}
</style>
