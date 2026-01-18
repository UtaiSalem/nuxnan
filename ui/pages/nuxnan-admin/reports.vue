<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { Icon } from '@iconify/vue'

definePageMeta({
  layout: 'nuxnan-admin-layout',
  middleware: 'nuxnan-admin'
})

// State
const selectedPeriod = ref('month')
const isLoading = ref(false)

// Stats data
const stats = ref({
  totalRevenue: 485250,
  totalUsers: 12458,
  totalCourses: 245,
  totalEnrollments: 8945,
  revenueGrowth: 18.5,
  usersGrowth: 12.3,
  coursesGrowth: 8.7,
  enrollmentsGrowth: 15.2
})

// Revenue by category
const revenueByCategory = ref([
  { name: 'คอร์สเรียน', value: 285000, percentage: 58.7, color: 'bg-blue-500' },
  { name: 'สมาชิกพรีเมียม', value: 95000, percentage: 19.6, color: 'bg-purple-500' },
  { name: 'อะคาเดมี', value: 65250, percentage: 13.4, color: 'bg-green-500' },
  { name: 'อื่นๆ', value: 40000, percentage: 8.3, color: 'bg-orange-500' }
])

// Top selling courses
const topCourses = ref([
  { name: 'Python สำหรับผู้เริ่มต้น', sales: 245, revenue: 61250 },
  { name: 'JavaScript Advanced', sales: 189, revenue: 56700 },
  { name: 'React.js Complete Guide', sales: 156, revenue: 46800 },
  { name: 'Digital Marketing 101', sales: 134, revenue: 40200 },
  { name: 'UI/UX Design Basics', sales: 121, revenue: 36300 }
])

// Recent transactions
const recentTransactions = ref([
  { id: 1, user: 'สมชาย ใจดี', type: 'ซื้อคอร์ส', amount: 500, status: 'success', date: '2026-01-18' },
  { id: 2, user: 'สุดา สวยงาม', type: 'เติม Wallet', amount: 1000, status: 'success', date: '2026-01-18' },
  { id: 3, user: 'วิชัย มั่นคง', type: 'ซื้อคอร์ส', amount: 350, status: 'pending', date: '2026-01-17' },
  { id: 4, user: 'พิมพ์ใจ รักเรียน', type: 'สมัครพรีเมียม', amount: 299, status: 'success', date: '2026-01-17' },
  { id: 5, user: 'ชาติชาย เก่งมาก', type: 'ซื้อคอร์ส', amount: 450, status: 'success', date: '2026-01-17' }
])

// Period options
const periods = [
  { value: 'week', label: 'สัปดาห์นี้' },
  { value: 'month', label: 'เดือนนี้' },
  { value: 'quarter', label: 'ไตรมาสนี้' },
  { value: 'year', label: 'ปีนี้' }
]

// Get status badge
const getStatusBadge = (status: string) => {
  const badges = {
    success: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
    pending: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
    failed: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'
  }
  return badges[status] || badges.pending
}

const getStatusLabel = (status: string) => {
  const labels = { success: 'สำเร็จ', pending: 'รอดำเนินการ', failed: 'ล้มเหลว' }
  return labels[status] || 'ไม่ทราบ'
}
</script>

<template>
  <div class="space-y-6">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-gray-800 dark:text-white">รายงาน</h1>
        <p class="text-gray-500 dark:text-gray-400 mt-1">ดูสถิติและรายงานต่างๆ ของระบบ</p>
      </div>
      <div class="flex items-center gap-3">
        <select
          v-model="selectedPeriod"
          class="px-4 py-2.5 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-xl text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500"
        >
          <option v-for="period in periods" :key="period.value" :value="period.value">
            {{ period.label }}
          </option>
        </select>
        <button class="inline-flex items-center gap-2 px-4 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-xl text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
          <Icon icon="fluent:arrow-download-24-regular" class="w-5 h-5" />
          ดาวน์โหลด
        </button>
      </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
      <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-sm border border-gray-100 dark:border-gray-700">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-500 dark:text-gray-400">รายได้รวม</p>
            <p class="text-2xl font-bold text-gray-800 dark:text-white mt-1">
              ฿{{ stats.totalRevenue.toLocaleString() }}
            </p>
            <div class="flex items-center gap-1 mt-2">
              <Icon icon="fluent:arrow-trending-24-regular" class="w-4 h-4 text-green-500" />
              <span class="text-green-500 text-sm font-medium">+{{ stats.revenueGrowth }}%</span>
            </div>
          </div>
          <div class="p-3 bg-green-100 dark:bg-green-900/30 rounded-xl">
            <Icon icon="fluent:money-24-regular" class="w-6 h-6 text-green-600 dark:text-green-400" />
          </div>
        </div>
      </div>

      <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-sm border border-gray-100 dark:border-gray-700">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-500 dark:text-gray-400">ผู้ใช้งาน</p>
            <p class="text-2xl font-bold text-gray-800 dark:text-white mt-1">
              {{ stats.totalUsers.toLocaleString() }}
            </p>
            <div class="flex items-center gap-1 mt-2">
              <Icon icon="fluent:arrow-trending-24-regular" class="w-4 h-4 text-green-500" />
              <span class="text-green-500 text-sm font-medium">+{{ stats.usersGrowth }}%</span>
            </div>
          </div>
          <div class="p-3 bg-blue-100 dark:bg-blue-900/30 rounded-xl">
            <Icon icon="fluent:people-24-regular" class="w-6 h-6 text-blue-600 dark:text-blue-400" />
          </div>
        </div>
      </div>

      <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-sm border border-gray-100 dark:border-gray-700">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-500 dark:text-gray-400">คอร์สทั้งหมด</p>
            <p class="text-2xl font-bold text-gray-800 dark:text-white mt-1">
              {{ stats.totalCourses.toLocaleString() }}
            </p>
            <div class="flex items-center gap-1 mt-2">
              <Icon icon="fluent:arrow-trending-24-regular" class="w-4 h-4 text-green-500" />
              <span class="text-green-500 text-sm font-medium">+{{ stats.coursesGrowth }}%</span>
            </div>
          </div>
          <div class="p-3 bg-purple-100 dark:bg-purple-900/30 rounded-xl">
            <Icon icon="fluent:hat-graduation-24-regular" class="w-6 h-6 text-purple-600 dark:text-purple-400" />
          </div>
        </div>
      </div>

      <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-sm border border-gray-100 dark:border-gray-700">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-500 dark:text-gray-400">การลงทะเบียน</p>
            <p class="text-2xl font-bold text-gray-800 dark:text-white mt-1">
              {{ stats.totalEnrollments.toLocaleString() }}
            </p>
            <div class="flex items-center gap-1 mt-2">
              <Icon icon="fluent:arrow-trending-24-regular" class="w-4 h-4 text-green-500" />
              <span class="text-green-500 text-sm font-medium">+{{ stats.enrollmentsGrowth }}%</span>
            </div>
          </div>
          <div class="p-3 bg-orange-100 dark:bg-orange-900/30 rounded-xl">
            <Icon icon="fluent:person-add-24-regular" class="w-6 h-6 text-orange-600 dark:text-orange-400" />
          </div>
        </div>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Revenue by Category -->
      <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-sm border border-gray-100 dark:border-gray-700">
        <h2 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">รายได้ตามประเภท</h2>
        <div class="space-y-4">
          <div v-for="category in revenueByCategory" :key="category.name">
            <div class="flex items-center justify-between mb-1">
              <span class="text-sm text-gray-600 dark:text-gray-300">{{ category.name }}</span>
              <span class="text-sm font-medium text-gray-800 dark:text-white">
                ฿{{ category.value.toLocaleString() }} ({{ category.percentage }}%)
              </span>
            </div>
            <div class="h-2 bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden">
              <div :class="[category.color, 'h-full rounded-full transition-all duration-500']" :style="{ width: `${category.percentage}%` }" />
            </div>
          </div>
        </div>
      </div>

      <!-- Top Selling Courses -->
      <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-sm border border-gray-100 dark:border-gray-700">
        <h2 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">คอร์สขายดี</h2>
        <div class="space-y-4">
          <div v-for="(course, index) in topCourses" :key="course.name" class="flex items-center gap-4">
            <span :class="[
              'flex items-center justify-center w-8 h-8 rounded-full text-sm font-bold',
              index === 0 ? 'bg-yellow-100 text-yellow-700' :
              index === 1 ? 'bg-gray-100 text-gray-700' :
              index === 2 ? 'bg-orange-100 text-orange-700' :
              'bg-gray-50 text-gray-500'
            ]">
              {{ index + 1 }}
            </span>
            <div class="flex-1 min-w-0">
              <p class="font-medium text-gray-800 dark:text-white truncate">{{ course.name }}</p>
              <p class="text-sm text-gray-500">{{ course.sales }} ยอดขาย</p>
            </div>
            <span class="font-semibold text-green-600">฿{{ course.revenue.toLocaleString() }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Recent Transactions -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-sm border border-gray-100 dark:border-gray-700">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-semibold text-gray-800 dark:text-white">ธุรกรรมล่าสุด</h2>
        <NuxtLink to="/nuxnan-admin/transactions" class="text-sm text-indigo-600 hover:text-indigo-700 font-medium">
          ดูทั้งหมด
        </NuxtLink>
      </div>
      <div class="overflow-x-auto">
        <table class="w-full">
          <thead>
            <tr class="border-b border-gray-100 dark:border-gray-700">
              <th class="pb-3 text-left text-xs font-medium text-gray-500 uppercase">ผู้ใช้</th>
              <th class="pb-3 text-left text-xs font-medium text-gray-500 uppercase">ประเภท</th>
              <th class="pb-3 text-right text-xs font-medium text-gray-500 uppercase">จำนวน</th>
              <th class="pb-3 text-center text-xs font-medium text-gray-500 uppercase">สถานะ</th>
              <th class="pb-3 text-right text-xs font-medium text-gray-500 uppercase">วันที่</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-50 dark:divide-gray-700/50">
            <tr v-for="tx in recentTransactions" :key="tx.id">
              <td class="py-3 text-gray-800 dark:text-white font-medium">{{ tx.user }}</td>
              <td class="py-3 text-gray-600 dark:text-gray-300">{{ tx.type }}</td>
              <td class="py-3 text-right font-medium text-green-600">฿{{ tx.amount.toLocaleString() }}</td>
              <td class="py-3 text-center">
                <span :class="[getStatusBadge(tx.status), 'px-2 py-1 rounded-full text-xs font-medium']">
                  {{ getStatusLabel(tx.status) }}
                </span>
              </td>
              <td class="py-3 text-right text-gray-500">{{ tx.date }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>
