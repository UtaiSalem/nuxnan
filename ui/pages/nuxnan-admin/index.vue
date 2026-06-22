<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { Icon } from '@iconify/vue'
import { useAuthStore } from '~/stores/auth'

definePageMeta({
  layout: 'nuxnan-admin-layout',
  middleware: 'nuxnan-admin'
})

const authStore = useAuthStore()
const config = useRuntimeConfig()

// Dashboard stats
const stats = ref([
  {
    title: 'ผู้ใช้งานทั้งหมด',
    value: '...',
    change: '0%',
    isPositive: true,
    icon: 'fluent:people-24-regular',
    color: 'bg-blue-500'
  },
  {
    title: 'คอร์สเรียนทั้งหมด',
    value: '...',
    change: '0%',
    isPositive: true,
    icon: 'fluent:hat-graduation-24-regular',
    color: 'bg-green-500'
  },
  {
    title: 'อะคาเดมี',
    value: '...',
    change: '0%',
    isPositive: true,
    icon: 'fluent:building-24-regular',
    color: 'bg-purple-500'
  },
  {
    title: 'รายได้รวม',
    value: '...',
    change: '0%',
    isPositive: true,
    icon: 'fluent:money-24-regular',
    color: 'bg-yellow-500'
  }
])

// Recent activities
const recentActivities = ref<any[]>([])
// Top courses
const topCourses = ref<any[]>([])

interface StatsResponse {
    success: boolean
    data: {
        total_users: number
        active_users: number
        users_growth: number
        total_courses: number
        courses_growth: number
        total_academies: number
        academies_growth: number
        total_revenue: number
        revenue_growth: number
    }
}

interface ActivitiesResponse {
    success: boolean
    data: any[]
}

interface CoursesResponse {
    success: boolean
    data: any[]
}

// Fetch Data
const fetchData = async () => {
    try {
        const [statsRes, activitiesRes, coursesRes] = await Promise.all([
            useFetch<StatsResponse>('/api/admin/stats'),
            useFetch<ActivitiesResponse>('/api/admin/dashboard/activities'),
            useFetch<CoursesResponse>('/api/admin/dashboard/top-courses')
        ])

        if (statsRes.data.value?.success) {
            const d = statsRes.data.value.data
            stats.value = [
                {
                    title: 'ผู้ใช้งานทั้งหมด',
                    value: d.total_users.toLocaleString(),
                    change: `+${d.users_growth || 0}%`,
                    isPositive: true,
                    icon: 'fluent:people-24-regular',
                    color: 'bg-blue-500'
                },
                {
                    title: 'คอร์สเรียนทั้งหมด',
                    value: d.total_courses.toLocaleString(),
                    change: `+${d.courses_growth || 0}%`,
                    isPositive: true,
                    icon: 'fluent:hat-graduation-24-regular',
                    color: 'bg-green-500'
                },
                {
                    title: 'อะคาเดมี',
                    value: d.total_academies.toLocaleString(),
                    change: `+${d.academies_growth || 0}%`,
                    isPositive: true,
                    icon: 'fluent:building-24-regular',
                    color: 'bg-purple-500'
                },
                {
                    title: 'รายได้รวม', // Changed from "Month" to "Total" as per backend logic
                    value: `฿${Number(d.total_revenue).toLocaleString()}`,
                    change: `+${d.revenue_growth || 0}%`,
                    isPositive: true,
                    icon: 'fluent:money-24-regular',
                    color: 'bg-yellow-500'
                }
            ]
        }

        if (activitiesRes.data.value?.success) {
            recentActivities.value = activitiesRes.data.value.data
        }

        if (coursesRes.data.value?.success) {
            topCourses.value = coursesRes.data.value.data
        }

    } catch (error) {
        console.error('Error fetching dashboard data:', error)
    }
}

onMounted(() => {
    fetchData()
})

// Quick actions
const quickActions = [
  {
    title: 'เพิ่มผู้ใช้ใหม่',
    description: 'สร้างบัญชีผู้ใช้งานใหม่',
    icon: 'fluent:person-add-24-regular',
    href: '/nuxnan-admin/users/create',
    color: 'from-blue-500 to-blue-600'
  },
  {
    title: 'สร้างคอร์สใหม่',
    description: 'เพิ่มคอร์สเรียนใหม่',
    icon: 'fluent:add-circle-24-regular',
    href: '/nuxnan-admin/courses/create',
    color: 'from-green-500 to-green-600'
  },
  {
    title: 'สร้างคูปอง',
    description: 'สร้างโค้ดส่วนลดใหม่',
    icon: 'fluent:ticket-diagonal-24-regular',
    href: '/nuxnan-admin/coupons/create',
    color: 'from-purple-500 to-purple-600'
  },
  {
    title: 'ดูรายงาน',
    description: 'ดูสถิติและรายงานต่างๆ',
    icon: 'fluent:data-pie-24-regular',
    href: '/nuxnan-admin/reports',
    color: 'from-orange-500 to-orange-600'
  }
]
</script>

<template>
  <div class="space-y-6">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-slate-800 dark:text-white">
          ยินดีต้อนรับ, {{ authStore.user?.name || 'Admin' }}! 👋
        </h1>
        <p class="text-slate-500 dark:text-slate-400 mt-1">
          นี่คือภาพรวมของระบบ Nuxnan ในวันนี้
        </p>
      </div>
      <div class="flex gap-3">
        <button class="inline-flex items-center gap-2 px-4 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
          <Icon icon="fluent:arrow-download-24-regular" class="w-5 h-5" />
          ดาวน์โหลดรายงาน
        </button>
        <button class="inline-flex items-center gap-2 px-4 py-2 bg-hopeui-primary-500 hover:bg-hopeui-primary-600 rounded-xl text-white transition-colors">
          <Icon icon="fluent:add-24-regular" class="w-5 h-5" />
          สร้างใหม่
        </button>
      </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-6">
      <div
        v-for="stat in stats"
        :key="stat.title"
        class="bg-white dark:bg-slate-800 rounded-2xl p-5 shadow-hopeui border border-slate-100 dark:border-slate-700"
      >
        <div class="flex items-start justify-between">
          <div>
            <p class="text-sm text-slate-500 dark:text-slate-400">{{ stat.title }}</p>
            <p class="text-2xl font-bold text-slate-800 dark:text-white mt-1">{{ stat.value }}</p>
            <div class="flex items-center gap-1 mt-2">
              <Icon
                :icon="stat.isPositive ? 'fluent:arrow-trending-24-regular' : 'fluent:arrow-trending-down-24-regular'"
                :class="stat.isPositive ? 'text-green-500' : 'text-red-500'"
                class="w-4 h-4"
              />
              <span :class="stat.isPositive ? 'text-green-500' : 'text-red-500'" class="text-sm font-medium">
                {{ stat.change }}
              </span>
              <span class="text-slate-400 text-sm">จากเดือนที่แล้ว</span>
            </div>
          </div>
          <div :class="[stat.color, 'p-3 rounded-xl text-white']">
            <Icon :icon="stat.icon" class="w-6 h-6" />
          </div>
        </div>
      </div>
    </div>

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Quick Actions -->
      <div class="lg:col-span-1 bg-white dark:bg-slate-800 rounded-2xl p-5 shadow-hopeui border border-slate-100 dark:border-slate-700">
        <h2 class="text-lg font-semibold text-slate-800 dark:text-white mb-4">การดำเนินการด่วน</h2>
        <div class="space-y-3">
          <NuxtLink
            v-for="action in quickActions"
            :key="action.title"
            :to="action.href"
            class="flex items-center gap-4 p-3 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors group"
          >
            <div :class="['bg-gradient-to-r', action.color, 'p-2.5 rounded-xl text-white']">
              <Icon :icon="action.icon" class="w-5 h-5" />
            </div>
            <div class="flex-1">
              <p class="font-medium text-slate-800 dark:text-white group-hover:text-hopeui-primary-600 dark:group-hover:text-hopeui-primary-400 transition-colors">
                {{ action.title }}
              </p>
              <p class="text-sm text-slate-500 dark:text-slate-400">{{ action.description }}</p>
            </div>
            <Icon icon="fluent:chevron-right-24-regular" class="w-5 h-5 text-slate-400 group-hover:text-hopeui-primary-500 transition-colors" />
          </NuxtLink>
        </div>
      </div>

      <!-- Recent Activities -->
      <div class="lg:col-span-2 bg-white dark:bg-slate-800 rounded-2xl p-5 shadow-hopeui border border-slate-100 dark:border-slate-700">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-lg font-semibold text-slate-800 dark:text-white">กิจกรรมล่าสุด</h2>
          <NuxtLink to="/nuxnan-admin/activities" class="text-sm text-hopeui-primary-600 hover:text-hopeui-primary-700 font-medium">
            ดูทั้งหมด
          </NuxtLink>
        </div>
        <div class="space-y-4">
          <div
            v-for="activity in recentActivities"
            :key="activity.id"
            class="flex items-start gap-4 p-3 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors"
          >
            <div class="p-2 rounded-lg bg-slate-100 dark:bg-slate-700">
              <Icon :icon="activity.icon" :class="[activity.color, 'w-5 h-5']" />
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-sm text-slate-800 dark:text-slate-200">
                <span class="font-medium">{{ activity.user }}</span>
                <span class="text-slate-500"> {{ activity.action }} </span>
                <span class="font-medium text-hopeui-primary-600 dark:text-hopeui-primary-400">{{ activity.target }}</span>
              </p>
              <p class="text-xs text-slate-400 mt-1">{{ activity.time }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Top Courses Table -->
    <div class="bg-white dark:bg-slate-800 rounded-2xl p-5 shadow-hopeui border border-slate-100 dark:border-slate-700">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-semibold text-slate-800 dark:text-white">คอร์สยอดนิยม</h2>
        <NuxtLink to="/nuxnan-admin/courses" class="text-sm text-hopeui-primary-600 hover:text-hopeui-primary-700 font-medium">
          ดูทั้งหมด
        </NuxtLink>
      </div>
      <div class="overflow-x-auto">
        <table class="w-full">
          <thead>
            <tr class="text-left border-b border-slate-100 dark:border-slate-700">
              <th class="pb-3 text-sm font-medium text-slate-500 dark:text-slate-400">อันดับ</th>
              <th class="pb-3 text-sm font-medium text-slate-500 dark:text-slate-400">ชื่อคอร์ส</th>
              <th class="pb-3 text-sm font-medium text-slate-500 dark:text-slate-400 text-right">ผู้เรียน</th>
              <th class="pb-3 text-sm font-medium text-slate-500 dark:text-slate-400 text-right">รายได้</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="(course, index) in topCourses"
              :key="course.name"
              class="border-b border-slate-50 dark:border-slate-700/50 last:border-0"
            >
              <td class="py-3">
                <span
                  :class="[
                    'inline-flex items-center justify-center w-6 h-6 rounded-full text-xs font-bold',
                    index === 0 ? 'bg-yellow-100 text-yellow-700' :
                    index === 1 ? 'bg-slate-100 text-slate-700' :
                    index === 2 ? 'bg-orange-100 text-orange-700' :
                    'bg-slate-50 text-slate-500'
                  ]"
                >
                  {{ index + 1 }}
                </span>
              </td>
              <td class="py-3">
                <p class="font-medium text-slate-800 dark:text-white">{{ course.name }}</p>
              </td>
              <td class="py-3 text-right">
                <span class="text-slate-600 dark:text-slate-300">{{ course.enrollments.toLocaleString() }}</span>
              </td>
              <td class="py-3 text-right">
                <span class="font-medium text-green-600">฿{{ course.revenue.toLocaleString() }}</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>
