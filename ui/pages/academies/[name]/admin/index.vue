<script setup lang="ts">
/**
 * Academy Admin Dashboard - Main Page
 * หน้า Dashboard หลักของ Admin Panel
 */
import { Icon } from '@iconify/vue'

definePageMeta({
  layout: false
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
const { can, isAdmin, fetchMyRole } = useAcademyRole(academyId)

onMounted(async () => {
  try {
    const response: any = await api.get(`/api/academies/${encodeURIComponent(academyName.value)}`)
    if (response.success) {
      academy.value = response.academy
      academyId.value = response.academy.id
      await fetchMyRole()
      
      if (!isAdmin.value) {
        navigateTo(`/academies/${academyName.value}`)
        return
      }
      
      await Promise.all([
        fetchStats(),
        fetchPendingRequests(),
        fetchRecentActivities(),
      ])
    }
  } catch (err) {
    console.error('Failed to load:', err)
  } finally {
    isLoading.value = false
  }
})

const fetchStats = async () => {
  if (!academyId.value) return
  
  try {
    // Get member stats
    const statsRes: any = await api.get(`/api/academies/${academyId.value}/members/stats`)
    if (statsRes.success) {
      stats.value.totalStudents = statsRes.stats.approved || 0
      stats.value.pendingRequests = statsRes.stats.pending || 0
    }
    
    // Get courses
    const coursesRes: any = await api.get(`/api/academies/${academyId.value}/courses`)
    if (coursesRes.success) {
      stats.value.totalCourses = coursesRes.courses?.length || 0
    }
    
    // Get groups
    const groupsRes: any = await api.get(`/api/academies/${academyId.value}/groups`)
    if (groupsRes.success) {
      stats.value.totalGroups = groupsRes.groups?.length || 0
    }
  } catch (err) {
    console.error('Failed to fetch stats:', err)
  }
}

const fetchPendingRequests = async () => {
  if (!academyId.value) return
  
  try {
    const response: any = await api.get(`/api/academies/${academyId.value}/pending-requests`)
    if (response.success) {
      pendingRequests.value = (response.pendingRequests || []).slice(0, 5)
    }
  } catch (err) {
    console.error('Failed to fetch pending:', err)
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

const quickActions = computed(() => [
  {
    title: 'จัดการสมาชิก',
    description: 'ดูและจัดการสมาชิกทั้งหมด',
    icon: 'fluent:people-24-filled',
    to: `/academies/${academyName.value}/admin/members`,
    color: 'from-blue-500 to-blue-600',
    show: can('members.view'),
  },
  {
    title: 'สร้างรายวิชา',
    description: 'สร้างรายวิชาใหม่',
    icon: 'fluent:book-add-24-filled',
    to: `/academies/${academyName.value}/admin/courses/create`,
    color: 'from-green-500 to-green-600',
    show: can('courses.create'),
  },
  {
    title: 'จัดการกลุ่ม',
    description: 'ห้องเรียนและกลุ่มต่างๆ',
    icon: 'fluent:people-community-24-filled',
    to: `/academies/${academyName.value}/admin/groups`,
    color: 'from-purple-500 to-purple-600',
    show: true,
  },
  {
    title: 'ประกาศใหม่',
    description: 'สร้างประกาศถึงสมาชิก',
    icon: 'fluent:megaphone-24-filled',
    to: `/academies/${academyName.value}/admin/announcements/create`,
    color: 'from-amber-500 to-amber-600',
    show: can('announcements.create'),
  },
  {
    title: 'ดูรายงาน',
    description: 'รายงานและสถิติ',
    icon: 'fluent:chart-multiple-24-filled',
    to: `/academies/${academyName.value}/admin/reports`,
    color: 'from-cyan-500 to-cyan-600',
    show: can('reports.view'),
  },
  {
    title: 'ตั้งค่าโรงเรียน',
    description: 'แก้ไขข้อมูลโรงเรียน',
    icon: 'fluent:settings-24-filled',
    to: `/academies/${academyName.value}/admin/settings`,
    color: 'from-gray-500 to-gray-600',
    show: can('academy.settings.edit'),
  },
])

const acceptRequest = async (memberId: number) => {
  try {
    await api.post(`/api/academies/${academyId.value}/members/${memberId}/accept`)
    pendingRequests.value = pendingRequests.value.filter(r => r.id !== memberId)
    stats.value.pendingRequests--
    stats.value.totalStudents++
  } catch (err) {
    console.error('Failed to accept:', err)
  }
}

const rejectRequest = async (memberId: number) => {
  try {
    await api.post(`/api/academies/${academyId.value}/members/${memberId}/reject`)
    pendingRequests.value = pendingRequests.value.filter(r => r.id !== memberId)
    stats.value.pendingRequests--
  } catch (err) {
    console.error('Failed to reject:', err)
  }
}
</script>

<template>
  <NuxtLayout name="academy-admin" :academy-name="academyName">
    <div v-if="isLoading" class="flex items-center justify-center py-20">
      <div class="animate-spin rounded-full h-10 w-10 border-4 border-primary-500 border-t-transparent"></div>
    </div>

    <div v-else class="space-y-8">
      <!-- Header -->
      <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Dashboard</h1>
        <p class="text-gray-600 dark:text-gray-400 mt-1">ภาพรวมการบริหารจัดการโรงเรียน</p>
      </div>

      <!-- Stats Cards -->
      <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow-sm border border-gray-100 dark:border-gray-700">
          <div class="flex items-center gap-4">
            <div class="p-3 bg-emerald-100 dark:bg-emerald-900/50 rounded-xl">
              <Icon name="fluent:hat-graduation-24-filled" class="w-6 h-6 text-emerald-600 dark:text-emerald-400" />
            </div>
            <div>
              <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.totalStudents }}</p>
              <p class="text-sm text-gray-500 dark:text-gray-400">นักเรียน</p>
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow-sm border border-gray-100 dark:border-gray-700">
          <div class="flex items-center gap-4">
            <div class="p-3 bg-blue-100 dark:bg-blue-900/50 rounded-xl">
              <Icon name="fluent:person-board-24-filled" class="w-6 h-6 text-blue-600 dark:text-blue-400" />
            </div>
            <div>
              <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.totalTeachers }}</p>
              <p class="text-sm text-gray-500 dark:text-gray-400">ครู/อาจารย์</p>
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow-sm border border-gray-100 dark:border-gray-700">
          <div class="flex items-center gap-4">
            <div class="p-3 bg-purple-100 dark:bg-purple-900/50 rounded-xl">
              <Icon name="fluent:book-24-filled" class="w-6 h-6 text-purple-600 dark:text-purple-400" />
            </div>
            <div>
              <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.totalCourses }}</p>
              <p class="text-sm text-gray-500 dark:text-gray-400">รายวิชา</p>
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow-sm border border-gray-100 dark:border-gray-700">
          <div class="flex items-center gap-4">
            <div class="p-3 bg-cyan-100 dark:bg-cyan-900/50 rounded-xl">
              <Icon name="fluent:people-community-24-filled" class="w-6 h-6 text-cyan-600 dark:text-cyan-400" />
            </div>
            <div>
              <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.totalGroups }}</p>
              <p class="text-sm text-gray-500 dark:text-gray-400">กลุ่ม/ห้อง</p>
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow-sm border border-gray-100 dark:border-gray-700">
          <div class="flex items-center gap-4">
            <div class="p-3 bg-amber-100 dark:bg-amber-900/50 rounded-xl">
              <Icon name="fluent:person-clock-24-filled" class="w-6 h-6 text-amber-600 dark:text-amber-400" />
            </div>
            <div>
              <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.pendingRequests }}</p>
              <p class="text-sm text-gray-500 dark:text-gray-400">รอดำเนินการ</p>
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow-sm border border-gray-100 dark:border-gray-700">
          <div class="flex items-center gap-4">
            <div class="p-3 bg-green-100 dark:bg-green-900/50 rounded-xl">
              <Icon name="fluent:pulse-24-filled" class="w-6 h-6 text-green-600 dark:text-green-400" />
            </div>
            <div>
              <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.activeToday }}</p>
              <p class="text-sm text-gray-500 dark:text-gray-400">ใช้งานวันนี้</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Quick Actions -->
      <div>
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">การดำเนินการด่วน</h2>
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
          <template v-for="action in quickActions" :key="action.title">
            <NuxtLink
              v-if="action.show"
              :to="action.to"
              class="group bg-white dark:bg-gray-800 rounded-xl p-5 shadow-sm border border-gray-100 dark:border-gray-700 hover:shadow-lg transition-all duration-200 hover:-translate-y-1"
            >
              <div :class="['w-12 h-12 rounded-xl bg-gradient-to-br flex items-center justify-center mb-4', action.color]">
                <Icon :name="action.icon" class="w-6 h-6 text-white" />
              </div>
              <h3 class="font-semibold text-gray-900 dark:text-white group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors">
                {{ action.title }}
              </h3>
              <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ action.description }}</p>
            </NuxtLink>
          </template>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Pending Requests -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700">
          <div class="flex items-center justify-between p-5 border-b border-gray-100 dark:border-gray-700">
            <h3 class="font-semibold text-gray-900 dark:text-white flex items-center gap-2">
              <Icon name="fluent:person-add-24-regular" class="w-5 h-5 text-amber-500" />
              คำขอเข้าร่วม
            </h3>
            <NuxtLink 
              :to="`/academies/${academyName}/admin/requests`"
              class="text-sm text-primary-600 dark:text-primary-400 hover:underline"
            >
              ดูทั้งหมด
            </NuxtLink>
          </div>
          
          <div v-if="pendingRequests.length === 0" class="p-8 text-center">
            <Icon name="fluent:checkmark-circle-24-regular" class="w-12 h-12 mx-auto text-green-500 mb-3" />
            <p class="text-gray-500 dark:text-gray-400">ไม่มีคำขอรอดำเนินการ</p>
          </div>
          
          <div v-else class="divide-y divide-gray-100 dark:divide-gray-700">
            <div v-for="request in pendingRequests" :key="request.id" class="p-4 flex items-center gap-4">
              <img 
                :src="request.user?.profile_photo_url || '/images/default-avatar.png'" 
                :alt="request.user?.name"
                class="w-10 h-10 rounded-full object-cover"
              />
              <div class="flex-1 min-w-0">
                <p class="font-medium text-gray-900 dark:text-white truncate">{{ request.user?.name }}</p>
                <p class="text-sm text-gray-500 dark:text-gray-400 truncate">{{ request.user?.email }}</p>
              </div>
              <div class="flex items-center gap-2">
                <button 
                  @click="acceptRequest(request.id)"
                  class="p-2 text-green-600 hover:bg-green-50 dark:hover:bg-green-900/30 rounded-lg transition-colors"
                >
                  <Icon name="fluent:checkmark-24-filled" class="w-5 h-5" />
                </button>
                <button 
                  @click="rejectRequest(request.id)"
                  class="p-2 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-lg transition-colors"
                >
                  <Icon name="fluent:dismiss-24-filled" class="w-5 h-5" />
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Recent Activities -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700">
          <div class="flex items-center justify-between p-5 border-b border-gray-100 dark:border-gray-700">
            <h3 class="font-semibold text-gray-900 dark:text-white flex items-center gap-2">
              <Icon name="fluent:history-24-regular" class="w-5 h-5 text-blue-500" />
              กิจกรรมล่าสุด
            </h3>
          </div>
          
          <div v-if="recentActivities.length === 0" class="p-8 text-center">
            <Icon name="fluent:calendar-empty-24-regular" class="w-12 h-12 mx-auto text-gray-400 mb-3" />
            <p class="text-gray-500 dark:text-gray-400">ยังไม่มีกิจกรรม</p>
          </div>
          
          <div v-else class="divide-y divide-gray-100 dark:divide-gray-700">
            <div v-for="activity in recentActivities" :key="activity.id" class="p-4 flex items-start gap-4">
              <div class="p-2 bg-gray-100 dark:bg-gray-700 rounded-lg">
                <Icon name="fluent:flash-24-regular" class="w-5 h-5 text-gray-600 dark:text-gray-400" />
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-gray-900 dark:text-white">{{ activity.description || activity.title }}</p>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ activity.created_at }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </NuxtLayout>
</template>
