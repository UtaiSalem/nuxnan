<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { Icon } from '@iconify/vue'
import { useAuthStore } from '~/stores/auth'

definePageMeta({
  layout: 'nuxnan-admin-layout',
  middleware: 'nuxnan-admin'
})

const authStore = useAuthStore()

// Dashboard stats
const stats = ref([
  {
    title: 'ผู้ใช้งานทั้งหมด',
    value: '12,458',
    change: '+12%',
    isPositive: true,
    icon: 'fluent:people-24-regular',
    color: 'bg-blue-500'
  },
  {
    title: 'คอร์สเรียนทั้งหมด',
    value: '245',
    change: '+8%',
    isPositive: true,
    icon: 'fluent:hat-graduation-24-regular',
    color: 'bg-green-500'
  },
  {
    title: 'อะคาเดมี',
    value: '58',
    change: '+5%',
    isPositive: true,
    icon: 'fluent:building-24-regular',
    color: 'bg-purple-500'
  },
  {
    title: 'รายได้เดือนนี้',
    value: '฿125,840',
    change: '+18%',
    isPositive: true,
    icon: 'fluent:money-24-regular',
    color: 'bg-yellow-500'
  }
])

// Recent activities
const recentActivities = ref([
  {
    id: 1,
    user: 'สมชาย ใจดี',
    action: 'สมัครเรียนคอร์ส',
    target: 'Python สำหรับผู้เริ่มต้น',
    time: '5 นาทีที่แล้ว',
    icon: 'fluent:book-24-regular',
    color: 'text-blue-500'
  },
  {
    id: 2,
    user: 'สุดา สวยงาม',
    action: 'สร้างอะคาเดมีใหม่',
    target: 'Digital Marketing Academy',
    time: '15 นาทีที่แล้ว',
    icon: 'fluent:building-24-regular',
    color: 'text-purple-500'
  },
  {
    id: 3,
    user: 'วิชัย มั่นคง',
    action: 'เติมเงิน Wallet',
    target: '฿500',
    time: '30 นาทีที่แล้ว',
    icon: 'fluent:wallet-24-regular',
    color: 'text-green-500'
  },
  {
    id: 4,
    user: 'พิมพ์ใจ รักเรียน',
    action: 'แลก Points เป็นคูปอง',
    target: '500 Points',
    time: '1 ชั่วโมงที่แล้ว',
    icon: 'fluent:gift-24-regular',
    color: 'text-yellow-500'
  },
  {
    id: 5,
    user: 'ชาติชาย เก่งมาก',
    action: 'สอบผ่านคอร์ส',
    target: 'JavaScript Advanced',
    time: '2 ชั่วโมงที่แล้ว',
    icon: 'fluent:checkmark-circle-24-regular',
    color: 'text-emerald-500'
  }
])

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

// Top courses
const topCourses = ref([
  { name: 'Python สำหรับผู้เริ่มต้น', enrollments: 1245, revenue: 62250 },
  { name: 'JavaScript Advanced', enrollments: 892, revenue: 53520 },
  { name: 'React.js Complete Guide', enrollments: 756, revenue: 45360 },
  { name: 'Digital Marketing 101', enrollments: 634, revenue: 38040 },
  { name: 'UI/UX Design Basics', enrollments: 521, revenue: 31260 }
])
</script>

<template>
  <div class="space-y-6">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-gray-800 dark:text-white">
          ยินดีต้อนรับ, {{ authStore.user?.name || 'Admin' }}! 👋
        </h1>
        <p class="text-gray-500 dark:text-gray-400 mt-1">
          นี่คือภาพรวมของระบบ Nuxnan ในวันนี้
        </p>
      </div>
      <div class="flex gap-3">
        <button class="inline-flex items-center gap-2 px-4 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
          <Icon icon="fluent:arrow-download-24-regular" class="w-5 h-5" />
          ดาวน์โหลดรายงาน
        </button>
        <button class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 rounded-xl text-white transition-colors">
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
        class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-sm border border-gray-100 dark:border-gray-700"
      >
        <div class="flex items-start justify-between">
          <div>
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ stat.title }}</p>
            <p class="text-2xl font-bold text-gray-800 dark:text-white mt-1">{{ stat.value }}</p>
            <div class="flex items-center gap-1 mt-2">
              <Icon
                :icon="stat.isPositive ? 'fluent:arrow-trending-24-regular' : 'fluent:arrow-trending-down-24-regular'"
                :class="stat.isPositive ? 'text-green-500' : 'text-red-500'"
                class="w-4 h-4"
              />
              <span :class="stat.isPositive ? 'text-green-500' : 'text-red-500'" class="text-sm font-medium">
                {{ stat.change }}
              </span>
              <span class="text-gray-400 text-sm">จากเดือนที่แล้ว</span>
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
      <div class="lg:col-span-1 bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-sm border border-gray-100 dark:border-gray-700">
        <h2 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">การดำเนินการด่วน</h2>
        <div class="space-y-3">
          <NuxtLink
            v-for="action in quickActions"
            :key="action.title"
            :to="action.href"
            class="flex items-center gap-4 p-3 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors group"
          >
            <div :class="['bg-gradient-to-r', action.color, 'p-2.5 rounded-xl text-white']">
              <Icon :icon="action.icon" class="w-5 h-5" />
            </div>
            <div class="flex-1">
              <p class="font-medium text-gray-800 dark:text-white group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">
                {{ action.title }}
              </p>
              <p class="text-sm text-gray-500 dark:text-gray-400">{{ action.description }}</p>
            </div>
            <Icon icon="fluent:chevron-right-24-regular" class="w-5 h-5 text-gray-400 group-hover:text-indigo-500 transition-colors" />
          </NuxtLink>
        </div>
      </div>

      <!-- Recent Activities -->
      <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-sm border border-gray-100 dark:border-gray-700">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-lg font-semibold text-gray-800 dark:text-white">กิจกรรมล่าสุด</h2>
          <NuxtLink to="/nuxnan-admin/activities" class="text-sm text-indigo-600 hover:text-indigo-700 font-medium">
            ดูทั้งหมด
          </NuxtLink>
        </div>
        <div class="space-y-4">
          <div
            v-for="activity in recentActivities"
            :key="activity.id"
            class="flex items-start gap-4 p-3 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"
          >
            <div class="p-2 rounded-lg bg-gray-100 dark:bg-gray-700">
              <Icon :icon="activity.icon" :class="[activity.color, 'w-5 h-5']" />
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-sm text-gray-800 dark:text-gray-200">
                <span class="font-medium">{{ activity.user }}</span>
                <span class="text-gray-500"> {{ activity.action }} </span>
                <span class="font-medium text-indigo-600 dark:text-indigo-400">{{ activity.target }}</span>
              </p>
              <p class="text-xs text-gray-400 mt-1">{{ activity.time }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Top Courses Table -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-sm border border-gray-100 dark:border-gray-700">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-semibold text-gray-800 dark:text-white">คอร์สยอดนิยม</h2>
        <NuxtLink to="/nuxnan-admin/courses" class="text-sm text-indigo-600 hover:text-indigo-700 font-medium">
          ดูทั้งหมด
        </NuxtLink>
      </div>
      <div class="overflow-x-auto">
        <table class="w-full">
          <thead>
            <tr class="text-left border-b border-gray-100 dark:border-gray-700">
              <th class="pb-3 text-sm font-medium text-gray-500 dark:text-gray-400">อันดับ</th>
              <th class="pb-3 text-sm font-medium text-gray-500 dark:text-gray-400">ชื่อคอร์ส</th>
              <th class="pb-3 text-sm font-medium text-gray-500 dark:text-gray-400 text-right">ผู้เรียน</th>
              <th class="pb-3 text-sm font-medium text-gray-500 dark:text-gray-400 text-right">รายได้</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="(course, index) in topCourses"
              :key="course.name"
              class="border-b border-gray-50 dark:border-gray-700/50 last:border-0"
            >
              <td class="py-3">
                <span
                  :class="[
                    'inline-flex items-center justify-center w-6 h-6 rounded-full text-xs font-bold',
                    index === 0 ? 'bg-yellow-100 text-yellow-700' :
                    index === 1 ? 'bg-gray-100 text-gray-700' :
                    index === 2 ? 'bg-orange-100 text-orange-700' :
                    'bg-gray-50 text-gray-500'
                  ]"
                >
                  {{ index + 1 }}
                </span>
              </td>
              <td class="py-3">
                <p class="font-medium text-gray-800 dark:text-white">{{ course.name }}</p>
              </td>
              <td class="py-3 text-right">
                <span class="text-gray-600 dark:text-gray-300">{{ course.enrollments.toLocaleString() }}</span>
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
