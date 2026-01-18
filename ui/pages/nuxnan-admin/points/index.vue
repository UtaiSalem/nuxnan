<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { Icon } from '@iconify/vue'

definePageMeta({
  layout: 'nuxnan-admin-layout',
  middleware: 'nuxnan-admin'
})

const config = useRuntimeConfig()
const apiBase = config.public.apiBase as string

// State
const pointsTransactions = ref([])
const isLoading = ref(true)
const searchQuery = ref('')

// Summary stats
const summary = ref({
  totalPointsIssued: 2850000,
  totalPointsUsed: 1250000,
  totalPointsExpired: 85000,
  activeUsers: 5680
})

// Fetch points transactions
const fetchPointsTransactions = async () => {
  isLoading.value = true
  try {
    const token = useCookie('token')
    const response = await $fetch(`${apiBase}/api/admin/points-transactions`, {
      headers: {
        Authorization: `Bearer ${token.value}`
      }
    })

    if (response.success) {
      pointsTransactions.value = response.data.data || response.data
    }
  } catch (error) {
    console.error('Failed to fetch points transactions:', error)
    // Mock data
    pointsTransactions.value = [
      { id: 1, user: 'สมชาย ใจดี', type: 'earned', points: 100, reason: 'ทำแบบทดสอบสำเร็จ', date: '2026-01-18' },
      { id: 2, user: 'สุดา สวยงาม', type: 'redeemed', points: 500, reason: 'แลกคูปองส่วนลด', date: '2026-01-18' },
      { id: 3, user: 'วิชัย มั่นคง', type: 'earned', points: 250, reason: 'เรียนจบคอร์ส', date: '2026-01-17' },
      { id: 4, user: 'พิมพ์ใจ รักเรียน', type: 'bonus', points: 1000, reason: 'โบนัสสมาชิกใหม่', date: '2026-01-17' },
      { id: 5, user: 'ชาติชาย เก่งมาก', type: 'expired', points: 200, reason: 'หมดอายุ', date: '2026-01-16' }
    ]
  } finally {
    isLoading.value = false
  }
}

// Get type badge
const getTypeBadge = (type: string) => {
  const badges = {
    earned: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
    redeemed: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
    bonus: 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400',
    expired: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'
  }
  return badges[type] || 'bg-gray-100 text-gray-700'
}

const getTypeLabel = (type: string) => {
  const labels = { earned: 'ได้รับ', redeemed: 'แลกใช้', bonus: 'โบนัส', expired: 'หมดอายุ' }
  return labels[type] || type
}

onMounted(() => {
  fetchPointsTransactions()
})
</script>

<template>
  <div class="space-y-6">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-gray-800 dark:text-white">จัดการ Points</h1>
        <p class="text-gray-500 dark:text-gray-400 mt-1">จัดการระบบคะแนนและธุรกรรม Points</p>
      </div>
      <button class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 rounded-xl text-white transition-colors">
        <Icon icon="fluent:add-24-regular" class="w-5 h-5" />
        มอบ Points
      </button>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
      <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-sm border border-gray-100 dark:border-gray-700">
        <div class="flex items-center gap-3">
          <div class="p-3 bg-green-100 dark:bg-green-900/30 rounded-xl">
            <Icon icon="fluent:coin-stack-24-regular" class="w-6 h-6 text-green-600" />
          </div>
          <div>
            <p class="text-sm text-gray-500">Points ที่ออก</p>
            <p class="text-xl font-bold text-gray-800 dark:text-white">{{ summary.totalPointsIssued.toLocaleString() }}</p>
          </div>
        </div>
      </div>

      <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-sm border border-gray-100 dark:border-gray-700">
        <div class="flex items-center gap-3">
          <div class="p-3 bg-blue-100 dark:bg-blue-900/30 rounded-xl">
            <Icon icon="fluent:gift-24-regular" class="w-6 h-6 text-blue-600" />
          </div>
          <div>
            <p class="text-sm text-gray-500">Points ที่ใช้แล้ว</p>
            <p class="text-xl font-bold text-gray-800 dark:text-white">{{ summary.totalPointsUsed.toLocaleString() }}</p>
          </div>
        </div>
      </div>

      <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-sm border border-gray-100 dark:border-gray-700">
        <div class="flex items-center gap-3">
          <div class="p-3 bg-red-100 dark:bg-red-900/30 rounded-xl">
            <Icon icon="fluent:calendar-cancel-24-regular" class="w-6 h-6 text-red-600" />
          </div>
          <div>
            <p class="text-sm text-gray-500">Points หมดอายุ</p>
            <p class="text-xl font-bold text-gray-800 dark:text-white">{{ summary.totalPointsExpired.toLocaleString() }}</p>
          </div>
        </div>
      </div>

      <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-sm border border-gray-100 dark:border-gray-700">
        <div class="flex items-center gap-3">
          <div class="p-3 bg-purple-100 dark:bg-purple-900/30 rounded-xl">
            <Icon icon="fluent:people-24-regular" class="w-6 h-6 text-purple-600" />
          </div>
          <div>
            <p class="text-sm text-gray-500">ผู้ใช้ที่มี Points</p>
            <p class="text-xl font-bold text-gray-800 dark:text-white">{{ summary.activeUsers.toLocaleString() }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Search -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl p-4 shadow-sm border border-gray-100 dark:border-gray-700">
      <div class="relative">
        <Icon icon="fluent:search-24-regular" class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
        <input
          v-model="searchQuery"
          type="text"
          placeholder="ค้นหาผู้ใช้..."
          class="w-full pl-10 pr-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-gray-800 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500"
        />
      </div>
    </div>

    <!-- Transactions Table -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
      <div v-if="isLoading" class="p-8 text-center">
        <Icon icon="fluent:spinner-ios-20-regular" class="w-8 h-8 text-indigo-600 animate-spin mx-auto" />
        <p class="text-gray-500 mt-2">กำลังโหลดข้อมูล...</p>
      </div>

      <div v-else class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-gray-50 dark:bg-gray-700/50">
            <tr>
              <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase">ผู้ใช้</th>
              <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase">ประเภท</th>
              <th class="px-6 py-4 text-right text-xs font-medium text-gray-500 uppercase">Points</th>
              <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase">เหตุผล</th>
              <th class="px-6 py-4 text-right text-xs font-medium text-gray-500 uppercase">วันที่</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
            <tr v-for="tx in pointsTransactions" :key="tx.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
              <td class="px-6 py-4 font-medium text-gray-800 dark:text-white">{{ tx.user }}</td>
              <td class="px-6 py-4">
                <span :class="[getTypeBadge(tx.type), 'px-2.5 py-1 rounded-full text-xs font-medium']">
                  {{ getTypeLabel(tx.type) }}
                </span>
              </td>
              <td class="px-6 py-4 text-right font-medium" :class="tx.type === 'redeemed' || tx.type === 'expired' ? 'text-red-600' : 'text-green-600'">
                {{ tx.type === 'redeemed' || tx.type === 'expired' ? '-' : '+' }}{{ tx.points.toLocaleString() }}
              </td>
              <td class="px-6 py-4 text-gray-600 dark:text-gray-300">{{ tx.reason }}</td>
              <td class="px-6 py-4 text-right text-gray-500">{{ tx.date }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>
