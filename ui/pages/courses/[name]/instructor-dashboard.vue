<script setup lang="ts">
/**
 * Instructor Dashboard - Course Analytics
 * แดชบอร์ดสำหรับผู้สอน แสดงภาพรวมและสถิติของรายวิชา
 */
import { Icon } from '@iconify/vue'

definePageMeta({
  layout: 'course',
  middleware: ['auth']
})

const route = useRoute()
const api = useApi()

const courseId = computed(() => route.params.name as string)

// State
const isLoading = ref(true)
const dashboard = ref<any>(null)
const trends = ref<any>(null)
const atRiskStudents = ref<any[]>([])
const topPerformers = ref<any[]>([])
const activeTab = ref('overview')

// Fetch dashboard data
const fetchDashboard = async () => {
  isLoading.value = true
  try {
    const [dashboardRes, trendsRes, atRiskRes, topRes] = await Promise.all([
      api.get(`/api/courses/${courseId.value}/instructor-dashboard`),
      api.get(`/api/courses/${courseId.value}/instructor-dashboard/trends?days=30`),
      api.get(`/api/courses/${courseId.value}/instructor-dashboard/at-risk?threshold=50`),
      api.get(`/api/courses/${courseId.value}/instructor-dashboard/top-performers?limit=5`),
    ])

    if (dashboardRes.success) {
      dashboard.value = dashboardRes.data
    }
    if (trendsRes.success) {
      trends.value = trendsRes.data
    }
    if (atRiskRes.success) {
      atRiskStudents.value = atRiskRes.data.students || []
    }
    if (topRes.success) {
      topPerformers.value = topRes.data || []
    }
  } catch (err) {
    console.error('Failed to fetch dashboard:', err)
  } finally {
    isLoading.value = false
  }
}

// Computed
const course = computed(() => dashboard.value?.course)
const overview = computed(() => dashboard.value?.overview)
const grades = computed(() => dashboard.value?.grades)
const attendance = computed(() => dashboard.value?.attendance)
const assignments = computed(() => dashboard.value?.assignments)
const quizzes = computed(() => dashboard.value?.quizzes)
const lessons = computed(() => dashboard.value?.lessons)
const pending = computed(() => dashboard.value?.pending_items)
const appeals = computed(() => dashboard.value?.appeals)
const certificates = computed(() => dashboard.value?.certificates)
const recentActivity = computed(() => dashboard.value?.recent_activity || [])

// Grade distribution for chart
const gradeDistribution = computed(() => {
  if (!grades.value?.distribution) return []
  return Object.entries(grades.value.distribution).map(([grade, count]) => ({
    grade,
    count: count as number,
    percentage: grades.value.total > 0 
      ? Math.round(((count as number) / grades.value.total) * 100) 
      : 0
  }))
})

// Grade color mapping
const getGradeColor = (grade: string): string => {
  const colors: Record<string, string> = {
    'A': 'bg-green-500',
    'B+': 'bg-emerald-500',
    'B': 'bg-teal-500',
    'C+': 'bg-cyan-500',
    'C': 'bg-blue-500',
    'D+': 'bg-yellow-500',
    'D': 'bg-orange-500',
    'F': 'bg-red-500',
    'I': 'bg-gray-500',
    'W': 'bg-gray-400',
  }
  return colors[grade] || 'bg-gray-500'
}

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('th-TH', {
    day: 'numeric',
    month: 'short',
    hour: '2-digit',
    minute: '2-digit',
  })
}

const getActivityColor = (color: string): string => {
  const classes: Record<string, string> = {
    blue: 'bg-blue-100 text-blue-600 dark:bg-blue-900 dark:text-blue-400',
    green: 'bg-green-100 text-green-600 dark:bg-green-900 dark:text-green-400',
    yellow: 'bg-yellow-100 text-yellow-600 dark:bg-yellow-900 dark:text-yellow-400',
    red: 'bg-red-100 text-red-600 dark:bg-red-900 dark:text-red-400',
  }
  return classes[color] || classes.blue
}

useHead({
  title: computed(() => course.value?.name ? `แดชบอร์ดผู้สอน - ${course.value.name}` : 'แดชบอร์ดผู้สอน')
})

onMounted(() => {
  fetchDashboard()
})
</script>

<template>
  <div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-6">
    <div class="container mx-auto px-4">
      <!-- Loading -->
      <div v-if="isLoading" class="flex items-center justify-center py-20">
        <Icon icon="heroicons:arrow-path" class="w-8 h-8 animate-spin text-primary-500" />
      </div>

      <template v-else-if="dashboard">
        <!-- Header -->
        <div class="mb-6">
          <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400 mb-2">
            <NuxtLink to="/courses" class="hover:text-primary-500">รายวิชา</NuxtLink>
            <Icon icon="heroicons:chevron-right" class="w-4 h-4" />
            <NuxtLink :to="`/courses/${courseId}`" class="hover:text-primary-500">{{ course?.name }}</NuxtLink>
            <Icon icon="heroicons:chevron-right" class="w-4 h-4" />
            <span>แดชบอร์ดผู้สอน</span>
          </div>
          <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
              <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                แดชบอร์ดผู้สอน
              </h1>
              <p class="text-gray-600 dark:text-gray-400 mt-1">
                ภาพรวมและสถิติของรายวิชา {{ course?.name }}
              </p>
            </div>
            
            <!-- Export Reports Button -->
            <NuxtLink
              :to="`/courses/${courseId}/reports`"
              class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700 transition-colors shadow-sm"
            >
              <Icon icon="tabler:file-report" class="w-5 h-5" />
              <span>ดาวน์โหลดรายงาน</span>
            </NuxtLink>
          </div>
        </div>

        <!-- Pending Actions Alert -->
        <div
          v-if="pending?.total_pending > 0"
          class="mb-6 p-4 bg-yellow-50 dark:bg-yellow-900/30 border border-yellow-200 dark:border-yellow-700 rounded-xl"
        >
          <div class="flex items-center gap-3">
            <Icon icon="heroicons:exclamation-triangle" class="w-6 h-6 text-yellow-600" />
            <div>
              <p class="font-medium text-yellow-800 dark:text-yellow-200">มีรายการรอดำเนินการ</p>
              <p class="text-sm text-yellow-600 dark:text-yellow-400">
                {{ pending.pending_assignment_grading }} งานรอตรวจ,
                {{ pending.pending_appeals }} อุทธรณ์รอพิจารณา
              </p>
            </div>
          </div>
        </div>

        <!-- Overview Cards -->
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4 mb-6">
          <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm">
            <div class="flex items-center gap-3">
              <div class="p-2 bg-blue-100 dark:bg-blue-900/50 rounded-lg">
                <Icon icon="heroicons:users" class="w-5 h-5 text-blue-600 dark:text-blue-400" />
              </div>
              <div>
                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ overview?.total_members }}</p>
                <p class="text-xs text-gray-500">นักเรียน</p>
              </div>
            </div>
          </div>

          <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm">
            <div class="flex items-center gap-3">
              <div class="p-2 bg-purple-100 dark:bg-purple-900/50 rounded-lg">
                <Icon icon="heroicons:squares-2x2" class="w-5 h-5 text-purple-600 dark:text-purple-400" />
              </div>
              <div>
                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ overview?.total_groups }}</p>
                <p class="text-xs text-gray-500">กลุ่ม</p>
              </div>
            </div>
          </div>

          <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm">
            <div class="flex items-center gap-3">
              <div class="p-2 bg-emerald-100 dark:bg-emerald-900/50 rounded-lg">
                <Icon icon="heroicons:book-open" class="w-5 h-5 text-emerald-600 dark:text-emerald-400" />
              </div>
              <div>
                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ overview?.total_lessons }}</p>
                <p class="text-xs text-gray-500">บทเรียน</p>
              </div>
            </div>
          </div>

          <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm">
            <div class="flex items-center gap-3">
              <div class="p-2 bg-orange-100 dark:bg-orange-900/50 rounded-lg">
                <Icon icon="heroicons:document-text" class="w-5 h-5 text-orange-600 dark:text-orange-400" />
              </div>
              <div>
                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ overview?.total_assignments }}</p>
                <p class="text-xs text-gray-500">งาน</p>
              </div>
            </div>
          </div>

          <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm">
            <div class="flex items-center gap-3">
              <div class="p-2 bg-cyan-100 dark:bg-cyan-900/50 rounded-lg">
                <Icon icon="heroicons:academic-cap" class="w-5 h-5 text-cyan-600 dark:text-cyan-400" />
              </div>
              <div>
                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ overview?.total_quizzes }}</p>
                <p class="text-xs text-gray-500">ข้อสอบ</p>
              </div>
            </div>
          </div>

          <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm">
            <div class="flex items-center gap-3">
              <div class="p-2 bg-green-100 dark:bg-green-900/50 rounded-lg">
                <Icon icon="heroicons:check-badge" class="w-5 h-5 text-green-600 dark:text-green-400" />
              </div>
              <div>
                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ certificates?.total_issued }}</p>
                <p class="text-xs text-gray-500">ใบประกาศ</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Main Content Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
          <!-- Left Column (2/3) -->
          <div class="lg:col-span-2 space-y-6">
            <!-- Grade Distribution -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
              <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                การกระจายเกรด
              </h2>
              
              <div class="flex items-center gap-4 mb-4">
                <div class="text-center">
                  <p class="text-3xl font-bold text-green-600">{{ grades?.pass_rate }}%</p>
                  <p class="text-sm text-gray-500">อัตราผ่าน</p>
                </div>
                <div class="text-center">
                  <p class="text-3xl font-bold text-blue-600">{{ grades?.average_gpa }}</p>
                  <p class="text-sm text-gray-500">GPA เฉลี่ย</p>
                </div>
                <div class="text-center">
                  <p class="text-3xl font-bold text-gray-600">{{ grades?.average_score }}</p>
                  <p class="text-sm text-gray-500">คะแนนเฉลี่ย</p>
                </div>
              </div>

              <!-- Grade Bars -->
              <div class="space-y-2">
                <div
                  v-for="item in gradeDistribution"
                  :key="item.grade"
                  class="flex items-center gap-3"
                >
                  <span class="w-8 font-semibold text-gray-700 dark:text-gray-300">{{ item.grade }}</span>
                  <div class="flex-1 bg-gray-200 dark:bg-gray-700 rounded-full h-6 overflow-hidden">
                    <div
                      :class="getGradeColor(item.grade)"
                      class="h-full rounded-full transition-all duration-500 flex items-center justify-end pr-2"
                      :style="{ width: `${Math.max(item.percentage, 5)}%` }"
                    >
                      <span v-if="item.count > 0" class="text-xs text-white font-medium">{{ item.count }}</span>
                    </div>
                  </div>
                  <span class="w-12 text-sm text-gray-500 text-right">{{ item.percentage }}%</span>
                </div>
              </div>
            </div>

            <!-- Performance Metrics -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <!-- Attendance -->
              <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-5">
                <div class="flex items-center gap-2 mb-3">
                  <Icon icon="heroicons:calendar-days" class="w-5 h-5 text-blue-500" />
                  <h3 class="font-medium text-gray-900 dark:text-white">การเข้าเรียน</h3>
                </div>
                <p class="text-3xl font-bold text-blue-600 mb-2">{{ attendance?.average_rate }}%</p>
                <div class="space-y-1 text-sm">
                  <div class="flex justify-between">
                    <span class="text-gray-500">ครั้งที่เช็ค</span>
                    <span class="font-medium text-gray-900 dark:text-white">{{ attendance?.total_sessions }}</span>
                  </div>
                  <div class="flex justify-between">
                    <span class="text-green-600">เข้าเรียน</span>
                    <span class="font-medium">{{ attendance?.present_rate }}%</span>
                  </div>
                  <div class="flex justify-between">
                    <span class="text-yellow-600">สาย</span>
                    <span class="font-medium">{{ attendance?.late_rate }}%</span>
                  </div>
                  <div class="flex justify-between">
                    <span class="text-red-600">ขาด</span>
                    <span class="font-medium">{{ attendance?.absent_rate }}%</span>
                  </div>
                </div>
              </div>

              <!-- Assignments -->
              <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-5">
                <div class="flex items-center gap-2 mb-3">
                  <Icon icon="heroicons:document-text" class="w-5 h-5 text-orange-500" />
                  <h3 class="font-medium text-gray-900 dark:text-white">การส่งงาน</h3>
                </div>
                <p class="text-3xl font-bold text-orange-600 mb-2">{{ assignments?.submission_rate }}%</p>
                <div class="space-y-1 text-sm">
                  <div class="flex justify-between">
                    <span class="text-gray-500">งานทั้งหมด</span>
                    <span class="font-medium text-gray-900 dark:text-white">{{ assignments?.total }}</span>
                  </div>
                  <div class="flex justify-between">
                    <span class="text-gray-500">ส่งแล้ว</span>
                    <span class="font-medium">{{ assignments?.total_submissions }}</span>
                  </div>
                  <div class="flex justify-between">
                    <span class="text-gray-500">รอตรวจ</span>
                    <span class="font-medium text-yellow-600">{{ assignments?.pending_count }}</span>
                  </div>
                  <div class="flex justify-between">
                    <span class="text-gray-500">คะแนนเฉลี่ย</span>
                    <span class="font-medium">{{ assignments?.average_score }}</span>
                  </div>
                </div>
              </div>

              <!-- Lessons -->
              <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-5">
                <div class="flex items-center gap-2 mb-3">
                  <Icon icon="heroicons:book-open" class="w-5 h-5 text-emerald-500" />
                  <h3 class="font-medium text-gray-900 dark:text-white">ความคืบหน้าบทเรียน</h3>
                </div>
                <p class="text-3xl font-bold text-emerald-600 mb-2">{{ lessons?.completion_rate }}%</p>
                <div class="space-y-1 text-sm">
                  <div class="flex justify-between">
                    <span class="text-gray-500">บทเรียนทั้งหมด</span>
                    <span class="font-medium text-gray-900 dark:text-white">{{ lessons?.total }}</span>
                  </div>
                  <div class="flex justify-between">
                    <span class="text-gray-500">อ่านแล้ว</span>
                    <span class="font-medium">{{ lessons?.total_completions }}</span>
                  </div>
                  <div class="flex justify-between">
                    <span class="text-gray-500">ควรอ่าน</span>
                    <span class="font-medium">{{ lessons?.expected_completions }}</span>
                  </div>
                </div>
              </div>
            </div>

            <!-- At-Risk Students -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
              <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                  <Icon icon="heroicons:exclamation-circle" class="w-5 h-5 text-red-500" />
                  นักเรียนที่ต้องติดตาม
                </h2>
                <span class="text-sm text-gray-500">{{ atRiskStudents.length }} คน</span>
              </div>

              <div v-if="atRiskStudents.length === 0" class="text-center py-8 text-gray-500">
                <Icon icon="heroicons:check-circle" class="w-12 h-12 mx-auto text-green-500 mb-2" />
                <p>ไม่มีนักเรียนที่ต้องติดตามเป็นพิเศษ</p>
              </div>

              <div v-else class="space-y-3">
                <div
                  v-for="student in atRiskStudents.slice(0, 5)"
                  :key="student.member.id"
                  class="flex items-center justify-between p-3 bg-red-50 dark:bg-red-900/20 rounded-lg"
                >
                  <div class="flex items-center gap-3">
                    <img
                      :src="student.member.avatar || `https://ui-avatars.com/api/?name=${student.member.name}`"
                      :alt="student.member.name"
                      class="w-10 h-10 rounded-full object-cover"
                    />
                    <div>
                      <p class="font-medium text-gray-900 dark:text-white">{{ student.member.name }}</p>
                      <div class="flex flex-wrap gap-1">
                        <span
                          v-for="risk in student.risks"
                          :key="risk.type"
                          class="text-xs px-2 py-0.5 bg-red-100 dark:bg-red-800 text-red-700 dark:text-red-300 rounded"
                        >
                          {{ risk.message }}
                        </span>
                      </div>
                    </div>
                  </div>
                  <div class="text-right">
                    <p class="text-lg font-bold text-red-600">{{ student.risk_score }}</p>
                    <p class="text-xs text-gray-500">ความเสี่ยง</p>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Right Column (1/3) -->
          <div class="space-y-6">
            <!-- Top Performers -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-5">
              <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <Icon icon="heroicons:trophy" class="w-5 h-5 text-yellow-500" />
                นักเรียนยอดเยี่ยม
              </h2>

              <div class="space-y-3">
                <div
                  v-for="(student, index) in topPerformers"
                  :key="student.member.id"
                  class="flex items-center gap-3"
                >
                  <div
                    class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-sm"
                    :class="[
                      index === 0 ? 'bg-yellow-100 text-yellow-700' :
                      index === 1 ? 'bg-gray-100 text-gray-700' :
                      index === 2 ? 'bg-orange-100 text-orange-700' :
                      'bg-gray-50 text-gray-600'
                    ]"
                  >
                    {{ index + 1 }}
                  </div>
                  <img
                    :src="student.member.avatar || `https://ui-avatars.com/api/?name=${student.member.name}`"
                    :alt="student.member.name"
                    class="w-8 h-8 rounded-full object-cover"
                  />
                  <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                      {{ student.member.name }}
                    </p>
                    <p class="text-xs text-gray-500">
                      เกรด {{ student.grade }}
                    </p>
                  </div>
                  <p class="text-sm font-bold text-green-600">{{ student.score_percentage }}%</p>
                </div>
              </div>
            </div>

            <!-- Appeals Summary -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-5">
              <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <Icon icon="heroicons:chat-bubble-left-right" class="w-5 h-5 text-purple-500" />
                อุทธรณ์เกรด
              </h2>

              <div class="grid grid-cols-2 gap-3">
                <div class="text-center p-3 bg-yellow-50 dark:bg-yellow-900/30 rounded-lg">
                  <p class="text-2xl font-bold text-yellow-600">{{ appeals?.pending }}</p>
                  <p class="text-xs text-gray-500">รอพิจารณา</p>
                </div>
                <div class="text-center p-3 bg-blue-50 dark:bg-blue-900/30 rounded-lg">
                  <p class="text-2xl font-bold text-blue-600">{{ appeals?.reviewing }}</p>
                  <p class="text-xs text-gray-500">กำลังตรวจสอบ</p>
                </div>
                <div class="text-center p-3 bg-green-50 dark:bg-green-900/30 rounded-lg">
                  <p class="text-2xl font-bold text-green-600">{{ appeals?.approved }}</p>
                  <p class="text-xs text-gray-500">อนุมัติ</p>
                </div>
                <div class="text-center p-3 bg-red-50 dark:bg-red-900/30 rounded-lg">
                  <p class="text-2xl font-bold text-red-600">{{ appeals?.rejected }}</p>
                  <p class="text-xs text-gray-500">ปฏิเสธ</p>
                </div>
              </div>
            </div>

            <!-- Recent Activity -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-5">
              <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <Icon icon="heroicons:clock" class="w-5 h-5 text-blue-500" />
                กิจกรรมล่าสุด
              </h2>

              <div v-if="recentActivity.length === 0" class="text-center py-4 text-gray-500">
                ไม่มีกิจกรรมล่าสุด
              </div>

              <div v-else class="space-y-3">
                <div
                  v-for="(activity, index) in recentActivity.slice(0, 6)"
                  :key="index"
                  class="flex items-start gap-3"
                >
                  <div
                    class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0"
                    :class="getActivityColor(activity.color)"
                  >
                    <Icon :icon="activity.icon" class="w-4 h-4" />
                  </div>
                  <div class="flex-1 min-w-0">
                    <p class="text-sm text-gray-900 dark:text-white line-clamp-2">
                      {{ activity.message }}
                    </p>
                    <p class="text-xs text-gray-500">
                      {{ formatDate(activity.created_at) }}
                    </p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Quick Links -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-5">
              <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                ลิงก์ด่วน
              </h2>

              <div class="grid grid-cols-2 gap-2">
                <NuxtLink
                  :to="`/courses/${courseId}/gradebook`"
                  class="p-3 bg-blue-50 dark:bg-blue-900/30 rounded-lg hover:bg-blue-100 dark:hover:bg-blue-900/50 transition-colors text-center"
                >
                  <Icon icon="heroicons:table-cells" class="w-6 h-6 mx-auto text-blue-600 mb-1" />
                  <p class="text-xs font-medium text-blue-700 dark:text-blue-300">เกรดบุ๊ค</p>
                </NuxtLink>
                <NuxtLink
                  :to="`/courses/${courseId}/appeals`"
                  class="p-3 bg-purple-50 dark:bg-purple-900/30 rounded-lg hover:bg-purple-100 dark:hover:bg-purple-900/50 transition-colors text-center"
                >
                  <Icon icon="heroicons:chat-bubble-left-right" class="w-6 h-6 mx-auto text-purple-600 mb-1" />
                  <p class="text-xs font-medium text-purple-700 dark:text-purple-300">อุทธรณ์</p>
                </NuxtLink>
                <NuxtLink
                  :to="`/courses/${courseId}/certificates`"
                  class="p-3 bg-green-50 dark:bg-green-900/30 rounded-lg hover:bg-green-100 dark:hover:bg-green-900/50 transition-colors text-center"
                >
                  <Icon icon="heroicons:document-check" class="w-6 h-6 mx-auto text-green-600 mb-1" />
                  <p class="text-xs font-medium text-green-700 dark:text-green-300">ใบประกาศ</p>
                </NuxtLink>
                <NuxtLink
                  :to="`/courses/${courseId}/remediation`"
                  class="p-3 bg-orange-50 dark:bg-orange-900/30 rounded-lg hover:bg-orange-100 dark:hover:bg-orange-900/50 transition-colors text-center"
                >
                  <Icon icon="heroicons:arrow-path" class="w-6 h-6 mx-auto text-orange-600 mb-1" />
                  <p class="text-xs font-medium text-orange-700 dark:text-orange-300">แก้ตัว</p>
                </NuxtLink>
              </div>
            </div>
          </div>
        </div>
      </template>

      <!-- Error State -->
      <div v-else class="text-center py-20">
        <Icon icon="heroicons:exclamation-triangle" class="w-16 h-16 mx-auto text-gray-300" />
        <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">ไม่สามารถโหลดข้อมูลได้</h3>
        <button
          @click="fetchDashboard"
          class="mt-4 px-4 py-2 bg-primary-500 text-white rounded-lg hover:bg-primary-600"
        >
          ลองอีกครั้ง
        </button>
      </div>
    </div>
  </div>
</template>
