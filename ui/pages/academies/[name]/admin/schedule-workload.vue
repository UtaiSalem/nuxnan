<script setup lang="ts">
import { Icon } from '@iconify/vue'
import Swal from 'sweetalert2'

definePageMeta({
  layout: 'main'
})

const route = useRoute()
const api = useApi()
const academyName = computed(() => route.params.name as string)

const academy = ref<any>(null)
const isLoading = ref(true)
const isLoadingWorkload = ref(false)

const academyId = ref<number | null>(null)
const { can, isAdmin, fetchMyRole } = useAcademyRole(academyId)
const canView = computed(() => isAdmin.value || can('schedule.view') || can('academy.view'))

const academicYears = ref<any[]>([])
const selectedAcademicYear = ref<number | null>(null)
const selectedSemester = ref<number | null>(null)

const summary = ref({
  teacher_count: 0,
  teachers_without_periods: 0,
  total_periods: 0,
  average_periods: 0
})
const teachers = ref<any[]>([])

const searchQuery = ref('')
const showNoPeriodsOnly = ref(false)
const sortKey = ref('periods_per_week')
const sortOrder = ref<'asc'|'desc'>('desc')

const selectedYearData = computed(() => {
  return academicYears.value.find(y => y.id === selectedAcademicYear.value)
})

const availableSemesters = computed(() => {
  return selectedYearData.value?.semesters || []
})

onMounted(async () => {
  try {
    const response: any = await api.get(`/api/academies/${academyName.value}`)
    if (response.success) {
      academy.value = response.academy
      academyId.value = response.academy.id
      await fetchMyRole()
      
      if (!canView.value) {
        navigateTo(`/academies/${academyName.value}`)
        return
      }
      
      await fetchAcademicYears()
    }
  } catch (err) {
    console.error('Failed to load:', err)
  } finally {
    isLoading.value = false
  }
})

const fetchAcademicYears = async () => {
  if (!academyId.value) return
  try {
    const response: any = await api.get(`/api/academies/${academyId.value}/academic-years`)
    if (response.success && response.academicYears?.length > 0) {
      academicYears.value = response.academicYears
      
      const currentYear = academicYears.value.find(y => y.is_current) || academicYears.value[0]
      selectedAcademicYear.value = currentYear.id
    }
  } catch (err) {
    console.error('Failed to fetch academic years:', err)
  }
}

watch(selectedAcademicYear, (newVal) => {
  if (newVal) {
    const year = academicYears.value.find(y => y.id === newVal)
    if (year && year.semesters?.length > 0) {
      const currentSem = year.semesters.find((s: any) => s.is_current) || year.semesters.sort((a: any, b: any) => a.semester_number - b.semester_number)[0]
      selectedSemester.value = currentSem.id
    } else {
      selectedSemester.value = null
    }
  }
})

const fetchWorkload = async () => {
  if (!academyId.value || !selectedSemester.value) {
    teachers.value = []
    return
  }
  
  isLoadingWorkload.value = true
  try {
    const response: any = await api.get(`/api/academies/${academyId.value}/schedules/workload`, {
      params: { semester_id: selectedSemester.value }
    })
    if (response.success) {
      summary.value = response.data.summary || {
        teacher_count: 0,
        teachers_without_periods: 0,
        total_periods: 0,
        average_periods: 0
      }
      teachers.value = response.data.teachers || []
    }
  } catch (err) {
    console.error('Failed to fetch workload:', err)
    teachers.value = []
  } finally {
    isLoadingWorkload.value = false
  }
}

watch(selectedSemester, () => {
  fetchWorkload()
})

const isExporting = ref(false)
const handleExport = async () => {
  if (!academyId.value || !selectedSemester.value) return
  isExporting.value = true
  try {
    const params: any = { semester_id: selectedSemester.value }
    const query = new URLSearchParams(
      Object.entries(params).map(([key, value]) => [key, String(value)])
    ).toString()

    const { blob, filename } = await api.getBlob(`/api/academies/${academyId.value}/schedules/workload/export?${query}`)
    const objectUrl = URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = objectUrl
    link.download = filename || `teacher-workload.xlsx`
    document.body.appendChild(link)
    link.click()
    link.remove()
    URL.revokeObjectURL(objectUrl)
  } catch (err: any) {
    Swal.fire({
      icon: 'error',
      title: 'ไม่สามารถส่งออกได้',
      text: err.data?.message || 'ระบบส่งออกอาจจะยังไม่พร้อมใช้งาน'
    })
  } finally {
    isExporting.value = false
  }
}

const toggleSort = (key: string) => {
  if (sortKey.value === key) {
    sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc'
  } else {
    sortKey.value = key
    sortOrder.value = 'desc' // default desc for numbers
  }
}

const filteredTeachers = computed(() => {
  let list = teachers.value
  
  if (showNoPeriodsOnly.value) {
    list = list.filter(t => t.periods_per_week === 0)
  }
  
  if (searchQuery.value) {
    const q = searchQuery.value.toLowerCase()
    list = list.filter(t => t.name.toLowerCase().includes(q))
  }
  
  // คัดลอกก่อนเรียง — .sort() เรียงทับอาเรย์เดิม (teachers.value) จากใน computed
  return [...list].sort((a, b) => {
    let valA = a[sortKey.value]
    let valB = b[sortKey.value]
    
    if (sortKey.value === 'name') {
      return sortOrder.value === 'asc' 
        ? a.name.localeCompare(b.name, 'th') 
        : b.name.localeCompare(a.name, 'th')
    }
    
    valA = Number(valA) || 0
    valB = Number(valB) || 0
    
    return sortOrder.value === 'asc' ? valA - valB : valB - valA
  })
})
</script>

<template>
  <div class="px-4 sm:px-0">
    <div v-if="isLoading" class="flex items-center justify-center py-20">
      <div class="animate-spin rounded-full h-10 w-10 border-4 border-primary-500 border-t-transparent"></div>
    </div>
    
    <div v-else class="space-y-6">
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center gap-3">
          <NuxtLink :to="`/academies/${academyName}/admin/schedule`" class="inline-flex items-center justify-center bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors w-[44px] h-[44px]">
            <Icon icon="fluent:arrow-left-24-regular" class="w-6 h-6 text-gray-700 dark:text-gray-300" />
          </NuxtLink>
          <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">ภาระงานสอนของครู</h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">นับจากคาบในตารางเรียนของภาคเรียนที่เลือก (ไม่นับคาบพัก)</p>
          </div>
        </div>
        
        <button
          :disabled="isExporting || !selectedSemester"
          @click="handleExport"
          class="min-h-[44px] inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-primary-600 hover:bg-primary-700 text-white rounded-xl font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
        >
          <Icon icon="fluent:arrow-download-24-regular" class="w-5 h-5" />
          <span>ส่งออก Excel</span>
        </button>
      </div>
      
      <!-- Filter Card -->
      <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-100 dark:border-gray-700">
        <div class="flex flex-col sm:flex-row flex-wrap gap-4 items-center">
          <select
            v-model="selectedAcademicYear"
            class="w-full sm:w-48 px-4 py-2.5 min-h-[44px] bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white"
          >
            <option :value="null" disabled>เลือกปีการศึกษา</option>
            <option v-for="year in academicYears" :key="year.id" :value="year.id">
              {{ year.name }}
            </option>
          </select>
          
          <select
            v-model="selectedSemester"
            :disabled="availableSemesters.length === 0"
            class="w-full sm:w-48 px-4 py-2.5 min-h-[44px] bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white"
          >
            <option :value="null" disabled>เลือกภาคเรียน</option>
            <option v-for="sem in availableSemesters" :key="sem.id" :value="sem.id">
              {{ sem.name }}
            </option>
          </select>
          
          <input
            v-model="searchQuery"
            type="text"
            placeholder="ค้นหาชื่อครู..."
            class="w-full sm:flex-1 px-4 py-2.5 min-h-[44px] bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white placeholder-gray-400"
          />
          
          <label class="w-full sm:w-auto flex items-center gap-2 min-h-[44px] cursor-pointer">
            <input type="checkbox" v-model="showNoPeriodsOnly" class="rounded text-primary-600 focus:ring-primary-500 w-5 h-5" />
            <span class="text-gray-700 dark:text-gray-300">แสดงเฉพาะครูที่ยังไม่มีคาบ</span>
          </label>
        </div>
      </div>
      
      <!-- Summary Grid -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-100 dark:border-gray-700">
          <p class="text-sm text-gray-500 dark:text-gray-400">ครูทั้งหมด</p>
          <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ summary.teacher_count }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-100 dark:border-gray-700">
          <p class="text-sm text-gray-500 dark:text-gray-400">ยังไม่มีคาบ</p>
          <p class="text-2xl font-bold mt-1" :class="summary.teachers_without_periods > 0 ? 'text-orange-600 dark:text-orange-500' : 'text-gray-900 dark:text-white'">
            {{ summary.teachers_without_periods }}
          </p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-100 dark:border-gray-700">
          <p class="text-sm text-gray-500 dark:text-gray-400">คาบรวม/สัปดาห์</p>
          <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ summary.total_periods }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-100 dark:border-gray-700">
          <p class="text-sm text-gray-500 dark:text-gray-400">เฉลี่ยคาบ/คน</p>
          <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ Number(summary.average_periods).toFixed(1) }}</p>
        </div>
      </div>
      
      <!-- Table Card -->
      <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
        <div v-if="isLoadingWorkload" class="flex items-center justify-center py-20">
          <div class="animate-spin rounded-full h-8 w-8 border-4 border-primary-500 border-t-transparent"></div>
        </div>
        <div v-else-if="!selectedSemester" class="p-12 text-center text-gray-500 dark:text-gray-400">
          เลือกภาคเรียนก่อน
        </div>
        <div v-else-if="teachers.length === 0" class="p-12 text-center text-gray-500 dark:text-gray-400">
          ยังไม่มีครูในโรงเรียนนี้
        </div>
        <div v-else-if="filteredTeachers.length === 0" class="p-12 text-center text-gray-500 dark:text-gray-400">
          ไม่พบครูตามเงื่อนไข
        </div>
        <div v-else class="overflow-x-auto">
          <table class="min-w-full text-sm">
            <thead>
              <tr class="bg-gray-50 dark:bg-gray-700/50">
                <th class="px-3 sm:px-6 py-3 sm:py-4 whitespace-nowrap text-left font-medium text-gray-600 dark:text-gray-300 w-16">ลำดับ</th>
                <th class="px-3 sm:px-6 py-3 sm:py-4 whitespace-nowrap text-left font-medium text-gray-600 dark:text-gray-300 min-w-[10rem]">
                  <button @click="toggleSort('name')" class="flex items-center gap-1 min-h-[44px] sm:min-h-0 w-full hover:text-gray-900 dark:hover:text-white transition-colors">
                    ชื่อครู
                    <Icon v-if="sortKey === 'name'" :icon="sortOrder === 'desc' ? 'fluent:arrow-sort-down-24-regular' : 'fluent:arrow-sort-up-24-regular'" class="w-4 h-4" />
                  </button>
                </th>
                <th class="px-3 sm:px-6 py-3 sm:py-4 whitespace-nowrap text-left font-medium text-gray-600 dark:text-gray-300">
                  <button @click="toggleSort('periods_per_week')" class="flex items-center gap-1 min-h-[44px] sm:min-h-0 w-full hover:text-gray-900 dark:hover:text-white transition-colors">
                    คาบ/สัปดาห์
                    <Icon v-if="sortKey === 'periods_per_week'" :icon="sortOrder === 'desc' ? 'fluent:arrow-sort-down-24-regular' : 'fluent:arrow-sort-up-24-regular'" class="w-4 h-4" />
                  </button>
                </th>
                <th class="px-3 sm:px-6 py-3 sm:py-4 whitespace-nowrap text-left font-medium text-gray-600 dark:text-gray-300">
                  <button @click="toggleSort('hours_per_week')" class="flex items-center gap-1 min-h-[44px] sm:min-h-0 w-full hover:text-gray-900 dark:hover:text-white transition-colors">
                    ชั่วโมง/สัปดาห์
                    <Icon v-if="sortKey === 'hours_per_week'" :icon="sortOrder === 'desc' ? 'fluent:arrow-sort-down-24-regular' : 'fluent:arrow-sort-up-24-regular'" class="w-4 h-4" />
                  </button>
                </th>
                <th class="px-3 sm:px-6 py-3 sm:py-4 whitespace-nowrap text-left font-medium text-gray-600 dark:text-gray-300">
                  <button @click="toggleSort('course_count')" class="flex items-center gap-1 min-h-[44px] sm:min-h-0 w-full hover:text-gray-900 dark:hover:text-white transition-colors">
                    จำนวนวิชา
                    <Icon v-if="sortKey === 'course_count'" :icon="sortOrder === 'desc' ? 'fluent:arrow-sort-down-24-regular' : 'fluent:arrow-sort-up-24-regular'" class="w-4 h-4" />
                  </button>
                </th>
                <th class="px-3 sm:px-6 py-3 sm:py-4 whitespace-nowrap text-left font-medium text-gray-600 dark:text-gray-300">
                  <button @click="toggleSort('classroom_count')" class="flex items-center gap-1 min-h-[44px] sm:min-h-0 w-full hover:text-gray-900 dark:hover:text-white transition-colors">
                    จำนวนห้อง
                    <Icon v-if="sortKey === 'classroom_count'" :icon="sortOrder === 'desc' ? 'fluent:arrow-sort-down-24-regular' : 'fluent:arrow-sort-up-24-regular'" class="w-4 h-4" />
                  </button>
                </th>
                <th class="px-3 sm:px-6 py-3 sm:py-4 whitespace-nowrap text-left font-medium text-gray-600 dark:text-gray-300">
                  <button @click="toggleSort('teaching_days')" class="flex items-center gap-1 min-h-[44px] sm:min-h-0 w-full hover:text-gray-900 dark:hover:text-white transition-colors">
                    วันที่สอน
                    <Icon v-if="sortKey === 'teaching_days'" :icon="sortOrder === 'desc' ? 'fluent:arrow-sort-down-24-regular' : 'fluent:arrow-sort-up-24-regular'" class="w-4 h-4" />
                  </button>
                </th>
                <th class="px-3 sm:px-6 py-3 sm:py-4 whitespace-nowrap text-left font-medium text-gray-600 dark:text-gray-300">
                  <button @click="toggleSort('max_periods_per_day')" class="flex items-center gap-1 min-h-[44px] sm:min-h-0 w-full hover:text-gray-900 dark:hover:text-white transition-colors">
                    คาบมากสุด/วัน
                    <Icon v-if="sortKey === 'max_periods_per_day'" :icon="sortOrder === 'desc' ? 'fluent:arrow-sort-down-24-regular' : 'fluent:arrow-sort-up-24-regular'" class="w-4 h-4" />
                  </button>
                </th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
              <tr 
                v-for="(teacher, index) in filteredTeachers" 
                :key="teacher.teacher_id"
                :class="teacher.periods_per_week === 0 ? 'bg-orange-50 dark:bg-orange-900/10' : ''"
              >
                <td class="px-3 sm:px-6 py-3 sm:py-4 whitespace-nowrap text-gray-500">{{ index + 1 }}</td>
                <td class="px-3 sm:px-6 py-3 sm:py-4 min-w-[10rem]">
                  <div class="flex items-center gap-3">
                    <!-- ไม่ใช้บริการสร้างรูปภายนอก (ui-avatars) — จะส่งชื่อครูออกไปนอกระบบ -->
                    <img v-if="teacher.avatar" :src="teacher.avatar" alt="" class="w-8 h-8 rounded-full object-cover flex-shrink-0" />
                    <div v-else class="w-8 h-8 rounded-full flex-shrink-0 inline-flex items-center justify-center bg-primary-100 text-primary-700 dark:bg-primary-900/40 dark:text-primary-300 text-sm font-semibold">
                      {{ (teacher.name || '?').charAt(0) }}
                    </div>
                    <div class="min-w-0 flex-1">
                      <div class="font-medium text-gray-900 dark:text-white break-words">{{ teacher.name }}</div>
                      <div v-if="!teacher.is_member_teacher" class="text-[11px] bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300 px-1.5 py-0.5 rounded mt-0.5 inline-block">ไม่ใช่ครูในทะเบียน</div>
                    </div>
                  </div>
                </td>
                <td class="px-3 sm:px-6 py-3 sm:py-4 whitespace-nowrap" :class="teacher.periods_per_week === 0 ? 'text-orange-600 font-medium' : 'text-gray-900 dark:text-white'">
                  {{ teacher.periods_per_week }}
                </td>
                <td class="px-3 sm:px-6 py-3 sm:py-4 whitespace-nowrap text-gray-900 dark:text-white">
                  {{ Number(teacher.hours_per_week).toFixed(1) }}
                </td>
                <td class="px-3 sm:px-6 py-3 sm:py-4 whitespace-nowrap text-gray-900 dark:text-white">
                  {{ teacher.course_count }}
                </td>
                <td class="px-3 sm:px-6 py-3 sm:py-4 whitespace-nowrap text-gray-900 dark:text-white">
                  {{ teacher.classroom_count }}
                </td>
                <td class="px-3 sm:px-6 py-3 sm:py-4 whitespace-nowrap text-gray-900 dark:text-white">
                  {{ teacher.teaching_days }}
                </td>
                <td class="px-3 sm:px-6 py-3 sm:py-4 whitespace-nowrap text-gray-900 dark:text-white">
                  {{ teacher.max_periods_per_day }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>
