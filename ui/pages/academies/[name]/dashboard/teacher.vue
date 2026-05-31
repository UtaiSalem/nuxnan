<script setup lang="ts">
/**
 * Teacher Dashboard
 * Dashboard สำหรับครู/อาจารย์
 */

import { Icon } from '@iconify/vue'

definePageMeta({
  layout: 'main',
  middleware: ['auth']
})

const route = useRoute()
const api = useApi()

const academyName = computed(() => route.params.name as string)

// State
const academy = ref<any>(null)
const myCourses = ref<any[]>([])
const todaySchedule = ref<any[]>([])
const pendingAssignments = ref<any[]>([])
const recentStudentActivities = ref<any[]>([])
const isLoading = ref(true)

// Academy Role
const academyId = ref<number | null>(null)
const { 
  myRole, 
  roleDisplayName, 
  roleColor, 
  roleIcon,
  can,
  isTeacher,
  fetchMyRole 
} = useAcademyRole(academyId)

// Stats
const stats = ref({
  totalCourses: 0,
  totalStudents: 0,
  pendingGrading: 0,
  classesToday: 0,
})

const upcomingMeetings = ref<any[]>([])

const fetchUpcomingMeetings = async () => {
  if (!academyId.value) return
  
  try {
    const response: any = await schoolApi.teacherBookings(academyId.value, { status: 'confirmed', per_page: 5 })
    if (response.success) {
      upcomingMeetings.value = response.data || []
    }
  } catch (err) {
    console.error('Failed to fetch upcoming meetings:', err)
  }
}

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
      
      // Check if user is teacher
      if (!isTeacher.value && !can('courses.create')) {
        navigateTo(`/academies/${academyName.value}/dashboard`)
        return
      }
      
      // Fetch teacher dashboard data
      await Promise.all([
        fetchMyCourses(),
        fetchTodaySchedule(),
        fetchStats(),
        fetchUpcomingMeetings(),
      ])
    }
  } catch (err) {
    console.error('Failed to load teacher dashboard:', err)
  } finally {
    isLoading.value = false
  }
})

const fetchMyCourses = async () => {
  if (!academyId.value) return
  
  try {
    const response: any = await api.get(`/api/academies/${academyId.value}/courses`)
    if (response.success) {
      // Filter courses where user is teacher/admin
      myCourses.value = (response.courses || []).slice(0, 6)
      stats.value.totalCourses = myCourses.value.length
    }
  } catch (err) {
    console.error('Failed to fetch courses:', err)
  }
}

const fetchTodaySchedule = async () => {
  if (!academyId.value) return
  
  try {
    const response: any = await api.get(`/api/academies/${academyId.value}/schedules/today`)
    if (response.success) {
      todaySchedule.value = response.data || []
      stats.value.classesToday = todaySchedule.value.length
    }
  } catch (err) {
    console.error('Failed to fetch today schedule:', err)
  }
}

const schoolApi = useSchoolManagement()

// Fetch stats using schoolApi
const fetchStats = async () => {
  if (!academyId.value) return
  
  try {
    const response: any = await schoolApi.getDashboardStats(academyId.value)
    if (response.success) {
      const dashboardStats = response.data || {}
      stats.value.totalStudents = dashboardStats.total_students || 0
      stats.value.pendingGrading = dashboardStats.pending_grading || 0
      stats.value.classesToday = dashboardStats.classes_today || 0
    }
  } catch (err) {
    console.error('Failed to fetch stats:', err)
  }
}

// Quick actions for teacher
const quickActions = computed(() => [
  {
    title: 'รายวิชาของฉัน',
    icon: 'fluent:book-24-regular',
    to: `/academies/${academyName.value}/teacher/courses`,
    color: 'bg-purple-500',
  },
  {
    title: 'เช็คชื่อนักเรียน',
    icon: 'fluent:person-available-24-regular',
    to: `/academies/${academyName.value}/teacher/attendance`,
    color: 'bg-green-500',
  },
  {
    title: 'ให้คะแนน',
    icon: 'fluent:clipboard-checkmark-24-regular',
    to: `/academies/${academyName.value}/teacher/gradebook`,
    color: 'bg-blue-500',
  },
  {
    title: 'สร้างงาน/แบบทดสอบ',
    icon: 'fluent:document-add-24-regular',
    to: `/academies/${academyName.value}/teacher/assignments/create`,
    color: 'bg-amber-500',
  },
  {
    title: 'เยี่ยมบ้าน',
    icon: 'fluent:home-24-regular',
    to: `/academies/${academyName.value}/teacher/home-visits`,
    color: 'bg-rose-500',
    show: can('home_visits.create'),
  },
  {
    title: 'ประกาศ',
    icon: 'fluent:megaphone-24-regular',
    to: `/academies/${academyName.value}/teacher/announcements`,
    color: 'bg-cyan-500',
    show: can('announcements.create.own'),
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
            สวัสดี, ครู {{ $auth?.user?.name?.split(' ')[0] || 'ครู' }}
          </h1>
          <span :class="['px-3 py-1 rounded-full text-sm font-medium', roleColor]">
            <Icon :name="roleIcon" class="w-4 h-4 inline mr-1" />
            {{ roleDisplayName }}
          </span>
        </div>
        <p class="text-gray-600 dark:text-gray-400">
          {{ academy?.name }} • Dashboard สำหรับครูผู้สอน
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
              <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.totalCourses }}</p>
              <p class="text-sm text-gray-500">รายวิชา</p>
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm">
          <div class="flex items-center gap-3">
            <div class="p-2 bg-emerald-100 dark:bg-emerald-900 rounded-lg">
              <Icon name="fluent:people-24-filled" class="w-6 h-6 text-emerald-600 dark:text-emerald-400" />
            </div>
            <div>
              <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.totalStudents }}</p>
              <p class="text-sm text-gray-500">นักเรียนทั้งหมด</p>
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm">
          <div class="flex items-center gap-3">
            <div class="p-2 bg-amber-100 dark:bg-amber-900 rounded-lg">
              <Icon name="fluent:clipboard-clock-24-filled" class="w-6 h-6 text-amber-600 dark:text-amber-400" />
            </div>
            <div>
              <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.pendingGrading }}</p>
              <p class="text-sm text-gray-500">รอตรวจ</p>
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm">
          <div class="flex items-center gap-3">
            <div class="p-2 bg-blue-100 dark:bg-blue-900 rounded-lg">
              <Icon name="fluent:calendar-today-24-filled" class="w-6 h-6 text-blue-600 dark:text-blue-400" />
            </div>
            <div>
              <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.classesToday }}</p>
              <p class="text-sm text-gray-500">คาบวันนี้</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Quick Actions -->
      <div class="mb-8">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">การดำเนินการ</h2>
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
          <template v-for="action in quickActions" :key="action.title">
            <NuxtLink
              v-if="action.show !== false"
              :to="action.to"
              class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm hover:shadow-md transition-shadow flex flex-col items-center gap-3 text-center"
            >
              <div :class="['p-3 rounded-full text-white', action.color]">
                <Icon :name="action.icon" class="w-6 h-6" />
              </div>
              <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ action.title }}</span>
            </NuxtLink>
          </template>
        </div>
      </div>

      <!-- My Courses -->
      <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm mb-8">
        <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
          <h2 class="text-lg font-semibold text-gray-900 dark:text-white">รายวิชาของฉัน</h2>
          <NuxtLink 
            :to="`/academies/${academyName}/teacher/courses`"
            class="text-sm text-primary-500 hover:underline"
          >
            ดูทั้งหมด
          </NuxtLink>
        </div>
        <div class="p-4">
          <div v-if="myCourses.length === 0" class="text-center py-8 text-gray-500">
            <Icon name="fluent:book-24-regular" class="w-12 h-12 mx-auto mb-2" />
            <p>ยังไม่มีรายวิชา</p>
            <NuxtLink 
              v-if="can('courses.create')"
              :to="`/academies/${academyName}/courses/create`"
              class="mt-2 inline-block text-primary-500 hover:underline"
            >
              สร้างรายวิชาใหม่
            </NuxtLink>
          </div>
          <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <div 
              v-for="course in myCourses" 
              :key="course.id"
              class="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors cursor-pointer"
            >
              <NuxtLink :to="`/courses/${course.name}`">
                <div class="flex items-start gap-3">
                  <img 
                    :src="course.logo || '/images/default-course.png'" 
                    class="w-16 h-16 rounded-lg object-cover"
                    alt=""
                  />
                  <div class="flex-1 min-w-0">
                    <h3 class="font-medium text-gray-900 dark:text-white truncate">{{ course.title || course.name }}</h3>
                    <p class="text-sm text-gray-500 mt-1">
                      <Icon name="fluent:people-24-regular" class="w-4 h-4 inline" />
                      {{ course.enrolled_students_count || course.course_members_count || course.enrolled_students || 0 }} นักเรียน
                    </p>
                    <p class="text-sm text-gray-500">
                      <Icon name="fluent:book-24-regular" class="w-4 h-4 inline" />
                      {{ course.course_lessons_count ?? course.lessons_count ?? course.lessons ?? 0 }} บทเรียน
                    </p>
                  </div>
                </div>
              </NuxtLink>
            </div>
          </div>
        </div>
      </div>

      <!-- Today's Schedule -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm">
          <div class="p-4 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">ตารางสอนวันนี้</h2>
          </div>
          <div class="p-4">
            <div v-if="todaySchedule.length === 0" class="text-center py-8 text-gray-500">
              <Icon name="fluent:calendar-empty-24-regular" class="w-12 h-12 mx-auto mb-2" />
              <p>ไม่มีคาบสอนวันนี้</p>
            </div>
            <div v-else class="space-y-3">
              <div 
                v-for="schedule in todaySchedule" 
                :key="schedule.id"
                class="flex items-center gap-4 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg"
              >
                <div class="text-center min-w-[60px]">
                  <p class="text-lg font-bold text-primary-500">{{ schedule.time }}</p>
                </div>
                <div class="flex-1">
                  <p class="font-medium text-gray-900 dark:text-white">{{ schedule.subject }}</p>
                  <p class="text-sm text-gray-500">{{ schedule.room }} • {{ schedule.students }} คน</p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Pending Assignments -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm">
          <div class="p-4 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">งานรอตรวจ</h2>
          </div>
          <div class="p-4">
            <div v-if="pendingAssignments.length === 0" class="text-center py-8 text-gray-500">
              <Icon name="fluent:checkmark-circle-24-regular" class="w-12 h-12 mx-auto mb-2 text-green-500" />
              <p>ตรวจงานครบแล้ว!</p>
            </div>
            <div v-else class="space-y-3">
              <div 
                v-for="assignment in pendingAssignments" 
                :key="assignment.id"
                class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg"
              >
                <div>
                  <p class="font-medium text-gray-900 dark:text-white">{{ assignment.title }}</p>
                  <p class="text-sm text-gray-500">{{ assignment.course }} • {{ assignment.pending }} งานรอตรวจ</p>
                </div>
                <button class="px-3 py-1 bg-primary-500 text-white rounded-lg text-sm hover:bg-primary-600">
                  ตรวจงาน
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Upcoming Meetings -->
      <div class="mt-8 bg-white dark:bg-gray-800 rounded-xl shadow-sm">
        <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
          <h2 class="text-lg font-semibold text-gray-900 dark:text-white">การนัดหมายกับผู้ปกครอง</h2>
          <NuxtLink 
            :to="`/academies/${academyName}/teacher/meetings`"
            class="text-sm text-primary-500 hover:underline"
          >
            จัดการคิวนัดหมาย
          </NuxtLink>
        </div>
        <div class="p-4">
          <div v-if="upcomingMeetings.length === 0" class="text-center py-8 text-gray-500">
            <Icon name="fluent:calendar-clock-24-regular" class="w-12 h-12 mx-auto mb-2" />
            <p>ไม่มีการนัดหมายที่ยืนยันแล้ว</p>
          </div>
          <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <div 
              v-for="booking in upcomingMeetings" 
              :key="booking.id"
              class="p-4 border border-gray-100 dark:border-gray-700 rounded-xl bg-gray-50 dark:bg-gray-800/50"
            >
              <div class="flex items-center gap-3 mb-3">
                <CircleAvatar :src="booking.parent?.profile_photo_path" :name="booking.parent?.name" size="sm" />
                <div>
                  <p class="font-medium text-gray-900 dark:text-white">{{ booking.parent?.name }}</p>
                  <p class="text-xs text-gray-500">ผู้ปกครองของ {{ booking.student?.name }}</p>
                </div>
              </div>
              <div class="space-y-2">
                <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                  <Icon name="fluent:calendar-ltr-24-regular" class="w-4 h-4" />
                  <span>{{ new Date(booking.slot?.meeting_date).toLocaleDateString('th-TH') }}</span>
                </div>
                <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                  <Icon name="fluent:clock-24-regular" class="w-4 h-4" />
                  <span>{{ booking.slot?.start_time }} - {{ booking.slot?.end_time }}</span>
                </div>
                <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                  <Icon name="fluent:location-24-regular" class="w-4 h-4" />
                  <span class="truncate">{{ booking.slot?.location || 'ออนไลน์' }}</span>
                </div>
              </div>
              <div class="mt-4 pt-3 border-t border-gray-100 dark:border-gray-700 flex justify-between items-center">
                <span class="text-xs px-2 py-1 bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400 rounded-full font-medium">ยืนยันแล้ว</span>
                <button class="text-sm text-primary-500 hover:underline font-medium">ดูรายละเอียด</button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
