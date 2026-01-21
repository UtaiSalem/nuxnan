<script setup lang="ts">
import { Icon } from '@iconify/vue'
import { useApi } from '~/composables/useApi'
import ProgressCard from '~/components/learn/course/ProgressCard.vue'
import ProgressCardSkeleton from '~/components/learn/course/ProgressCardSkeleton.vue'

interface Props {
  courseId: string | number
  isCourseAdmin?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  isCourseAdmin: false
})

const api = useApi()

// State
const members = ref<any[]>([])
const loading = ref(true)
const searchQuery = ref('')
const sortBy = ref<'name' | 'progress' | 'last_activity'>('progress')
const sortOrder = ref<'asc' | 'desc'>('desc')
const showDetailsModal = ref(false)
const selectedMember = ref<any>(null)
const memberDetails = ref<any>(null)
const groups = ref<any[]>([])
const activeTab = ref<string | number>('all')
const viewMode = ref<'grid' | 'table'>('grid')

// Pagination State
const pagination = ref({
  current_page: 1,
  last_page: 1,
  total: 0,
  per_page: 20,
  from: 0,
  to: 0
})

const stats = ref({
  avg: 0,
  min: 0,
  max: 0,
  completed: 0,
  total: 0
})

// Fetch progress data
const fetchProgress = async (page = 1) => {
  loading.value = true
  try {
    const params: any = {
      page,
      per_page: pagination.value.per_page,
      sort: sortBy.value,
      order: sortOrder.value
    }
    
    if (activeTab.value !== 'all') {
      params.group_id = activeTab.value
    }
    
    if (searchQuery.value) {
      params.search = searchQuery.value
    }

    const response: any = await api.get(`/api/courses/${props.courseId}/progress`, { params })
    
    // Update Members
    if (response.courseMembersProgress) {
        members.value = response.courseMembersProgress.map((item: any) => ({
        // Include root-level properties (lessons_completed, overall_progress, etc.)
        lessons_completed: item.lessons_completed,
        total_lessons: item.total_lessons,
        lessons_progress: item.lessons_progress,
        assignments_completed: item.assignments_completed,
        total_assignments: item.total_assignments,
        assignments_progress: item.assignments_progress,
        quizzes_completed: item.quizzes_completed,
        total_quizzes: item.total_quizzes,
        quizzes_progress: item.quizzes_progress,
        // Attendance data
        attendance_present: item.attendance_present,
        total_attendance: item.total_attendance,
        attendance_rate: item.attendance_rate,
        overall_progress: item.overall_progress,
        // Spread existing structures
        ...item.progress,
        ...item.member,
        scores: { 
          ...item.scores,
          original_bonus: item.scores?.bonus_points || 0,
          original_edited_grade: item.scores?.edited_grade // Store original to detect changes
        }
      }))
    } else {
        members.value = []
    }

    // Update Groups
    groups.value = response.groups || []
    
    // Update Pagination
    if (response.pagination) {
        pagination.value = response.pagination
    }
    
    // Update Stats
    if (response.stats) {
        stats.value = {
            ...stats.value,
            ...response.stats,
            // Calculate pseudo-stats if backend doesn't provide all (Backen provides total/completed)
            // Backend doesn't provide avg/min/max yet. We might display 0 or hide them.
            // Wait, existing UI shows avg/min/max. Backend implementation didn't calculate them.
            // I should hide them or mock them for now until backend supports them?
            // Actually, backend controller lines 411+ added 'stats' => [ 'total', 'completed' ].
            // It did NOT add 'avg', 'min', 'max'.
            // I'll set them to 0 or '-' to avoid errors.
        }
    }
    
  } catch (error) {
    console.error('Error fetching progress:', error)
  } finally {
    loading.value = false
  }
}

// Watchers
watch(activeTab, () => {
    fetchProgress(1)
})

let searchTimeout: any
watch(searchQuery, (newVal) => {
    clearTimeout(searchTimeout)
    searchTimeout = setTimeout(() => {
        fetchProgress(1)
    }, 500)
})

const changePage = (page: number) => {
    if (page >= 1 && page <= pagination.value.last_page) {
        fetchProgress(page)
    }
}

// View member details
const viewMemberDetails = async (member: any) => {
  selectedMember.value = member
  showDetailsModal.value = true
  
  try {
    const response = await api.get(`/api/courses/${props.courseId}/members/${member.id}/progress`)
    memberDetails.value = response
  } catch (error) {
    console.error('Error fetching member details:', error)
    memberDetails.value = null
  }
}

// Export progress
const exportProgress = async () => {
  try {
    const config = useRuntimeConfig()
    const authStore = useAuthStore()
    const apiBase = config.public.apiBase as string
    
    // Build URL with group filter
    let exportUrl = `${apiBase}/api/courses/${props.courseId}/export/results`
    if (activeTab.value !== 'all') {
      exportUrl += `?group_id=${activeTab.value}`
    }
    
    // Use native fetch for blob download (not $fetch which doesn't handle blob well)
    const response = await fetch(exportUrl, {
      method: 'GET',
      headers: {
        'Authorization': `Bearer ${authStore.token}`,
      }
    })
    
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`)
    }
    
    // Generate filename based on active group
    let filename = `course-progress-${props.courseId}`
    if (activeTab.value === 'all') {
      filename += '-ทั้งหมด'
    } else if (activeTab.value === 'ungrouped') {
      filename += '-ไม่มีกลุ่ม'
    } else {
      const selectedGroup = groups.value.find(g => g.id === activeTab.value)
      if (selectedGroup) {
        filename += `-${selectedGroup.name}`
      }
    }
    filename += '.xlsx'
    
    const blob = await response.blob()
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', filename)
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
  } catch (error) {
    console.error('Error exporting progress:', error)
  }
}

// Update bonus points (allow negative for deductions)
const updateBonusPoints = async (member: any) => {
  const newValue = member.scores?.bonus_points ?? 0
  const oldValue = member.scores?.original_bonus ?? 0
  
  if (newValue === oldValue) return

  try {
    const response: any = await api.patch(`/api/courses/${props.courseId}/members/${member.id}/bonus-points`, {
      bonus_points: newValue
    })
    
    // Diff calculation
    const diff = (member.scores.bonus_points || 0) - (member.scores.original_bonus || 0)
    member.scores.total_score = (member.scores.total_score || 0) + diff
    member.scores.original_bonus = member.scores.bonus_points // Update original to new saved value
    
    // Update Grade Display immediately
    member.scores.grade_progress = response.grade_progress
    member.scores.grade_name = response.grade_name

  } catch (error) {
    console.error('Error updating bonus points:', error)
  }
}

const updateEditedGrade = async (member: any) => {
  try {
    const response: any = await api.patch(`/api/courses/${props.courseId}/members/${member.id}/edited-grade`, {
      edited_grade: member.scores.edited_grade
    })
    
    // Update local state
    member.scores.original_edited_grade = member.scores.edited_grade
    member.scores.grade_progress = response.edited_grade ?? member.scores.calculated_grade ?? response.grade_progress // Update logic? Response has edited_grade and grade_name?
    // Wait, updateEditedGrade response (Step 509) returns { edited_grade, grade_name }
    
    member.scores.edited_grade = response.edited_grade
    member.scores.grade_name = response.grade_name
    
  } catch (error) {
    console.error('Error updating edited grade:', error)
  }
}
// Toggle sort
const toggleSort = (field: 'name' | 'progress' | 'last_activity') => {
  if (sortBy.value === field) {
    sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc'
  } else {
    sortBy.value = field
    sortOrder.value = 'desc'
  }
  fetchProgress(1)
}

// Grade Distribution computed
const gradeDistribution = computed(() => {
  const distribution = { A: 0, 'B+': 0, B: 0, 'C+': 0, C: 0, 'D+': 0, D: 0, F: 0 }
  members.value.forEach((m: any) => {
    const grade = m.scores?.grade_name || 'F'
    if (distribution.hasOwnProperty(grade)) {
      distribution[grade as keyof typeof distribution]++
    }
  })
  return distribution
})

// Top Performers state (loaded from API to get all students, not just current page)
const topPerformers = ref<any[]>([])
const loadingTopPerformers = ref(false)

// Fetch Top Performers from API
const fetchTopPerformers = async () => {
  loadingTopPerformers.value = true
  try {
    const response: any = await api.get(`/api/courses/${props.courseId}/top-performers`, {
      params: { limit: 5 }
    })
    
    if (response.data || response.topPerformers) {
      topPerformers.value = response.topPerformers || response.data || []
    }
  } catch (error) {
    console.error('Error fetching top performers:', error)
    // Fallback: use current page members sorted by total_score
    topPerformers.value = [...members.value]
      .sort((a, b) => (b.scores?.total_score || 0) - (a.scores?.total_score || 0))
      .slice(0, 5)
  } finally {
    loadingTopPerformers.value = false
  }
}

// At-Risk Students (less than 50% overall progress)
const atRiskStudents = computed(() => {
  return members.value.filter((m: any) => (m.overall_progress || 0) < 50)
})

// Average progress
const averageProgress = computed(() => {
  if (members.value.length === 0) return 0
  const total = members.value.reduce((sum: number, m: any) => sum + (m.overall_progress || 0), 0)
  return Math.round(total / members.value.length)
})

// Pass rate (students with >= 50% progress)
const passRate = computed(() => {
  if (members.value.length === 0) return 0
  const passed = members.value.filter((m: any) => (m.overall_progress || 0) >= 50).length
  return Math.round((passed / members.value.length) * 100)
})

// Init
onMounted(() => {
  fetchProgress()
  fetchTopPerformers()
})
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
          ความคืบหน้าของผู้เรียน
        </h3>
        <p class="text-sm text-gray-500 dark:text-gray-400">
          ทั้งหมด {{ pagination.total }} คน
        </p>
      </div>
    </div>
    
<!-- Dashboard -->
    <div class="space-y-4" v-if="members.length > 0">
      <!-- Key Metrics -->
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <!-- Total Students -->
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-100 dark:border-gray-700">
          <div class="flex items-center gap-3">
            <div class="p-2 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
              <Icon icon="fluent:people-community-24-filled" class="w-6 h-6 text-blue-600 dark:text-blue-400" />
            </div>
            <div>
              <p class="text-sm text-gray-500 dark:text-gray-400">ผู้เรียนทั้งหมด</p>
              <div class="text-xl font-bold text-gray-900 dark:text-white">{{ stats.total }}</div>
            </div>
          </div>
        </div>

        <!-- Average Progress -->
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-100 dark:border-gray-700">
          <div class="flex items-center gap-3">
            <div class="p-2 bg-indigo-50 dark:bg-indigo-900/20 rounded-lg">
              <Icon icon="fluent:data-trending-24-filled" class="w-6 h-6 text-indigo-600 dark:text-indigo-400" />
            </div>
            <div>
              <p class="text-sm text-gray-500 dark:text-gray-400">ความคืบหน้าเฉลี่ย</p>
              <div class="text-xl font-bold text-gray-900 dark:text-white">{{ averageProgress }}%</div>
            </div>
          </div>
        </div>

        <!-- Pass Rate -->
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-100 dark:border-gray-700">
          <div class="flex items-center gap-3">
            <div class="p-2 bg-green-50 dark:bg-green-900/20 rounded-lg">
              <Icon icon="fluent:checkmark-starburst-24-filled" class="w-6 h-6 text-green-600 dark:text-green-400" />
            </div>
            <div>
              <p class="text-sm text-gray-500 dark:text-gray-400">ผ่านเกณฑ์ (>50%)</p>
              <div class="text-xl font-bold text-gray-900 dark:text-white">{{ passRate }}%</div>
            </div>
          </div>
        </div>

        <!-- Completed -->
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-100 dark:border-gray-700">
          <div class="flex items-center gap-3">
            <div class="p-2 bg-teal-50 dark:bg-teal-900/20 rounded-lg">
              <Icon icon="fluent:trophy-24-filled" class="w-6 h-6 text-teal-600 dark:text-teal-400" />
            </div>
            <div>
              <p class="text-sm text-gray-500 dark:text-gray-400">เรียนจบแล้ว</p>
              <div class="text-xl font-bold text-gray-900 dark:text-white">{{ stats.completed }}</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Detail Charts Row -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <!-- Grade Distribution -->
        <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-xl p-5 shadow-sm border border-gray-100 dark:border-gray-700">
          <h4 class="font-semibold text-gray-800 dark:text-gray-200 mb-6 flex items-center gap-2">
            <Icon icon="fluent:chart-person-24-regular" class="w-5 h-5" />
            การกระจายเกรด (Grade Distribution)
          </h4>
          <div class="flex items-end justify-between h-40 gap-2 px-2">
             <div v-for="(count, grade) in gradeDistribution" :key="grade" class="flex-1 flex flex-col items-center justify-end h-full gap-2 group relative">
                <!-- Tooltip -->
                 <div class="absolute -top-8 bg-gray-900 text-white text-xs px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition-opacity">
                    {{ count }} คน
                 </div>
                <!-- Bar -->
                <div class="w-full max-w-[40px] bg-blue-100 dark:bg-blue-900/30 rounded-t-md relative overflow-hidden transition-all duration-500 hover:bg-blue-200 dark:hover:bg-blue-800/50"
                     :style="{ height: `${Math.max((count / members.length) * 100, 4)}%` }">
                     <!-- Fill animation could go here -->
                     <div v-if="count > 0" class="absolute bottom-0 left-0 right-0 bg-blue-500/20 h-full w-full"></div>
                </div>
                <!-- Label -->
                <div class="text-sm font-medium text-gray-600 dark:text-gray-400">{{ grade }}</div>
             </div>
          </div>
        </div>

        <!-- Top Performers -->
        <div class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow-sm border border-gray-100 dark:border-gray-700 flex flex-col">
          <h4 class="font-semibold text-gray-800 dark:text-gray-200 mb-4 flex items-center gap-2">
            <Icon icon="fluent:ribbon-star-24-filled" class="w-5 h-5 text-yellow-500" />
            Top Performers
          </h4>
          <div class="flex-1 overflow-y-auto space-y-3 pr-2 custom-scrollbar">
             <div v-for="(student, index) in topPerformers" :key="student.id" 
                  class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors cursor-pointer"
                  @click="viewMemberDetails(student)">
                <div class="relative">
                    <img :src="student.user?.avatar || '/images/default-avatar.png'" class="w-10 h-10 rounded-full object-cover border border-gray-200">
                    <div v-if="index < 3" class="absolute -top-1 -right-1 w-5 h-5 bg-yellow-400 rounded-full flex items-center justify-center text-[10px] font-bold text-white shadow-sm border border-white">
                        {{ index + 1 }}
                    </div>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ student.user?.name }}</p>
                    <p class="text-xs text-gray-500 truncate">{{ student.scores?.grade_name || '-' }} • {{ student.overall_progress || 0 }}%</p>
                </div>
                <div class="text-sm font-bold text-green-600">{{ student.scores?.total_score || 0 }}</div>
             </div>
          </div>
        </div>
      </div>

      <!-- At Risk Alert -->
      <div v-if="atRiskStudents.length > 0" class="bg-red-50 dark:bg-red-900/10 border border-red-100 dark:border-red-800/30 rounded-xl p-4 flex items-start gap-4">
          <div class="p-2 bg-red-100 dark:bg-red-900/30 rounded-full shrink-0">
              <Icon icon="fluent:alert-24-filled" class="w-6 h-6 text-red-600 dark:text-red-400" />
          </div>
          <div class="flex-1">
              <h4 class="font-bold text-red-700 dark:text-red-400 text-sm">ต้องการความช่วยเหลือ ({{ atRiskStudents.length }} คน)</h4>
              <p class="text-xs text-red-600/80 dark:text-red-400/80 mt-1 mb-2">นักเรียนกลุ่มนี้มีความคืบหน้าน้อยกว่า 50% หรือคะแนนต่ำกว่าเกณฑ์</p>
              <div class="flex flex-wrap gap-2">
                  <div v-for="student in atRiskStudents.slice(0, 5)" :key="student.id" 
                       class="flex items-center gap-1.5 bg-white dark:bg-gray-800 px-2 py-1 rounded-md border border-red-100 dark:border-red-900/30 shadow-sm cursor-pointer hover:shadow-md transition-shadow"
                       @click="viewMemberDetails(student)">
                      <img :src="student.user?.avatar || '/images/default-avatar.png'" class="w-5 h-5 rounded-full">
                      <span class="text-xs font-medium text-gray-700 dark:text-gray-300">{{ student.user?.name }}</span>
                      <span class="text-xs text-red-500 font-bold">({{ student.overall_progress }}%)</span>
                  </div>
                  <div v-if="atRiskStudents.length > 5" class="px-2 py-1 text-xs text-red-600 font-medium">
                      + อีก {{ atRiskStudents.length - 5 }} คน
                  </div>
              </div>
          </div>
      </div>
    </div>
    
    <!-- Group Tabs -->
    <div v-if="isCourseAdmin && (groups.length > 0 || members.some(m => !m.group_id))" class="mb-4">
        <ul class="flex flex-wrap items-center border-b bg-gray-50 dark:bg-gray-800 rounded-t-lg">
             <!-- All -->
             <li class="mr-1">
                <button @click="activeTab = 'all'" :class="['px-4 py-2 text-sm font-medium rounded-t-lg', activeTab === 'all' ? 'bg-white dark:bg-gray-900 text-blue-600 border-t border-l border-r border-gray-200 dark:border-gray-700' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400']">
                    ทั้งหมด ({{ members.length }})
                </button>
             </li>
             <!-- Groups -->
             <li v-for="group in groups" :key="group.id" class="mr-1">
                <button @click="activeTab = group.id" :class="['px-4 py-2 text-sm font-medium rounded-t-lg', activeTab === group.id ? 'bg-white dark:bg-gray-900 text-blue-600 border-t border-l border-r border-gray-200 dark:border-gray-700' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400']">
                    {{ group.name }}
                </button>
             </li>
             <!-- No Group -->
             <li v-if="members.some(m => !m.group_id)" class="mr-1">
                <button @click="activeTab = 'ungrouped'" :class="['px-4 py-2 text-sm font-medium rounded-t-lg', activeTab === 'ungrouped' ? 'bg-white dark:bg-gray-900 text-blue-600 border-t border-l border-r border-gray-200 dark:border-gray-700' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400']">
                    ไม่มีกลุ่ม
                </button>
             </li>
        </ul>
    </div>

    <!-- Filters -->
    <div class="flex flex-col sm:flex-row gap-3">
      <!-- Search -->
      <div class="relative flex-1">
        <Icon 
          icon="fluent:search-24-regular" 
          class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400"
        />
        <input
          v-model="searchQuery"
          type="text"
          placeholder="ค้นหาผู้เรียน..."
          class="w-full pl-10 pr-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
        />
      </div>
      
      <div class="flex gap-2">
        <!-- View Mode -->
        <div class="flex bg-gray-100 dark:bg-gray-700 rounded-lg p-1 mr-2">
            <button @click="viewMode = 'grid'" :class="['p-1.5 rounded-md transition-colors', viewMode === 'grid' ? 'bg-white dark:bg-gray-600 shadow text-blue-600' : 'text-gray-500 hover:text-gray-700']">
                <Icon icon="fluent:grid-24-regular" class="w-5 h-5" />
            </button>
            <button @click="viewMode = 'table'" :class="['p-1.5 rounded-md transition-colors', viewMode === 'table' ? 'bg-white dark:bg-gray-600 shadow text-blue-600' : 'text-gray-500 hover:text-gray-700']">
                <Icon icon="fluent:table-24-regular" class="w-5 h-5" />
            </button>
        </div>

        <button
          @click="toggleSort('name')"
          :class="[
            'px-3 py-2 rounded-lg text-sm font-medium flex items-center gap-1 transition-colors',
            sortBy === 'name' 
              ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400' 
              : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-600'
          ]"
        >
          ชื่อ
          <Icon 
            v-if="sortBy === 'name'"
            :icon="sortOrder === 'asc' ? 'fluent:arrow-up-24-regular' : 'fluent:arrow-down-24-regular'" 
            class="w-4 h-4"
          />
        </button>
        
        <button
          @click="toggleSort('progress')"
          :class="[
            'px-3 py-2 rounded-lg text-sm font-medium flex items-center gap-1 transition-colors',
            sortBy === 'progress' 
              ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400' 
              : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-600'
          ]"
        >
          ความคืบหน้า
          <Icon 
            v-if="sortBy === 'progress'"
            :icon="sortOrder === 'asc' ? 'fluent:arrow-up-24-regular' : 'fluent:arrow-down-24-regular'" 
            class="w-4 h-4"
          />
        </button>
        
        <button
          @click="toggleSort('last_activity')"
          :class="[
            'px-3 py-2 rounded-lg text-sm font-medium flex items-center gap-1 transition-colors',
            sortBy === 'last_activity' 
              ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400' 
              : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-600'
          ]"
        >
          ล่าสุด
          <Icon 
            v-if="sortBy === 'last_activity'"
            :icon="sortOrder === 'asc' ? 'fluent:arrow-up-24-regular' : 'fluent:arrow-down-24-regular'" 
            class="w-4 h-4"
          />
        </button>

        <!-- Export Button -->
        <button
          v-if="isCourseAdmin"
          @click="exportProgress"
          class="inline-flex items-center gap-2 px-3 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors text-sm font-medium"
        >
          <Icon icon="fluent:arrow-download-24-regular" class="w-4 h-4" />
          <span>ส่งออก Excel</span>
        </button>
      </div>
    </div>
    
    <!-- Loading Skeleton -->
    <div v-if="loading" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
      <ProgressCardSkeleton v-for="i in 6" :key="i" />
    </div>
    
    <!-- Progress Grid -->
    <div v-else-if="members.length > 0 && viewMode === 'grid'" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
      <ProgressCard
        v-for="member in members"
        :key="member.id"
        :member="member"
        :is-course-admin="isCourseAdmin"
        @view-details="viewMemberDetails"
      />
    </div>

    <!-- Progress Table -->
    <div v-else-if="members.length > 0 && viewMode === 'table'" class="overflow-x-auto bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
        <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-300">
                <tr>
                    <th scope="col" class="px-4 py-3 min-w-[50px]">#</th>
                    <th scope="col" class="px-4 py-3 min-w-[200px]">สมาชิก</th>
                    <th scope="col" class="px-4 py-3 text-center">บทเรียน</th>
                    <th scope="col" class="px-4 py-3 text-center">งาน</th>
                    <th scope="col" class="px-4 py-3 text-center">ทดสอบ</th>
                    <th scope="col" class="px-6 py-3 min-w-[120px]">
                    พิเศษ
                </th>
                <th scope="col" class="px-6 py-3 whitespace-nowrap">
                    รวม
                </th>
                    <th scope="col" class="px-4 py-3 text-center">เกรด</th>
                    <th scope="col" class="px-4 py-3 text-center min-w-[120px]">เกรดแก้</th>
                    <th scope="col" class="px-4 py-3 text-center">สถานะ</th>
                    <th scope="col" class="px-4 py-3 text-center">จัดการ</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="(member, index) in members" :key="member.id" class="border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50">
                    <td class="px-4 py-3">{{ index + 1 }}</td>
                    <td class="px-4 py-3 font-medium text-gray-900 dark:text-white whitespace-nowrap">
                        <div class="flex items-center gap-3">
                            <img :src="member.user?.avatar || 'https://ui-avatars.com/api/?name='+ member.user?.name" class="w-8 h-8 rounded-full" alt="">
                            <div class="flex flex-col">
                                <span>{{ member.user?.name }}</span>
                                <span class="text-xs text-gray-400">{{ member.member_code || '-' }}</span>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-3 text-center">
                        <div class="flex flex-col items-center">
                            <span class="text-xs text-gray-400">งาน: {{ member.scores?.lesson_assignments || 0 }}</span>
                            <span class="text-xs text-gray-400">สอบ: {{ member.scores?.lesson_quizzes || 0 }}</span>
                        </div>
                    </td>
                    <td class="px-4 py-3 text-center">{{ member.scores?.course_assignments || 0 }}</td>
                    <td class="px-4 py-3 text-center">{{ member.scores?.course_quizzes || 0 }}</td>
                    <td class="px-6 py-4">
                    <div v-if="isCourseAdmin" class="flex items-center gap-1">
                        <input 
                            type="number" 
                            v-model.number="member.scores.bonus_points"
                            class="w-16 px-2 py-1 text-sm border rounded focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                        >
                        <button 
                            v-if="member.scores.bonus_points !== member.scores.original_bonus"
                            @click="updateBonusPoints(member)"
                            class="p-1 text-green-600 hover:bg-green-100 rounded dark:hover:bg-green-900/30"
                            title="บันทึก"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
                        </button>
                    </div>
                    <span v-else class="text-orange-600 font-medium">{{ member.scores?.bonus_points || 0 }}</span>
                </td>
                    <td class="px-4 py-3 text-center font-bold text-blue-700 bg-blue-50 dark:bg-blue-900/20 dark:text-blue-400">
                        {{ member.scores?.total_score || 0 }}
                    </td>
                    <td class="px-4 py-3 text-center font-bold text-lg" 
                        :class="{'text-green-600 dark:text-green-400': (member.scores?.grade_progress || 0) >= 2, 'text-red-600 dark:text-red-400': (member.scores?.grade_progress || 0) < 1}">
                        {{ member.scores?.grade_name || '-' }} 
                        <span class="text-xs text-gray-400 font-normal">({{ member.scores?.grade_progress || 0 }})</span>
                    </td>
                    <!-- เกรดแก้ Column (After Grade) -->
                    <td class="px-4 py-3 text-center">
                        <div v-if="isCourseAdmin" class="flex items-center justify-center gap-1">
                            <select 
                                v-model.number="member.scores.edited_grade"
                                class="w-20 px-2 py-1 text-sm border rounded focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                            >
                                <option :value="null">-</option>
                                <option :value="1">1 (D)</option>
                                <option :value="1.5">1.5 (D+)</option>
                                <option :value="2">2 (C)</option>
                                <option :value="2.5">2.5 (C+)</option>
                                <option :value="3">3 (B)</option>
                                <option :value="3.5">3.5 (B+)</option>
                                <option :value="4">4 (A)</option>
                            </select>
                            <button 
                                v-if="member.scores.edited_grade !== member.scores.original_edited_grade"
                                @click="updateEditedGrade(member)"
                                class="p-1 text-blue-600 hover:bg-blue-100 rounded dark:hover:bg-blue-900/30"
                                title="บันทึกเกรดแก้"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
                            </button>
                        </div>
                        <span v-else class="font-medium">{{ member.scores?.edited_grade || '-' }}</span>
                    </td>
                    <td class="px-4 py-3 text-center">
                         <span v-if="member.course_member_status === 1" class="bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded dark:bg-green-900 dark:text-green-300">ปกติ</span>
                         <span v-else class="bg-red-100 text-red-800 text-xs font-medium px-2.5 py-0.5 rounded dark:bg-red-900 dark:text-red-300">พัก</span>
                    </td>
                    <td class="px-4 py-3 text-center">
                        <button @click="viewMemberDetails(member)" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 font-medium text-xs">
                            รายละเอียด
                        </button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div v-if="members.length > 0" class="flex flex-col sm:flex-row items-center justify-between gap-4 mt-6">
        <span class="text-sm text-gray-500 dark:text-gray-400">
            แสดง {{ pagination.from }}-{{ pagination.to }} จากทั้งหมด {{ pagination.total }} รายการ
        </span>
        <div class="flex gap-2">
            <button 
                @click="changePage(pagination.current_page - 1)" 
                :disabled="pagination.current_page === 1"
                class="px-3 py-1 text-sm border rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed dark:border-gray-600 dark:hover:bg-gray-700"
            >
                ก่อนหน้า
            </button>
            <div class="flex items-center gap-1">
                <template v-for="page in pagination.last_page" :key="page">
                    <button 
                        v-if="Math.abs(page - pagination.current_page) <= 2 || page === 1 || page === pagination.last_page"
                        @click="changePage(page)"
                        :class="['px-3 py-1 text-sm border rounded-lg', page === pagination.current_page ? 'bg-blue-600 text-white border-blue-600' : 'hover:bg-gray-50 dark:border-gray-600 dark:hover:bg-gray-700']"
                    >
                        {{ page }}
                    </button>
                    <span v-else-if="Math.abs(page - pagination.current_page) === 3" class="px-2">...</span>
                </template>
            </div>
            <button 
                @click="changePage(pagination.current_page + 1)" 
                :disabled="pagination.current_page === pagination.last_page"
                class="px-3 py-1 text-sm border rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed dark:border-gray-600 dark:hover:bg-gray-700"
            >
                ถัดไป
            </button>
        </div>
    </div>
    
    <!-- Empty State -->
    <div v-else class="text-center py-12 bg-white dark:bg-gray-800 rounded-xl">
      <Icon icon="fluent:data-area-24-regular" class="w-16 h-16 mx-auto text-gray-300 dark:text-gray-600" />
      <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">
        {{ searchQuery ? 'ไม่พบผู้เรียน' : 'ยังไม่มีข้อมูลความคืบหน้า' }}
      </h3>
      <p class="mt-2 text-gray-500 dark:text-gray-400">
        {{ searchQuery ? 'ลองค้นหาด้วยคำอื่น' : 'ยังไม่มีผู้เรียนในคอร์สนี้' }}
      </p>
    </div>
    
    <!-- Member Details Modal -->
    <DialogModal :show="showDetailsModal" @close="showDetailsModal = false" max-width="2xl">
      <template #title>
        รายละเอียดความคืบหน้า - {{ selectedMember?.user?.name }}
      </template>
      
      <template #content>
        <div v-if="memberDetails" class="space-y-6 max-h-[70vh] overflow-y-auto pr-2">
          
          <!-- Summary Stats Section -->
          <div class="grid grid-cols-2 md:grid-cols-4 gap-4 p-4 bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-gray-800 dark:to-gray-700 rounded-xl">
            <!-- Overall Progress -->
            <div class="text-center">
              <div class="relative w-16 h-16 mx-auto">
                <svg class="w-full h-full transform -rotate-90" viewBox="0 0 36 36">
                  <circle cx="18" cy="18" r="15.5" fill="none" stroke="currentColor" stroke-width="3" class="text-gray-200 dark:text-gray-600" />
                  <circle cx="18" cy="18" r="15.5" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"
                    class="text-blue-500"
                    :stroke-dasharray="`${selectedMember?.overall_progress || 0} 100`"
                  />
                </svg>
                <div class="absolute inset-0 flex items-center justify-center">
                  <span class="text-lg font-bold text-blue-600 dark:text-blue-400">{{ selectedMember?.overall_progress || 0 }}%</span>
                </div>
              </div>
              <p class="text-xs text-gray-500 mt-2">ความคืบหน้า</p>
            </div>
            
            <!-- Grade -->
            <div class="text-center">
              <div class="w-16 h-16 mx-auto rounded-full bg-white dark:bg-gray-700 shadow flex items-center justify-center">
                <span class="text-2xl font-bold text-green-600 dark:text-green-400">{{ selectedMember?.scores?.grade_name || '-' }}</span>
              </div>
              <p class="text-xs text-gray-500 mt-2">เกรด ({{ selectedMember?.scores?.grade_progress || 0 }})</p>
            </div>
            
            <!-- Attendance -->
            <div class="text-center">
              <div class="w-16 h-16 mx-auto rounded-full bg-white dark:bg-gray-700 shadow flex items-center justify-center">
                <span class="text-xl font-bold text-orange-600 dark:text-orange-400">{{ selectedMember?.attendance_rate || 0 }}%</span>
              </div>
              <p class="text-xs text-gray-500 mt-2">เข้าเรียน ({{ selectedMember?.attendance_present || 0 }}/{{ selectedMember?.total_attendance || 0 }})</p>
            </div>
            
            <!-- Total Score -->
            <div class="text-center">
              <div class="w-16 h-16 mx-auto rounded-full bg-white dark:bg-gray-700 shadow flex items-center justify-center">
                <span class="text-xl font-bold text-purple-600 dark:text-purple-400">{{ selectedMember?.scores?.total_score || 0 }}</span>
              </div>
              <p class="text-xs text-gray-500 mt-2">คะแนนรวม</p>
            </div>
          </div>

          <!-- Progress Bars Summary -->
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- Lessons Progress -->
            <div class="p-4 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-100 dark:border-gray-700">
              <div class="flex items-center justify-between mb-2">
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300 flex items-center gap-2">
                  <Icon icon="fluent:book-24-regular" class="w-4 h-4 text-blue-500" />
                  บทเรียน
                </span>
                <span class="text-sm font-bold text-blue-600 dark:text-blue-400">
                  {{ selectedMember?.lessons_completed || 0 }}/{{ selectedMember?.total_lessons || 0 }}
                </span>
              </div>
              <div class="h-2 rounded-full bg-gray-100 dark:bg-gray-700 overflow-hidden">
                <div class="h-full rounded-full bg-blue-500 transition-all duration-500" :style="{ width: `${selectedMember?.lessons_progress || 0}%` }"></div>
              </div>
            </div>
            
            <!-- Assignments Progress -->
            <div class="p-4 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-100 dark:border-gray-700">
              <div class="flex items-center justify-between mb-2">
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300 flex items-center gap-2">
                  <Icon icon="fluent:document-text-24-regular" class="w-4 h-4 text-orange-500" />
                  งาน
                </span>
                <span class="text-sm font-bold text-orange-600 dark:text-orange-400">
                  {{ selectedMember?.assignments_completed || 0 }}/{{ selectedMember?.total_assignments || 0 }}
                </span>
              </div>
              <div class="h-2 rounded-full bg-gray-100 dark:bg-gray-700 overflow-hidden">
                <div class="h-full rounded-full bg-orange-500 transition-all duration-500" :style="{ width: `${selectedMember?.assignments_progress || 0}%` }"></div>
              </div>
            </div>
            
            <!-- Quizzes Progress -->
            <div class="p-4 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-100 dark:border-gray-700">
              <div class="flex items-center justify-between mb-2">
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300 flex items-center gap-2">
                  <Icon icon="fluent:quiz-new-24-regular" class="w-4 h-4 text-purple-500" />
                  ทดสอบ
                </span>
                <span class="text-sm font-bold text-purple-600 dark:text-purple-400">
                  {{ selectedMember?.quizzes_completed || 0 }}/{{ selectedMember?.total_quizzes || 0 }}
                </span>
              </div>
              <div class="h-2 rounded-full bg-gray-100 dark:bg-gray-700 overflow-hidden">
                <div class="h-full rounded-full bg-purple-500 transition-all duration-500" :style="{ width: `${selectedMember?.quizzes_progress || 0}%` }"></div>
              </div>
            </div>
          </div>

          <!-- Score Breakdown -->
          <div class="p-4 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-100 dark:border-gray-700">
            <h4 class="font-medium text-gray-900 dark:text-white flex items-center gap-2 mb-3">
              <Icon icon="fluent:chart-multiple-24-regular" class="w-5 h-5 text-blue-500" />
              รายละเอียดคะแนน
            </h4>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-center">
              <div class="p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                <div class="text-lg font-bold text-gray-900 dark:text-white">
                  {{ selectedMember?.scores?.lesson_assignments || 0 }}<span class="text-sm font-normal text-gray-500">/{{ selectedMember?.scores?.max_lesson_assignments || 0 }}</span>
                </div>
                <p class="text-xs text-gray-500">งานบทเรียน</p>
              </div>
              <div class="p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                <div class="text-lg font-bold text-gray-900 dark:text-white">
                  {{ selectedMember?.scores?.lesson_quizzes || 0 }}<span class="text-sm font-normal text-gray-500">/{{ selectedMember?.scores?.max_lesson_quizzes || 0 }}</span>
                </div>
                <p class="text-xs text-gray-500">ทดสอบบทเรียน</p>
              </div>
              <div class="p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                <div class="text-lg font-bold text-gray-900 dark:text-white">
                  {{ selectedMember?.scores?.course_assignments || 0 }}<span class="text-sm font-normal text-gray-500">/{{ selectedMember?.scores?.max_course_assignments || 0 }}</span>
                </div>
                <p class="text-xs text-gray-500">งานรายวิชา</p>
              </div>
              <div class="p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                <div class="text-lg font-bold text-gray-900 dark:text-white">
                  {{ selectedMember?.scores?.course_quizzes || 0 }}<span class="text-sm font-normal text-gray-500">/{{ selectedMember?.scores?.max_course_quizzes || 0 }}</span>
                </div>
                <p class="text-xs text-gray-500">ทดสอบรายวิชา</p>
              </div>
            </div>
            <div class="mt-3 flex items-center justify-between p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
              <span class="text-sm font-medium text-blue-700 dark:text-blue-300">คะแนนพิเศษ (Bonus)</span>
              <span class="text-lg font-bold text-blue-600 dark:text-blue-400">+{{ selectedMember?.scores?.bonus_points || 0 }}</span>
            </div>
          </div>
          
          <!-- Lessons Progress Detail -->
          <div class="p-4 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-100 dark:border-gray-700">
            <h4 class="font-medium text-gray-900 dark:text-white flex items-center gap-2 mb-3">
              <Icon icon="fluent:book-24-regular" class="w-5 h-5 text-blue-500" />
              บทเรียน
            </h4>
            <div v-if="memberDetails.lessons && memberDetails.lessons.length > 0" class="space-y-2">
              <div 
                v-for="lesson in memberDetails.lessons" 
                :key="lesson.id"
                class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg"
              >
                <span class="text-sm text-gray-700 dark:text-gray-300">{{ lesson.title }}</span>
                <span 
                  :class="[
                    'text-xs px-2 py-1 rounded-full',
                    lesson.completed 
                      ? 'bg-green-100 text-green-600 dark:bg-green-900/30 dark:text-green-400'
                      : 'bg-gray-100 text-gray-500 dark:bg-gray-600 dark:text-gray-400'
                  ]"
                >
                  {{ lesson.completed ? 'เรียนจบแล้ว' : 'ยังไม่เริ่ม' }}
                </span>
              </div>
            </div>
            <p v-else class="text-sm text-gray-500 text-center py-4">ไม่มีข้อมูลบทเรียน</p>
          </div>
          
          <!-- Assignments Progress Detail -->
          <div class="p-4 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-100 dark:border-gray-700">
            <h4 class="font-medium text-gray-900 dark:text-white flex items-center gap-2 mb-3">
              <Icon icon="fluent:document-text-24-regular" class="w-5 h-5 text-orange-500" />
              งานที่มอบหมาย
            </h4>
            <div v-if="memberDetails.assignments && memberDetails.assignments.length > 0" class="space-y-2">
              <div 
                v-for="assignment in memberDetails.assignments" 
                :key="assignment.id"
                class="flex items-center justify-between gap-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg"
              >
                <span class="text-sm text-gray-700 dark:text-gray-300 truncate flex-1 min-w-0" :title="assignment.title">{{ assignment.title }}</span>
                <div class="flex items-center gap-2">
                  <!-- Score Display (only if graded) -->
                  <span v-if="assignment.graded" class="text-sm font-bold text-green-600 dark:text-green-400">
                    {{ assignment.score }}/{{ assignment.max_score }}
                  </span>
                  
                  <!-- Status Badge -->
                  <span 
                    :class="[
                      'text-xs px-2 py-1 rounded-full whitespace-nowrap',
                      assignment.status === 'graded' 
                        ? 'bg-green-100 text-green-600 dark:bg-green-900/30 dark:text-green-400'
                        : assignment.status === 'in_review'
                          ? 'bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400'
                          : assignment.status === 'submitted'
                            ? 'bg-yellow-100 text-yellow-600 dark:bg-yellow-900/30 dark:text-yellow-400'
                            : 'bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400'
                    ]"
                  >
                    {{ 
                      assignment.status === 'graded' ? 'ตรวจแล้ว' 
                      : assignment.status === 'in_review' ? 'กำลังตรวจ'
                      : assignment.status === 'submitted' ? 'รอตรวจ'
                      : 'ยังไม่ส่ง' 
                    }}
                  </span>
                </div>
              </div>
            </div>
            <p v-else class="text-sm text-gray-500 text-center py-4">ไม่มีข้อมูลงานที่มอบหมาย</p>
          </div>
          
          <!-- Quizzes Progress Detail -->
          <div class="p-4 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-100 dark:border-gray-700">
            <h4 class="font-medium text-gray-900 dark:text-white flex items-center gap-2 mb-3">
              <Icon icon="fluent:quiz-new-24-regular" class="w-5 h-5 text-purple-500" />
              แบบทดสอบ
            </h4>
            <div v-if="memberDetails.quizzes && memberDetails.quizzes.length > 0" class="space-y-2">
              <div 
                v-for="quiz in memberDetails.quizzes" 
                :key="quiz.id"
                class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg"
              >
                <div class="flex-1">
                  <span class="text-sm text-gray-700 dark:text-gray-300">{{ quiz.title }}</span>
                  <span v-if="quiz.attempt_count > 0" class="text-xs text-gray-400 ml-2">
                    (ทำ {{ quiz.attempt_count }} ครั้ง)
                  </span>
                </div>
                <div class="flex items-center gap-2">
                  <!-- Score Display (only if completed) -->
                  <span v-if="quiz.completed" class="text-sm font-bold" :class="quiz.passed === false ? 'text-red-500' : 'text-green-600 dark:text-green-400'">
                    {{ quiz.score }}/{{ quiz.max_score }}
                  </span>
                  
                  <!-- Status Badge -->
                  <span 
                    :class="[
                      'text-xs px-2 py-1 rounded-full whitespace-nowrap',
                      quiz.completed 
                        ? (quiz.passed === false 
                          ? 'bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400'
                          : 'bg-green-100 text-green-600 dark:bg-green-900/30 dark:text-green-400')
                        : 'bg-gray-100 text-gray-500 dark:bg-gray-600 dark:text-gray-400'
                    ]"
                  >
                    {{ quiz.completed ? (quiz.passed === false ? 'ไม่ผ่าน' : 'ผ่าน') : 'ยังไม่ได้ทำ' }}
                  </span>
                </div>
              </div>
            </div>
            <p v-else class="text-sm text-gray-500 text-center py-4">ไม่มีข้อมูลแบบทดสอบ</p>
          </div>
        </div>
        
        <div v-else class="flex flex-col items-center justify-center py-12">
          <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-blue-600 mb-4"></div>
          <p class="text-gray-500">กำลังโหลดข้อมูล...</p>
        </div>
      </template>
      
      <template #footer>
        <button
          @click="showDetailsModal = false"
          class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600"
        >
          ปิด
        </button>
      </template>
    </DialogModal>
  </div>
</template>
