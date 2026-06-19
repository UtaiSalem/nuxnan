<script setup lang="ts">
/**
 * Student Dashboard
 * Dashboard สำหรับนักเรียน/นักศึกษา
 */

import { Icon } from '@iconify/vue'

definePageMeta({
  layout: 'main',
  middleware: ['auth']
})

const route = useRoute()
const api = useApi()
const { user } = storeToRefs(useAuthStore())

const academyName = computed(() => route.params.name as string)

// State
const academy = ref<any>(null)
const enrolledCourses = ref<any[]>([])
const upcomingAssignments = ref<any[]>([])
const recentGrades = ref<any[]>([])
const announcements = ref<any[]>([])
const isLoading = ref(true)

// Academy Role
const academyId = ref<number | null>(null)
const { 
  myRole, 
  roleDisplayName, 
  roleColor, 
  roleIcon,
  can,
  isStudent,
  fetchMyRole 
} = useAcademyRole(academyId)

// Stats
const stats = ref({
  enrolledCourses: 0,
  completedLessons: 0,
  totalPoints: 0,
  attendanceRate: 0,
})

const schoolApi = useSchoolManagement()

// Fetch data
onMounted(async () => {
  try {
    // Fetch academy info
    const response: any = await api.get(`/api/academies/${encodeURIComponent(academyName.value)}`)
    if (response.success) {
      academy.value = response.academy
      academyId.value = response.academy.id
      
      // Fetch role
      await fetchMyRole()
      
      // Fetch student dashboard data
      await Promise.all([
        fetchStats(),
        fetchEnrolledCourses(),
        fetchUpcomingAssignments(),
        fetchAnnouncements(),
      ])
    }
  } catch (err) {
    console.error('Failed to load student dashboard:', err)
  } finally {
    isLoading.value = false
  }
})

const fetchStats = async () => {
  if (!academyId.value) return
  
  try {
    const response: any = await schoolApi.getStudentDashboardStats(academyId.value)
    if (response.success) {
      const dashboardStats = response.data || {}
      stats.value.enrolledCourses = dashboardStats.enrolled_courses || 0
      stats.value.completedLessons = dashboardStats.completed_lessons || 0
      stats.value.totalPoints = dashboardStats.total_points || 0
      stats.value.attendanceRate = dashboardStats.attendance_rate || 0
    }
  } catch (err) {
    console.error('Failed to fetch student stats:', err)
  }
}

const fetchEnrolledCourses = async () => {
  if (!academyId.value) return
  
  try {
    // Get courses from academy where student is enrolled
    const response: any = await api.get(`/api/academies/${academyId.value}/courses`)
    if (response.success) {
      // Filter to enrolled courses only
      enrolledCourses.value = (response.courses || []).slice(0, 6)
    }
  } catch (err) {
    console.error('Failed to fetch courses:', err)
  }
}

const fetchUpcomingAssignments = async () => {
  try {
    const response: any = await api.get('/api/assignments', { 
      params: { 
        status: 'pending',
        per_page: 5
      } 
    })
    if (response.success) {
      upcomingAssignments.value = (response.data || []).map((asm: any) => ({
        id: asm.id,
        title: asm.title,
        course: asm.course?.title || 'วิชาทั่วไป',
        dueDate: asm.due_date ? new Date(asm.due_date).toLocaleDateString('th-TH') : 'ไม่มีกำหนด'
      }))
    }
  } catch (err) {
    console.error('Failed to fetch assignments:', err)
  }
}

const fetchAnnouncements = async () => {
  if (!academyId.value) return
  
  try {
    const response: any = await api.get(`/api/academies/${academyId.value}/activities`)
    if (response.success) {
      announcements.value = (response.activities || []).slice(0, 3)
    }
  } catch (err) {
    console.error('Failed to fetch announcements:', err)
  }
}

// Quick actions for student
const quickActions = computed(() => [
  {
    title: 'โปรไฟล์ของฉัน',
    icon: 'fluent:person-info-24-regular',
    to: `/academies/${academyName.value}/my-profile`,
    color: 'bg-indigo-500',
  },
  {
    title: 'หน้ารวมวิชา',
    icon: 'fluent:book-24-regular',
    to: `/academies/${academyName.value}`,
    color: 'bg-purple-500',
  },
  {
    title: 'ดูผลการเรียน',
    icon: 'fluent:document-checkmark-24-regular',
    to: `/academies/${academyName.value}/my-transcript`,
    color: 'bg-green-500',
  },
  {
    title: 'บัตรนักเรียน',
    icon: 'fluent:person-v-card-24-regular',
    to: `/academies/${academyName.value}/my-card`,
    color: 'bg-amber-500',
  },
  {
    title: 'ร้านค้าโรงเรียน',
    icon: 'fluent:building-shop-24-regular',
    to: `/academies/${academyName.value}/store`,
    color: 'bg-blue-500',
  },
])
</script>

<template>
  <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
    <!-- Loading -->
    <div v-if="isLoading" class="flex items-center justify-center min-h-screen">
      <div class="animate-spin rounded-full h-12 w-12 border-4 border-primary-500 border-t-transparent"></div>
    </div>

    <div v-else class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Header -->
      <div class="mb-8">
        <div class="flex items-center gap-4 mb-2">
          <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
            สวัสดี, {{ user?.name?.split(' ')[0] || 'นักเรียน' }} 👋
          </h1>
          <span :class="['px-3 py-1 rounded-full text-sm font-medium', roleColor]">
            <Icon :name="roleIcon" class="w-4 h-4 inline mr-1" />
            {{ roleDisplayName }}
          </span>
        </div>
        <p class="text-gray-600 dark:text-gray-400">
          {{ academy?.name }} • วันนี้เรามาเรียนรู้อะไรกันดี?
        </p>
      </div>

      <!-- Stats Grid -->
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm">
          <div class="flex items-center gap-3">
            <div class="p-2 bg-purple-100 dark:bg-purple-900 rounded-lg">
              <Icon name="fluent:book-24-filled" class="w-6 h-6 text-purple-600 dark:text-purple-400" />
            </div>
            <div>
              <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.enrolledCourses }}</p>
              <p class="text-sm text-gray-500">รายวิชา</p>
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm">
          <div class="flex items-center gap-3">
            <div class="p-2 bg-green-100 dark:bg-green-900 rounded-lg">
              <Icon name="fluent:checkmark-circle-24-filled" class="w-6 h-6 text-green-600 dark:text-green-400" />
            </div>
            <div>
              <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.completedLessons }}</p>
              <p class="text-sm text-gray-500">บทเรียนเสร็จ</p>
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm">
          <div class="flex items-center gap-3">
            <div class="p-2 bg-amber-100 dark:bg-amber-900 rounded-lg">
              <Icon name="fluent:star-24-filled" class="w-6 h-6 text-amber-600 dark:text-amber-400" />
            </div>
            <div>
              <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ user?.total_points?.toLocaleString() || 0 }}</p>
              <p class="text-sm text-gray-500">แต้มสะสม</p>
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm">
          <div class="flex items-center gap-3">
            <div class="p-2 bg-blue-100 dark:bg-blue-900 rounded-lg">
              <Icon name="fluent:person-available-24-filled" class="w-6 h-6 text-blue-600 dark:text-blue-400" />
            </div>
            <div>
              <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.attendanceRate }}%</p>
              <p class="text-sm text-gray-500">การเข้าเรียน</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Quick Actions -->
      <div class="mb-8">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">เข้าถึงด่วน</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
          <NuxtLink
            v-for="action in quickActions"
            :key="action.title"
            :to="action.to"
            class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm hover:shadow-md transition-shadow flex flex-col items-center gap-3 text-center"
          >
            <div :class="['p-3 rounded-full text-white', action.color]">
              <Icon :name="action.icon" class="w-6 h-6" />
            </div>
            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ action.title }}</span>
          </NuxtLink>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Enrolled Courses -->
        <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-xl shadow-sm">
          <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">รายวิชาของฉัน</h2>
            <NuxtLink 
              :to="`/academies/${academyName}`"
              class="text-sm text-primary-500 hover:underline"
            >
              ดูทั้งหมด
            </NuxtLink>
          </div>
          <div class="p-4">
            <div v-if="enrolledCourses.length === 0" class="text-center py-8 text-gray-500">
              <Icon name="fluent:book-search-24-regular" class="w-12 h-12 mx-auto mb-2" />
              <p>ยังไม่ได้ลงทะเบียนเรียน</p>
              <NuxtLink 
                :to="`/academies/${academyName}`"
                class="mt-2 inline-block text-primary-500 hover:underline"
              >
                ดูรายวิชาที่เปิดสอน
              </NuxtLink>
            </div>
            <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div 
                v-for="course in enrolledCourses" 
                :key="course.id"
                class="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors"
              >
                <NuxtLink :to="`/courses/${course.name}`" class="block">
                  <div class="flex items-start gap-3">
                    <img 
                      :src="course.logo || '/images/default-course.png'" 
                      class="w-16 h-16 rounded-lg object-cover"
                      alt=""
                    />
                    <div class="flex-1 min-w-0">
                      <h3 class="font-medium text-gray-900 dark:text-white truncate">{{ course.title || course.name }}</h3>
                      <p class="text-sm text-gray-500 mt-1">{{ course.course_lessons_count ?? course.lessons_count ?? course.lessons ?? 0 }} บทเรียน</p>
                      <!-- Progress bar -->
                      <div class="mt-2">
                        <div class="flex justify-between text-xs text-gray-500 mb-1">
                          <span>ความคืบหน้า</span>
                          <span>{{ course.progress || 0 }}%</span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-600 rounded-full h-1.5">
                          <div 
                            class="bg-primary-500 h-1.5 rounded-full transition-all"
                            :style="{ width: `${course.progress || 0}%` }"
                          ></div>
                        </div>
                      </div>
                    </div>
                  </div>
                </NuxtLink>
              </div>
            </div>
          </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
          <!-- Upcoming Assignments -->
          <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm">
            <div class="p-4 border-b border-gray-200 dark:border-gray-700">
              <h2 class="text-lg font-semibold text-gray-900 dark:text-white">งานที่ต้องส่ง</h2>
            </div>
            <div class="p-4">
              <div v-if="upcomingAssignments.length === 0" class="text-center py-4 text-gray-500">
                <Icon name="fluent:checkmark-circle-24-regular" class="w-8 h-8 mx-auto mb-2 text-green-500" />
                <p class="text-sm">ไม่มีงานค้าง!</p>
              </div>
              <div v-else class="space-y-3">
                <div 
                  v-for="assignment in upcomingAssignments" 
                  :key="assignment.id"
                  class="p-3 bg-gray-50 dark:bg-gray-700 rounded-lg"
                >
                  <p class="font-medium text-gray-900 dark:text-white text-sm">{{ assignment.title }}</p>
                  <p class="text-xs text-gray-500 mt-1">{{ assignment.course }}</p>
                  <p class="text-xs text-red-500 mt-1">
                    <Icon name="fluent:clock-24-regular" class="w-3 h-3 inline" />
                    กำหนดส่ง: {{ assignment.dueDate }}
                  </p>
                </div>
              </div>
            </div>
          </div>

          <!-- Announcements -->
          <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm">
            <div class="p-4 border-b border-gray-200 dark:border-gray-700">
              <h2 class="text-lg font-semibold text-gray-900 dark:text-white">ประกาศล่าสุด</h2>
            </div>
            <div class="p-4">
              <div v-if="announcements.length === 0" class="text-center py-4 text-gray-500">
                <p class="text-sm">ไม่มีประกาศใหม่</p>
              </div>
              <div v-else class="space-y-3">
                <div 
                  v-for="announcement in announcements" 
                  :key="announcement.id"
                  class="p-3 bg-gray-50 dark:bg-gray-700 rounded-lg"
                >
                  <p class="text-sm text-gray-900 dark:text-white line-clamp-2">
                    {{ announcement.content }}
                  </p>
                  <p class="text-xs text-gray-500 mt-2">
                    {{ new Date(announcement.created_at).toLocaleDateString('th-TH') }}
                  </p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
