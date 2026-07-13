<script setup lang="ts">
/**
 * Academy Admin - Student Card Detail View
 * หน้าดูรายละเอียดบัตรนักเรียน พร้อมบัตรด้านหน้า-หลัง
 */
import { Icon } from '@iconify/vue'
import StudentCardFront from '~/components/learn/student-card/StudentCardFront.vue'
import StudentCardBack from '~/components/learn/student-card/StudentCardBack.vue'

definePageMeta({
  layout: 'main'
})

const route = useRoute()
const api = useApi()
const academyName = computed(() => route.params.name as string)
const studentId = computed(() => route.params.id as string)

// State
const academy = ref<any>(null)
const student = ref<any>(null)
const isLoading = ref(true)
const showBack = ref(false)
const isUploading = ref(false)

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
      
      if (!isAdmin.value && !can('students.view')) {
        navigateTo(`/academies/${academyName.value}`)
        return
      }
      
      await fetchStudent()
    }
  } catch (err) {
    console.error('Failed to load:', err)
  } finally {
    isLoading.value = false
  }
})

const fetchStudent = async () => {
  try {
    const response: any = await api.get(`/api/academies/${academyId.value}/student-cards/profile/${studentId.value}`)
    if (response.success) {
      student.value = response.student
    }
  } catch (err) {
    console.error('Failed to fetch student:', err)
  }
}

const printCard = () => {
  window.print()
}

const academyLogo = computed(() => {
  return academy.value?.logo
    ? `/storage/${academy.value.logo}`
    : '/images/default-school-logo.png'
})

const academyAddress = computed(() => {
  return academy.value?.address || ''
})

// Photo upload handler
const fileInput = ref<HTMLInputElement | null>(null)

const triggerPhotoUpload = () => {
  fileInput.value?.click()
}

const handlePhotoUpload = async (event: Event) => {
  const target = event.target as HTMLInputElement
  const file = target.files?.[0]
  if (!file) return

  isUploading.value = true
  try {
    const formData = new FormData()
    formData.append('photo', file)

    const response: any = await api.post(
      `/api/academies/${academyId.value}/student-cards/admin/upload-photo/${studentId.value}`,
      formData
    )
    if (response.success) {
      await fetchStudent()
    }
  } catch (err) {
    console.error('Upload failed:', err)
  } finally {
    isUploading.value = false
    if (target) target.value = ''
  }
}

const deletePhoto = async () => {
  if (!confirm('ต้องการลบรูปภาพหรือไม่?')) return
  try {
    const response: any = await api.delete(
      `/api/academies/${academyId.value}/student-cards/${studentId.value}/photo`
    )
    if (response.success) {
      await fetchStudent()
    }
  } catch (err) {
    console.error('Delete photo failed:', err)
  }
}

// Format date for info section
const formatDate = (dateStr?: string) => {
  if (!dateStr) return '-'
  try {
    return new Date(dateStr).toLocaleDateString('th-TH', {
      day: 'numeric', month: 'long', year: 'numeric'
    })
  } catch { return '-' }
}
</script>

<template>
  <div>
    <div v-if="isLoading" class="flex items-center justify-center py-20">
      <div class="animate-spin rounded-full h-10 w-10 border-4 border-primary-500 border-t-transparent"></div>
    </div>

    <div v-else-if="student" class="space-y-6">
      <!-- Header -->
      <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 print:hidden">
        <div class="flex items-center gap-4">
          <NuxtLink 
            :to="`/academies/${academyName}/admin/student-cards`"
            class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg"
          >
            <Icon icon="fluent:arrow-left-24-regular" class="w-5 h-5" />
          </NuxtLink>
          <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">บัตรนักเรียน</h1>
            <p class="text-gray-600 dark:text-gray-400">{{ student.first_name_thai }} {{ student.last_name_thai }}</p>
          </div>
        </div>
        
        <div class="flex flex-wrap gap-2">
          <button
            @click="showBack = !showBack"
            class="px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 flex items-center gap-2"
          >
            <Icon icon="fluent:flip-horizontal-24-regular" class="w-5 h-5" />
            <span>{{ showBack ? 'ด้านหน้า' : 'ด้านหลัง' }}</span>
          </button>
          
          <button
            @click="printCard"
            class="px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 flex items-center gap-2"
          >
            <Icon icon="fluent:print-24-regular" class="w-5 h-5" />
            <span>พิมพ์</span>
          </button>
          
          <NuxtLink 
            :to="`/academies/${academyName}/admin/student-cards/${studentId}/edit`"
            class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 flex items-center gap-2"
          >
            <Icon icon="fluent:edit-24-regular" class="w-5 h-5" />
            <span>แก้ไข</span>
          </NuxtLink>
        </div>
      </div>

      <!-- Card Preview (Front/Back) -->
      <div class="flex justify-center">
        <div class="w-full max-w-[640px]">
          <Transition name="flip" mode="out-in">
            <StudentCardFront
              v-if="!showBack"
              key="front"
              :student="student"
              :academy-name="academy?.name"
              :academy-logo="academyLogo"
              :academy-address="academyAddress"
              card-id="student-card-front"
            />
            <StudentCardBack
              v-else
              key="back"
              :academy-name="academy?.name"
              :academy-logo="academyLogo"
              :academy-address="academyAddress"
              card-id="student-card-back"
            />
          </Transition>
        </div>
      </div>

      <!-- Photo Management -->
      <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-100 dark:border-gray-700 print:hidden">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">จัดการรูปภาพ</h3>
        <div class="flex items-center gap-4">
          <input ref="fileInput" type="file" accept="image/*" class="hidden" @change="handlePhotoUpload" />
          <button
            @click="triggerPhotoUpload"
            :disabled="isUploading"
            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 flex items-center gap-2"
          >
            <Icon :icon="isUploading ? 'eos-icons:loading' : 'fluent:arrow-upload-24-regular'" class="w-5 h-5" />
            <span>{{ isUploading ? 'กำลังอัพโหลด...' : 'อัพโหลดรูปภาพ' }}</span>
          </button>
          <button
            v-if="student.profile_image"
            @click="deletePhoto"
            class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 flex items-center gap-2"
          >
            <Icon icon="fluent:delete-24-regular" class="w-5 h-5" />
            <span>ลบรูปภาพ</span>
          </button>
        </div>
      </div>

      <!-- Student Details -->
      <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-100 dark:border-gray-700 print:hidden">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">ข้อมูลนักเรียน</h3>
        
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
          <div>
            <p class="text-sm text-gray-500 dark:text-gray-400">รหัสนักเรียน</p>
            <p class="text-gray-900 dark:text-white font-mono text-lg">{{ student.student_number }}</p>
          </div>
          <div>
            <p class="text-sm text-gray-500 dark:text-gray-400">เลขประจำตัวประชาชน</p>
            <p class="text-gray-900 dark:text-white font-mono">{{ student.national_id || '-' }}</p>
          </div>
          <div>
            <p class="text-sm text-gray-500 dark:text-gray-400">คำนำหน้า</p>
            <p class="text-gray-900 dark:text-white">{{ student.title_name || '-' }}</p>
          </div>
          <div>
            <p class="text-sm text-gray-500 dark:text-gray-400">ชื่อ (ไทย)</p>
            <p class="text-gray-900 dark:text-white">{{ student.first_name_thai }} {{ student.last_name_thai }}</p>
          </div>
          <div>
            <p class="text-sm text-gray-500 dark:text-gray-400">ชื่อ (อังกฤษ)</p>
            <p class="text-gray-900 dark:text-white">{{ student.first_name_english || '-' }}</p>
          </div>
          <div>
            <p class="text-sm text-gray-500 dark:text-gray-400">ระดับชั้น / ห้อง</p>
            <p class="text-gray-900 dark:text-white">ม.{{ student.class_level }}/{{ student.class_section }}</p>
          </div>
          <div>
            <p class="text-sm text-gray-500 dark:text-gray-400">วันเกิด</p>
            <p class="text-gray-900 dark:text-white">{{ formatDate(student.birth_date) }}</p>
          </div>
          <div>
            <p class="text-sm text-gray-500 dark:text-gray-400">วันออกบัตร</p>
            <p class="text-gray-900 dark:text-white">{{ formatDate(student.card_issue_date) }}</p>
          </div>
          <div>
            <p class="text-sm text-gray-500 dark:text-gray-400">วันหมดอายุบัตร</p>
            <p class="text-gray-900 dark:text-white">{{ formatDate(student.card_expiry_date) }}</p>
          </div>
        </div>
      </div>
    </div>

    <div v-else class="text-center py-20">
      <Icon icon="fluent:person-24-regular" class="w-16 h-16 mx-auto text-gray-300 mb-4" />
      <p class="text-gray-500 dark:text-gray-400">ไม่พบข้อมูลนักเรียน</p>
    </div>
  </div>
</template>

<style scoped>
@media print {
  .print\:hidden {
    display: none !important;
  }
}

.flip-enter-active,
.flip-leave-active {
  transition: transform 0.4s ease, opacity 0.4s ease;
}
.flip-enter-from {
  transform: rotateY(90deg);
  opacity: 0;
}
.flip-leave-to {
  transform: rotateY(-90deg);
  opacity: 0;
}
</style>
