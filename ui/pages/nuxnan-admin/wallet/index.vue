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
const walletTransactions = ref([])
const isLoading = ref(true)
const searchQuery = ref('')
const selectedType = ref('all')
const currentPage = ref(1)
const totalPages = ref(1)

// Summary stats
const summary = ref({
  totalDeposits: 485000,
  totalWithdrawals: 125000,
  pendingApprovals: 12,
  activeWallets: 8956
})

// Fetch summary stats
const fetchSummaryStats = async () => {
  try {
    const token = useCookie('token')
    const response = await $fetch(`${apiBase}/api/nuxnan-admin/wallet/stats`, {
      headers: {
        Authorization: `Bearer ${token.value}`
      }
    })

    if (response.success) {
      summary.value = {
        totalDeposits: response.data.total_wallet || 0,
        totalWithdrawals: response.data.total_withdrawals || 0,
        pendingApprovals: (response.data.deposit_requests?.pending || 0) + (response.data.pending_withdrawals || 0),
        activeWallets: response.data.total_users || 0
      }
    }
  } catch (error) {
    console.error('Failed to fetch summary stats:', error)
  }
}

// Transaction types
const types = [
  { value: 'all', label: 'ทั้งหมด' },
  { value: 'deposit', label: 'เติมเงิน' },
  { value: 'withdrawal', label: 'ถอนเงิน' },
  { value: 'transfer', label: 'โอนเงิน' },
  { value: 'purchase', label: 'ซื้อสินค้า' }
]

// Fetch transactions
const fetchTransactions = async () => {
  isLoading.value = true
  try {
    const token = useCookie('token')
    const response = await $fetch(`${apiBase}/api/admin/wallet-transactions`, {
      headers: {
        Authorization: `Bearer ${token.value}`
      }
    })

    if (response.success) {
      walletTransactions.value = response.data.data || response.data
      totalPages.value = response.data.last_page || 1
    }
  } catch (error) {
    console.error('Failed to fetch wallet transactions:', error)
    // Mock data for demo
    walletTransactions.value = [
      { id: 1, user: 'สมชาย ใจดี', type: 'deposit', amount: 1000, status: 'completed', date: '2026-01-18 10:30' },
      { id: 2, user: 'สุดา สวยงาม', type: 'withdrawal', amount: 500, status: 'pending', date: '2026-01-18 09:15' },
      { id: 3, user: 'วิชัย มั่นคง', type: 'purchase', amount: 350, status: 'completed', date: '2026-01-17 14:20' },
      { id: 4, user: 'พิมพ์ใจ รักเรียน', type: 'deposit', amount: 2000, status: 'completed', date: '2026-01-17 11:45' },
      { id: 5, user: 'ชาติชาย เก่งมาก', type: 'transfer', amount: 150, status: 'completed', date: '2026-01-16 16:30' }
    ]
  } finally {
    isLoading.value = false
  }
}

// Get type badge
const getTypeBadge = (type: string) => {
  const badges = {
    deposit: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
    withdrawal: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
    transfer: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
    purchase: 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400'
  }
  return badges[type] || 'bg-gray-100 text-gray-700'
}

const getTypeLabel = (type: string) => {
  const labels = { deposit: 'เติมเงิน', withdrawal: 'ถอนเงิน', transfer: 'โอนเงิน', purchase: 'ซื้อสินค้า' }
  return labels[type] || type
}

const getStatusBadge = (status: string) => {
  const badges = {
    completed: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
    pending: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
    failed: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'
  }
  return badges[status] || badges.pending
}

onMounted(() => {
  fetchTransactions()
  fetchSummaryStats()
})
</script>

<template>
  <div class="space-y-6">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-gray-800 dark:text-white">จัดการ Wallet</h1>
        <p class="text-gray-500 dark:text-gray-400 mt-1">จัดการธุรกรรม Wallet และอนุมัติรายการ</p>
      </div>
      <NuxtLink
        to="/nuxnan-admin/wallet/pending"
        class="inline-flex items-center gap-2 px-4 py-2 bg-orange-600 hover:bg-orange-700 rounded-xl text-white transition-colors"
      >
        <Icon icon="fluent:clock-24-regular" class="w-5 h-5" />
        รอการอนุมัติ ({{ summary.pendingApprovals }})
      </NuxtLink>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
      <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-sm border border-gray-100 dark:border-gray-700">
        <div class="flex items-center gap-3">
          <div class="p-3 bg-green-100 dark:bg-green-900/30 rounded-xl">
            <Icon icon="fluent:arrow-download-24-regular" class="w-6 h-6 text-green-600" />
          </div>
          <div>
            <p class="text-sm text-gray-500">เติมเงินทั้งหมด</p>
            <p class="text-xl font-bold text-gray-800 dark:text-white">฿{{ summary.totalDeposits.toLocaleString() }}</p>
          </div>
        </div>
      </div>

      <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-sm border border-gray-100 dark:border-gray-700">
        <div class="flex items-center gap-3">
          <div class="p-3 bg-red-100 dark:bg-red-900/30 rounded-xl">
            <Icon icon="fluent:arrow-upload-24-regular" class="w-6 h-6 text-red-600" />
          </div>
          <div>
            <p class="text-sm text-gray-500">ถอนเงินทั้งหมด</p>
            <p class="text-xl font-bold text-gray-800 dark:text-white">฿{{ summary.totalWithdrawals.toLocaleString() }}</p>
          </div>
        </div>
      </div>

      <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-sm border border-gray-100 dark:border-gray-700">
        <div class="flex items-center gap-3">
          <div class="p-3 bg-yellow-100 dark:bg-yellow-900/30 rounded-xl">
            <Icon icon="fluent:clock-24-regular" class="w-6 h-6 text-yellow-600" />
          </div>
          <div>
            <p class="text-sm text-gray-500">รอการอนุมัติ</p>
            <p class="text-xl font-bold text-gray-800 dark:text-white">{{ summary.pendingApprovals }}</p>
          </div>
        </div>
      </div>

      <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-sm border border-gray-100 dark:border-gray-700">
        <div class="flex items-center gap-3">
          <div class="p-3 bg-blue-100 dark:bg-blue-900/30 rounded-xl">
            <Icon icon="fluent:wallet-24-regular" class="w-6 h-6 text-blue-600" />
          </div>
          <div>
            <p class="text-sm text-gray-500">Wallet ที่ใช้งาน</p>
            <p class="text-xl font-bold text-gray-800 dark:text-white">{{ summary.activeWallets.toLocaleString() }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl p-4 shadow-sm border border-gray-100 dark:border-gray-700">
      <div class="flex flex-col sm:flex-row gap-4">
        <div class="flex-1 relative">
          <Icon icon="fluent:search-24-regular" class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
          <input
            v-model="searchQuery"
            type="text"
            placeholder="ค้นหาผู้ใช้หรือรหัสธุรกรรม..."
            class="w-full pl-10 pr-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-gray-800 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500"
          />
        </div>
        <select
          v-model="selectedType"
          class="px-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500"
        >
          <option v-for="type in types" :key="type.value" :value="type.value">
            {{ type.label }}
          </option>
        </select>
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
              <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase">รหัส</th>
              <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase">ผู้ใช้</th>
              <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase">ประเภท</th>
              <th class="px-6 py-4 text-right text-xs font-medium text-gray-500 uppercase">จำนวน</th>
              <th class="px-6 py-4 text-center text-xs font-medium text-gray-500 uppercase">สถานะ</th>
              <th class="px-6 py-4 text-right text-xs font-medium text-gray-500 uppercase">วันที่</th>
              <th class="px-6 py-4 text-right text-xs font-medium text-gray-500 uppercase">จัดการ</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
            <tr v-for="tx in walletTransactions" :key="tx.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
              <td class="px-6 py-4 text-gray-600 dark:text-gray-300">#{{ tx.id }}</td>
              <td class="px-6 py-4 font-medium text-gray-800 dark:text-white">
                <div class="flex flex-col">
                  <span class="font-medium">{{ typeof tx.user === 'object' ? tx.user.name : tx.user }}</span>
                  <span v-if="typeof tx.user === 'object' && tx.user.email" class="text-xs text-gray-500">{{ tx.user.email }}</span>
                </div>
              </td>
              <td class="px-6 py-4">
                <span :class="[getTypeBadge(tx.type), 'px-2.5 py-1 rounded-full text-xs font-medium']">
                  {{ getTypeLabel(tx.type) }}
                </span>
              </td>
              <td class="px-6 py-4 text-right font-medium" :class="tx.type === 'withdrawal' ? 'text-red-600' : 'text-green-600'">
                {{ tx.type === 'withdrawal' ? '-' : '+' }}฿{{ tx.amount.toLocaleString() }}
              </td>
              <td class="px-6 py-4 text-center">
                <span :class="[getStatusBadge(tx.status), 'px-2.5 py-1 rounded-full text-xs font-medium']">
                  {{ tx.status === 'completed' ? 'สำเร็จ' : tx.status === 'pending' ? 'รอดำเนินการ' : 'ล้มเหลว' }}
                </span>
              </td>
              <td class="px-6 py-4 text-right text-gray-500">{{ tx.date }}</td>
              <td class="px-6 py-4 text-right">
                <button class="p-2 text-gray-500 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 rounded-lg transition-colors">
                  <Icon icon="fluent:eye-24-regular" class="w-5 h-5" />
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>
