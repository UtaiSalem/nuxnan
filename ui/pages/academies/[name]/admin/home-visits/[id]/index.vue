<script setup lang="ts">
/**
 * Academy Admin - Home Visit Detail
 * หน้าดูรายละเอียดการเยี่ยมบ้าน
 */
import { Icon } from '@iconify/vue'

definePageMeta({
  layout: 'main'
})

const route = useRoute()
const api = useApi()
const academyName = computed(() => route.params.name as string)
const visitId = computed(() => route.params.id as string)

// State
const academy = ref<any>(null)
const visit = ref<any>(null)
const isLoading = ref(true)
const showDeleteModal = ref(false)
const isDeleting = ref(false)

// Academy Role
const academyId = ref<number | null>(null)
const { can, isAdmin, fetchMyRole } = useAcademyRole(academyId)

onMounted(async () => {
  try {
    const response: any = await api.get(`/api/academies/${academyName.value}`)
    if (response.success) {
      academy.value = response.academy
      academyId.value = response.academy.id
      await fetchMyRole()
      
      if (!isAdmin.value && !can('home_visits.view')) {
        navigateTo(`/academies/${academyName.value}`)
        return
      }
      
      await fetchVisit()
    }
  } catch (err) {
    console.error('Failed to load:', err)
  } finally {
    isLoading.value = false
  }
})

const fetchVisit = async () => {
  if (!academyId.value) return
  try {
    const response: any = await api.get(`/api/academies/${academyId.value}/home-visits/admin/visits/${visitId.value}`)
    visit.value = response.visit || response.data
  } catch (err) {
    console.error('Failed to fetch visit:', err)
  }
}

const deleteVisit = async () => {
  if (!academyId.value) return
  isDeleting.value = true
  try {
    await api.delete(`/api/academies/${academyId.value}/home-visits/admin/visits/${visitId.value}`)
    navigateTo(`/academies/${academyName}/admin/home-visits`)
  } catch (err) {
    console.error('Failed to delete:', err)
  } finally {
    isDeleting.value = false
    showDeleteModal.value = false
  }
}

const formatDate = (date: string) => {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('th-TH', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}

const formatDateTime = (date: string) => {
  if (!date) return '-'
  return new Date(date).toLocaleString('th-TH', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const getStatusBadge = (status: string) => {
  const badges: Record<string, { class: string, label: string }> = {
    'completed': { class: 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400', label: 'เสร็จสิ้น' },
    'pending': { class: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400', label: 'รอดำเนินการ' },
    'cancelled': { class: 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400', label: 'ยกเลิก' }
  }
  return badges[status] || badges['pending']
}
</script>

<template>
  <div>
    <div v-if="isLoading" class="flex items-center justify-center py-12">
      <Icon icon="fluent:spinner-ios-20-regular" class="w-8 h-8 animate-spin text-primary-600" />
    </div>

    <div v-else-if="visit" class="space-y-6">
      <!-- Header -->
      <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
        <div class="flex items-start gap-4">
          <NuxtLink 
            :to="`/academies/${academyName}/admin/home-visits`"
            class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg mt-1"
          >
            <Icon icon="fluent:arrow-left-24-regular" class="w-5 h-5" />
          </NuxtLink>
          <div>
            <div class="flex items-center gap-3 mb-1">
              <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                เยี่ยมบ้าน: {{ visit.student?.full_name || visit.student_name }}
              </h1>
              <span :class="['px-3 py-1 rounded-full text-sm font-medium', getStatusBadge(visit.status).class]">
                {{ getStatusBadge(visit.status).label }}
              </span>
            </div>
            <p class="text-gray-600 dark:text-gray-400">
              โดย ครู{{ visit.teacher?.name || visit.teacher_name }} • {{ formatDate(visit.visit_date) }}
            </p>
          </div>
        </div>
        
        <div class="flex gap-2 ml-11 md:ml-0">
          <NuxtLink
            :to="`/academies/${academyName}/admin/home-visits/${visitId}/edit`"
            class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 flex items-center gap-2"
          >
            <Icon icon="fluent:edit-24-regular" class="w-5 h-5" />
            <span class="hidden sm:inline">แก้ไข</span>
          </NuxtLink>
          <button
            @click="showDeleteModal = true"
            class="min-h-[44px] sm:min-h-0 px-4 py-2 border border-red-300 dark:border-red-800 text-red-600 dark:text-red-400 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20 flex items-center gap-2"
          >
            <Icon icon="fluent:delete-24-regular" class="w-5 h-5" />
            <span class="hidden sm:inline">ลบ</span>
          </button>
        </div>
      </div>

      <!-- Main Content -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left: Visit Details -->
        <div class="lg:col-span-2 space-y-6">
          <!-- Visit Info -->
          <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
              <Icon icon="fluent:document-24-regular" class="w-5 h-5 text-primary-600" />
              ข้อมูลการเยี่ยมบ้าน
            </h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">วันที่เยี่ยมบ้าน</p>
                <p class="text-gray-900 dark:text-white">{{ formatDate(visit.visit_date) }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">เวลา</p>
                <p class="text-gray-900 dark:text-white">{{ visit.visit_time || '-' }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">วัตถุประสงค์</p>
                <p class="text-gray-900 dark:text-white">{{ visit.purpose || '-' }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">โซน</p>
                <p class="text-gray-900 dark:text-white">{{ visit.zone?.name || '-' }}</p>
              </div>
            </div>

            <div v-if="visit.observations" class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700">
              <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">สิ่งที่พบ / ข้อสังเกต</p>
              <p class="text-gray-900 dark:text-white whitespace-pre-line">{{ visit.observations }}</p>
            </div>

            <div v-if="visit.recommendations" class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700">
              <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">ข้อเสนอแนะ</p>
              <p class="text-gray-900 dark:text-white whitespace-pre-line">{{ visit.recommendations }}</p>
            </div>
          </div>

          <!-- Location Info -->
          <div v-if="visit.address || visit.latitude" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
              <Icon icon="fluent:location-24-regular" class="w-5 h-5 text-primary-600" />
              ที่ตั้งบ้าน
            </h2>
            
            <p v-if="visit.address" class="text-gray-900 dark:text-white mb-4">{{ visit.address }}</p>
            
            <div v-if="visit.latitude && visit.longitude" class="aspect-video bg-gray-100 dark:bg-gray-700 rounded-lg overflow-hidden">
              <iframe
                :src="`https://www.google.com/maps?q=${visit.latitude},${visit.longitude}&z=15&output=embed`"
                class="w-full h-full"
                frameborder="0"
                allowfullscreen
              ></iframe>
            </div>
          </div>

          <!-- Photos -->
          <div v-if="visit.photos && visit.photos.length > 0" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
              <Icon icon="fluent:image-24-regular" class="w-5 h-5 text-primary-600" />
              รูปภาพ ({{ visit.photos.length }})
            </h2>
            
            <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
              <div
                v-for="(photo, i) in visit.photos"
                :key="i"
                class="aspect-square rounded-lg overflow-hidden bg-gray-100 dark:bg-gray-700"
              >
                <img 
                  :src="photo.url || photo"
                  class="w-full h-full object-cover hover:scale-105 transition-transform cursor-pointer"
                />
              </div>
            </div>
          </div>
        </div>

        <!-- Right: Sidebar -->
        <div class="space-y-6">
          <!-- Student Info -->
          <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
              <Icon icon="fluent:person-24-regular" class="w-5 h-5 text-primary-600" />
              ข้อมูลนักเรียน
            </h2>
            
            <div class="text-center mb-4">
              <img 
                :src="visit.student?.avatar || '/images/default-avatar.png'"
                class="w-24 h-24 rounded-full object-cover mx-auto mb-2"
              />
              <p class="font-medium text-gray-900 dark:text-white">
                {{ visit.student?.full_name || visit.student_name }}
              </p>
              <p class="text-sm text-gray-500 dark:text-gray-400">
                {{ visit.student?.student_number }}
              </p>
            </div>
            
            <div class="space-y-3">
              <div class="flex justify-between">
                <span class="text-gray-500 dark:text-gray-400">ชั้น</span>
                <span class="text-gray-900 dark:text-white">ม.{{ visit.student?.class_level || '-' }}/{{ visit.student?.class_section || '-' }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-500 dark:text-gray-400">เลขที่</span>
                <span class="text-gray-900 dark:text-white">{{ visit.student?.class_number || '-' }}</span>
              </div>
            </div>
          </div>

          <!-- Teacher Info -->
          <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
              <Icon icon="fluent:person-board-24-regular" class="w-5 h-5 text-primary-600" />
              ครูผู้เยี่ยมบ้าน
            </h2>
            
            <div class="flex items-center gap-3">
              <img 
                :src="visit.teacher?.avatar || '/images/default-avatar.png'"
                class="w-12 h-12 rounded-full object-cover"
              />
              <div>
                <p class="font-medium text-gray-900 dark:text-white">
                  {{ visit.teacher?.name || visit.teacher_name }}
                </p>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                  {{ visit.teacher?.position || 'ครูประจำชั้น' }}
                </p>
              </div>
            </div>
          </div>

          <!-- Meta Info -->
          <div class="bg-gray-50 dark:bg-gray-800/50 rounded-xl p-4 text-sm text-gray-500 dark:text-gray-400">
            <p>สร้างเมื่อ: {{ formatDateTime(visit.created_at) }}</p>
            <p v-if="visit.updated_at !== visit.created_at">
              แก้ไขล่าสุด: {{ formatDateTime(visit.updated_at) }}
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- Delete Modal -->
    <Teleport to="body">
      <div v-if="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
        <div class="bg-white dark:bg-gray-800 rounded-xl p-6 max-w-md w-full mx-4">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">ยืนยันการลบ</h3>
          <p class="text-gray-600 dark:text-gray-400 mb-6">
            คุณต้องการลบข้อมูลการเยี่ยมบ้านนี้หรือไม่? การดำเนินการนี้ไม่สามารถย้อนกลับได้
          </p>
          <div class="flex justify-end gap-3">
            <button
              @click="showDeleteModal = false"
              class="min-h-[44px] sm:min-h-0 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300"
            >
              ยกเลิก
            </button>
            <button
              @click="deleteVisit"
              :disabled="isDeleting"
              class="min-h-[44px] sm:min-h-0 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 flex items-center gap-2"
            >
              <Icon v-if="isDeleting" icon="fluent:spinner-ios-20-regular" class="w-4 h-4 animate-spin" />
              ลบ
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>
