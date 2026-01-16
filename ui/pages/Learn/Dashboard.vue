<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { Icon } from '@iconify/vue'
import { useAuthStore } from '~/stores/auth'

definePageMeta({
  layout: 'main',
  middleware: 'auth',
})

useHead({
  title: 'Learning Dashboard | Nuxnan',
})

const authStore = useAuthStore()

// State
const isLoading = ref(true)
const userCourses = ref<any[]>([])
const recentCourses = ref<any[]>([])
const upcomingAssignments = ref<any[]>([])
const recentActivities = ref<any[]>([])
const learningStats = ref({
  totalCourses: 0,
  activeCourses: 0,
  completedCourses: 0,
  totalLessons: 0,
  completedLessons: 0,
  totalAssignments: 0,
  completedAssignments: 0,
  totalQuizzes: 0,
  completedQuizzes: 0,
  attendanceRate: 0,
  averageScore: 0,
})

// Computed
const userName = computed(() => authStore.user?.name || 'ผู้ใช้')

const greeting = computed(() => {
  const hour = new Date().getHours()
  if (hour < 12) return 'สวัสดีตอนเช้า'
  if (hour < 18) return 'สวัสดีตอนบ่าย'
  return 'สวัสดีตอนค่ำ'
})

const currentDate = computed(() => {
  return new Date().toLocaleDateString('th-TH', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
})

const overallProgress = computed(() => {
  const total = learningStats.value.totalLessons + learningStats.value.totalAssignments + learningStats.value.totalQuizzes
  const completed = learningStats.value.completedLessons + learningStats.value.completedAssignments + learningStats.value.completedQuizzes
  if (total === 0) return 0
  return Math.round((completed / total) * 100)
})

const getProgressColor = (progress: number): string => {
  if (progress >= 80) return 'from-green-400 to-green-600'
  if (progress >= 60) return 'from-blue-400 to-blue-600'
  if (progress >= 40) return 'from-yellow-400 to-yellow-600'
  return 'from-red-400 to-red-600'
}

const getProgressStatus = (progress: number): { text: string, color: string } => {
  if (progress >= 80) return { text: 'ยอดเยี่ยม', color: 'text-green-600' }
  if (progress >= 60) return { text: 'ดีมาก', color: 'text-blue-600' }
  if (progress >= 40) return { text: 'ปานกลาง', color: 'text-yellow-600' }
  return { text: 'ต้องปรับปรุง', color: 'text-red-600' }
}

const formatDate = (date: string): string => {
  return new Date(date).toLocaleDateString('th-TH', {
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const formatTime = (time: string): string => {
  return time ? time.substring(0, 5) : '--:--'
}

// Methods
const loadDashboardData = async () => {
  try {
    isLoading.value = true
    
    // Simulate loading data (in real app, fetch from API)
    // This is a mock implementation - replace with actual API calls
    
    // Mock courses data
    userCourses.value = [
      {
        id: 1,
        name: 'คณิตศาสตร์พื้นฐาน',
        description: 'เรียนรู้จักคณิตศาสตร์พื้นฐาน ตั้งแต่ละ องค์ประกอบพื้นฐาน',
        cover: 'course1.jpg',
        lessons_count: 20,
        hours: 40,
        progress: 75,
        category: 'คณิตศาสตร์',
        isMember: true,
        auth_progress: 75,
      },
      {
        id: 2,
        name: 'ภาษาศาสตร์ 1',
        description: 'เรียนรู้จักภาษาศาสตร์ ตั้งแต่ละ องค์ประกอบภาษา',
        cover: 'course2.jpg',
        lessons_count: 15,
        hours: 30,
        progress: 60,
        category: 'ภาษาศาสตร์',
        isMember: true,
        auth_progress: 60,
      },
      {
        id: 3,
        name: 'คณิตศาสตร์ 2',
        description: 'ต่อยจากคณิตศาสตร์ 1 เรียนเรื่องเกี่ยวของฟังก์ชัน',
        cover: 'course3.jpg',
        lessons_count: 18,
        hours: 36,
        progress: 45,
        category: 'คณิตศาสตร์',
        isMember: true,
        auth_progress: 45,
      },
    ]
    
    recentCourses.value = userCourses.value.slice(0, 3)
    
    // Mock assignments
    upcomingAssignments.value = [
      {
        id: 1,
        title: 'การบ้านที่ 1: แก้ปัญหาสมการ',
        course: 'คณิตศาสตร์พื้นฐาน',
        due_date: new Date(Date.now() + 86400000 * 2).toISOString(),
        points: 100,
        status: 'pending',
      },
      {
        id: 2,
        title: 'แบบฝึกหัด: การออกแบบฝึกหัด',
        course: 'คณิตศาสตร์พื้นฐาน',
        due_date: new Date(Date.now() + 86400000 * 5).toISOString(),
        points: 50,
        status: 'pending',
      },
      {
        id: 3,
        title: 'การบ้านที่ 2: การประกอบฟังก์ชัน',
        course: 'คณิตศาสตร์พื้นฐาน',
        due_date: new Date(Date.now() + 86400000 * 7).toISOString(),
        points: 75,
        status: 'pending',
      },
    ]
    
    // Mock stats
    learningStats.value = {
      totalCourses: userCourses.value.length,
      activeCourses: userCourses.value.filter(c => c.progress < 100).length,
      completedCourses: userCourses.value.filter(c => c.progress >= 100).length,
      totalLessons: 53,
      completedLessons: 40,
      totalAssignments: 12,
      completedAssignments: 8,
      totalQuizzes: 6,
      completedQuizzes: 4,
      attendanceRate: 92,
      averageScore: 85,
    }
    
    // Mock activities
    recentActivities.value = [
      {
        id: 1,
        type: 'lesson',
        title: 'เรียนบทเรียน: บทนำ 5',
        course: 'คณิตศาสตร์พื้นฐาน',
        date: new Date(Date.now() - 3600000).toISOString(),
      },
      {
        id: 2,
        type: 'assignment',
        title: 'ส่งการบ้าน: แก้ปัญหาสมการ',
        course: 'คณิตศาสตร์พื้นฐาน',
        date: new Date(Date.now() - 7200000).toISOString(),
      },
      {
        id: 3,
        type: 'quiz',
        title: 'ทำแบบทดสอบ: แบบทดสอบที่ 3',
        course: 'คณิตศาสตร์พื้นฐาน',
        date: new Date(Date.now() - 10800000).toISOString(),
      },
      {
        id: 4,
        type: 'attendance',
        title: 'เข้าเรียน: เช็คชื่อวันที่ 15 ม.ค.',
        course: 'คณิตศาสตร์พื้นฐาน',
        date: new Date(Date.now() - 86400000).toISOString(),
      },
    ]
    
  } catch (error) {
    console.error('Failed to load dashboard data:', error)
  } finally {
    isLoading.value = false
  }
}

const getActivityIcon = (type: string): string => {
  const icons: Record<string, string> = {
    lesson: 'fluent:book-open-24-regular',
    assignment: 'fluent:document-text-24-regular',
    quiz: 'fluent:quiz-new-24-regular',
    attendance: 'fluent:calendar-clock-24-regular',
  }
  return icons[type] || 'fluent:info-24-regular'
}

const getActivityColor = (type: string): string => {
  const colors: Record<string, string> = {
    lesson: 'text-blue-500 bg-blue-100 dark:bg-blue-900/30',
    assignment: 'text-orange-500 bg-orange-100 dark:bg-orange-900/30',
    quiz: 'text-purple-500 bg-purple-100 dark:bg-purple-900/30',
    attendance: 'text-green-500 bg-green-100 dark:bg-green-900/30',
  }
  return colors[type] || 'text-gray-500 bg-gray-100 dark:bg-gray-900/30'
}

// Lifecycle
onMounted(() => {
  loadDashboardData()
})
</script>

<template>
  <div class="learning-dashboard min-h-screen bg-gray-50 dark:bg-gray-900">
    <!-- Header Section -->
    <div class="bg-gradient-to-r from-blue-600 via-indigo-600 to-purple-600 text-white">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
          <div>
            <h1 class="text-3xl md:text-4xl font-bold mb-2">
              {{ greeting }}, {{ userName }}! 👋
            </h1>
            <p class="text-white/80 text-lg">{{ currentDate }}</p>
          </div>
          <div class="flex items-center gap-3">
            <div class="bg-white/20 backdrop-blur-sm rounded-xl px-4 py-2">
              <p class="text-sm text-white/80">คอร์สที่เรียน</p>
              <p class="text-2xl font-bold">{{ learningStats.activeCourses }}</p>
            </div>
            <div class="bg-white/20 backdrop-blur-sm rounded-xl px-4 py-2">
              <p class="text-sm text-white/80">เกรดเฉลี่ย</p>
              <p class="text-2xl font-bold">{{ learningStats.averageScore }}%</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Stats Cards -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Overall Progress Card -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 hover:shadow-xl transition-shadow">
          <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl flex items-center justify-center">
              <Icon icon="fluent:chart-multiple-24-filled" class="w-6 h-6 text-white" />
            </div>
            <span class="text-xs text-gray-500 dark:text-gray-400">ความสำเร็จโดยรวม</span>
          </div>
          <p class="text-3xl font-bold text-gray-900 dark:text-white mb-1">{{ overallProgress }}%</p>
          <div class="flex items-center gap-2">
            <div class="flex-grow h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
              <div 
                class="h-full rounded-full transition-all duration-500"
                :class="`bg-gradient-to-r ${getProgressColor(overallProgress)}`"
                :style="{ width: `${overallProgress}%` }"
              ></div>
            </div>
            <span class="text-sm font-medium" :class="getProgressStatus(overallProgress).color">
              {{ getProgressStatus(overallProgress).text }}
            </span>
          </div>
        </div>

        <!-- Courses Card -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 hover:shadow-xl transition-shadow">
          <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 bg-gradient-to-br from-emerald-500 to-teal-600 rounded-xl flex items-center justify-center">
              <Icon icon="fluent:book-open-24-filled" class="w-6 h-6 text-white" />
            </div>
            <span class="text-xs text-gray-500 dark:text-gray-400">คอร์สทั้งหมด</span>
          </div>
          <p class="text-3xl font-bold text-gray-900 dark:text-white mb-1">{{ learningStats.totalCourses }}</p>
          <p class="text-sm text-gray-500 dark:text-gray-400">
            {{ learningStats.activeCourses }} กำลังเรียน
          </p>
        </div>

        <!-- Lessons Card -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 hover:shadow-xl transition-shadow">
          <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 bg-gradient-to-br from-violet-500 to-purple-600 rounded-xl flex items-center justify-center">
              <Icon icon="fluent:learning-app-24-filled" class="w-6 h-6 text-white" />
            </div>
            <span class="text-xs text-gray-500 dark:text-gray-400">บทเรียนทั้งหมด</span>
          </div>
          <p class="text-3xl font-bold text-gray-900 dark:text-white mb-1">{{ learningStats.totalLessons }}</p>
          <p class="text-sm text-gray-500 dark:text-gray-400">
            {{ learningStats.completedLessons }} บทเรียนแล้ว
          </p>
        </div>

        <!-- Assignments Card -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 hover:shadow-xl transition-shadow">
          <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 bg-gradient-to-br from-amber-500 to-orange-600 rounded-xl flex items-center justify-center">
              <Icon icon="fluent:clipboard-task-24-filled" class="w-6 h-6 text-white" />
            </div>
            <span class="text-xs text-gray-500 dark:text-gray-400">การบ้านทั้งหมด</span>
          </div>
          <p class="text-3xl font-bold text-gray-900 dark:text-white mb-1">{{ learningStats.totalAssignments }}</p>
          <p class="text-sm text-gray-500 dark:text-gray-400">
            {{ learningStats.completedAssignments }} ส่งแล้ว
          </p>
        </div>
      </div>

      <!-- Main Grid -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Left Column (2/3) -->
        <div class="lg:col-span-2 space-y-8">
          <!-- Quick Actions -->
          <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
              <Icon icon="fluent:lightning-24-filled" class="w-5 h-5 text-yellow-500" />
              การกระทำด่วน
            </h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
              <NuxtLink 
                to="/Learn/Courses"
                class="group bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-xl p-4 text-center hover:shadow-md transition-all hover:scale-105"
              >
                <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl flex items-center justify-center mx-auto mb-3 group-hover:scale-110 transition-transform">
                  <Icon icon="fluent:book-open-24-filled" class="w-6 h-6 text-white" />
                </div>
                <p class="font-semibold text-gray-900 dark:text-white text-sm">คอร์ส</p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">ดูทั้งหมด</p>
              </NuxtLink>

              <NuxtLink 
                to="/Learn/Courses/UserCourses"
                class="group bg-gradient-to-br from-emerald-50 to-teal-50 dark:from-emerald-900/20 dark:to-teal-900/20 rounded-xl p-4 text-center hover:shadow-md transition-all hover:scale-105"
              >
                <div class="w-12 h-12 bg-gradient-to-br from-emerald-500 to-teal-600 rounded-xl flex items-center justify-center mx-auto mb-3 group-hover:scale-110 transition-transform">
                  <Icon icon="fluent:learning-app-24-filled" class="w-6 h-6 text-white" />
                </div>
                <p class="font-semibold text-gray-900 dark:text-white text-sm">บทเรียน</p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">เข้าเรียน</p>
              </NuxtLink>

              <NuxtLink 
                to="/Learn/Courses/[id]/assignments"
                class="group bg-gradient-to-br from-amber-50 to-orange-50 dark:from-amber-900/20 dark:to-orange-900/20 rounded-xl p-4 text-center hover:shadow-md transition-all hover:scale-105"
              >
                <div class="w-12 h-12 bg-gradient-to-br from-amber-500 to-orange-600 rounded-xl flex items-center justify-center mx-auto mb-3 group-hover:scale-110 transition-transform">
                  <Icon icon="fluent:clipboard-task-24-filled" class="w-6 h-6 text-white" />
                </div>
                <p class="font-semibold text-gray-900 dark:text-white text-sm">การบ้าน</p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">ส่งงาน</p>
              </NuxtLink>

              <NuxtLink 
                to="/Learn/Courses/[id]/quizzes"
                class="group bg-gradient-to-br from-rose-50 to-pink-50 dark:from-rose-900/20 dark:to-pink-900/20 rounded-xl p-4 text-center hover:shadow-md transition-all hover:scale-105"
              >
                <div class="w-12 h-12 bg-gradient-to-br from-rose-500 to-pink-600 rounded-xl flex items-center justify-center mx-auto mb-3 group-hover:scale-110 transition-transform">
                  <Icon icon="fluent:quiz-new-24-filled" class="w-6 h-6 text-white" />
                </div>
                <p class="font-semibold text-gray-900 dark:text-white text-sm">แบบทดสอบ</p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">ทำแบบทดสอบ</p>
              </NuxtLink>
            </div>
          </div>

          <!-- Recent Courses -->
          <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
            <div class="flex items-center justify-between mb-6">
              <h2 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                <Icon icon="fluent:book-open-24-filled" class="w-5 h-5 text-blue-500" />
                คอร์สล่าสุด
              </h2>
              <NuxtLink to="/Learn/Courses/UserCourses" class="text-primary-500 hover:text-primary-600 text-sm font-medium">
                ดูทั้งหมด →
              </NuxtLink>
            </div>

            <div v-if="recentCourses.length > 0" class="space-y-4">
              <div 
                v-for="course in recentCourses" 
                :key="course.id"
                class="flex items-start gap-4 p-4 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors border border-gray-100 dark:border-gray-700"
              >
                <div class="w-16 h-16 rounded-xl overflow-hidden flex-shrink-0">
                  <div class="w-full h-full bg-gradient-to-br from-blue-400 to-indigo-500 flex items-center justify-center">
                    <Icon icon="fluent:book-open-24-filled" class="w-8 h-8 text-white" />
                  </div>
                </div>
                <div class="flex-grow min-w-0">
                  <h3 class="font-semibold text-gray-900 dark:text-white text-sm mb-1">
                    {{ course.name }}
                  </h3>
                  <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">
                    {{ course.category }}
                  </p>
                  <div class="flex items-center gap-4">
                    <div class="flex items-center gap-1 text-xs text-gray-500 dark:text-gray-400">
                      <Icon icon="fluent:book-open-24-regular" class="w-4 h-4" />
                      <span>{{ course.lessons_count }} บทเรียน</span>
                    </div>
                    <div class="flex items-center gap-1 text-xs text-gray-500 dark:text-gray-400">
                      <Icon icon="fluent:clock-24-regular" class="w-4 h-4" />
                      <span>{{ course.hours }} ชม.</span>
                    </div>
                  </div>
                  <div class="w-full h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                    <div 
                      class="h-full bg-gradient-to-r from-blue-400 to-indigo-500 rounded-full transition-all duration-500"
                      :style="{ width: `${course.progress}%` }"
                    ></div>
                  </div>
                </div>
              </div>
            </div>

            <div v-else class="text-center py-8 text-gray-500 dark:text-gray-400">
              <Icon icon="fluent:book-open-24-regular" class="w-12 h-12 mx-auto mb-3" />
              <p>ยังไม่มีคอร์ส</p>
              <p class="text-sm mt-1">เริ่มลงทะเบียนคอร์สแรก</p>
            </div>
          </div>

          <!-- Upcoming Assignments -->
          <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
            <div class="flex items-center justify-between mb-6">
              <h2 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                <Icon icon="fluent:clipboard-task-24-filled" class="w-5 h-5 text-orange-500" />
                การบ้านที่ต้องส่ง
              </h2>
              <span class="text-xs bg-orange-100 dark:bg-orange-900/30 text-orange-600 dark:text-orange-400 px-3 py-1 rounded-full">
                {{ upcomingAssignments.length }} รายการ
              </span>
            </div>

            <div v-if="upcomingAssignments.length > 0" class="space-y-3">
              <div 
                v-for="assignment in upcomingAssignments" 
                :key="assignment.id"
                class="flex items-start gap-4 p-4 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors border border-gray-100 dark:border-gray-700"
              >
                <div class="w-12 h-12 rounded-xl overflow-hidden flex-shrink-0">
                  <div class="w-full h-full bg-gradient-to-br from-orange-400 to-amber-500 flex items-center justify-center">
                    <Icon icon="fluent:document-text-24-filled" class="w-6 h-6 text-white" />
                  </div>
                </div>
                <div class="flex-grow min-w-0">
                  <h3 class="font-semibold text-gray-900 dark:text-white text-sm mb-1">
                    {{ assignment.title }}
                  </h3>
                  <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">
                    {{ assignment.course }}
                  </p>
                  <div class="flex items-center gap-3">
                    <div class="flex items-center gap-1 text-xs text-gray-500 dark:text-gray-400">
                      <Icon icon="fluent:calendar-clock-24-regular" class="w-4 h-4" />
                      <span>กำหนดส่ง: {{ formatDate(assignment.due_date) }}</span>
                    </div>
                    <div class="flex items-center gap-1 text-xs text-amber-600 dark:text-amber-400 font-medium">
                      <Icon icon="fluent:star-24-filled" class="w-4 h-4" />
                      <span>{{ assignment.points }} คะแนน</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div v-else class="text-center py-8 text-gray-500 dark:text-gray-400">
              <Icon icon="fluent:checkmark-circle-24-regular" class="w-12 h-12 mx-auto mb-3" />
              <p>ไม่มีการบ้านที่ต้องส่ง</p>
              <p class="text-sm mt-1">เยี่ยม! คุณทำการบ้านครบแล้ว</p>
            </div>
          </div>
        </div>

        <!-- Right Column (1/3) -->
        <div class="space-y-8">
          <!-- Learning Progress Widget -->
          <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
              <Icon icon="fluent:chart-multiple-24-filled" class="w-5 h-5 text-violet-500" />
              สถิติการเรียน
            </h2>
            <div class="space-y-4">
              <div class="flex items-center justify-between">
                <span class="text-sm text-gray-600 dark:text-gray-400">เข้าเรียน</span>
                <span class="text-sm font-bold text-gray-900 dark:text-white">{{ learningStats.attendanceRate }}%</span>
              </div>
              <div class="flex items-center justify-between">
                <span class="text-sm text-gray-600 dark:text-gray-400">เกรดเฉลี่ย</span>
                <span class="text-sm font-bold text-gray-900 dark:text-white">{{ learningStats.averageScore }}%</span>
              </div>
              <div class="flex items-center justify-between">
                <span class="text-sm text-gray-600 dark:text-gray-400">คอร์สที่เรียน</span>
                <span class="text-sm font-bold text-gray-900 dark:text-white">{{ learningStats.activeCourses }}</span>
              </div>
              <div class="flex items-center justify-between">
                <span class="text-sm text-gray-600 dark:text-gray-400">คอร์สที่เสร็จแล้ว</span>
                <span class="text-sm font-bold text-gray-900 dark:text-white">{{ learningStats.completedCourses }}</span>
              </div>
            </div>
          </div>

          <!-- Recent Activity Widget -->
          <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
              <Icon icon="fluent:history-24-filled" class="w-5 h-5 text-blue-500" />
              กิจกรรมล่าสุด
            </h2>
            <div v-if="recentActivities.length > 0" class="space-y-3">
              <div 
                v-for="activity in recentActivities" 
                :key="activity.id"
                class="flex items-center gap-3 p-3 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"
              >
                <div 
                  class="w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0"
                  :class="getActivityColor(activity.type)"
                >
                  <Icon :icon="getActivityIcon(activity.type)" class="w-5 h-5" />
                </div>
                <div class="flex-grow min-w-0">
                  <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                    {{ activity.title }}
                  </p>
                  <p class="text-xs text-gray-500 dark:text-gray-400">
                    {{ activity.course }} • {{ formatDate(activity.date) }}
                  </p>
                </div>
              </div>
            </div>

            <div v-else class="text-center py-4 text-gray-500 dark:text-gray-400">
              <Icon icon="fluent:history-24-regular" class="w-8 h-8 mx-auto mb-2" />
              <p class="text-sm">ยังไม่มีกิจกรรม</p>
            </div>
          </div>

          <!-- Tips Widget -->
          <div class="bg-gradient-to-br from-blue-600 via-indigo-600 to-purple-600 rounded-2xl shadow-lg p-6 text-white">
            <div class="flex items-center gap-2 mb-4">
              <Icon icon="fluent:lightbulb-24-filled" class="w-5 h-5 text-yellow-300" />
              <h3 class="font-bold">เคล็ดลับการเรียน</h3>
            </div>
            <ul class="space-y-2 text-sm text-white/90">
              <li class="flex items-start gap-2">
                <Icon icon="fluent:checkmark-circle-24-filled" class="w-4 h-4 flex-shrink-0 mt-0.5" />
                <span>เข้าเรียนสม่วยเพื่อรักษาความสำเร็จ</span>
              </li>
              <li class="flex items-start gap-2">
                <Icon icon="fluent:checkmark-circle-24-filled" class="w-4 h-4 flex-shrink-0 mt-0.5" />
                <span>ทำการบ้านและแบบทดสอบตรงเวลา</span>
              </li>
              <li class="flex items-start gap-2">
                <Icon icon="fluent:checkmark-circle-24-filled" class="w-4 h-4 flex-shrink-0 mt-0.5" />
                <span>ทบททวนเรียนอย่างละเอียย</span>
              </li>
              <li class="flex items-start gap-2">
                <Icon icon="fluent:checkmark-circle-24-filled" class="w-4 h-4 flex-shrink-0 mt-0.5" />
                <span>ถามปัญหาหากไม่เข้าใจ ถามครู</span>
              </li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.learning-dashboard > div > div {
  animation: fadeIn 0.5s ease-out;
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
</style>
