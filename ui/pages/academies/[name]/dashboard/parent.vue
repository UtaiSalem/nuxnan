<script setup lang="ts">
/**
 * Parent Dashboard
 * Dashboard สำหรับผู้ปกครอง
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
const children = ref<any[]>([])
const selectedChild = ref<any>(null)
const notifications = ref<any[]>([])
const recentPayments = ref<any[]>([])
const isLoading = ref(true)

// Academy Role
const academyId = ref<number | null>(null)
const { 
  myRole, 
  roleDisplayName, 
  roleColor, 
  roleIcon,
  can,
  isParent,
  fetchMyRole 
} = useAcademyRole(academyId)

// Stats for selected child
const childStats = ref({
  enrolledCourses: 0,
  attendanceRate: 0,
  averageGrade: 0,
  totalPoints: 0,
})

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
      
      // Fetch parent dashboard data
      await Promise.all([
        fetchChildren(),
        fetchNotifications(),
        fetchRecentPayments(),
      ])
    }
  } catch (err) {
    console.error('Failed to load parent dashboard:', err)
  } finally {
    isLoading.value = false
  }
})

const fetchChildren = async () => {
  if (!academyId.value) return
  
  try {
    // TODO: Implement when parent-child API is ready
    // For now, show mock data structure
    children.value = []
  } catch (err) {
    console.error('Failed to fetch children:', err)
  }
}

const fetchNotifications = async () => {
  if (!academyId.value) return
  
  try {
    const response: any = await api.get(`/api/academies/${academyId.value}/activities`)
    if (response.success) {
      notifications.value = (response.activities || []).slice(0, 5)
    }
  } catch (err) {
    console.error('Failed to fetch notifications:', err)
  }
}

const fetchRecentPayments = async () => {
  // TODO: Implement when payment API is ready
}

// Select child to view details
const selectChild = (child: any) => {
  selectedChild.value = child
  // Update stats based on selected child
  // TODO: Fetch child-specific data
}

// Quick actions for parent
const quickActions = computed(() => [
  {
    title: 'ข้อมูลบุตรหลาน',
    icon: 'fluent:people-24-regular',
    to: `/academies/${academyName.value}/parent/children`,
    color: 'bg-purple-500',
    show: can('children.view'),
  },
  {
    title: 'ดูผลการเรียน',
    icon: 'fluent:document-checkmark-24-regular',
    to: `/academies/${academyName.value}/parent/grades`,
    color: 'bg-green-500',
    show: can('grades.view'),
  },
  {
    title: 'ประวัติการเข้าเรียน',
    icon: 'fluent:calendar-checkmark-24-regular',
    to: `/academies/${academyName.value}/parent/attendance`,
    color: 'bg-blue-500',
    show: can('attendance.view'),
  },
  {
    title: 'ชำระค่าเรียน',
    icon: 'fluent:payment-24-regular',
    to: `/academies/${academyName.value}/parent/payments`,
    color: 'bg-amber-500',
    show: can('payments.make'),
  },
  {
    title: 'ติดต่อครูผู้สอน',
    icon: 'fluent:chat-24-regular',
    to: `/academies/${academyName.value}/parent/messages`,
    color: 'bg-pink-500',
    show: can('messages.send'),
  },
  {
    title: 'นัดพบเยี่ยมบ้าน',
    icon: 'fluent:home-24-regular',
    to: `/academies/${academyName.value}/parent/home-visits`,
    color: 'bg-teal-500',
    show: can('home_visits.view'),
  },
])

const visibleQuickActions = computed(() => quickActions.value.filter(a => a.show))
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
            สวัสดี, {{ user?.name?.split(' ')[0] || 'ผู้ปกครอง' }} 👋
          </h1>
          <span :class="['px-3 py-1 rounded-full text-sm font-medium', roleColor]">
            <Icon :name="roleIcon" class="w-4 h-4 inline mr-1" />
            {{ roleDisplayName }}
          </span>
        </div>
        <p class="text-gray-600 dark:text-gray-400">
          {{ academy?.name }} • ติดตามความก้าวหน้าของบุตรหลาน
        </p>
      </div>

      <!-- Children Selector (if multiple children) -->
      <div v-if="children.length > 1" class="mb-6">
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
          เลือกบุตรหลาน
        </label>
        <div class="flex gap-3 overflow-x-auto pb-2">
          <button
            v-for="child in children"
            :key="child.id"
            @click="selectChild(child)"
            :class="[
              'flex items-center gap-3 px-4 py-3 rounded-xl border-2 transition-all',
              selectedChild?.id === child.id 
                ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20' 
                : 'border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 hover:border-gray-300'
            ]"
          >
            <img 
              :src="child.avatar || '/images/default-avatar.png'" 
              class="w-10 h-10 rounded-full object-cover"
              alt=""
            />
            <div class="text-left">
              <p class="font-medium text-gray-900 dark:text-white">{{ child.name }}</p>
              <p class="text-xs text-gray-500">{{ child.class_name }}</p>
            </div>
          </button>
        </div>
      </div>

      <!-- No Children Notice -->
      <div v-if="children.length === 0" class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700 rounded-xl p-6 mb-8">
        <div class="flex items-start gap-4">
          <Icon name="fluent:info-24-filled" class="w-6 h-6 text-amber-500 flex-shrink-0" />
          <div>
            <h3 class="font-medium text-amber-800 dark:text-amber-200">ยังไม่มีข้อมูลบุตรหลาน</h3>
            <p class="text-sm text-amber-700 dark:text-amber-300 mt-1">
              กรุณาติดต่อสถาบันเพื่อเชื่อมโยงข้อมูลบุตรหลานของท่าน
            </p>
          </div>
        </div>
      </div>

      <!-- Stats Grid (for selected child or overall) -->
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm">
          <div class="flex items-center gap-3">
            <div class="p-2 bg-purple-100 dark:bg-purple-900 rounded-lg">
              <Icon name="fluent:book-24-filled" class="w-6 h-6 text-purple-600 dark:text-purple-400" />
            </div>
            <div>
              <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ childStats.enrolledCourses }}</p>
              <p class="text-sm text-gray-500">รายวิชาที่ลงทะเบียน</p>
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm">
          <div class="flex items-center gap-3">
            <div class="p-2 bg-green-100 dark:bg-green-900 rounded-lg">
              <Icon name="fluent:person-available-24-filled" class="w-6 h-6 text-green-600 dark:text-green-400" />
            </div>
            <div>
              <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ childStats.attendanceRate }}%</p>
              <p class="text-sm text-gray-500">อัตราการเข้าเรียน</p>
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm">
          <div class="flex items-center gap-3">
            <div class="p-2 bg-blue-100 dark:bg-blue-900 rounded-lg">
              <Icon name="fluent:trophy-24-filled" class="w-6 h-6 text-blue-600 dark:text-blue-400" />
            </div>
            <div>
              <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ childStats.averageGrade || '-' }}</p>
              <p class="text-sm text-gray-500">เกรดเฉลี่ย</p>
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm">
          <div class="flex items-center gap-3">
            <div class="p-2 bg-amber-100 dark:bg-amber-900 rounded-lg">
              <Icon name="fluent:star-24-filled" class="w-6 h-6 text-amber-600 dark:text-amber-400" />
            </div>
            <div>
              <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ childStats.totalPoints.toLocaleString() }}</p>
              <p class="text-sm text-gray-500">แต้มสะสม</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Quick Actions -->
      <div class="mb-8">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">เมนูด่วน</h2>
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
          <NuxtLink
            v-for="action in visibleQuickActions"
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
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
          <!-- Child's Courses -->
          <div v-if="selectedChild || children.length === 1" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm">
            <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
              <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                รายวิชาที่ลงทะเบียน
              </h2>
              <NuxtLink 
                :to="`/academies/${academyName}/parent/courses`"
                class="text-sm text-primary-500 hover:underline"
              >
                ดูทั้งหมด
              </NuxtLink>
            </div>
            <div class="p-4">
              <div class="text-center py-8 text-gray-500">
                <Icon name="fluent:book-24-regular" class="w-12 h-12 mx-auto mb-2" />
                <p>กรุณาเลือกบุตรหลานเพื่อดูรายวิชา</p>
              </div>
            </div>
          </div>

          <!-- Attendance Summary -->
          <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm">
            <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
              <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                สรุปการเข้าเรียน (เดือนนี้)
              </h2>
              <NuxtLink 
                :to="`/academies/${academyName}/parent/attendance`"
                class="text-sm text-primary-500 hover:underline"
              >
                ดูรายละเอียด
              </NuxtLink>
            </div>
            <div class="p-6">
              <div class="grid grid-cols-4 gap-4 text-center">
                <div class="p-4 bg-green-50 dark:bg-green-900/20 rounded-lg">
                  <p class="text-2xl font-bold text-green-600 dark:text-green-400">0</p>
                  <p class="text-sm text-gray-500">มาเรียน</p>
                </div>
                <div class="p-4 bg-red-50 dark:bg-red-900/20 rounded-lg">
                  <p class="text-2xl font-bold text-red-600 dark:text-red-400">0</p>
                  <p class="text-sm text-gray-500">ขาดเรียน</p>
                </div>
                <div class="p-4 bg-amber-50 dark:bg-amber-900/20 rounded-lg">
                  <p class="text-2xl font-bold text-amber-600 dark:text-amber-400">0</p>
                  <p class="text-sm text-gray-500">มาสาย</p>
                </div>
                <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                  <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">0</p>
                  <p class="text-sm text-gray-500">ลา</p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
          <!-- Notifications -->
          <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm">
            <div class="p-4 border-b border-gray-200 dark:border-gray-700">
              <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                การแจ้งเตือน
              </h2>
            </div>
            <div class="p-4">
              <div v-if="notifications.length === 0" class="text-center py-4 text-gray-500">
                <Icon name="fluent:bell-24-regular" class="w-8 h-8 mx-auto mb-2" />
                <p class="text-sm">ไม่มีการแจ้งเตือนใหม่</p>
              </div>
              <div v-else class="space-y-3">
                <div 
                  v-for="notification in notifications" 
                  :key="notification.id"
                  class="p-3 bg-gray-50 dark:bg-gray-700 rounded-lg"
                >
                  <p class="text-sm text-gray-900 dark:text-white line-clamp-2">
                    {{ notification.content }}
                  </p>
                  <p class="text-xs text-gray-500 mt-2">
                    {{ new Date(notification.created_at).toLocaleDateString('th-TH') }}
                  </p>
                </div>
              </div>
            </div>
          </div>

          <!-- Payment History -->
          <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm">
            <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
              <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                การชำระเงินล่าสุด
              </h2>
              <NuxtLink 
                :to="`/academies/${academyName}/parent/payments`"
                class="text-sm text-primary-500 hover:underline"
              >
                ดูทั้งหมด
              </NuxtLink>
            </div>
            <div class="p-4">
              <div v-if="recentPayments.length === 0" class="text-center py-4 text-gray-500">
                <Icon name="fluent:receipt-24-regular" class="w-8 h-8 mx-auto mb-2" />
                <p class="text-sm">ไม่มีประวัติการชำระเงิน</p>
              </div>
              <div v-else class="space-y-3">
                <div 
                  v-for="payment in recentPayments" 
                  :key="payment.id"
                  class="p-3 bg-gray-50 dark:bg-gray-700 rounded-lg flex justify-between items-center"
                >
                  <div>
                    <p class="text-sm font-medium text-gray-900 dark:text-white">{{ payment.description }}</p>
                    <p class="text-xs text-gray-500 mt-1">{{ payment.date }}</p>
                  </div>
                  <span :class="[
                    'px-2 py-1 rounded-full text-xs font-medium',
                    payment.status === 'paid' ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700'
                  ]">
                    {{ payment.amount?.toLocaleString() }} ฿
                  </span>
                </div>
              </div>
            </div>
          </div>

          <!-- Quick Contact -->
          <div class="bg-gradient-to-br from-primary-500 to-primary-600 rounded-xl p-6 text-white">
            <Icon name="fluent:chat-24-filled" class="w-8 h-8 mb-3" />
            <h3 class="font-semibold mb-2">ต้องการติดต่อสถาบัน?</h3>
            <p class="text-sm opacity-90 mb-4">สามารถส่งข้อความหรือโทรติดต่อได้ตลอดเวลา</p>
            <NuxtLink 
              :to="`/academies/${academyName}/contact`"
              class="inline-block bg-white text-primary-600 px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-100 transition-colors"
            >
              ติดต่อเรา
            </NuxtLink>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
