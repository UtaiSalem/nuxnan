<script setup lang="ts">
/**
 * Academy Admin - Student Cards Management
 * หน้าจัดการบัตรนักเรียนภายใต้การบริหารของโรงเรียน
 */
import { Icon } from '@iconify/vue'

definePageMeta({
  layout: false
})

const route = useRoute()
const api = useApi()
const academyName = computed(() => route.params.name as string)

// State
const academy = ref<any>(null)
const students = ref<any[]>([])
const levels = ref<string[]>([])
const isLoading = ref(true)
const searchQuery = ref('')
const selectedLevel = ref('')
const selectedRoom = ref('')
const currentPage = ref(1)
const totalPages = ref(1)

// Academy Role
const academyId = ref<number | null>(null)
const { can, isAdmin, fetchMyRole } = useAcademyRole(academyId)

// Statistics
const stats = ref({
  totalStudents: 0,
  withPhoto: 0,
  withoutPhoto: 0,
  byLevel: {} as Record<string, number>
})

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
      
      await Promise.all([
        fetchStatistics(),
        fetchLevels(),
        fetchStudents()
      ])
    }
  } catch (err) {
    console.error('Failed to load:', err)
  } finally {
    isLoading.value = false
  }
})

const fetchStatistics = async () => {
  if (!academyId.value) return
  
  try {
    // Try academy-based API first
    const response: any = await api.get(`/api/academies/${academyId.value}/student-cards/statistics`)
    if (response.success) {
      stats.value = response.statistics
    }
  } catch (err) {
    // Fallback to legacy API
    try {
      const response: any = await api.get('/api/student-card/dashboard')
      if (response) {
        stats.value.totalStudents = response.totalStudents || 0
        levels.value = response.levels || []
      }
    } catch (legacyErr) {
      console.error('Failed to fetch statistics:', legacyErr)
    }
  }
}

const fetchLevels = async () => {
  try {
    const response: any = await api.get(`/api/academies/${academyId.value}/student-cards/levels`)
    if (response.success) {
      levels.value = response.levels
    }
  } catch (err) {
    // Fallback to legacy
    console.error('Failed to fetch levels:', err)
  }
}

const fetchStudents = async () => {
  if (!academyId.value) return
  
  try {
    const params = new URLSearchParams()
    if (searchQuery.value) params.append('search', searchQuery.value)
    if (selectedLevel.value) params.append('level', selectedLevel.value)
    if (selectedRoom.value) params.append('section', selectedRoom.value)
    params.append('page', currentPage.value.toString())
    
    // Try academy-based API first
    const response: any = await api.get(`/api/academies/${academyId.value}/student-cards/admin/students?${params.toString()}`)
    if (response.success) {
      students.value = response.students?.data || response.students || []
      totalPages.value = response.students?.last_page || 1
    }
  } catch (err) {
    // Fallback to legacy API
    try {
      const params = new URLSearchParams()
      if (searchQuery.value) params.append('search', searchQuery.value)
      if (selectedLevel.value) params.append('level', selectedLevel.value)
      if (selectedRoom.value) params.append('section', selectedRoom.value)
      
      const response: any = await api.get(`/api/student-card/admin/students?${params.toString()}`)
      students.value = response.students?.data || response.students || []
      totalPages.value = response.students?.last_page || 1
    } catch (legacyErr) {
      console.error('Failed to fetch students:', legacyErr)
    }
  }
}

const handleSearch = () => {
  currentPage.value = 1
  fetchStudents()
}

const handleFilter = () => {
  currentPage.value = 1
  fetchStudents()
}

const viewStudentCard = (student: any) => {
  navigateTo(`/academies/${academyName.value}/admin/student-cards/${student.id}`)
}

const editStudentCard = (student: any) => {
  navigateTo(`/academies/${academyName.value}/admin/student-cards/${student.id}/edit`)
}

// Rooms by level
const rooms = computed(() => {
  // Common room numbers
  return ['1', '2', '3', '4', '5', '6', '7', '8', '9', '10']
})
</script>

<template>
  <div>
    <div v-if="isLoading" class="flex items-center justify-center py-20">
      <div class="animate-spin rounded-full h-10 w-10 border-4 border-primary-500 border-t-transparent"></div>
    </div>

    <div v-else class="space-y-6">
      <!-- Header -->
      <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 dark:text-white">บัตรนักเรียน</h1>
          <p class="text-gray-600 dark:text-gray-400 mt-1">จัดการข้อมูลบัตรนักเรียนของโรงเรียน</p>
        </div>
        
        <div class="flex gap-2">
          <NuxtLink 
            :to="`/academies/${academyName}/admin/student-cards/print`"
            class="px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 flex items-center gap-2"
          >
            <Icon icon="fluent:print-24-regular" class="w-5 h-5" />
            <span>พิมพ์บัตร</span>
          </NuxtLink>
          
          <NuxtLink 
            :to="`/academies/${academyName}/admin/student-cards/import`"
            class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 flex items-center gap-2"
          >
            <Icon icon="fluent:arrow-upload-24-regular" class="w-5 h-5" />
            <span>นำเข้าข้อมูล</span>
          </NuxtLink>
        </div>
      </div>

      <!-- Stats Cards -->
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow-sm border border-gray-100 dark:border-gray-700">
          <div class="flex items-center gap-4">
            <div class="p-3 bg-blue-100 dark:bg-blue-900/50 rounded-xl">
              <Icon icon="fluent:people-24-filled" class="w-6 h-6 text-blue-600 dark:text-blue-400" />
            </div>
            <div>
              <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.totalStudents }}</p>
              <p class="text-sm text-gray-500 dark:text-gray-400">นักเรียนทั้งหมด</p>
            </div>
          </div>
        </div>
        
        <div class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow-sm border border-gray-100 dark:border-gray-700">
          <div class="flex items-center gap-4">
            <div class="p-3 bg-green-100 dark:bg-green-900/50 rounded-xl">
              <Icon icon="fluent:image-24-filled" class="w-6 h-6 text-green-600 dark:text-green-400" />
            </div>
            <div>
              <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.withPhoto }}</p>
              <p class="text-sm text-gray-500 dark:text-gray-400">มีรูปภาพ</p>
            </div>
          </div>
        </div>
        
        <div class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow-sm border border-gray-100 dark:border-gray-700">
          <div class="flex items-center gap-4">
            <div class="p-3 bg-orange-100 dark:bg-orange-900/50 rounded-xl">
              <Icon icon="fluent:image-off-24-filled" class="w-6 h-6 text-orange-600 dark:text-orange-400" />
            </div>
            <div>
              <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.withoutPhoto }}</p>
              <p class="text-sm text-gray-500 dark:text-gray-400">ไม่มีรูปภาพ</p>
            </div>
          </div>
        </div>
        
        <div class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow-sm border border-gray-100 dark:border-gray-700">
          <div class="flex items-center gap-4">
            <div class="p-3 bg-purple-100 dark:bg-purple-900/50 rounded-xl">
              <Icon icon="fluent:building-24-filled" class="w-6 h-6 text-purple-600 dark:text-purple-400" />
            </div>
            <div>
              <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ levels.length }}</p>
              <p class="text-sm text-gray-500 dark:text-gray-400">ระดับชั้น</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Search & Filters -->
      <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-100 dark:border-gray-700">
        <div class="flex flex-col md:flex-row gap-4">
          <div class="flex-1">
            <div class="relative">
              <Icon icon="fluent:search-24-regular" class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
              <input
                v-model="searchQuery"
                type="text"
                placeholder="ค้นหาชื่อ, รหัสนักเรียน..."
                class="w-full pl-10 pr-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500"
                @keyup.enter="handleSearch"
              />
            </div>
          </div>
          
          <div class="flex gap-2">
            <select
              v-model="selectedLevel"
              class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
              @change="handleFilter"
            >
              <option value="">ทุกระดับชั้น</option>
              <option v-for="level in levels" :key="level" :value="level">{{ level }}</option>
            </select>
            
            <select
              v-model="selectedRoom"
              class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
              @change="handleFilter"
            >
              <option value="">ทุกห้อง</option>
              <option v-for="room in rooms" :key="room" :value="room">ห้อง {{ room }}</option>
            </select>
            
            <button
              @click="handleSearch"
              class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700"
            >
              ค้นหา
            </button>
          </div>
        </div>
      </div>

      <!-- Students Table -->
      <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead class="bg-gray-50 dark:bg-gray-700">
              <tr>
                <th class="px-4 py-3 text-left text-sm font-medium text-gray-500 dark:text-gray-400">รูปภาพ</th>
                <th class="px-4 py-3 text-left text-sm font-medium text-gray-500 dark:text-gray-400">รหัสนักเรียน</th>
                <th class="px-4 py-3 text-left text-sm font-medium text-gray-500 dark:text-gray-400">ชื่อ-นามสกุล</th>
                <th class="px-4 py-3 text-left text-sm font-medium text-gray-500 dark:text-gray-400">ระดับชั้น</th>
                <th class="px-4 py-3 text-left text-sm font-medium text-gray-500 dark:text-gray-400">ห้อง</th>
                <th class="px-4 py-3 text-center text-sm font-medium text-gray-500 dark:text-gray-400">จัดการ</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
              <tr 
                v-for="student in students" 
                :key="student.id"
                class="hover:bg-gray-50 dark:hover:bg-gray-700/50"
              >
                <td class="px-4 py-3">
                  <img 
                    :src="student.profile_image || '/images/default-avatar.png'"
                    :alt="student.first_name_thai"
                    class="w-10 h-10 rounded-full object-cover"
                  />
                </td>
                <td class="px-4 py-3 text-sm text-gray-900 dark:text-white font-mono">
                  {{ student.student_number }}
                </td>
                <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">
                  {{ student.first_name_thai }} {{ student.last_name_thai }}
                </td>
                <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                  {{ student.class_level }}
                </td>
                <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                  {{ student.class_section }}
                </td>
                <td class="px-4 py-3">
                  <div class="flex justify-center gap-2">
                    <button
                      @click="viewStudentCard(student)"
                      class="p-2 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/50 rounded-lg"
                      title="ดูบัตร"
                    >
                      <Icon icon="fluent:eye-24-regular" class="w-5 h-5" />
                    </button>
                    <button
                      @click="editStudentCard(student)"
                      class="p-2 text-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg"
                      title="แก้ไข"
                    >
                      <Icon icon="fluent:edit-24-regular" class="w-5 h-5" />
                    </button>
                  </div>
                </td>
              </tr>
              
              <tr v-if="students.length === 0">
                <td colspan="6" class="px-4 py-12 text-center text-gray-500 dark:text-gray-400">
                  <Icon icon="fluent:people-24-regular" class="w-12 h-12 mx-auto mb-4 opacity-50" />
                  <p>ไม่พบข้อมูลนักเรียน</p>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        
        <!-- Pagination -->
        <div v-if="totalPages > 1" class="px-4 py-3 border-t border-gray-100 dark:border-gray-700 flex items-center justify-between">
          <p class="text-sm text-gray-600 dark:text-gray-400">
            หน้า {{ currentPage }} จาก {{ totalPages }}
          </p>
          <div class="flex gap-2">
            <button
              @click="currentPage--; fetchStudents()"
              :disabled="currentPage <= 1"
              class="px-3 py-1 border border-gray-300 dark:border-gray-600 rounded text-sm disabled:opacity-50"
            >
              ก่อนหน้า
            </button>
            <button
              @click="currentPage++; fetchStudents()"
              :disabled="currentPage >= totalPages"
              class="px-3 py-1 border border-gray-300 dark:border-gray-600 rounded text-sm disabled:opacity-50"
            >
              ถัดไป
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
