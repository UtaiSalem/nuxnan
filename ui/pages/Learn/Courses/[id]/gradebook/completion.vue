<script setup lang="ts">
/**
 * Course Completion - Grade Finalization
 */
import { Icon } from '@iconify/vue'

const route = useRoute()
const api = useApi()
const courseId = computed(() => route.params.id as string)

// Inject from parent layout
const course = inject('course') as Ref<any>
const isCourseAdmin = inject('isCourseAdmin') as Ref<boolean>

// State
const summary = ref<any>(null)
const grades = ref<any[]>([])
const groups = ref<any[]>([])
const ungroupedCount = ref(0)
const isLoading = ref(true)
const isProcessing = ref(false)
const processingMessage = ref('')

// Filters
const searchQuery = ref('')
const gradeFilter = ref<string>('all')
const statusFilter = ref<string>('all')
const activeGroup = ref<string | number>('all')

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
  await fetchData()
  isLoading.value = false
})

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
      groups.value = res.data.groups || []
      ungroupedCount.value = res.data.ungrouped_count || 0
    }
  } catch (err) {
    console.error('Failed to fetch grades:', err)
  }
}

// Filtered grades
const filteredGrades = computed(() => {
  let result = [...grades.value]

  // Group filter
  if (activeGroup.value === 'ungrouped') {
    result = result.filter((g: any) => !g.group_id)
  } else if (activeGroup.value !== 'all') {
    result = result.filter((g: any) => g.group_id === activeGroup.value)
  }

  // Search (name / username / member_code)
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    result = result.filter((g: any) =>
      g.user?.name?.toLowerCase().includes(query) ||
      g.user?.username?.toLowerCase().includes(query) ||
      g.member_code?.toLowerCase().includes(query)
    )
  }

  // Grade filter
  if (gradeFilter.value !== 'all') {
    if (gradeFilter.value === 'ungraded') {
      result = result.filter((g: any) => !g.grade)
    } else {
      result = result.filter((g: any) => g.grade === gradeFilter.value)
    }
  }

  // Status filter (acceptance status)
  if (statusFilter.value !== 'all') {
    if (statusFilter.value === 'accepted') {
      result = result.filter((g: any) => g.is_accepted)
    } else if (statusFilter.value === 'pending') {
      result = result.filter((g: any) => !g.is_accepted)
    }
  }

  return result
})

// Counts per group/grade for badges in filter UI
const groupCount = (gid: string | number | null) =>
  gid === 'ungrouped'
    ? grades.value.filter((g: any) => !g.group_id).length
    : grades.value.filter((g: any) => g.group_id === gid).length

const gradeCount = (g: string) =>
  g === 'ungraded'
    ? grades.value.filter((x: any) => !x.grade).length
    : grades.value.filter((x: any) => x.grade === g).length

// Actions
const startGrading = async () => {
  isProcessing.value = true
  processingMessage.value = 'กำลังเริ่มกระบวนการออกเกรด...'
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
    processingMessage.value = ''
  }
}

const publishGrades = async () => {
  isProcessing.value = true
  processingMessage.value = `กำลังประกาศเกรด ${grades.value.length} คน... อาจใช้เวลาสักครู่`
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
    processingMessage.value = ''
  }
}

const finalizeGrades = async () => {
  isProcessing.value = true
  processingMessage.value = 'กำลังปิดเกรดและบันทึกใบประมวลผล...'
  try {
    const res: any = await api.post(`/api/courses/${courseId.value}/completion/finalize`)
    if (res.success) {
      useToast().success('ปิดเกรดเรียบร้อยแล้ว')
      showFinalizeModal.value = false
      await fetchData()
    }
  } catch (err: any) {
    console.error('Failed to finalize:', err)
    const msg = err?.data?.message || err?.message || 'ไม่สามารถปิดเกรดได้'
    useToast().error(msg)
  } finally {
    isProcessing.value = false
    processingMessage.value = ''
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
  processingMessage.value = 'กำลังบันทึกการแก้ไขเกรด...'
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
    processingMessage.value = ''
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
              :to="`/Learn/Courses/${courseId}/gradebook`"
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
                class="min-h-[44px] sm:min-h-0 inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700 disabled:opacity-50"
              >
                <Icon :icon="isProcessing ? 'heroicons:arrow-path' : 'heroicons:calculator'" :class="['w-4 h-4 mr-2', isProcessing && 'animate-spin']" />
                {{ isProcessing ? 'กำลังเริ่ม...' : 'เริ่มออกเกรด' }}
              </button>
            </template>

            <template v-else-if="summary?.finalization_status === 'grading'">
              <button
                @click="showPublishModal = true"
                :disabled="isProcessing"
                class="min-h-[44px] sm:min-h-0 inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 disabled:opacity-50"
              >
                <Icon icon="heroicons:megaphone" class="w-4 h-4 mr-2" />
                ประกาศเกรด
              </button>
            </template>

            <template v-else-if="summary?.finalization_status === 'published'">
              <button
                @click="showFinalizeModal = true"
                :disabled="isProcessing || summary?.pending_appeals > 0"
                class="min-h-[44px] sm:min-h-0 inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-purple-600 rounded-lg hover:bg-purple-700 disabled:opacity-50"
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
      <!-- Step Bar -->
      <div class="mb-8 overflow-hidden bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
        <nav aria-label="Progress">
          <ol role="list" class="flex items-center">
            <!-- Step 1: Active -->
            <li class="relative flex-1">
              <div class="flex items-center group">
                <span class="flex items-center">
                  <span class="relative flex h-8 w-8 items-center justify-center rounded-full" :class="summary?.finalization_status !== 'active' ? 'bg-primary-600' : 'border-2 border-primary-600 bg-white'">
                    <Icon v-if="summary?.finalization_status !== 'active'" icon="heroicons:check" class="h-5 w-5 text-white" />
                    <span v-else class="h-2.5 w-2.5 rounded-full bg-primary-600"></span>
                  </span>
                  <span class="ml-4 text-sm font-medium" :class="summary?.finalization_status === 'active' ? 'text-primary-600' : 'text-gray-900 dark:text-white'">เริ่มออกเกรด</span>
                </span>
              </div>
              <div class="absolute top-4 left-0 -ml-px mt-0.5 h-0.5 w-full bg-gray-200 dark:bg-gray-700" aria-hidden="true">
                 <div class="h-full bg-primary-600" :style="{ width: summary?.finalization_status !== 'active' ? '100%' : '0%' }"></div>
              </div>
            </li>

            <!-- Step 2: Grading -->
            <li class="relative flex-1">
              <div class="flex items-center group ml-8">
                <span class="flex items-center">
                  <span class="relative flex h-8 w-8 items-center justify-center rounded-full" :class="['published', 'finalized', 'archived'].includes(summary?.finalization_status) ? 'bg-primary-600' : (summary?.finalization_status === 'grading' ? 'border-2 border-primary-600 bg-white' : 'border-2 border-gray-300 bg-white')">
                    <Icon v-if="['published', 'finalized', 'archived'].includes(summary?.finalization_status)" icon="heroicons:check" class="h-5 w-5 text-white" />
                    <span v-else-if="summary?.finalization_status === 'grading'" class="h-2.5 w-2.5 rounded-full bg-primary-600"></span>
                    <span v-else class="h-2.5 w-2.5 rounded-full bg-gray-300"></span>
                  </span>
                  <span class="ml-4 text-sm font-medium" :class="summary?.finalization_status === 'grading' ? 'text-primary-600' : 'text-gray-500'">ประกาศเกรด</span>
                </span>
              </div>
              <div class="absolute top-4 left-0 -ml-px mt-0.5 h-0.5 w-full bg-gray-200 dark:bg-gray-700" aria-hidden="true">
                 <div class="h-full bg-primary-600" :style="{ width: ['published', 'finalized', 'archived'].includes(summary?.finalization_status) ? '100%' : '0%' }"></div>
              </div>
            </li>

            <!-- Step 3: Published -->
            <li class="relative flex-1">
              <div class="flex items-center group ml-8">
                <span class="flex items-center">
                  <span class="relative flex h-8 w-8 items-center justify-center rounded-full" :class="['finalized', 'archived'].includes(summary?.finalization_status) ? 'bg-primary-600' : (summary?.finalization_status === 'published' ? 'border-2 border-primary-600 bg-white' : 'border-2 border-gray-300 bg-white')">
                    <Icon v-if="['finalized', 'archived'].includes(summary?.finalization_status)" icon="heroicons:check" class="h-5 w-5 text-white" />
                    <span v-else-if="summary?.finalization_status === 'published'" class="h-2.5 w-2.5 rounded-full bg-primary-600"></span>
                    <span v-else class="h-2.5 w-2.5 rounded-full bg-gray-300"></span>
                  </span>
                  <span class="ml-4 text-sm font-medium" :class="summary?.finalization_status === 'published' ? 'text-primary-600' : 'text-gray-500'">รอการยืนยัน</span>
                </span>
              </div>
              <div class="absolute top-4 left-0 -ml-px mt-0.5 h-0.5 w-full bg-gray-200 dark:bg-gray-700" aria-hidden="true">
                 <div class="h-full bg-primary-600" :style="{ width: ['finalized', 'archived'].includes(summary?.finalization_status) ? '100%' : '0%' }"></div>
              </div>
            </li>

            <!-- Step 4: Finalized -->
            <li class="relative">
              <div class="flex items-center group ml-8">
                <span class="flex items-center">
                  <span class="relative flex h-8 w-8 items-center justify-center rounded-full" :class="['finalized', 'archived'].includes(summary?.finalization_status) ? 'bg-green-600' : 'border-2 border-gray-300 bg-white'">
                    <Icon v-if="['finalized', 'archived'].includes(summary?.finalization_status)" icon="heroicons:check" class="h-5 w-5 text-white" />
                    <span v-else class="h-2.5 w-2.5 rounded-full bg-gray-300"></span>
                  </span>
                  <span class="ml-4 text-sm font-medium" :class="['finalized', 'archived'].includes(summary?.finalization_status) ? 'text-green-600' : 'text-gray-500'">ปิดเกรดถาวร</span>
                </span>
              </div>
            </li>
          </ol>
        </nav>
      </div>

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
                :to="`/Learn/Courses/${courseId}/gradebook/appeals`"
                class="mt-2 inline-flex items-center text-sm font-medium text-yellow-700 dark:text-yellow-300 hover:underline"
              >
                ดูคำร้องอุทธรณ์
                <Icon icon="heroicons:arrow-right" class="w-4 h-4 ml-1" />
              </NuxtLink>
            </div>
          </div>
        </div>

        <!-- Filters -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 mb-6 space-y-3">
          <!-- Group tabs -->
          <div v-if="groups.length > 0 || ungroupedCount > 0" class="flex flex-wrap gap-2">
            <button class="min-h-[44px] sm:min-h-0"
              @click="activeGroup = 'all'"
              :class="['px-3 py-1.5 text-sm rounded-lg border', activeGroup === 'all' ? 'bg-primary-500 text-white border-primary-500' : 'bg-white dark:bg-gray-700 border-gray-200 dark:border-gray-600 text-gray-700 dark:text-gray-200']"
            >
              ทั้งหมด ({{ grades.length }})
            </button>
            <button class="min-h-[44px] sm:min-h-0"
              v-for="g in groups"
              :key="g.id"
              @click="activeGroup = g.id"
              :class="['px-3 py-1.5 text-sm rounded-lg border', activeGroup === g.id ? 'bg-primary-500 text-white border-primary-500' : 'bg-white dark:bg-gray-700 border-gray-200 dark:border-gray-600 text-gray-700 dark:text-gray-200']"
            >
              {{ g.name }} ({{ groupCount(g.id) }})
            </button>
            <button class="min-h-[44px] sm:min-h-0"
              v-if="ungroupedCount > 0"
              @click="activeGroup = 'ungrouped'"
              :class="['px-3 py-1.5 text-sm rounded-lg border', activeGroup === 'ungrouped' ? 'bg-primary-500 text-white border-primary-500' : 'bg-white dark:bg-gray-700 border-gray-200 dark:border-gray-600 text-gray-700 dark:text-gray-200']"
            >
              ยังไม่จัดกลุ่ม ({{ ungroupedCount }})
            </button>
          </div>

          <!-- Search + grade + status -->
          <div class="flex flex-col sm:flex-row gap-3 sm:gap-4">
            <div class="flex-1">
              <div class="relative">
                <Icon icon="heroicons:magnifying-glass" class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
                <input
                  v-model="searchQuery"
                  type="text"
                  placeholder="ค้นหาชื่อ / username / รหัสนักเรียน"
                  class="w-full pl-10 pr-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                />
              </div>
            </div>
            <div class="flex flex-col gap-3 sm:flex-row">
              <select
                v-model="gradeFilter"
                class="min-w-0 w-full sm:flex-1 min-h-[44px] sm:min-h-0 px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
              >
                <option value="all">ทุกเกรด</option>
                <option v-for="g in gradeOptions" :key="g" :value="g">{{ g }} ({{ gradeCount(g) }})</option>
                <option value="ungraded">ยังไม่ออกเกรด ({{ gradeCount('ungraded') }})</option>
              </select>
              <select
                v-model="statusFilter"
                class="min-w-0 w-full sm:flex-1 min-h-[44px] sm:min-h-0 px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
              >
                <option value="all">ทุกสถานะ</option>
                <option value="accepted">ยอมรับแล้ว</option>
                <option value="pending">รอยอมรับ</option>
              </select>
            </div>
          </div>

          <!-- Result count + clear -->
          <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400 pt-1">
            <span>แสดง <b class="text-gray-900 dark:text-white">{{ filteredGrades.length }}</b> / {{ grades.length }} คน</span>
            <button
              v-if="activeGroup !== 'all' || searchQuery || gradeFilter !== 'all' || statusFilter !== 'all'"
              @click="activeGroup = 'all'; searchQuery = ''; gradeFilter = 'all'; statusFilter = 'all'"
              class="text-primary-600 dark:text-primary-400 hover:underline"
            >
              ล้างตัวกรอง
            </button>
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
                      class="min-h-[44px] sm:min-h-0 inline-flex items-center px-3 py-1.5 text-xs font-medium text-gray-600 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200"
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
                class="min-h-[44px] sm:min-h-0 mt-3 w-full py-2 text-sm font-medium text-gray-600 bg-gray-100 rounded-lg"
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
                class="min-h-[44px] sm:min-h-0 flex-1 px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200"
              >
                ยกเลิก
              </button>
              <button
                @click="publishGrades"
                :disabled="isProcessing"
                class="min-h-[44px] sm:min-h-0 flex-1 inline-flex items-center justify-center gap-2 px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 disabled:opacity-50"
              >
                <Icon v-if="isProcessing" icon="heroicons:arrow-path" class="w-4 h-4 animate-spin" />
                {{ isProcessing ? 'กำลังประกาศ...' : 'ประกาศ' }}
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
                class="min-h-[44px] sm:min-h-0 flex-1 px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200"
              >
                ยกเลิก
              </button>
              <button
                @click="finalizeGrades"
                :disabled="isProcessing"
                class="min-h-[44px] sm:min-h-0 flex-1 inline-flex items-center justify-center gap-2 px-4 py-2 text-sm font-medium text-white bg-purple-600 rounded-lg hover:bg-purple-700 disabled:opacity-50"
              >
                <Icon v-if="isProcessing" icon="heroicons:arrow-path" class="w-4 h-4 animate-spin" />
                {{ isProcessing ? 'กำลังปิดเกรด...' : 'ปิดเกรด' }}
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
              <p class="text-sm text-gray-500 dark:text-gray-400">เกรดปัจจุบัน: {{ selectedStudent?.grade || '-' }}</p>
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
                  class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">เกรดใหม่</label>
                <select
                  v-model="editGradeForm.grade"
                  class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                >
                  <option v-for="g in gradeOptions" :key="g" :value="g" class="bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100">{{ g }}</option>
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
                  class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                ></textarea>
              </div>
            </div>

            <div class="mt-6 flex gap-3">
              <button
                @click="showEditGradeModal = false"
                class="min-h-[44px] sm:min-h-0 flex-1 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg"
              >
                ยกเลิก
              </button>
              <button
                @click="saveGradeEdit"
                :disabled="!editGradeForm.reason || isProcessing"
                class="min-h-[44px] sm:min-h-0 flex-1 inline-flex items-center justify-center gap-2 px-4 py-2 text-sm font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700 disabled:opacity-50"
              >
                <Icon v-if="isProcessing" icon="heroicons:arrow-path" class="w-4 h-4 animate-spin" />
                {{ isProcessing ? 'กำลังบันทึก...' : 'บันทึก' }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- Global processing overlay -->
    <Teleport to="body">
      <div
        v-if="isProcessing"
        class="fixed inset-0 z-[60] flex items-center justify-center bg-black/40 backdrop-blur-sm"
      >
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl p-6 flex flex-col items-center gap-3 max-w-sm mx-4 border border-gray-200 dark:border-gray-700">
          <div class="relative">
            <Icon icon="heroicons:arrow-path" class="w-12 h-12 text-primary-600 animate-spin" />
          </div>
          <p class="text-sm font-medium text-gray-900 dark:text-white text-center">
            {{ processingMessage || 'กำลังประมวลผล...' }}
          </p>
          <p class="text-xs text-gray-500 dark:text-gray-400 text-center">
            กรุณาอย่าปิดหน้านี้
          </p>
        </div>
      </div>
    </Teleport>
  </div>
</template>
