<script setup lang="ts">
/**
 * Academy Admin - Announcement Management
 * หน้าจัดการประกาศของโรงเรียน
 */
import { Icon } from '@iconify/vue'
import Swal from 'sweetalert2'

definePageMeta({
  layout: 'main'
})

const route = useRoute()
const api = useApi()
const academyName = computed(() => route.params.name as string)

// State
const academy = ref<any>(null)
const announcements = ref<any[]>([])
const statistics = ref<any>(null)
const isLoading = ref(true)
const isLoadingAnnouncements = ref(false)

// Filters
const searchQuery = ref('')
const filterStatus = ref<string>('')
const filterType = ref<string>('')

// Pagination
const pagination = ref({
  current_page: 1,
  last_page: 1,
  per_page: 10,
  total: 0
})

// Modals
const showCreateModal = ref(false)
const showEditModal = ref(false)
const showViewModal = ref(false)
const selectedAnnouncement = ref<any>(null)

// Academy Role
const academyId = ref<number | null>(null)
const { can, isAdmin, fetchMyRole } = useAcademyRole(academyId)

// Form
const announcementForm = ref({
  title: '',
  content: '',
  announcement_type: 'general',
  priority: 'normal',
  is_pinned: false,
  is_published: false,
  expires_at: null as string | null
})
const isSubmitting = ref(false)
const formErrors = ref<Record<string, string[]>>({})

// Types and priorities
const announcementTypes = [
  { value: 'general', label: 'ทั่วไป', icon: 'fluent:info-24-regular', color: 'blue' },
  { value: 'academic', label: 'วิชาการ', icon: 'fluent:book-24-regular', color: 'green' },
  { value: 'financial', label: 'การเงิน', icon: 'fluent:money-24-regular', color: 'amber' },
  { value: 'event', label: 'กิจกรรม', icon: 'fluent:calendar-24-regular', color: 'purple' },
  { value: 'emergency', label: 'ฉุกเฉิน', icon: 'fluent:warning-24-regular', color: 'red' }
]

const priorities = [
  { value: 'low', label: 'ต่ำ', color: 'gray' },
  { value: 'normal', label: 'ปกติ', color: 'blue' },
  { value: 'high', label: 'สูง', color: 'orange' },
  { value: 'urgent', label: 'ด่วน', color: 'red' }
]

onMounted(async () => {
  try {
    const response: any = await api.get(`/api/academies/${academyName.value}`)
    if (response.success) {
      academy.value = response.academy
      academyId.value = response.academy.id
      await fetchMyRole()
      
      if (!isAdmin.value) {
        navigateTo(`/academies/${academyName.value}/admin`)
        return
      }
      
      await Promise.all([
        fetchAnnouncements(),
        fetchStatistics()
      ])
    }
  } catch (err) {
    console.error('Failed to load:', err)
  } finally {
    isLoading.value = false
  }
})

// Fetch announcements
const fetchAnnouncements = async () => {
  if (!academyId.value) return
  
  isLoadingAnnouncements.value = true
  try {
    const response: any = await api.get(`/api/academies/${academyId.value}/announcements`, {
      search: searchQuery.value || undefined,
      status: filterStatus.value || undefined,
      type: filterType.value || undefined,
      page: pagination.value.current_page,
      per_page: pagination.value.per_page
    })
    
    if (response.success) {
      announcements.value = response.data || []
      if (response.pagination) {
        pagination.value = response.pagination
      }
    }
  } catch (err) {
    console.error('Failed to fetch announcements:', err)
  } finally {
    isLoadingAnnouncements.value = false
  }
}

// Fetch statistics
const fetchStatistics = async () => {
  if (!academyId.value) return
  
  try {
    const response: any = await api.get(`/api/academies/${academyId.value}/announcements/stats`)
    if (response.success) {
      statistics.value = response.data
    }
  } catch (err) {
    console.error('Failed to fetch statistics:', err)
  }
}

// Search with debounce
let searchTimeout: NodeJS.Timeout
const handleSearch = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    pagination.value.current_page = 1
    fetchAnnouncements()
  }, 300)
}

// Open create modal
const openCreateModal = () => {
  announcementForm.value = {
    title: '',
    content: '',
    announcement_type: 'general',
    priority: 'normal',
    is_pinned: false,
    is_published: false,
    expires_at: null
  }
  formErrors.value = {}
  showCreateModal.value = true
}

// Open edit modal
const openEditModal = (announcement: any) => {
  selectedAnnouncement.value = announcement
  announcementForm.value = {
    title: announcement.title,
    content: announcement.content,
    announcement_type: announcement.announcement_type || 'general',
    priority: announcement.priority || 'normal',
    is_pinned: announcement.is_pinned || false,
    is_published: announcement.is_published || false,
    expires_at: announcement.expires_at ? announcement.expires_at.split('T')[0] : null
  }
  formErrors.value = {}
  showEditModal.value = true
}

// View announcement
const viewAnnouncement = (announcement: any) => {
  selectedAnnouncement.value = announcement
  showViewModal.value = true
}

// Create announcement
const createAnnouncement = async () => {
  if (!academyId.value) return
  
  isSubmitting.value = true
  formErrors.value = {}
  
  try {
    const response: any = await api.post(`/api/academies/${academyId.value}/announcements`, {
      title: announcementForm.value.title,
      content: announcementForm.value.content,
      announcement_type: announcementForm.value.announcement_type,
      priority: announcementForm.value.priority,
      is_pinned: announcementForm.value.is_pinned,
      is_published: announcementForm.value.is_published,
      expires_at: announcementForm.value.expires_at || undefined
    })
    
    if (response.success) {
      showCreateModal.value = false
      await fetchAnnouncements()
      await fetchStatistics()
      
      Swal.fire({
        icon: 'success',
        title: 'สร้างประกาศสำเร็จ',
        text: `สร้างประกาศ "${announcementForm.value.title}" เรียบร้อยแล้ว`,
        timer: 2000,
        showConfirmButton: false
      })
    }
  } catch (err: any) {
    if (err.response?.data?.errors) {
      formErrors.value = err.response.data.errors
    } else {
      Swal.fire({
        icon: 'error',
        title: 'เกิดข้อผิดพลาด',
        text: err.response?.data?.message || 'ไม่สามารถสร้างประกาศได้'
      })
    }
  } finally {
    isSubmitting.value = false
  }
}

// Update announcement
const updateAnnouncement = async () => {
  if (!academyId.value || !selectedAnnouncement.value) return
  
  isSubmitting.value = true
  formErrors.value = {}
  
  try {
    const response: any = await api.put(
      `/api/academies/${academyId.value}/announcements/${selectedAnnouncement.value.id}`,
      {
        title: announcementForm.value.title,
        content: announcementForm.value.content,
        announcement_type: announcementForm.value.announcement_type,
        priority: announcementForm.value.priority,
        is_pinned: announcementForm.value.is_pinned,
        expires_at: announcementForm.value.expires_at || undefined
      }
    )
    
    if (response.success) {
      showEditModal.value = false
      await fetchAnnouncements()
      
      Swal.fire({
        icon: 'success',
        title: 'อัปเดตสำเร็จ',
        text: 'อัปเดตประกาศเรียบร้อยแล้ว',
        timer: 2000,
        showConfirmButton: false
      })
    }
  } catch (err: any) {
    if (err.response?.data?.errors) {
      formErrors.value = err.response.data.errors
    } else {
      Swal.fire({
        icon: 'error',
        title: 'เกิดข้อผิดพลาด',
        text: err.response?.data?.message || 'ไม่สามารถอัปเดตประกาศได้'
      })
    }
  } finally {
    isSubmitting.value = false
  }
}

// Toggle publish status
const togglePublish = async (announcement: any) => {
  if (!academyId.value) return
  
  const action = announcement.is_published ? 'unpublish' : 'publish'
  const actionLabel = announcement.is_published ? 'ยกเลิกเผยแพร่' : 'เผยแพร่'
  
  try {
    const response: any = await api.post(
      `/api/academies/${academyId.value}/announcements/${announcement.id}/${action}`
    )
    
    if (response.success) {
      await fetchAnnouncements()
      await fetchStatistics()
      
      Swal.fire({
        icon: 'success',
        title: `${actionLabel}สำเร็จ`,
        timer: 1500,
        showConfirmButton: false
      })
    }
  } catch (err: any) {
    Swal.fire({
      icon: 'error',
      title: 'เกิดข้อผิดพลาด',
      text: err.response?.data?.message || `ไม่สามารถ${actionLabel}ได้`
    })
  }
}

// Delete announcement
const deleteAnnouncement = async (announcement: any) => {
  const result = await Swal.fire({
    icon: 'warning',
    title: 'ยืนยันการลบ',
    text: `คุณต้องการลบประกาศ "${announcement.title}" หรือไม่?`,
    showCancelButton: true,
    confirmButtonText: 'ลบ',
    cancelButtonText: 'ยกเลิก',
    confirmButtonColor: '#ef4444'
  })
  
  if (result.isConfirmed) {
    try {
      const response: any = await api.delete(
        `/api/academies/${academyId.value}/announcements/${announcement.id}`
      )
      
      if (response.success) {
        await fetchAnnouncements()
        await fetchStatistics()
        
        Swal.fire({
          icon: 'success',
          title: 'ลบสำเร็จ',
          text: 'ลบประกาศเรียบร้อยแล้ว',
          timer: 2000,
          showConfirmButton: false
        })
      }
    } catch (err: any) {
      Swal.fire({
        icon: 'error',
        title: 'เกิดข้อผิดพลาด',
        text: err.response?.data?.message || 'ไม่สามารถลบประกาศได้'
      })
    }
  }
}

// Helper functions
const getTypeInfo = (type: string) => {
  return announcementTypes.find(t => t.value === type) || announcementTypes[0]
}

const getPriorityInfo = (priority: string) => {
  return priorities.find(p => p.value === priority) || priorities[1]
}

const formatDate = (dateString: string) => {
  if (!dateString) return '-'
  return new Date(dateString).toLocaleDateString('th-TH', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}
</script>

<template>
  <div class="px-4 sm:px-0">
    <div v-if="isLoading" class="flex items-center justify-center py-20">
      <div class="animate-spin rounded-full h-10 w-10 border-4 border-primary-500 border-t-transparent"></div>
    </div>

    <div v-else class="space-y-6">
      <!-- Header -->
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 dark:text-white">จัดการประกาศ</h1>
          <p class="text-gray-600 dark:text-gray-400 mt-1">สร้างและจัดการประกาศของโรงเรียน</p>
        </div>
        <button
          @click="openCreateModal"
          class="min-h-[44px] sm:min-h-0 inline-flex items-center gap-2 px-4 py-2.5 bg-primary-600 hover:bg-primary-700 text-white rounded-xl font-medium transition-colors"
        >
          <Icon icon="fluent:add-24-filled" class="w-5 h-5" />
          <span>สร้างประกาศ</span>
        </button>
      </div>

      <!-- Statistics Cards -->
      <div v-if="statistics" class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow-sm border border-gray-100 dark:border-gray-700">
          <div class="flex items-center gap-4">
            <div class="p-3 bg-blue-100 dark:bg-blue-900/50 rounded-xl">
              <Icon icon="fluent:megaphone-24-filled" class="w-6 h-6 text-blue-600 dark:text-blue-400" />
            </div>
            <div>
              <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ statistics.total || 0 }}</p>
              <p class="text-sm text-gray-500 dark:text-gray-400">ประกาศทั้งหมด</p>
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow-sm border border-gray-100 dark:border-gray-700">
          <div class="flex items-center gap-4">
            <div class="p-3 bg-green-100 dark:bg-green-900/50 rounded-xl">
              <Icon icon="fluent:checkmark-circle-24-filled" class="w-6 h-6 text-green-600 dark:text-green-400" />
            </div>
            <div>
              <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ statistics.published || 0 }}</p>
              <p class="text-sm text-gray-500 dark:text-gray-400">เผยแพร่แล้ว</p>
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow-sm border border-gray-100 dark:border-gray-700">
          <div class="flex items-center gap-4">
            <div class="p-3 bg-amber-100 dark:bg-amber-900/50 rounded-xl">
              <Icon icon="fluent:drafts-24-filled" class="w-6 h-6 text-amber-600 dark:text-amber-400" />
            </div>
            <div>
              <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ statistics.draft || 0 }}</p>
              <p class="text-sm text-gray-500 dark:text-gray-400">ฉบับร่าง</p>
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow-sm border border-gray-100 dark:border-gray-700">
          <div class="flex items-center gap-4">
            <div class="p-3 bg-purple-100 dark:bg-purple-900/50 rounded-xl">
              <Icon icon="fluent:pin-24-filled" class="w-6 h-6 text-purple-600 dark:text-purple-400" />
            </div>
            <div>
              <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ statistics.pinned || 0 }}</p>
              <p class="text-sm text-gray-500 dark:text-gray-400">ปักหมุด</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Filters -->
      <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-100 dark:border-gray-700">
        <div class="flex flex-col sm:flex-row gap-4">
          <div class="relative flex-1">
            <Icon icon="fluent:search-24-regular" class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
            <input
              v-model="searchQuery"
              @input="handleSearch"
              type="text"
              placeholder="ค้นหาประกาศ..."
              class="w-full pl-10 pr-4 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white placeholder-gray-500 focus:ring-2 focus:ring-primary-500 focus:border-transparent"
            />
          </div>
          <select
            v-model="filterType"
            @change="pagination.current_page = 1; fetchAnnouncements()"
            class="px-4 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white"
          >
            <option value="">ประเภททั้งหมด</option>
            <option v-for="type in announcementTypes" :key="type.value" :value="type.value">
              {{ type.label }}
            </option>
          </select>
          <select
            v-model="filterStatus"
            @change="pagination.current_page = 1; fetchAnnouncements()"
            class="px-4 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white"
          >
            <option value="">สถานะทั้งหมด</option>
            <option value="published">เผยแพร่แล้ว</option>
            <option value="draft">ฉบับร่าง</option>
          </select>
        </div>
      </div>

      <!-- Announcement List -->
      <div v-if="isLoadingAnnouncements" class="flex items-center justify-center py-12">
        <div class="animate-spin rounded-full h-8 w-8 border-4 border-primary-500 border-t-transparent"></div>
      </div>

      <div v-else-if="announcements.length === 0" class="bg-white dark:bg-gray-800 rounded-xl p-4 sm:p-12 text-center shadow-sm border border-gray-100 dark:border-gray-700">
        <Icon icon="fluent:megaphone-24-regular" class="w-16 h-16 mx-auto text-gray-300 dark:text-gray-600 mb-4" />
        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">ยังไม่มีประกาศ</h3>
        <p class="text-gray-500 dark:text-gray-400 mb-4">เริ่มต้นสร้างประกาศแรกของโรงเรียน</p>
        <button
          @click="openCreateModal"
          class="min-h-[44px] sm:min-h-0 inline-flex items-center gap-2 px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-xl font-medium transition-colors"
        >
          <Icon icon="fluent:add-24-filled" class="w-5 h-5" />
          <span>สร้างประกาศ</span>
        </button>
      </div>

      <div v-else class="space-y-4">
        <div
          v-for="announcement in announcements"
          :key="announcement.id"
          class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 hover:shadow-md transition-shadow"
        >
          <div class="p-5">
            <div class="flex items-start justify-between gap-4">
              <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 mb-2">
                  <!-- Type Badge -->
                  <span
                    :class="[
                      'inline-flex items-center gap-1 px-2 py-1 rounded-lg text-xs font-medium',
                      `bg-${getTypeInfo(announcement.announcement_type).color}-100 text-${getTypeInfo(announcement.announcement_type).color}-700`,
                      `dark:bg-${getTypeInfo(announcement.announcement_type).color}-900/50 dark:text-${getTypeInfo(announcement.announcement_type).color}-300`
                    ]"
                  >
                    <Icon :icon="getTypeInfo(announcement.announcement_type).icon" class="w-3.5 h-3.5" />
                    {{ getTypeInfo(announcement.announcement_type).label }}
                  </span>
                  
                  <!-- Priority Badge -->
                  <span
                    v-if="announcement.priority !== 'normal'"
                    :class="[
                      'inline-flex items-center px-2 py-1 rounded-lg text-xs font-medium',
                      announcement.priority === 'urgent' ? 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300' :
                      announcement.priority === 'high' ? 'bg-orange-100 text-orange-700 dark:bg-orange-900/50 dark:text-orange-300' :
                      'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300'
                    ]"
                  >
                    {{ getPriorityInfo(announcement.priority).label }}
                  </span>
                  
                  <!-- Pinned Badge -->
                  <span v-if="announcement.is_pinned" class="inline-flex items-center gap-1 px-2 py-1 bg-purple-100 dark:bg-purple-900/50 rounded-lg text-xs font-medium text-purple-700 dark:text-purple-300">
                    <Icon icon="fluent:pin-24-filled" class="w-3.5 h-3.5" />
                    ปักหมุด
                  </span>
                  
                  <!-- Status Badge -->
                  <span
                    :class="[
                      'inline-flex items-center px-2 py-1 rounded-lg text-xs font-medium',
                      announcement.is_published
                        ? 'bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-300'
                        : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400'
                    ]"
                  >
                    {{ announcement.is_published ? 'เผยแพร่แล้ว' : 'ฉบับร่าง' }}
                  </span>
                </div>
                
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-1 truncate">
                  {{ announcement.title }}
                </h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 line-clamp-2 mb-3">
                  {{ announcement.content.replace(/<[^>]*>/g, '').substring(0, 150) }}...
                </p>
                
                <div class="flex items-center gap-4 text-xs text-gray-500 dark:text-gray-400">
                  <span class="flex items-center gap-1">
                    <Icon icon="fluent:person-24-regular" class="w-4 h-4" />
                    {{ announcement.creator?.name || 'Unknown' }}
                  </span>
                  <span class="flex items-center gap-1">
                    <Icon icon="fluent:calendar-24-regular" class="w-4 h-4" />
                    {{ formatDate(announcement.created_at) }}
                  </span>
                  <span v-if="announcement.published_at" class="flex items-center gap-1">
                    <Icon icon="fluent:send-24-regular" class="w-4 h-4" />
                    เผยแพร่: {{ formatDate(announcement.published_at) }}
                  </span>
                </div>
              </div>
              
              <!-- Actions -->
              <div class="flex items-center gap-2">
                <button
                  @click="viewAnnouncement(announcement)"
                  class="min-h-[44px] sm:min-h-0 min-w-[44px] sm:min-w-0 inline-flex items-center justify-center p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
                  title="ดู"
                >
                  <Icon icon="fluent:eye-24-regular" class="w-5 h-5 text-gray-500" />
                </button>
                <button
                  @click="openEditModal(announcement)"
                  class="min-h-[44px] sm:min-h-0 min-w-[44px] sm:min-w-0 inline-flex items-center justify-center p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
                  title="แก้ไข"
                >
                  <Icon icon="fluent:edit-24-regular" class="w-5 h-5 text-gray-500" />
                </button>
                <button
                  @click="togglePublish(announcement)"
                  :class="[
                    'p-2 rounded-lg transition-colors',
                    announcement.is_published
                      ? 'hover:bg-amber-100 dark:hover:bg-amber-900/30'
                      : 'hover:bg-green-100 dark:hover:bg-green-900/30'
                  ]"
                  :title="announcement.is_published ? 'ยกเลิกเผยแพร่' : 'เผยแพร่'"
                >
                  <Icon
                    :icon="announcement.is_published ? 'fluent:arrow-undo-24-regular' : 'fluent:send-24-regular'"
                    :class="[
                      'w-5 h-5',
                      announcement.is_published ? 'text-amber-600' : 'text-green-600'
                    ]"
                  />
                </button>
                <button
                  @click="deleteAnnouncement(announcement)"
                  class="min-h-[44px] sm:min-h-0 min-w-[44px] sm:min-w-0 inline-flex items-center justify-center p-2 hover:bg-red-100 dark:hover:bg-red-900/30 rounded-lg transition-colors"
                  title="ลบ"
                >
                  <Icon icon="fluent:delete-24-regular" class="w-5 h-5 text-red-500" />
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Pagination -->
      <div v-if="pagination.last_page > 1" class="flex items-center justify-center gap-2">
        <button
          @click="pagination.current_page--; fetchAnnouncements()"
          :disabled="pagination.current_page === 1"
          class="min-h-[44px] sm:min-h-0 px-4 py-2 rounded-lg border border-gray-200 dark:border-gray-700 disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50 dark:hover:bg-gray-700"
        >
          ก่อนหน้า
        </button>
        <span class="text-gray-600 dark:text-gray-400">
          หน้า {{ pagination.current_page }} / {{ pagination.last_page }}
        </span>
        <button
          @click="pagination.current_page++; fetchAnnouncements()"
          :disabled="pagination.current_page === pagination.last_page"
          class="min-h-[44px] sm:min-h-0 px-4 py-2 rounded-lg border border-gray-200 dark:border-gray-700 disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50 dark:hover:bg-gray-700"
        >
          ถัดไป
        </button>
      </div>
    </div>

    <!-- Create Modal -->
    <Teleport to="body">
      <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50" @click="showCreateModal = false"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
          <div class="flex items-center justify-between p-5 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">สร้างประกาศใหม่</h3>
            <button @click="showCreateModal = false" class="min-h-[44px] sm:min-h-0 min-w-[44px] sm:min-w-0 inline-flex items-center justify-center p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
              <Icon icon="fluent:dismiss-24-regular" class="w-5 h-5 text-gray-500" />
            </button>
          </div>
          
          <form @submit.prevent="createAnnouncement" class="p-5 space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">หัวข้อ *</label>
              <input
                v-model="announcementForm.title"
                type="text"
                required
                class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                placeholder="หัวข้อประกาศ"
              />
              <p v-if="formErrors.title" class="mt-1 text-sm text-red-500">{{ formErrors.title[0] }}</p>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">เนื้อหา *</label>
              <textarea
                v-model="announcementForm.content"
                rows="6"
                required
                class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent resize-none"
                placeholder="เนื้อหาประกาศ..."
              ></textarea>
              <p v-if="formErrors.content" class="mt-1 text-sm text-red-500">{{ formErrors.content[0] }}</p>
            </div>
            
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">ประเภท</label>
                <select
                  v-model="announcementForm.announcement_type"
                  class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white"
                >
                  <option v-for="type in announcementTypes" :key="type.value" :value="type.value">
                    {{ type.label }}
                  </option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">ลำดับความสำคัญ</label>
                <select
                  v-model="announcementForm.priority"
                  class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white"
                >
                  <option v-for="p in priorities" :key="p.value" :value="p.value">
                    {{ p.label }}
                  </option>
                </select>
              </div>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">วันหมดอายุ (ถ้ามี)</label>
              <input
                v-model="announcementForm.expires_at"
                type="date"
                class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent"
              />
            </div>
            
            <div class="flex items-center gap-6">
              <label class="flex items-center gap-2 cursor-pointer">
                <input
                  v-model="announcementForm.is_pinned"
                  type="checkbox"
                  class="w-4 h-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500"
                />
                <span class="text-sm text-gray-700 dark:text-gray-300">ปักหมุด</span>
              </label>
              <label class="flex items-center gap-2 cursor-pointer">
                <input
                  v-model="announcementForm.is_published"
                  type="checkbox"
                  class="w-4 h-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500"
                />
                <span class="text-sm text-gray-700 dark:text-gray-300">เผยแพร่ทันที</span>
              </label>
            </div>
            
            <div class="flex items-center gap-3 pt-4">
              <button
                type="button"
                @click="showCreateModal = false"
                class="min-h-[44px] sm:min-h-0 flex-1 px-4 py-2.5 border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 rounded-xl font-medium hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
              >
                ยกเลิก
              </button>
              <button
                type="submit"
                :disabled="isSubmitting"
                class="min-h-[44px] sm:min-h-0 flex-1 px-4 py-2.5 bg-primary-600 hover:bg-primary-700 text-white rounded-xl font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
              >
                <div v-if="isSubmitting" class="animate-spin rounded-full h-4 w-4 border-2 border-white border-t-transparent"></div>
                <span>{{ isSubmitting ? 'กำลังสร้าง...' : 'สร้างประกาศ' }}</span>
              </button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>

    <!-- Edit Modal -->
    <Teleport to="body">
      <div v-if="showEditModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50" @click="showEditModal = false"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
          <div class="flex items-center justify-between p-5 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">แก้ไขประกาศ</h3>
            <button @click="showEditModal = false" class="min-h-[44px] sm:min-h-0 min-w-[44px] sm:min-w-0 inline-flex items-center justify-center p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
              <Icon icon="fluent:dismiss-24-regular" class="w-5 h-5 text-gray-500" />
            </button>
          </div>
          
          <form @submit.prevent="updateAnnouncement" class="p-5 space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">หัวข้อ *</label>
              <input
                v-model="announcementForm.title"
                type="text"
                required
                class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent"
              />
              <p v-if="formErrors.title" class="mt-1 text-sm text-red-500">{{ formErrors.title[0] }}</p>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">เนื้อหา *</label>
              <textarea
                v-model="announcementForm.content"
                rows="6"
                required
                class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent resize-none"
              ></textarea>
              <p v-if="formErrors.content" class="mt-1 text-sm text-red-500">{{ formErrors.content[0] }}</p>
            </div>
            
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">ประเภท</label>
                <select
                  v-model="announcementForm.announcement_type"
                  class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white"
                >
                  <option v-for="type in announcementTypes" :key="type.value" :value="type.value">
                    {{ type.label }}
                  </option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">ลำดับความสำคัญ</label>
                <select
                  v-model="announcementForm.priority"
                  class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white"
                >
                  <option v-for="p in priorities" :key="p.value" :value="p.value">
                    {{ p.label }}
                  </option>
                </select>
              </div>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">วันหมดอายุ</label>
              <input
                v-model="announcementForm.expires_at"
                type="date"
                class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent"
              />
            </div>
            
            <div class="flex items-center gap-6">
              <label class="flex items-center gap-2 cursor-pointer">
                <input
                  v-model="announcementForm.is_pinned"
                  type="checkbox"
                  class="w-4 h-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500"
                />
                <span class="text-sm text-gray-700 dark:text-gray-300">ปักหมุด</span>
              </label>
            </div>
            
            <div class="flex items-center gap-3 pt-4">
              <button
                type="button"
                @click="showEditModal = false"
                class="min-h-[44px] sm:min-h-0 flex-1 px-4 py-2.5 border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 rounded-xl font-medium hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
              >
                ยกเลิก
              </button>
              <button
                type="submit"
                :disabled="isSubmitting"
                class="min-h-[44px] sm:min-h-0 flex-1 px-4 py-2.5 bg-primary-600 hover:bg-primary-700 text-white rounded-xl font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
              >
                <div v-if="isSubmitting" class="animate-spin rounded-full h-4 w-4 border-2 border-white border-t-transparent"></div>
                <span>{{ isSubmitting ? 'กำลังบันทึก...' : 'บันทึก' }}</span>
              </button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>

    <!-- View Modal -->
    <Teleport to="body">
      <div v-if="showViewModal && selectedAnnouncement" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50" @click="showViewModal = false"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
          <div class="flex items-center justify-between p-5 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center gap-2">
              <span
                :class="[
                  'inline-flex items-center gap-1 px-2 py-1 rounded-lg text-xs font-medium',
                  `bg-${getTypeInfo(selectedAnnouncement.announcement_type).color}-100 text-${getTypeInfo(selectedAnnouncement.announcement_type).color}-700`
                ]"
              >
                <Icon :icon="getTypeInfo(selectedAnnouncement.announcement_type).icon" class="w-3.5 h-3.5" />
                {{ getTypeInfo(selectedAnnouncement.announcement_type).label }}
              </span>
              <span
                :class="[
                  'inline-flex items-center px-2 py-1 rounded-lg text-xs font-medium',
                  selectedAnnouncement.is_published
                    ? 'bg-green-100 text-green-700'
                    : 'bg-gray-100 text-gray-600'
                ]"
              >
                {{ selectedAnnouncement.is_published ? 'เผยแพร่แล้ว' : 'ฉบับร่าง' }}
              </span>
            </div>
            <button @click="showViewModal = false" class="min-h-[44px] sm:min-h-0 min-w-[44px] sm:min-w-0 inline-flex items-center justify-center p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
              <Icon icon="fluent:dismiss-24-regular" class="w-5 h-5 text-gray-500" />
            </button>
          </div>
          
          <div class="p-5">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">{{ selectedAnnouncement.title }}</h2>
            <div class="prose dark:prose-invert max-w-none" v-html="selectedAnnouncement.content"></div>
            
            <div class="mt-6 pt-4 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between text-sm text-gray-500 dark:text-gray-400">
              <div class="flex items-center gap-4">
                <span class="flex items-center gap-1">
                  <Icon icon="fluent:person-24-regular" class="w-4 h-4" />
                  {{ selectedAnnouncement.creator?.name }}
                </span>
                <span class="flex items-center gap-1">
                  <Icon icon="fluent:calendar-24-regular" class="w-4 h-4" />
                  {{ formatDate(selectedAnnouncement.created_at) }}
                </span>
              </div>
              <button
                @click="showViewModal = false; openEditModal(selectedAnnouncement)"
                class="inline-flex items-center gap-1 text-primary-600 hover:underline"
              >
                <Icon icon="fluent:edit-24-regular" class="w-4 h-4" />
                แก้ไข
              </button>
            </div>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>
