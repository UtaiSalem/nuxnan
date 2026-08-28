<script setup lang="ts">
/**
 * Admin Dashboard
 * Dashboard สำหรับ Owner, Director, Admin ของโรงเรียน
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
const stats = ref({
  totalStudents: 0,
  totalTeachers: 0,
  totalCourses: 0,
  totalGroups: 0,
  pendingRequests: 0,
  activeToday: 0,
})
const recentActivities = ref<any[]>([])
const pendingRequests = ref<any[]>([])
const isLoading = ref(true)

// Academy Role
const academyId = ref<number | null>(null)
const { 
  myRole, 
  roleDisplayName, 
  roleColor, 
  roleIcon,
  can, 
  isOwner, 
  isAdmin,
  fetchMyRole 
} = useAcademyRole(academyId)

// Fetch data
onMounted(async () => {
  try {
    // Fetch academy info
    const response: any = await api.get(`/api/academies/${academyName.value}`)
    if (response.success) {
      academy.value = response.academy
      academyId.value = response.academy.id
      
      // Fetch role
      await fetchMyRole()
      
      // Check if user has admin access
      if (!isAdmin.value) {
        navigateTo(`/academies/${academyName.value}/dashboard`)
        return
      }
      
      // Fetch dashboard data
      await Promise.all([
        fetchStats(),
        fetchRecentActivities(),
        fetchPendingRequests(),
      ])
    }
  } catch (err) {
    console.error('Failed to load dashboard:', err)
  } finally {
    isLoading.value = false
  }
})

const fetchStats = async () => {
  if (!academyId.value) return
  
  try {
    // Get members count
    const membersRes: any = await api.get(`/api/academies/${academyId.value}/members`)
    if (membersRes.success) {
      const members = membersRes.members || []
      stats.value.totalStudents = members.filter((m: any) => m.role === 'student' || !m.role).length
      stats.value.totalTeachers = members.filter((m: any) => m.role === 'teacher').length
    }
    
    // Get courses count
    const coursesRes: any = await api.get(`/api/academies/${academyId.value}/courses`)
    if (coursesRes.success) {
      stats.value.totalCourses = coursesRes.courses?.length || 0
    }
    
    // Get groups count
    const groupsRes: any = await api.get(`/api/academies/${academyId.value}/groups`)
    if (groupsRes.success) {
      stats.value.totalGroups = groupsRes.groups?.length || 0
    }
  } catch (err) {
    console.error('Failed to fetch stats:', err)
  }
}

const fetchRecentActivities = async () => {
  if (!academyId.value) return
  
  try {
    const response: any = await api.get(`/api/academies/${academyId.value}/activities`)
    if (response.success) {
      recentActivities.value = (response.activities || []).slice(0, 5)
    }
  } catch (err) {
    console.error('Failed to fetch activities:', err)
  }
}

const fetchPendingRequests = async () => {
  if (!academyId.value) return
  
  try {
    const response: any = await api.get(`/api/academies/${academyId.value}/pending-requests`)
    if (response.success) {
      pendingRequests.value = response.pendingRequests || []
      stats.value.pendingRequests = pendingRequests.value.length
    }
  } catch (err) {
    console.error('Failed to fetch pending requests:', err)
  }
}

// Quick actions
const quickActions = computed(() => [
  {
    title: 'จัดการสมาชิก',
    icon: 'fluent:people-24-regular',
    to: `/academies/${academyName.value}/admin/members`,
    color: 'bg-blue-500',
    show: can('members.manage'),
  },
  {
    title: 'สร้างรายวิชา',
    icon: 'fluent:book-add-24-regular',
    to: `/academies/${academyName.value}/admin/courses/create`,
    color: 'bg-green-500',
    show: can('courses.create'),
  },
  {
    title: 'ประกาศใหม่',
    icon: 'fluent:megaphone-24-regular',
    to: `/academies/${academyName.value}/admin/announcements/create`,
    color: 'bg-amber-500',
    show: can('announcements.create'),
  },
  {
    title: 'ดูรายงาน',
    icon: 'fluent:chart-multiple-24-regular',
    to: `/academies/${academyName.value}/admin/reports`,
    color: 'bg-purple-500',
    show: can('reports.view'),
  },
  {
    title: 'ตั้งค่าโรงเรียน',
    icon: 'fluent:settings-24-regular',
    to: `/academies/${academyName.value}/admin/settings`,
    color: 'bg-gray-500',
    show: can('settings.manage'),
  },
  {
    title: 'จัดการบทบาท',
    icon: 'fluent:shield-person-24-regular',
    to: `/academies/${academyName.value}/admin/roles`,
    color: 'bg-indigo-500',
    show: can('members.roles.manage'),
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
            {{ academy?.name || 'Dashboard' }}
          </h1>
          <span :class="['px-3 py-1 rounded-full text-sm font-medium', roleColor]">
            <Icon :icon="roleIcon" class="w-4 h-4 inline mr-1" />
            {{ roleDisplayName }}
          </span>
        </div>
        <p class="text-gray-600 dark:text-gray-400">
          ภาพรวมการบริหารจัดการโรงเรียน
        </p>
      </div>

      <!-- Stats Grid -->
      <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-8">
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm">
          <div class="flex items-center gap-3">
            <div class="p-2 bg-emerald-100 dark:bg-emerald-900 rounded-lg">
              <Icon icon="fluent:hat-graduation-24-filled" class="w-6 h-6 text-emerald-600 dark:text-emerald-400" />
            </div>
            <div>
              <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.totalStudents }}</p>
              <p class="text-sm text-gray-500">นักเรียน</p>
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm">
          <div class="flex items-center gap-3">
            <div class="p-2 bg-blue-100 dark:bg-blue-900 rounded-lg">
              <Icon icon="fluent:person-board-24-filled" class="w-6 h-6 text-blue-600 dark:text-blue-400" />
            </div>
            <div>
              <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.totalTeachers }}</p>
              <p class="text-sm text-gray-500">ครู</p>
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm">
          <div class="flex items-center gap-3">
            <div class="p-2 bg-purple-100 dark:bg-purple-900 rounded-lg">
              <Icon icon="fluent:book-24-filled" class="w-6 h-6 text-purple-600 dark:text-purple-400" />
            </div>
            <div>
              <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.totalCourses }}</p>
              <p class="text-sm text-gray-500">รายวิชา</p>
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm">
          <div class="flex items-center gap-3">
            <div class="p-2 bg-cyan-100 dark:bg-cyan-900 rounded-lg">
              <Icon icon="fluent:people-community-24-filled" class="w-6 h-6 text-cyan-600 dark:text-cyan-400" />
            </div>
            <div>
              <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.totalGroups }}</p>
              <p class="text-sm text-gray-500">กลุ่ม/ห้อง</p>
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm">
          <div class="flex items-center gap-3">
            <div class="p-2 bg-amber-100 dark:bg-amber-900 rounded-lg">
              <Icon icon="fluent:person-clock-24-filled" class="w-6 h-6 text-amber-600 dark:text-amber-400" />
            </div>
            <div>
              <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.pendingRequests }}</p>
              <p class="text-sm text-gray-500">รอดำเนินการ</p>
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm">
          <div class="flex items-center gap-3">
            <div class="p-2 bg-green-100 dark:bg-green-900 rounded-lg">
              <Icon icon="fluent:pulse-24-filled" class="w-6 h-6 text-green-600 dark:text-green-400" />
            </div>
            <div>
              <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.activeToday }}</p>
              <p class="text-sm text-gray-500">ใช้งานวันนี้</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Quick Actions -->
      <div class="mb-8">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">การดำเนินการด่วน</h2>
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
          <template v-for="action in quickActions" :key="action.title">
            <NuxtLink
              v-if="action.show"
              :to="action.to"
              class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm hover:shadow-md transition-shadow flex flex-col items-center gap-3 text-center"
            >
              <div :class="['p-3 rounded-full text-white', action.color]">
                <Icon :icon="action.icon" class="w-6 h-6" />
              </div>
              <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ action.title }}</span>
            </NuxtLink>
          </template>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Pending Requests -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm">
          <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">คำขอรอดำเนินการ</h2>
            <NuxtLink 
              :to="`/academies/${academyName}/admin/members?tab=pending`"
              class="text-sm text-primary-500 hover:underline"
            >
              ดูทั้งหมด
            </NuxtLink>
          </div>
          <div class="p-4">
            <div v-if="pendingRequests.length === 0" class="text-center py-8 text-gray-500">
              <Icon icon="fluent:checkmark-circle-24-regular" class="w-12 h-12 mx-auto mb-2 text-green-500" />
              <p>ไม่มีคำขอรอดำเนินการ</p>
            </div>
            <div v-else class="space-y-3">
              <div 
                v-for="request in pendingRequests.slice(0, 5)" 
                :key="request.id"
                class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg"
              >
                <div class="flex items-center gap-3">
                  <img 
                    :src="request.user?.profile_photo_url || '/images/default-avatar.png'" 
                    class="w-10 h-10 rounded-full"
                    alt=""
                  />
                  <div>
                    <p class="font-medium text-gray-900 dark:text-white">{{ request.user?.name }}</p>
                    <p class="text-sm text-gray-500">ขอเข้าร่วม</p>
                  </div>
                </div>
                <div class="flex gap-2">
                  <button class="min-h-[44px] sm:min-h-0 px-3 py-1 bg-green-500 text-white rounded-lg text-sm hover:bg-green-600">
                    อนุมัติ
                  </button>
                  <button class="min-h-[44px] sm:min-h-0 px-3 py-1 bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-200 rounded-lg text-sm hover:bg-gray-300">
                    ปฏิเสธ
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Recent Activities -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm">
          <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">กิจกรรมล่าสุด</h2>
            <NuxtLink 
              :to="`/academies/${academyName}`"
              class="text-sm text-primary-500 hover:underline"
            >
              ดูทั้งหมด
            </NuxtLink>
          </div>
          <div class="p-4">
            <div v-if="recentActivities.length === 0" class="text-center py-8 text-gray-500">
              <Icon icon="fluent:document-24-regular" class="w-12 h-12 mx-auto mb-2" />
              <p>ยังไม่มีกิจกรรม</p>
            </div>
            <div v-else class="space-y-3">
              <div 
                v-for="activity in recentActivities" 
                :key="activity.id"
                class="flex items-start gap-3 p-3 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg transition-colors"
              >
                <img 
                  :src="activity.user?.profile_photo_url || '/images/default-avatar.png'" 
                  class="w-8 h-8 rounded-full"
                  alt=""
                />
                <div class="flex-1 min-w-0">
                  <p class="text-sm text-gray-900 dark:text-white">
                    <span class="font-medium">{{ activity.user?.name }}</span>
                    <span class="text-gray-500"> โพสต์ใหม่</span>
                  </p>
                  <p class="text-xs text-gray-500 truncate">{{ activity.content?.substring(0, 50) }}...</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Navigation Menu -->
      <div class="mt-8 bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4 sm:p-6">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">เมนูจัดการ</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
          <NuxtLink 
            v-if="can('members.view')"
            :to="`/academies/${academyName}/admin/members`"
            class="flex items-center gap-3 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors"
          >
            <Icon icon="fluent:people-24-regular" class="w-6 h-6 text-blue-500" />
            <span class="font-medium">สมาชิก</span>
          </NuxtLink>
          
          <NuxtLink 
            v-if="can('courses.view')"
            :to="`/academies/${academyName}/admin/courses`"
            class="flex items-center gap-3 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors"
          >
            <Icon icon="fluent:book-24-regular" class="w-6 h-6 text-purple-500" />
            <span class="font-medium">รายวิชา</span>
          </NuxtLink>
          
          <NuxtLink 
            v-if="can('students.view')"
            :to="`/academies/${academyName}/admin/students`"
            class="flex items-center gap-3 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors"
          >
            <Icon icon="fluent:hat-graduation-24-regular" class="w-6 h-6 text-emerald-500" />
            <span class="font-medium">นักเรียน</span>
          </NuxtLink>
          
          <NuxtLink 
            v-if="can('teachers.view')"
            :to="`/academies/${academyName}/admin/teachers`"
            class="flex items-center gap-3 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors"
          >
            <Icon icon="fluent:person-board-24-regular" class="w-6 h-6 text-blue-500" />
            <span class="font-medium">ครูอาจารย์</span>
          </NuxtLink>
          
          <NuxtLink 
            v-if="can('announcements.view')"
            :to="`/academies/${academyName}/admin/announcements`"
            class="flex items-center gap-3 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors"
          >
            <Icon icon="fluent:megaphone-24-regular" class="w-6 h-6 text-amber-500" />
            <span class="font-medium">ประกาศ</span>
          </NuxtLink>
          
          <NuxtLink 
            v-if="can('home_visits.view')"
            :to="`/academies/${academyName}/admin/home-visits`"
            class="flex items-center gap-3 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors"
          >
            <Icon icon="fluent:home-24-regular" class="w-6 h-6 text-rose-500" />
            <span class="font-medium">เยี่ยมบ้าน</span>
          </NuxtLink>
          
          <NuxtLink 
            v-if="can('finance.view')"
            :to="`/academies/${academyName}/admin/finance`"
            class="flex items-center gap-3 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors"
          >
            <Icon icon="fluent:money-24-regular" class="w-6 h-6 text-green-500" />
            <span class="font-medium">การเงิน</span>
          </NuxtLink>
          
          <NuxtLink 
            v-if="can('reports.view')"
            :to="`/academies/${academyName}/admin/reports`"
            class="flex items-center gap-3 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors"
          >
            <Icon icon="fluent:chart-multiple-24-regular" class="w-6 h-6 text-indigo-500" />
            <span class="font-medium">รายงาน</span>
          </NuxtLink>
        </div>
      </div>
    </div>
  </div>
</template>
