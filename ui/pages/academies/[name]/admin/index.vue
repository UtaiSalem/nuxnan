<script setup lang="ts">
/**
 * Academy Admin Dashboard - Main Page
 * หน้า Dashboard หลักของ Admin Panel
 */
import { Icon } from '@iconify/vue'
import AcademyAdminRevenueWidget from '~/components/academy/revenue/AcademyAdminRevenueWidget.vue'

definePageMeta({
  layout: 'main',
  ssr: false // Disable SSR to avoid hydration issues with auth
})

const route = useRoute()
const api = useApi()
const academyName = computed(() => route.params.name as string)

// State
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
const { can } = useAcademyRole(academyId)

onMounted(async () => {
  try {
    // Get academy ID first
    const response: any = await api.get(`/api/academies/${academyName.value}`)
    if (response.success) {
      academyId.value = response.academy.id
      
      // Fetch dashboard data
      await Promise.all([
        fetchStats(),
        fetchPendingRequests(),
        fetchRecentActivities(),
      ])
    }
  } catch (err: any) {
    console.error('Failed to load dashboard data:', err)
  } finally {
    isLoading.value = false
  }
})

const fetchStats = async () => {
  if (!academyId.value) return
  
  try {
    // Get member stats (includes role-based counts)
    const statsRes: any = await api.get(`/api/academies/${academyId.value}/members/stats`)
    if (statsRes.success) {
      stats.value.totalStudents = statsRes.stats.approved || 0
      stats.value.pendingRequests = statsRes.stats.pending || 0
      // Get teacher count from role stats if available
      if (statsRes.stats.by_role) {
        stats.value.totalTeachers = statsRes.stats.by_role.teacher || 0
      }
    }
    
    // Get courses
    const coursesRes: any = await api.get(`/api/academies/${academyId.value}/courses`)
    if (coursesRes.success) {
      stats.value.totalCourses = coursesRes.courses?.length || 0
    }
    
    // Get groups
    try {
      const groupsRes: any = await api.get(`/api/academies/${academyId.value}/groups`)
      if (groupsRes.success) {
        stats.value.totalGroups = groupsRes.groups?.length || 0
      }
    } catch (e) {
      // Silently handle if groups endpoint fails
      console.warn('Groups endpoint unavailable')
    }
    
    // Get teacher count directly if not in stats
    if (!stats.value.totalTeachers) {
      try {
        const teachersRes: any = await api.get(`/api/academies/${academyId.value}/members`, {
          params: { role: 'teacher', per_page: 1 }
        })
        if (teachersRes.success && teachersRes.meta?.total) {
          stats.value.totalTeachers = teachersRes.meta.total
        }
      } catch (e) {
        // Silently fail if this endpoint doesn't support role filter
      }
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
      const activities = response.activities
      recentActivities.value = Array.isArray(activities) ? activities.slice(0, 5) : []
    }
  } catch (err) {
    console.error('Failed to fetch activities:', err)
  }
}

const statCards = computed(() => [
  {
    label: 'นักเรียน',
    caption: 'สมาชิกที่อนุมัติแล้ว',
    value: stats.value.totalStudents,
    icon: 'fluent:hat-graduation-24-filled',
    tone: 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400',
    to: `/academies/${academyName.value}/admin/students`,
  },
  {
    label: 'ครู/อาจารย์',
    caption: 'บุคลากรในระบบ',
    value: stats.value.totalTeachers,
    icon: 'fluent:person-board-24-filled',
    tone: 'bg-blue-500/10 text-blue-600 dark:text-blue-400',
    to: `/academies/${academyName.value}/admin/staff`,
  },
  {
    label: 'รายวิชา',
    caption: 'รายวิชาทั้งหมด',
    value: stats.value.totalCourses,
    icon: 'fluent:book-24-filled',
    tone: 'bg-purple-500/10 text-purple-600 dark:text-purple-400',
    to: `/academies/${academyName.value}/admin/courses`,
  },
  {
    label: 'กลุ่ม/ห้อง',
    caption: 'ห้องเรียนและกลุ่ม',
    value: stats.value.totalGroups,
    icon: 'fluent:people-community-24-filled',
    tone: 'bg-cyan-500/10 text-cyan-600 dark:text-cyan-400',
    to: `/academies/${academyName.value}/admin/classrooms`,
  },
  {
    label: 'รอดำเนินการ',
    caption: 'คำขอเข้าร่วมใหม่',
    value: stats.value.pendingRequests,
    icon: 'fluent:person-clock-24-filled',
    tone: 'bg-amber-500/10 text-amber-600 dark:text-amber-400',
    to: `/academies/${academyName.value}/admin/requests`,
  },
  {
    label: 'ใช้งานวันนี้',
    caption: 'สมาชิกที่ใช้งานล่าสุด',
    value: stats.value.activeToday,
    icon: 'fluent:pulse-24-filled',
    tone: 'bg-green-500/10 text-green-600 dark:text-green-400',
    to: `/academies/${academyName.value}/admin/activity-log`,
  },
])

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
    to: `/academies/${academyName.value}/admin/classrooms`,
    color: 'from-purple-500 to-purple-600',
    show: true,
  },
  {
    title: 'ฝ่ายงาน/แผนก',
    description: 'จัดการโครงสร้างฝ่ายงาน',
    icon: 'heroicons:building-office',
    to: `/academies/${academyName.value}/admin/departments`,
    color: 'from-cyan-500 to-cyan-600',
    show: true,
  },
  {
    title: 'ประกาศใหม่',
    description: 'สร้างประกาศถึงสมาชิก',
    icon: 'fluent:megaphone-24-filled',
    to: `/academies/${academyName.value}/admin/announcements`,
    color: 'from-amber-500 to-amber-600',
    show: can('announcements.create'),
  },
  {
    title: 'ดูรายงาน',
    description: 'รายงานและสถิติ',
    icon: 'fluent:chart-multiple-24-filled',
    to: `/academies/${academyName.value}/admin`,
    color: 'from-cyan-500 to-cyan-600',
    show: can('reports.view'),
  },
  {
    title: 'จัดการรายได้',
    description: 'จัดการการสนับสนุนและแคมเปญโฆษณา',
    icon: 'fluent:money-hand-24-filled',
    to: `/academies/${academyName.value}/admin/revenue`,
    color: 'from-emerald-500 to-emerald-600',
    show: can('finance.view'),
  },
  {
    title: 'ตั้งค่าโรงเรียน',
    description: 'แก้ไขข้อมูลโรงเรียน',
    icon: 'fluent:settings-24-filled',
    to: `/academies/${academyName.value}/admin/settings`,
    color: 'from-slate-600 to-slate-700',
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
  <div>
    <div v-if="isLoading" class="flex items-center justify-center py-20">
      <div class="animate-spin rounded-full h-10 w-10 border-4 border-primary-500 border-t-transparent"></div>
    </div>

    <div v-else class="space-y-6">
      <!-- HopeUI nav header: wave banner behind, content cards overlap it -->
      <div class="relative isolate">
        <div class="absolute inset-x-0 top-0 -z-10 h-52 overflow-hidden rounded-2xl lg:h-56">
          <img
            src="/images/hopeui/top-header.png"
            alt=""
            class="hero-wave h-full w-full rounded-2xl object-cover"
          />
        </div>
        <div class="flex flex-wrap items-center justify-between gap-4 p-6 lg:p-8">
          <div>
            <h1 class="mb-2 text-2xl font-bold text-white">Dashboard</h1>
            <p class="mb-4 text-white">ภาพรวมการบริหารจัดการโรงเรียน</p>
          </div>
          <div>
            <NuxtLink
              :to="`/academies/${academyName}/admin/announcements`"
              class="inline-flex items-center gap-2 rounded-lg bg-white/20 px-4 py-2 font-medium text-white shadow-md backdrop-blur transition hover:bg-white/30 hover:shadow-xl"
            >
              <Icon icon="fluent:megaphone-24-filled" class="w-5 h-5" />
              สร้างประกาศ
            </NuxtLink>
          </div>
        </div>

        <!-- Stats Cards (HopeUI card style, pulled up over the banner) -->
        <div class="grid grid-cols-1 gap-4 px-1 sm:grid-cols-2 xl:grid-cols-3">
        <div
          v-for="stat in statCards"
          :key="stat.label"
          class="relative flex flex-col overflow-hidden rounded-lg bg-white p-5 shadow-md transition hover:shadow-lg dark:bg-gray-800"
        >
          <div class="mb-2 flex flex-wrap items-center justify-between gap-2">
            <div class="flex items-center gap-3">
              <div :class="['rounded p-2.5', stat.tone]">
                <Icon :icon="stat.icon" class="w-5 h-5" />
              </div>
              <p class="font-medium text-gray-900 dark:text-white">{{ stat.label }}</p>
            </div>
            <NuxtLink
              :to="stat.to"
              class="rounded-full bg-primary-500/10 px-2 py-1 text-xs font-semibold leading-none text-primary-600 transition hover:bg-primary-500/20 dark:text-primary-400"
            >
              ดู
            </NuxtLink>
          </div>
          <h2 class="text-2xl lg:text-3xl font-bold text-gray-900 dark:text-white">{{ stat.value }}</h2>
          <small class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">{{ stat.caption }}</small>
        </div>
        </div>
      </div>

      <!-- Quick Actions (HopeUI widget-card style) -->
      <div>
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">การดำเนินการด่วน</h2>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
          <template v-for="action in quickActions" :key="action.title">
            <NuxtLink
              v-if="action.show"
              :to="action.to"
              class="group relative flex flex-col overflow-hidden rounded-lg bg-white p-5 shadow-sm transition-all duration-200 hover:-translate-y-1 hover:shadow-lg dark:bg-gray-800"
            >
              <div class="mb-4 flex items-start justify-between">
                <div :class="['flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br shadow-md', action.color]">
                  <Icon :icon="action.icon" class="w-6 h-6 text-white" />
                </div>
                <Icon
                  icon="fluent:arrow-right-24-regular"
                  class="w-5 h-5 -translate-x-1 text-primary-500 opacity-0 transition-all duration-200 group-hover:translate-x-0 group-hover:opacity-100"
                />
              </div>
              <h3 class="font-semibold text-gray-900 dark:text-white group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors">
                {{ action.title }}
              </h3>
              <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ action.description }}</p>
            </NuxtLink>
          </template>
        </div>
      </div>

      <!-- Revenue Summary -->
      <div v-if="academyId" class="space-y-3">
        <div class="flex items-center justify-between">
          <h2 class="text-lg font-semibold text-gray-900 dark:text-white">สรุปรายได้</h2>
          <NuxtLink
            v-if="can('finance.view')"
            :to="`/academies/${academyName}/admin/revenue`"
            class="inline-flex items-center gap-1 rounded-full bg-primary-500/10 px-3 py-1 text-xs font-semibold leading-none text-primary-600 transition hover:bg-primary-500/20 dark:text-primary-400"
          >
            ดูรายละเอียดรายได้
          </NuxtLink>
        </div>
        <AcademyAdminRevenueWidget :academy-id="academyId" />
      </div>

      <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
        <!-- Pending Requests -->
        <div class="flex flex-col overflow-hidden rounded-lg bg-white shadow-sm dark:bg-gray-800">
          <div class="flex items-center justify-between p-5 border-b border-gray-100 dark:border-gray-700">
            <h3 class="font-semibold text-gray-900 dark:text-white flex items-center gap-2">
              <Icon icon="fluent:person-add-24-regular" class="w-5 h-5 text-amber-500" />
              คำขอเข้าร่วม
            </h3>
            <NuxtLink
              :to="`/academies/${academyName}/admin/members`"
              class="rounded-full bg-primary-500/10 px-2.5 py-1 text-xs font-semibold leading-none text-primary-600 transition hover:bg-primary-500/20 dark:text-primary-400"
            >
              ดูทั้งหมด
            </NuxtLink>
          </div>
          
          <div v-if="pendingRequests.length === 0" class="p-8 text-center">
            <Icon icon="fluent:checkmark-circle-24-regular" class="w-12 h-12 mx-auto text-green-500 mb-3" />
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
                  <Icon icon="fluent:checkmark-24-filled" class="w-5 h-5" />
                </button>
                <button 
                  @click="rejectRequest(request.id)"
                  class="p-2 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-lg transition-colors"
                >
                  <Icon icon="fluent:dismiss-24-filled" class="w-5 h-5" />
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Recent Activities -->
        <div class="flex flex-col overflow-hidden rounded-lg bg-white shadow-sm dark:bg-gray-800">
          <div class="flex items-center justify-between p-5 border-b border-gray-100 dark:border-gray-700">
            <h3 class="font-semibold text-gray-900 dark:text-white flex items-center gap-2">
              <Icon icon="fluent:history-24-regular" class="w-5 h-5 text-blue-500" />
              กิจกรรมล่าสุด
            </h3>
            <NuxtLink
              :to="`/academies/${academyName}/admin/activity-log`"
              class="rounded-full bg-primary-500/10 px-2.5 py-1 text-xs font-semibold leading-none text-primary-600 transition hover:bg-primary-500/20 dark:text-primary-400"
            >
              ดูทั้งหมด
            </NuxtLink>
          </div>
          
          <div v-if="recentActivities.length === 0" class="p-8 text-center">
            <Icon icon="fluent:calendar-empty-24-regular" class="w-12 h-12 mx-auto text-gray-400 mb-3" />
            <p class="text-gray-500 dark:text-gray-400">ยังไม่มีกิจกรรม</p>
          </div>
          
          <div v-else class="divide-y divide-gray-100 dark:divide-gray-700">
            <div v-for="activity in recentActivities" :key="activity.id" class="p-4 flex items-start gap-4">
              <div class="p-2 bg-gray-100 dark:bg-gray-700 rounded-lg">
                <Icon icon="fluent:flash-24-regular" class="w-5 h-5 text-gray-600 dark:text-gray-400" />
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
  </div>
</template>

<style scoped>
/* HopeUI banner slow-zoom (animated-scaleX) */
.hero-wave {
  animation: hero-wave-scale 45s 1s ease-in-out infinite;
}

@keyframes hero-wave-scale {
  0% { transform: scale(1); }
  50% { transform: scale(1.175); }
  100% { transform: scale(1); }
}
</style>
