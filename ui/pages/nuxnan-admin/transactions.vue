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
const transactions = ref([])
const isLoading = ref(true)
const currentPage = ref(1)
const totalPages = ref(1)
const perPage = ref(20)
const totalTransactions = ref(0)
const selectedType = ref('all')
const selectedStatus = ref('all')

// Types
const transactionTypes = [
  { value: 'all', label: 'ทั้งหมด' },
  { value: 'deposit', label: 'เติมเงิน' },
  { value: 'withdraw', label: 'ถอนเงิน' },
  { value: 'transfer', label: 'โอน' },
  { value: 'payment', label: 'ชำระเงิน' },
  { value: 'refund', label: 'คืนเงิน' }
]

// Statuses
const transactionStatuses = [
  { value: 'all', label: 'ทั้งหมด' },
  { value: 'pending', label: 'รอดำเนินการ' },
  { value: 'completed', label: 'สำเร็จ' },
  { value: 'failed', label: 'ล้มเหลว' },
  { value: 'cancelled', label: 'ยกเลิก' }
]

// Fetch transactions
const fetchTransactions = async () => {
  isLoading.value = true
  try {
    const token = useCookie('token')
    const params = new URLSearchParams({
      page: currentPage.value.toString(),
      per_page: perPage.value.toString(),
      ...(selectedType.value !== 'all' && { type: selectedType.value }),
      ...(selectedStatus.value !== 'all' && { status: selectedStatus.value })
    })

    const response = await $fetch(`${apiBase}/api/admin/wallet-transactions?${params}`, {
      headers: {
        Authorization: `Bearer ${token.value}`
      }
    })

    if (response.success) {
      transactions.value = response.data.data || response.data
      totalPages.value = response.data.last_page || 1
      totalTransactions.value = response.data.total || transactions.value.length
    }
  } catch (error) {
    console.error('Failed to fetch transactions:', error)
    // Mock data
    transactions.value = []
    totalTransactions.value = 0
  } finally {
    isLoading.value = false
  }
}

// Handle filter
const handleFilter = () => {
  currentPage.value = 1
  fetchTransactions()
}

// Handle pagination
const goToPage = (page: number) => {
  currentPage.value = page
  fetchTransactions()
}

// Get status badge
const getStatusBadge = (status: string) => {
  const badges: Record<string, string> = {
    pending: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
    completed: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
    failed: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
    cancelled: 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-400'
  }
  return badges[status] || badges.pending
}

// Format currency
const formatCurrency = (amount: number) => {
  return new Intl.NumberFormat('th-TH', {
    style: 'currency',
    currency: 'THB'
  }).format(amount)
}

// Format date
const formatDate = (dateStr: string) => {
  const date = new Date(dateStr)
  return date.toLocaleString('th-TH', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

onMounted(() => {
  fetchTransactions()
})
</script>

<template>
  <div class="space-y-6">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-gray-800 dark:text-white">ธุรกรรมทั้งหมด</h1>
        <p class="text-gray-500 dark:text-gray-400 mt-1">
          ธุรกรรม {{ totalTransactions.toLocaleString() }} รายการ
        </p>
      </div>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl p-4 shadow-sm border border-gray-100 dark:border-gray-700">
      <div class="flex flex-col sm:flex-row gap-4">
        <!-- Type Filter -->
        <select
          v-model="selectedType"
          class="px-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500"
          @change="handleFilter"
        >
          <option v-for="type in transactionTypes" :key="type.value" :value="type.value">
            {{ type.label }}
          </option>
        </select>

        <!-- Status Filter -->
        <select
          v-model="selectedStatus"
          class="px-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500"
          @change="handleFilter"
        >
          <option v-for="status in transactionStatuses" :key="status.value" :value="status.value">
            {{ status.label }}
          </option>
        </select>

        <button
          @click="fetchTransactions"
          class="px-4 py-2.5 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-xl text-gray-700 dark:text-gray-300 transition-colors inline-flex items-center gap-2"
        >
          <Icon icon="fluent:arrow-sync-24-regular" class="w-5 h-5" />
          รีเฟรช
        </button>
      </div>
    </div>

    <!-- Transactions Table -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
      <!-- Loading State -->
      <div v-if="isLoading" class="p-8 text-center">
        <Icon icon="fluent:spinner-ios-20-regular" class="w-8 h-8 text-indigo-600 animate-spin mx-auto" />
        <p class="text-gray-500 mt-2">กำลังโหลดข้อมูล...</p>
      </div>

      <!-- Empty State -->
      <div v-else-if="transactions.length === 0" class="p-8 text-center">
        <Icon icon="fluent:wallet-24-regular" class="w-12 h-12 text-gray-300 mx-auto" />
        <p class="text-gray-500 mt-2">ไม่พบธุรกรรม</p>
      </div>

      <!-- Table -->
      <div v-else class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-gray-50 dark:bg-gray-700/50">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">ID</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">ผู้ใช้</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">ประเภท</th>
              <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">จำนวน</th>
              <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">สถานะ</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">วันที่</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
            <tr v-for="tx in transactions" :key="tx.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
              <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">#{{ tx.id }}</td>
              <td class="px-4 py-3 text-sm text-gray-800 dark:text-white">{{ tx.user?.name || '-' }}</td>
              <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300 capitalize">{{ tx.type }}</td>
              <td class="px-4 py-3 text-sm text-right font-medium" :class="tx.amount >= 0 ? 'text-green-600' : 'text-red-600'">
                {{ formatCurrency(tx.amount) }}
              </td>
              <td class="px-4 py-3 text-center">
                <span class="px-2 py-1 text-xs rounded-lg" :class="getStatusBadge(tx.status)">
                  {{ transactionStatuses.find(s => s.value === tx.status)?.label || tx.status }}
                </span>
              </td>
              <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">
                {{ formatDate(tx.created_at) }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="totalPages > 1" class="p-4 border-t border-gray-100 dark:border-gray-700">
        <div class="flex justify-center gap-1">
          <button
            v-for="page in Math.min(totalPages, 10)"
            :key="page"
            @click="goToPage(page)"
            class="w-10 h-10 rounded-lg text-sm font-medium transition-colors"
            :class="currentPage === page 
              ? 'bg-indigo-600 text-white' 
              : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'"
          >
            {{ page }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
