<script setup lang="ts">
/**
 * Academy Admin - Student Cards Management
 * หน้าจัดการบัตรนักเรียน - มี 2 โหมด: ดูตามห้อง / ดูรายชื่อ
 */
import { Icon } from '@iconify/vue'
import StudentCardRoomManager from '~/components/academy/student-card/StudentCardRoomManager.vue'

definePageMeta({
  layout: 'main'
})

const api = useApi()

// Consume parent-provided academy context to avoid re-fetching (and to avoid
// racing an extra useAcademyRole watcher against admin.vue's own instance —
// two concurrent /my-role calls collide with the JWT refresh path in useApi
// and can leave this page stuck when arriving from a page that already had
// requests in flight, e.g. the students registry).
const academyId = inject<Ref<number | null>>('academyId', ref(null))
const academyName = inject<ComputedRef<string>>('academyName', computed(() => String(useRoute().params.name)))
const academy = inject<Ref<any>>('academy', ref(null))

// State
const students = ref<any[]>([])
const isLoading = ref(true)
const searchQuery = ref('')
const selectedLevel = ref('')
const selectedRoom = ref('')
const currentPage = ref(1)
const totalPages = ref(1)

// View Mode: 'rooms' = browse by classroom, 'list' = search all
const viewMode = ref<'rooms' | 'list'>('rooms')

// Academy Role — reuse parent academyId so the watcher inside useAcademyRole
// does not re-reset/re-fetch just because this page mounted.
const { can, isAdmin, fetchMyRole } = useAcademyRole(academyId)

// Statistics
const stats = ref({
  totalStudents: 0,
  withPhoto: 0,
  withoutPhoto: 0,
  byLevel: {} as Record<string, number>,
  sectionsByLevel: {} as Record<string, string[]>,
})

// Room browsing — เมื่อเลือกห้องแล้ว StudentCardRoomManager เป็นคนโหลดและจัดการเอง
const activeLevel = ref('')

// Scoped state for errors & separate loading
const pageError = ref<string | null>(null)
const statsError = ref<string | null>(null)
const listError = ref<string | null>(null)
const isLoadingStats = ref(false)
const isLoadingList = ref(false)
const hasLoaded = ref(false)

const initializePage = async () => {
  if (!academyId.value) return
  pageError.value = null
  statsError.value = null
  listError.value = null
  isLoading.value = true
  try {
    // Parent (admin.vue) already resolved my-role, but our local useAcademyRole
    // instance still needs its own copy. Await once — no watcher race because
    // academyId was set before this composable's watcher was even registered.
    await fetchMyRole()

    if (!isAdmin.value && !can('students.view')) {
      pageError.value = 'คุณไม่มีสิทธิ์เข้าถึงหน้านี้'
      return
    }

    await fetchStatistics()
    hasLoaded.value = true
  } catch (err: any) {
    console.error('Failed to load:', err)
    pageError.value = err?.data?.message || 'เกิดข้อผิดพลาดในการโหลดข้อมูลโรงเรียน'
  } finally {
    isLoading.value = false
  }
}

// academyId may already be resolved by parent (typical navigation from a
// sibling admin page) or may arrive a tick later (deep link / hard refresh).
// Handle both without double-firing.
if (academyId.value) {
  onMounted(initializePage)
} else {
  const stop = watch(academyId, (id) => {
    if (id) {
      stop()
      initializePage()
    }
  })
}

const fetchStatistics = async () => {
  if (!academyId.value) return
  isLoadingStats.value = true
  statsError.value = null
  
  try {
    const response: any = await api.get(`/api/academies/${academyId.value}/student-cards/statistics`)
    if (response.success) {
      stats.value = response.statistics
      // Set first level as active
      const levels = Object.keys(response.statistics.byLevel || {}).sort()
      if (levels.length > 0) {
        activeLevel.value = levels[0]
      }
    } else {
      statsError.value = 'ไม่สามารถดึงข้อมูลสถิติได้'
    }
  } catch (err: any) {
    console.error('Failed to fetch statistics:', err)
    statsError.value = err?.data?.message || 'เกิดข้อผิดพลาดในการดึงข้อมูลสถิติประจำโรงเรียน'
  } finally {
    isLoadingStats.value = false
  }
}

const fetchStudents = async () => {
  if (!academyId.value) return
  isLoadingList.value = true
  listError.value = null
  
  try {
    const params = new URLSearchParams()
    if (searchQuery.value) params.append('search', searchQuery.value)
    if (selectedLevel.value) params.append('level', selectedLevel.value)
    if (selectedRoom.value) params.append('section', selectedRoom.value)
    params.append('page', currentPage.value.toString())
    
    const isManager = isAdmin.value || can('students.manage')
    const endpoint = isManager
      ? `/api/academies/${academyId.value}/student-cards/admin/students`
      : `/api/academies/${academyId.value}/student-cards/search`
      
    const response: any = await api.get(`${endpoint}?${params.toString()}`)
    if (response.success) {
      students.value = response.students?.data || response.students || []
      totalPages.value = response.students?.last_page || 1
    } else {
      listError.value = 'ไม่สามารถดึงข้อมูลรายชื่อนักเรียนได้'
    }
  } catch (err: any) {
    console.error('Failed to fetch students:', err)
    if (err?.status === 403) {
      listError.value = 'คุณไม่มีสิทธิ์เข้าถึงข้อมูลรายชื่อนักเรียนในโรงเรียนนี้'
    } else {
      listError.value = err?.data?.message || 'เกิดข้อผิดพลาดในการดึงข้อมูลรายชื่อนักเรียน'
    }
  } finally {
    isLoadingList.value = false
  }
}

const openRoom = (level: string, room: string) => {
  selectedLevel.value = level
  selectedRoom.value = room
}

const handleSearch = () => {
  currentPage.value = 1
  fetchStudents()
}

const handleFilter = () => {
  currentPage.value = 1
  fetchStudents()
}

const switchToList = () => {
  viewMode.value = 'list'
  fetchStudents()
}

const switchToRooms = () => {
  viewMode.value = 'rooms'
  selectedLevel.value = ''
  selectedRoom.value = ''
}

const viewStudentCard = (student: any) => {
  if (student.student_id) {
    navigateTo(`/academies/${academyName.value}/students/${student.student_id}/profile?tab=card`)
  } else {
    navigateTo(`/academies/${academyName.value}/admin/student-cards/${student.id}`)
  }
}

const editStudentCard = (student: any) => {
  navigateTo(`/academies/${academyName.value}/admin/student-cards/${student.id}/edit`)
}

// Computed
const levels = computed(() => Object.keys(stats.value.byLevel || {}).sort())

// ห้องที่มีอยู่จริงเท่านั้น — sectionsByLevel มาจาก query เดียวกับ byLevel
// (เดิม fallback เป็น 1–10 ตายตัว ทำให้ขึ้นห้องที่โรงเรียนไม่มี แล้วกดเข้าไปเจอห้องว่าง)
const roomsForLevel = computed(() => {
  if (!activeLevel.value) return []
  return stats.value.sectionsByLevel?.[activeLevel.value] || []
})

const photoRate = computed(() => {
  if (!stats.value.totalStudents) return 0
  return Math.round((stats.value.withPhoto / stats.value.totalStudents) * 100)
})

const getProfileImage = (student: any) => {
  return student.profile_image_url || null
}
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
        
        <div class="flex flex-wrap gap-2">
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
              <Icon icon="fluent:checkmark-circle-24-filled" class="w-6 h-6 text-purple-600 dark:text-purple-400" />
            </div>
            <div>
              <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ photoRate }}%</p>
              <p class="text-sm text-gray-500 dark:text-gray-400">ครบรูปภาพ</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Empty State: No students at all -->
      <div v-if="stats.totalStudents === 0 && !isLoadingStats && !statsError" class="bg-white dark:bg-gray-800 rounded-xl p-8 shadow-sm border border-gray-100 dark:border-gray-700 text-center max-w-xl mx-auto my-12">
        <div class="w-16 h-16 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mx-auto mb-4">
          <Icon icon="fluent:contact-card-key-24-regular" class="w-10 h-10 text-gray-400" />
        </div>
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-2">ยังไม่มีข้อมูลบัตรนักเรียนในระบบ</h2>
        <p class="text-gray-600 dark:text-gray-400 mb-6">
          โรงเรียนนี้ยังไม่มีการจัดทำหรือนำเข้าข้อมูลบัตรนักเรียนใดๆ
        </p>
        
        <div v-if="isAdmin || can('students.manage')" class="flex justify-center gap-3">
          <NuxtLink :to="`/academies/${academyName}/admin/student-cards/import`" class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg flex items-center gap-2 transition">
            <Icon icon="fluent:arrow-upload-24-regular" class="w-5 h-5" />
            <span>นำเข้าข้อมูลนักเรียน</span>
          </NuxtLink>
        </div>
        <p v-else class="text-sm text-amber-600 dark:text-amber-400 font-medium">
          กรุณาติดต่อผู้ดูแลระบบเพื่อทำการนำเข้าข้อมูลนักเรียนเข้าระบบจัดการบัตร
        </p>
      </div>

      <div v-else-if="stats.totalStudents > 0" class="space-y-6">
        <!-- View Mode Toggle -->
        <div class="flex gap-2">
          <button
            @click="switchToRooms"
            :class="[
              'px-4 py-2 rounded-lg font-medium transition-colors flex items-center gap-2',
              viewMode === 'rooms'
                ? 'bg-primary-600 text-white'
                : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700'
            ]"
          >
            <Icon icon="fluent:grid-24-regular" class="w-5 h-5" />
            <span>ดูตามห้อง</span>
          </button>
          <button
            @click="switchToList"
            :class="[
              'px-4 py-2 rounded-lg font-medium transition-colors flex items-center gap-2',
              viewMode === 'list'
                ? 'bg-primary-600 text-white'
                : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700'
            ]"
          >
            <Icon icon="fluent:list-24-regular" class="w-5 h-5" />
            <span>รายชื่อ</span>
          </button>
        </div>

        <!-- =============== ROOM BROWSING MODE =============== -->
        <template v-if="viewMode === 'rooms'">
          <!-- Level Tabs -->
          <div v-if="levels.length > 0" class="flex flex-wrap gap-2 border-b border-gray-200 dark:border-gray-700 pb-4">
            <button
              v-for="level in levels"
              :key="level"
              @click="activeLevel = level; selectedRoom = ''"
              :class="[
                'px-5 py-3 rounded-xl font-semibold transition-all flex items-center gap-2',
                activeLevel === level
                  ? 'bg-primary-600 text-white shadow-lg scale-105'
                  : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 hover:scale-105'
              ]"
            >
              <span class="text-lg">🏫</span>
              <span>ม.{{ level }}</span>
              <span class="text-xs opacity-80">({{ stats.byLevel[level] || 0 }})</span>
            </button>
          </div>

          <!-- Room Grid -->
          <div v-if="activeLevel && !selectedRoom && levels.length > 0" class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 lg:grid-cols-6 gap-4">
            <div
              v-for="room in roomsForLevel"
              :key="room"
              @click="openRoom(activeLevel, room)"
              class="group bg-white dark:bg-gray-800 shadow-sm rounded-2xl p-6 cursor-pointer hover:scale-105 hover:shadow-lg transition-all text-center border-2 border-gray-200 dark:border-gray-700 hover:border-primary-400 dark:hover:border-primary-500 relative overflow-hidden"
            >
              <div class="flex flex-col items-center justify-center">
                <span class="text-2xl sm:text-3xl font-bold text-primary-700 dark:text-primary-400 group-hover:text-primary-800 transition mb-1">
                  ม.{{ activeLevel }}/{{ room }}
                </span>
                <span class="text-xs text-gray-400 font-semibold tracking-widest uppercase">Classroom</span>
              </div>
            </div>
          </div>

          <!-- Room Empty State when levels length is 0 but total students > 0 -->
          <div v-else-if="levels.length === 0 && !isLoadingStats && !statsError" class="bg-white dark:bg-gray-800 rounded-xl p-8 shadow-sm border border-gray-100 dark:border-gray-700 text-center max-w-xl mx-auto my-12">
            <div class="w-16 h-16 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mx-auto mb-4">
              <Icon icon="fluent:branch-fork-24-regular" class="w-10 h-10 text-gray-400" />
            </div>
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-2">ยังไม่มีข้อมูลโครงสร้างระดับชั้น</h2>
            <p class="text-gray-600 dark:text-gray-400">
              พบข้อมูลนักเรียนประจำสถาบัน แต่ยังไม่มีการจัดกลุ่มข้อมูลแยกตามระดับชั้นหรือห้องเรียนเพื่อจัดเรียงในแท็บนี้
            </p>
          </div>

          <!-- Room Students — จัดการได้เต็มรูปแบบในหน้าเดียว -->
          <StudentCardRoomManager
            v-if="selectedRoom && academyId"
            :key="`${selectedLevel}-${selectedRoom}`"
            :academy-id="academyId"
            :academy="academy"
            :level="selectedLevel"
            :room="selectedRoom"
            @back="selectedRoom = ''"
          />
        </template>

        <!-- =============== LIST MODE =============== -->
        <template v-if="viewMode === 'list'">
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
                  <option v-for="level in levels" :key="level" :value="level">ม.{{ level }}</option>
                </select>
                
                <button
                  @click="handleSearch"
                  class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition"
                >
                  ค้นหา
                </button>
              </div>
            </div>
          </div>

          <!-- List Loading State -->
          <div v-if="isLoadingList" class="p-12 text-center text-gray-500 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 flex flex-col items-center justify-center gap-2">
            <div class="animate-spin rounded-full h-8 w-8 border-4 border-primary-500 border-t-transparent"></div>
            <span class="text-sm">กำลังโหลดรายชื่อนักเรียน...</span>
          </div>

          <!-- List Error State -->
          <div v-else-if="listError" class="p-12 text-center text-red-500 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-red-100 dark:border-red-955 flex flex-col items-center justify-center gap-2">
            <Icon icon="fluent:error-circle-24-regular" class="w-8 h-8 text-red-500" />
            <span class="text-sm">{{ listError }}</span>
            <button @click="fetchStudents" class="mt-2 text-xs font-semibold px-3 py-1.5 bg-red-100 hover:bg-red-200 dark:bg-red-900/50 dark:hover:bg-red-900 rounded-lg transition text-red-700 dark:text-red-400">
              ลองใหม่อีกครั้ง
            </button>
          </div>

          <!-- Students Table -->
          <div v-else class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
            <div class="overflow-x-auto">
              <table class="w-full">
                <thead class="bg-gray-50 dark:bg-gray-700">
                  <tr>
                    <th class="px-4 py-3 text-left text-sm font-medium text-gray-500 dark:text-gray-400">รูปภาพ</th>
                    <th class="px-4 py-3 text-left text-sm font-medium text-gray-500 dark:text-gray-400">รหัสนักเรียน</th>
                    <th class="px-4 py-3 text-left text-sm font-medium text-gray-500 dark:text-gray-400">ชื่อ-นามสกุล</th>
                    <th class="px-4 py-3 text-left text-sm font-medium text-gray-500 dark:text-gray-400">ชั้น/ห้อง</th>
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
                      <div class="w-10 h-10 rounded-full overflow-hidden bg-gray-200 dark:bg-gray-600">
                        <img
                          v-if="getProfileImage(student)"
                          :src="getProfileImage(student)"
                          :alt="student.first_name_thai"
                          class="w-full h-full object-cover"
                        />
                        <div v-else class="w-full h-full flex items-center justify-center">
                          <Icon icon="fluent:person-24-regular" class="w-5 h-5 text-gray-400" />
                        </div>
                      </div>
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-900 dark:text-white font-mono">
                      {{ student.student_number }}
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">
                      {{ student.title_name }} {{ student.first_name_thai }} {{ student.last_name_thai }}
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                      ม.{{ student.class_level }}/{{ student.class_section }}
                    </td>
                    <td class="px-4 py-3">
                      <div class="flex justify-center gap-2">
                        <button
                          @click="viewStudentCard(student)"
                          class="p-2 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/50 rounded-lg transition"
                          title="ดูบัตร"
                        >
                          <Icon icon="fluent:eye-24-regular" class="w-5 h-5" />
                        </button>
                        <button
                          v-if="isAdmin || can('students.manage')"
                          @click="editStudentCard(student)"
                          class="p-2 text-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition"
                          title="แก้ไข"
                        >
                          <Icon icon="fluent:edit-24-regular" class="w-5 h-5" />
                        </button>
                      </div>
                    </td>
                  </tr>
                  
                  <tr v-if="students.length === 0">
                    <td colspan="5" class="px-4 py-12 text-center text-gray-500 dark:text-gray-400">
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
                  class="px-3 py-1 border border-gray-300 dark:border-gray-600 rounded text-sm disabled:opacity-50 transition"
                >
                  ก่อนหน้า
                </button>
                <button
                  @click="currentPage++; fetchStudents()"
                  :disabled="currentPage >= totalPages"
                  class="px-3 py-1 border border-gray-300 dark:border-gray-600 rounded text-sm disabled:opacity-50 transition"
                >
                  ถัดไป
                </button>
              </div>
            </div>
          </div>
        </template>
      </div>
    </div>
  </div>
</template>
