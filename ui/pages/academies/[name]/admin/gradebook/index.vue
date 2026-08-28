<script setup lang="ts">
/**
 * Academy Gradebook Admin - Main Page
 * หน้าจัดการระบบสมุดคะแนนของโรงเรียน
 */
import { Icon } from '@iconify/vue'

definePageMeta({
  layout: 'main'
})

const route = useRoute()
const api = useApi()
const academyName = computed(() => route.params.name as string)

// State
const academy = ref<any>(null)
const academyId = ref<number | null>(null)
const isLoading = ref(true)

// Data
const academicYears = ref<any[]>([])
const currentSemester = ref<any>(null)
const classrooms = ref<any[]>([])
const gradeLevels = ref<string[]>([])
const transcriptStats = ref<any>(null)

// Filters
const selectedAcademicYear = ref<number | null>(null)
const selectedGradeLevel = ref<string>('')

// Academy Role
const { isAdmin, fetchMyRole } = useAcademyRole(academyId)

onMounted(async () => {
  try {
    const response: any = await api.get(`/api/academies/${academyName.value}`)
    if (response.success) {
      academy.value = response.academy
      academyId.value = response.academy.id
      await fetchMyRole()
      
      if (!isAdmin.value) {
        navigateTo(`/academies/${academyName.value}`)
        return
      }
      
      await fetchData()
    }
  } catch (err) {
    console.error('Failed to load:', err)
  } finally {
    isLoading.value = false
  }
})

const fetchData = async () => {
  await Promise.all([
    fetchAcademicYears(),
    fetchClassrooms(),
    fetchGradeLevels(),
  ])
}

const fetchAcademicYears = async () => {
  try {
    const res: any = await api.get(`/api/academies/${academyId.value}/academic-years`)
    if (res.success) {
      academicYears.value = res.academicYears || []
      const current = academicYears.value.find((y: any) => y.is_current)
      if (current) {
        selectedAcademicYear.value = current.id
        currentSemester.value = current.semesters?.find((s: any) => s.is_current)
      }
    }
  } catch (err) {
    console.error('Failed to fetch academic years:', err)
  }
}

const fetchClassrooms = async () => {
  try {
    const params = new URLSearchParams()
    if (selectedAcademicYear.value) {
      params.append('academic_year_id', selectedAcademicYear.value.toString())
    }
    if (selectedGradeLevel.value) {
      params.append('grade_level', selectedGradeLevel.value)
    }
    
    const res: any = await api.get(`/api/academies/${academyId.value}/classrooms?${params}`)
    if (res.success) {
      classrooms.value = res.classrooms || []
    }
  } catch (err) {
    console.error('Failed to fetch classrooms:', err)
  }
}

const fetchGradeLevels = async () => {
  try {
    const res: any = await api.get(`/api/academies/${academyId.value}/classrooms/grade-levels`)
    if (res.success) {
      gradeLevels.value = res.gradeLevels || []
    }
  } catch (err) {
    console.error('Failed to fetch grade levels:', err)
  }
}

const fetchTranscriptStats = async () => {
  if (!currentSemester.value) return
  try {
    const res: any = await api.get(`/api/academies/${academyId.value}/transcripts/overview?semester_id=${currentSemester.value.id}`)
    if (res.success) {
      transcriptStats.value = res.stats
    }
  } catch (err) {
    console.error('Failed to fetch transcript stats:', err)
  }
}

watch([selectedAcademicYear, selectedGradeLevel], () => {
  fetchClassrooms()
  fetchTranscriptStats()
})

// Computed stats
const totalStudents = computed(() => {
  return classrooms.value.reduce((sum, c) => sum + (c.active_students_count || 0), 0)
})

// Menu items
const menuItems = computed(() => [
  {
    title: 'ปีการศึกษา & ภาคเรียน',
    description: 'จัดการปีการศึกษาและภาคเรียน',
    icon: 'fluent:calendar-24-filled',
    to: `/academies/${academyName.value}/admin/gradebook/academic-years`,
    color: 'bg-blue-500',
  },
  {
    title: 'นักเรียน',
    description: 'จัดการนักเรียน บัตรนักเรียน ผลการเรียน',
    icon: 'fluent:people-24-filled',
    to: `/academies/${academyName.value}/admin/gradebook/students`,
    color: 'bg-indigo-500',
  },
  {
    title: 'ห้องเรียน',
    description: 'จัดการห้องเรียนและจัดนักเรียน',
    icon: 'fluent:building-24-filled',
    to: `/academies/${academyName.value}/admin/gradebook/classrooms`,
    color: 'bg-purple-500',
  },
  {
    title: 'รายวิชา',
    description: 'จัดการวิชาเรียนและหน่วยกิต',
    icon: 'fluent:book-24-filled',
    to: `/academies/${academyName.value}/admin/gradebook/subjects`,
    color: 'bg-green-500',
  },
  {
    title: 'เกณฑ์การประเมิน',
    description: 'ตั้งค่าเกณฑ์การตัดเกรดและหมวดหมู่การประเมิน',
    icon: 'fluent:star-settings-24-filled',
    to: `/academies/${academyName.value}/admin/gradebook/grade-scales`,
    color: 'bg-amber-500',
  },
  {
    title: 'ผลการเรียน & Transcript',
    description: 'สรุปผลและออกใบ Transcript',
    icon: 'fluent:document-bullet-list-24-filled',
    to: `/academies/${academyName.value}/admin/gradebook/transcripts`,
    color: 'bg-cyan-500',
  },
  {
    title: 'Year Rollover Wizard',
    description: 'เลื่อนชั้นรายปีแบบมี preview, commit gate และ undo 24 ชั่วโมง',
    icon: 'fluent:arrow-sync-circle-24-filled',
    to: `/academies/${academyName.value}/admin/gradebook/rollover`,
    color: 'bg-rose-500',
  },
])
</script>

<template>
  <div class="px-4 sm:px-0">
    <div v-if="isLoading" class="flex items-center justify-center py-20">
      <div class="animate-spin rounded-full h-10 w-10 border-4 border-primary-500 border-t-transparent"></div>
    </div>

    <div v-else class="space-y-8">
      <!-- Header -->
      <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
            <Icon icon="fluent:text-grammar-checkmark-24-filled" class="w-8 h-8 text-primary-500" />
            ระบบสมุดคะแนน
          </h1>
          <p class="text-gray-600 dark:text-gray-400 mt-1">จัดการผลการเรียน เกรด และ Transcript</p>
        </div>

        <!-- Current Semester Badge -->
        <div v-if="currentSemester" class="flex items-center gap-3 px-4 py-2 bg-primary-50 dark:bg-primary-900/30 rounded-lg border border-primary-200 dark:border-primary-800">
          <Icon icon="fluent:calendar-today-24-filled" class="w-5 h-5 text-primary-500" />
          <div>
            <div class="text-sm text-primary-600 dark:text-primary-400 font-medium">ภาคเรียนปัจจุบัน</div>
            <div class="text-primary-800 dark:text-primary-200 font-bold">
              {{ currentSemester.name }}
            </div>
          </div>
        </div>
      </div>

      <!-- Quick Stats -->
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 sm:p-6 shadow-sm border border-gray-200 dark:border-gray-700">
          <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-lg bg-blue-100 dark:bg-blue-900/50 flex items-center justify-center">
              <Icon icon="fluent:calendar-24-filled" class="w-6 h-6 text-blue-600 dark:text-blue-400" />
            </div>
            <div>
              <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ academicYears.length }}</div>
              <div class="text-sm text-gray-600 dark:text-gray-400">ปีการศึกษา</div>
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 sm:p-6 shadow-sm border border-gray-200 dark:border-gray-700">
          <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-lg bg-purple-100 dark:bg-purple-900/50 flex items-center justify-center">
              <Icon icon="fluent:building-24-filled" class="w-6 h-6 text-purple-600 dark:text-purple-400" />
            </div>
            <div>
              <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ classrooms.length }}</div>
              <div class="text-sm text-gray-600 dark:text-gray-400">ห้องเรียน</div>
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 sm:p-6 shadow-sm border border-gray-200 dark:border-gray-700">
          <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-lg bg-green-100 dark:bg-green-900/50 flex items-center justify-center">
              <Icon icon="fluent:people-24-filled" class="w-6 h-6 text-green-600 dark:text-green-400" />
            </div>
            <div>
              <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ totalStudents }}</div>
              <div class="text-sm text-gray-600 dark:text-gray-400">นักเรียน</div>
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 sm:p-6 shadow-sm border border-gray-200 dark:border-gray-700">
          <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-lg bg-amber-100 dark:bg-amber-900/50 flex items-center justify-center">
              <Icon icon="fluent:book-24-filled" class="w-6 h-6 text-amber-600 dark:text-amber-400" />
            </div>
            <div>
              <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ gradeLevels.length }}</div>
              <div class="text-sm text-gray-600 dark:text-gray-400">ระดับชั้น</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Menu Grid -->
      <div>
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">เมนูหลัก</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          <NuxtLink
            v-for="item in menuItems"
            :key="item.title"
            :to="item.to"
            class="group bg-white dark:bg-gray-800 rounded-xl p-4 sm:p-6 shadow-sm border border-gray-200 dark:border-gray-700 hover:shadow-lg hover:border-primary-300 dark:hover:border-primary-600 transition-all duration-200"
          >
            <div class="flex items-start gap-4">
              <div :class="['w-12 h-12 rounded-lg flex items-center justify-center text-white', item.color]">
                <Icon :icon="item.icon" class="w-6 h-6" />
              </div>
              <div class="flex-1">
                <h3 class="font-semibold text-gray-900 dark:text-white group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors">
                  {{ item.title }}
                </h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ item.description }}</p>
              </div>
              <Icon icon="fluent:chevron-right-24-filled" class="w-5 h-5 text-gray-400 group-hover:text-primary-500 group-hover:translate-x-1 transition-all" />
            </div>
          </NuxtLink>
        </div>
      </div>

      <!-- Classrooms Overview -->
      <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
          <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
              <Icon icon="fluent:building-24-filled" class="w-5 h-5 text-purple-500" />
              ห้องเรียนทั้งหมด
            </h2>
            
            <div class="flex items-center gap-3">
              <select
                v-model="selectedGradeLevel"
                class="px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-primary-500 focus:border-transparent"
              >
                <option value="">ทุกระดับชั้น</option>
                <option v-for="level in gradeLevels" :key="level" :value="level">{{ level }}</option>
              </select>
              
              <NuxtLink
                :to="`/academies/${academyName}/admin/gradebook/classrooms`"
                class="px-4 py-2 bg-primary-500 hover:bg-primary-600 text-white rounded-lg font-medium text-sm transition-colors flex items-center gap-2"
              >
                <Icon icon="fluent:add-24-filled" class="w-4 h-4" />
                เพิ่มห้องเรียน
              </NuxtLink>
            </div>
          </div>
        </div>

        <div class="p-6">
          <div v-if="classrooms.length === 0" class="text-center py-12">
            <Icon icon="fluent:building-24-regular" class="w-16 h-16 text-gray-300 dark:text-gray-600 mx-auto mb-4" />
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">ยังไม่มีห้องเรียน</h3>
            <p class="text-gray-600 dark:text-gray-400 mb-6">เริ่มต้นด้วยการสร้างห้องเรียนใหม่</p>
            <NuxtLink
              :to="`/academies/${academyName}/admin/gradebook/classrooms`"
              class="inline-flex items-center gap-2 px-6 py-3 bg-primary-500 hover:bg-primary-600 text-white rounded-lg font-medium transition-colors"
            >
              <Icon icon="fluent:add-24-filled" class="w-5 h-5" />
              สร้างห้องเรียน
            </NuxtLink>
          </div>

          <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <div
              v-for="classroom in classrooms.slice(0, 6)"
              :key="classroom.id"
              class="p-4 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"
            >
              <div class="flex items-center justify-between">
                <div>
                  <h3 class="font-semibold text-gray-900 dark:text-white">{{ classroom.name }}</h3>
                  <p class="text-sm text-gray-600 dark:text-gray-400">{{ classroom.grade_level }}/{{ classroom.section }}</p>
                </div>
                <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                  <Icon icon="fluent:people-24-filled" class="w-4 h-4" />
                  {{ classroom.active_students_count || 0 }}
                </div>
              </div>
              <div class="mt-3 flex items-center gap-2">
                <NuxtLink
                  :to="`/academies/${academyName}/admin/gradebook/classrooms/${classroom.id}`"
                  class="text-sm text-primary-600 dark:text-primary-400 hover:underline"
                >
                  ดูรายละเอียด
                </NuxtLink>
              </div>
            </div>
          </div>

          <div v-if="classrooms.length > 6" class="mt-4 text-center">
            <NuxtLink
              :to="`/academies/${academyName}/admin/gradebook/classrooms`"
              class="text-primary-600 dark:text-primary-400 hover:underline text-sm font-medium"
            >
              ดูทั้งหมด {{ classrooms.length }} ห้องเรียน →
            </NuxtLink>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
