<script setup lang="ts">
/**
 * Course Completion - Grade Finalization
 * หน้าจัดการการปิดเกรดและออกผลการเรียน
 */
import { Icon } from '@iconify/vue'

definePageMeta({
  layout: false
})

const route = useRoute()
const api = useApi()
const courseName = computed(() => route.params.name as string)
const courseId = ref<number | null>(null)

// State
const course = ref<any>(null)
const summary = ref<any>(null)
const grades = ref<any[]>([])
const isLoading = ref(true)
const isProcessing = ref(false)

// Filters
const searchQuery = ref('')
const gradeFilter = ref<string>('all')
const statusFilter = ref<string>('all')

// Modals
const showPublishModal = ref(false)
const showFinalizeModal = ref(false)
const showEditGradeModal = ref(false)
const selectedStudent = ref<any>(null)
const editGradeForm = ref({
  score: 0,
  grade: '',
  reason: ''
})

onMounted(async () => {
  await fetchCourse()
})

const fetchCourse = async () => {
  try {
    const res: any = await api.get(`/api/courses/${courseName.value}`)
    if (res.success) {
      course.value = res.course
      courseId.value = res.course.id
      await fetchData()
    }
  } catch (err) {
    console.error('Failed to fetch course:', err)
  } finally {
    isLoading.value = false
  }
}

const fetchData = async () => {
  await Promise.all([
    fetchSummary(),
    fetchGrades()
  ])
}

const fetchSummary = async () => {
  try {
    const res: any = await api.get(`/api/courses/${courseId.value}/completion/summary`)
    if (res.success && res.data) {
      summary.value = res.data
    }
  } catch (err) {
    console.error('Failed to fetch summary:', err)
  }
}

const fetchGrades = async () => {
  try {
    const res: any = await api.get(`/api/courses/${courseId.value}/completion/preview-grades`)
    if (res.success && res.data) {
      grades.value = res.data.grades || []
    }
  } catch (err) {
    console.error('Failed to fetch grades:', err)
  }
}

// Filtered grades
const filteredGrades = computed(() => {
  let result = [...grades.value]

  // Search
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    result = result.filter((g: any) =>
      g.user?.name?.toLowerCase().includes(query) ||
      g.member_code?.toLowerCase().includes(query)
    )
  }

  // Grade filter
  if (gradeFilter.value !== 'all') {
    result = result.filter((g: any) => g.grade === gradeFilter.value)
  }

  // Status filter
  if (statusFilter.value !== 'all') {
    result = result.filter((g: any) => g.grade_status === statusFilter.value)
  }

  return result
})

// Actions
const startGrading = async () => {
  isProcessing.value = true
  try {
    const res: any = await api.post(`/api/courses/${courseId.value}/completion/start-grading`)
    if (res.success) {
      useToast().success('เริ่มกระบวนการออกเกรดแล้ว')
      await fetchData()
    }
  } catch (err) {
    console.error('Failed to start grading:', err)
    useToast().error('ไม่สามารถเริ่มกระบวนการได้')
  } finally {
    isProcessing.value = false
  }
}

const publishGrades = async () => {
  isProcessing.value = true
  try {
    const res: any = await api.post(`/api/courses/${courseId.value}/completion/publish-grades`)
    if (res.success) {
      useToast().success('ประกาศเกรดแล้ว')
      showPublishModal.value = false
      await fetchData()
    }
  } catch (err) {
    console.error('Failed to publish:', err)
    useToast().error('ไม่สามารถประกาศได้')
  } finally {
    isProcessing.value = false
  }
}

const finalizeGrades = async () => {
  isProcessing.value = true
  try {
    const res: any = await api.post(`/api/courses/${courseId.value}/completion/finalize`)
    if (res.success) {
      useToast().success('ปิดเกรดเรียบร้อยแล้ว')
      showFinalizeModal.value = false
      await fetchData()
    }
  } catch (err) {
    console.error('Failed to finalize:', err)
    useToast().error('ไม่สามารถปิดเกรดได้')
  } finally {
    isProcessing.value = false
  }
}

const openEditGrade = (student: any) => {
  selectedStudent.value = student
  editGradeForm.value = {
    score: student.total_score || 0,
    grade: student.grade || '',
    reason: ''
  }
  showEditGradeModal.value = true
}

const saveGradeEdit = async () => {
  if (!selectedStudent.value || !editGradeForm.value.reason) return
  
  isProcessing.value = true
  try {
    const res: any = await api.patch(
      `/api/courses/${courseId.value}/completion/members/${selectedStudent.value.member_id}/grade`,
      editGradeForm.value
    )
    if (res.success) {
      useToast().success('บันทึกการแก้ไขแล้ว')
      showEditGradeModal.value = false
      await fetchGrades()
    }
  } catch (err) {
    console.error('Failed to save:', err)
    useToast().error('ไม่สามารถบันทึกได้')
  } finally {
    isProcessing.value = false
  }
}

// Status helpers
const getFinalizationStatusLabel = (status: string) => {
  switch (status) {
    case 'active': return 'เปิดสอน'
    case 'grading': return 'กำลังออกเกรด'
    case 'published': return 'ประกาศแล้ว'
    case 'finalized': return 'ปิดเกรดแล้ว'
    case 'archived': return 'เก็บถาวร'
    default: return status
  }
}

const getFinalizationStatusColor = (status: string) => {
  switch (status) {
    case 'active': return 'bg-green-100 text-green-800'
    case 'grading': return 'bg-yellow-100 text-yellow-800'
    case 'published': return 'bg-blue-100 text-blue-800'
    case 'finalized': return 'bg-purple-100 text-purple-800'
    case 'archived': return 'bg-gray-100 text-gray-800'
    default: return 'bg-gray-100 text-gray-800'
  }
}

const getGradeColor = (grade: string) => {
  if (['A', 'B+', 'B'].includes(grade)) return 'text-green-600 dark:text-green-400'
  if (['C+', 'C'].includes(grade)) return 'text-yellow-600 dark:text-yellow-400'
  if (['D+', 'D'].includes(grade)) return 'text-orange-600 dark:text-orange-400'
  if (grade === 'F') return 'text-red-600 dark:text-red-400'
  return 'text-gray-600 dark:text-gray-400'
}

const gradeOptions = ['A', 'B+', 'B', 'C+', 'C', 'D+', 'D', 'F']
</script>

<template>
  <NuxtLayout name="course">
    <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
      <!-- Header -->
      <div class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-6">
          <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
              <div class="flex items-center gap-3">
                <h1 class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white">
                  การปิดเกรด
                </h1>
                <span 
                  v-if="summary?.finalization_status"
                  :class="getFinalizationStatusColor(summary.finalization_status)"
                  class="px-2.5 py-0.5 rounded-full text-xs font-medium"
                >
                  {{ getFinalizationStatusLabel(summary.finalization_status) }}
                </span>
              </div>
              <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                จัดการการออกเกรดและปิดผลการเรียน
              </p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
              <NuxtLink
                :to="`/courses/${courseName}/gradebook`"
                class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50"
              >
                <Icon icon="heroicons:arrow-left" class="w-4 h-4 mr-2" />
                <span class="hidden sm:inline">สมุดคะแนน</span>
                <span class="sm:hidden">กลับ</span>
              </NuxtLink>
              
              <!-- Action buttons based on status -->
              <template v-if="summary?.finalization_status === 'active'">
                <button
                  @click="startGrading"
                  :disabled="isProcessing"
                  class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700 disabled:opacity-50"
                >
                  <Icon icon="heroicons:calculator" class="w-4 h-4 mr-2" />
                  เริ่มออกเกรด
                </button>
              </template>

              <template v-else-if="summary?.finalization_status === 'grading'">
                <button
                  @click="showPublishModal = true"
                  :disabled="isProcessing"
                  class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 disabled:opacity-50"
                >
                  <Icon icon="heroicons:megaphone" class="w-4 h-4 mr-2" />
                  ประกาศเกรด
                </button>
              </template>

              <template v-else-if="summary?.finalization_status === 'published'">
                <button
                  @click="showFinalizeModal = true"
                  :disabled="isProcessing || summary?.pending_appeals > 0"
                  class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-purple-600 rounded-lg hover:bg-purple-700 disabled:opacity-50"
                >
                  <Icon icon="heroicons:lock-closed" class="w-4 h-4 mr-2" />
                  ปิดเกรด
                </button>
              </template>
            </div>
          </div>
        </div>
      </div>

      <!-- Content -->
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-6">
        <!-- Loading -->
        <div v-if="isLoading" class="flex justify-center py-12">
          <Icon icon="heroicons:arrow-path" class="w-8 h-8 animate-spin text-primary-600" />
        </div>

        <template v-else>
          <!-- Statistics Cards -->
          <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mb-6">
            <div class="bg-white dark:bg-gray-800 rounded-xl p-4 sm:p-5 border border-gray-200 dark:border-gray-700">
              <div class="flex items-center gap-3">
                <div class="p-2 sm:p-3 bg-gray-100 dark:bg-gray-700 rounded-lg">
                  <Icon icon="heroicons:users" class="w-5 h-5 sm:w-6 sm:h-6 text-gray-600 dark:text-gray-300" />
                </div>
                <div>
                  <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400">นักเรียน</p>
                  <p class="text-lg sm:text-2xl font-bold text-gray-900 dark:text-white">
                    {{ summary?.statistics?.total || 0 }}
                  </p>
                </div>
              </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-xl p-4 sm:p-5 border border-gray-200 dark:border-gray-700">
              <div class="flex items-center gap-3">
                <div class="p-2 sm:p-3 bg-green-100 dark:bg-green-900 rounded-lg">
                  <Icon icon="heroicons:check-circle" class="w-5 h-5 sm:w-6 sm:h-6 text-green-600 dark:text-green-400" />
                </div>
                <div>
                  <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400">ผ่าน</p>
                  <p class="text-lg sm:text-2xl font-bold text-green-600 dark:text-green-400">
                    {{ summary?.statistics?.passed || 0 }}
                  </p>
                </div>
              </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-xl p-4 sm:p-5 border border-gray-200 dark:border-gray-700">
              <div class="flex items-center gap-3">
                <div class="p-2 sm:p-3 bg-red-100 dark:bg-red-900 rounded-lg">
                  <Icon icon="heroicons:x-circle" class="w-5 h-5 sm:w-6 sm:h-6 text-red-600 dark:text-red-400" />
                </div>
                <div>
                  <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400">ไม่ผ่าน</p>
                  <p class="text-lg sm:text-2xl font-bold text-red-600 dark:text-red-400">
                    {{ summary?.statistics?.failed || 0 }}
                  </p>
                </div>
              </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-xl p-4 sm:p-5 border border-gray-200 dark:border-gray-700">
              <div class="flex items-center gap-3">
                <div class="p-2 sm:p-3 bg-blue-100 dark:bg-blue-900 rounded-lg">
                  <Icon icon="heroicons:chart-bar" class="w-5 h-5 sm:w-6 sm:h-6 text-blue-600 dark:text-blue-400" />
                </div>
                <div>
                  <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400">GPA เฉลี่ย</p>
                  <p class="text-lg sm:text-2xl font-bold text-blue-600 dark:text-blue-400">
                    {{ (summary?.statistics?.average_gpa || 0).toFixed(2) }}
                  </p>
                </div>
              </div>
            </div>
          </div>

          <!-- Alert: Pending Appeals -->
          <div 
            v-if="summary?.pending_appeals > 0"
            class="mb-6 p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-xl"
          >
            <div class="flex items-start gap-3">
              <Icon icon="heroicons:exclamation-triangle" class="w-6 h-6 text-yellow-600 dark:text-yellow-400 flex-shrink-0" />
              <div>
                <p class="font-medium text-yellow-800 dark:text-yellow-200">
                  มีคำร้องอุทธรณ์ที่รอดำเนินการ {{ summary.pending_appeals }} รายการ
                </p>
                <p class="mt-1 text-sm text-yellow-700 dark:text-yellow-300">
                  ต้องพิจารณาคำร้องทั้งหมดก่อนปิดเกรด
                </p>
                <NuxtLink 
                  :to="`/courses/${courseName}/gradebook/appeals`"
                  class="mt-2 inline-flex items-center text-sm font-medium text-yellow-700 dark:text-yellow-300 hover:underline"
                >
                  ดูคำร้องอุทธรณ์
                  <Icon icon="heroicons:arrow-right" class="w-4 h-4 ml-1" />
                </NuxtLink>
              </div>
            </div>
          </div>

          <!-- Filters -->
          <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 mb-6">
            <div class="flex flex-col sm:flex-row gap-3 sm:gap-4">
              <div class="flex-1">
                <div class="relative">
                  <Icon icon="heroicons:magnifying-glass" class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
                  <input
                    v-model="searchQuery"
                    type="text"
                    placeholder="ค้นหานักเรียน..."
                    class="w-full pl-10 pr-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                  />
                </div>
              </div>
              <div class="flex gap-3">
                <select
                  v-model="gradeFilter"
                  class="px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                >
                  <option value="all">ทุกเกรด</option>
                  <option v-for="g in gradeOptions" :key="g" :value="g">{{ g }}</option>
                </select>
              </div>
            </div>
          </div>

          <!-- Grades Table -->
          <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
            <!-- Desktop Table -->
            <div class="hidden md:block overflow-x-auto">
              <table class="w-full">
                <thead class="bg-gray-50 dark:bg-gray-700">
                  <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">นักเรียน</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">คะแนนรวม</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">เกรด</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">GPA</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">สถานะ</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">จัดการ</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-600">
                  <tr v-for="student in filteredGrades" :key="student.member_id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                    <td class="px-6 py-4 whitespace-nowrap">
                      <div class="flex items-center">
                        <img :src="student.user?.avatar || '/images/default-avatar.png'" class="w-10 h-10 rounded-full object-cover" />
                        <div class="ml-4">
                          <div class="text-sm font-medium text-gray-900 dark:text-white">{{ student.user?.name }}</div>
                          <div class="text-sm text-gray-500 dark:text-gray-400">{{ student.member_code || '-' }}</div>
                        </div>
                      </div>
                    </td>
                    <td class="px-6 py-4 text-center text-sm text-gray-900 dark:text-white font-medium">
                      {{ (student.total_score || 0).toFixed(1) }}
                    </td>
                    <td class="px-6 py-4 text-center">
                      <span :class="getGradeColor(student.grade)" class="text-2xl font-bold">
                        {{ student.grade || '-' }}
                      </span>
                    </td>
                    <td class="px-6 py-4 text-center text-sm text-gray-900 dark:text-white">
                      {{ student.grade_point || '-' }}
                    </td>
                    <td class="px-6 py-4 text-center">
                      <span 
                        v-if="student.is_accepted"
                        class="inline-flex items-center px-2 py-1 text-xs font-medium bg-green-100 text-green-800 rounded-full"
                      >
                        <Icon icon="heroicons:check" class="w-3 h-3 mr-1" />
                        ยอมรับแล้ว
                      </span>
                      <span 
                        v-else
                        class="inline-flex items-center px-2 py-1 text-xs font-medium bg-gray-100 text-gray-600 rounded-full"
                      >
                        รอยอมรับ
                      </span>
                    </td>
                    <td class="px-6 py-4 text-right">
                      <button
                        v-if="summary?.finalization_status !== 'finalized'"
                        @click="openEditGrade(student)"
                        class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-gray-600 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200"
                      >
                        <Icon icon="heroicons:pencil" class="w-4 h-4 mr-1" />
                        แก้ไข
                      </button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Mobile Cards -->
            <div class="md:hidden divide-y divide-gray-200 dark:divide-gray-600">
              <div v-for="student in filteredGrades" :key="student.member_id" class="p-4">
                <div class="flex items-start justify-between">
                  <div class="flex items-center">
                    <img :src="student.user?.avatar || '/images/default-avatar.png'" class="w-12 h-12 rounded-full" />
                    <div class="ml-3">
                      <p class="font-medium text-gray-900 dark:text-white">{{ student.user?.name }}</p>
                      <p class="text-xs text-gray-500">{{ student.member_code }}</p>
                    </div>
                  </div>
                  <span :class="getGradeColor(student.grade)" class="text-2xl font-bold">
                    {{ student.grade || '-' }}
                  </span>
                </div>
                <div class="mt-3 grid grid-cols-3 gap-2 text-center text-xs">
                  <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-2">
                    <p class="text-gray-500">คะแนน</p>
                    <p class="font-semibold text-gray-900 dark:text-white">{{ (student.total_score || 0).toFixed(1) }}</p>
                  </div>
                  <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-2">
                    <p class="text-gray-500">GPA</p>
                    <p class="font-semibold text-gray-900 dark:text-white">{{ student.grade_point || '-' }}</p>
                  </div>
                  <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-2">
                    <p class="text-gray-500">สถานะ</p>
                    <p class="font-semibold" :class="student.is_accepted ? 'text-green-600' : 'text-gray-500'">
                      {{ student.is_accepted ? 'ยอมรับ' : 'รอ' }}
                    </p>
                  </div>
                </div>
                <button
                  v-if="summary?.finalization_status !== 'finalized'"
                  @click="openEditGrade(student)"
                  class="mt-3 w-full py-2 text-sm font-medium text-gray-600 bg-gray-100 rounded-lg"
                >
                  <Icon icon="heroicons:pencil" class="w-4 h-4 mr-1 inline" />
                  แก้ไขเกรด
                </button>
              </div>
            </div>

            <!-- Empty -->
            <div v-if="filteredGrades.length === 0" class="py-12 text-center">
              <Icon icon="heroicons:academic-cap" class="w-12 h-12 mx-auto text-gray-400" />
              <p class="mt-2 text-gray-500">ไม่พบข้อมูลเกรด</p>
            </div>
          </div>
        </template>
      </div>

      <!-- Publish Modal -->
      <Teleport to="body">
        <div v-if="showPublishModal" class="fixed inset-0 z-50 overflow-y-auto">
          <div class="flex min-h-screen items-center justify-center p-4">
            <div class="fixed inset-0 bg-black/50" @click="showPublishModal = false"></div>
            <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-md w-full p-6">
              <div class="text-center">
                <Icon icon="heroicons:megaphone" class="w-12 h-12 mx-auto text-blue-600" />
                <h3 class="mt-4 text-lg font-semibold text-gray-900 dark:text-white">ประกาศเกรด</h3>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                  นักเรียนจะเห็นเกรดของตนเองและสามารถยื่นอุทธรณ์ได้
                </p>
              </div>
              <div class="mt-6 flex gap-3">
                <button
                  @click="showPublishModal = false"
                  class="flex-1 px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200"
                >
                  ยกเลิก
                </button>
                <button
                  @click="publishGrades"
                  :disabled="isProcessing"
                  class="flex-1 px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 disabled:opacity-50"
                >
                  ประกาศ
                </button>
              </div>
            </div>
          </div>
        </div>
      </Teleport>

      <!-- Finalize Modal -->
      <Teleport to="body">
        <div v-if="showFinalizeModal" class="fixed inset-0 z-50 overflow-y-auto">
          <div class="flex min-h-screen items-center justify-center p-4">
            <div class="fixed inset-0 bg-black/50" @click="showFinalizeModal = false"></div>
            <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-md w-full p-6">
              <div class="text-center">
                <Icon icon="heroicons:lock-closed" class="w-12 h-12 mx-auto text-purple-600" />
                <h3 class="mt-4 text-lg font-semibold text-gray-900 dark:text-white">ปิดเกรด</h3>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                  เมื่อปิดเกรดแล้วจะไม่สามารถแก้ไขได้อีก และนักเรียนจะสามารถดาวน์โหลดใบประกาศนียบัตรได้
                </p>
              </div>
              <div class="mt-6 flex gap-3">
                <button
                  @click="showFinalizeModal = false"
                  class="flex-1 px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200"
                >
                  ยกเลิก
                </button>
                <button
                  @click="finalizeGrades"
                  :disabled="isProcessing"
                  class="flex-1 px-4 py-2 text-sm font-medium text-white bg-purple-600 rounded-lg hover:bg-purple-700 disabled:opacity-50"
                >
                  ปิดเกรด
                </button>
              </div>
            </div>
          </div>
        </div>
      </Teleport>

      <!-- Edit Grade Modal -->
      <Teleport to="body">
        <div v-if="showEditGradeModal" class="fixed inset-0 z-50 overflow-y-auto">
          <div class="flex min-h-screen items-center justify-center p-4">
            <div class="fixed inset-0 bg-black/50" @click="showEditGradeModal = false"></div>
            <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-md w-full p-6">
              <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">แก้ไขเกรด</h3>
              
              <div class="mb-4 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                <p class="font-medium text-gray-900 dark:text-white">{{ selectedStudent?.user?.name }}</p>
                <p class="text-sm text-gray-500">เกรดปัจจุบัน: {{ selectedStudent?.grade || '-' }}</p>
              </div>

              <div class="space-y-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">คะแนนใหม่</label>
                  <input
                    v-model.number="editGradeForm.score"
                    type="number"
                    min="0"
                    max="100"
                    step="0.1"
                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg"
                  />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">เกรดใหม่</label>
                  <select
                    v-model="editGradeForm.grade"
                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg"
                  >
                    <option v-for="g in gradeOptions" :key="g" :value="g">{{ g }}</option>
                  </select>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    เหตุผลการแก้ไข <span class="text-red-500">*</span>
                  </label>
                  <textarea
                    v-model="editGradeForm.reason"
                    rows="3"
                    placeholder="ระบุเหตุผล..."
                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg"
                  ></textarea>
                </div>
              </div>

              <div class="mt-6 flex gap-3">
                <button
                  @click="showEditGradeModal = false"
                  class="flex-1 px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200"
                >
                  ยกเลิก
                </button>
                <button
                  @click="saveGradeEdit"
                  :disabled="!editGradeForm.reason || isProcessing"
                  class="flex-1 px-4 py-2 text-sm font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700 disabled:opacity-50"
                >
                  บันทึก
                </button>
              </div>
            </div>
          </div>
        </div>
      </Teleport>
    </div>
  </NuxtLayout>
</template>
