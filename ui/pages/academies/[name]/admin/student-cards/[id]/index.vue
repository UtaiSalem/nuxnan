<script setup lang="ts">
/**
 * Academy Admin - Student Card Detail View
 * หน้าดูรายละเอียดบัตรนักเรียน
 */
import { Icon } from '@iconify/vue'

definePageMeta({
  layout: false
})

const route = useRoute()
const api = useApi()
const academyName = computed(() => route.params.name as string)
const studentId = computed(() => route.params.id as string)

// State
const academy = ref<any>(null)
const student = ref<any>(null)
const isLoading = ref(true)

// Academy Role
const academyId = ref<number | null>(null)
const { can, isAdmin, fetchMyRole } = useAcademyRole(academyId)

onMounted(async () => {
  try {
    const response: any = await api.get(`/api/academies/${encodeURIComponent(academyName.value)}`)
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

const getProfileImage = () => {
  if (!student.value) return '/images/default-avatar.png'
  if (student.value.profile_image) {
    return `/storage/images/students/${student.value.class_level}/${student.value.class_section}/${student.value.profile_image}`
  }
  return '/images/default-avatar.png'
}
</script>

<template>
  <div>
    <div v-if="isLoading" class="flex items-center justify-center py-20">
      <div class="animate-spin rounded-full h-10 w-10 border-4 border-primary-500 border-t-transparent"></div>
    </div>

    <div v-else-if="student" class="space-y-6">
      <!-- Header -->
      <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
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
        
        <div class="flex gap-2">
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

      <!-- Student Card Preview -->
      <div class="max-w-md mx-auto">
        <div class="bg-gradient-to-br from-blue-600 to-blue-800 rounded-2xl p-6 text-white shadow-xl print:shadow-none">
          <!-- School Header -->
          <div class="text-center mb-4">
            <h2 class="text-lg font-bold">{{ academy?.name || 'โรงเรียน' }}</h2>
            <p class="text-sm opacity-80">บัตรประจำตัวนักเรียน</p>
          </div>
          
          <!-- Photo & Info -->
          <div class="flex gap-4">
            <div class="w-24 h-32 bg-white rounded-lg overflow-hidden shrink-0">
              <img 
                :src="getProfileImage()"
                :alt="student.first_name_thai"
                class="w-full h-full object-cover"
              />
            </div>
            
            <div class="flex-1 space-y-1">
              <div>
                <p class="text-xs opacity-70">ชื่อ-นามสกุล</p>
                <p class="font-semibold">{{ student.first_name_thai }} {{ student.last_name_thai }}</p>
              </div>
              <div>
                <p class="text-xs opacity-70">รหัสนักเรียน</p>
                <p class="font-mono text-lg">{{ student.student_number }}</p>
              </div>
              <div class="flex gap-4">
                <div>
                  <p class="text-xs opacity-70">ชั้น</p>
                  <p>ม.{{ student.class_level }}</p>
                </div>
                <div>
                  <p class="text-xs opacity-70">ห้อง</p>
                  <p>{{ student.class_section }}</p>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Barcode Area -->
          <div class="mt-4 pt-4 border-t border-white/20 text-center">
            <div class="bg-white text-black font-mono text-sm py-1 px-3 rounded inline-block">
              {{ student.student_number }}
            </div>
          </div>
        </div>
      </div>

      <!-- Student Details -->
      <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-100 dark:border-gray-700">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">ข้อมูลนักเรียน</h3>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <p class="text-sm text-gray-500 dark:text-gray-400">รหัสนักเรียน</p>
            <p class="text-gray-900 dark:text-white font-mono">{{ student.student_number }}</p>
          </div>
          <div>
            <p class="text-sm text-gray-500 dark:text-gray-400">เลขประจำตัวประชาชน</p>
            <p class="text-gray-900 dark:text-white font-mono">{{ student.national_id || '-' }}</p>
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
            <p class="text-sm text-gray-500 dark:text-gray-400">ระดับชั้น</p>
            <p class="text-gray-900 dark:text-white">มัธยมศึกษาปีที่ {{ student.class_level }}</p>
          </div>
          <div>
            <p class="text-sm text-gray-500 dark:text-gray-400">ห้อง</p>
            <p class="text-gray-900 dark:text-white">{{ student.class_section }}</p>
          </div>
          <div>
            <p class="text-sm text-gray-500 dark:text-gray-400">วันเกิด</p>
            <p class="text-gray-900 dark:text-white">{{ student.birth_date || '-' }}</p>
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
  .no-print {
    display: none !important;
  }
}
</style>
