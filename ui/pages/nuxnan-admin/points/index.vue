<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'
import { Icon } from '@iconify/vue'

definePageMeta({
  layout: 'nuxnan-admin-layout',
  middleware: 'nuxnan-admin'
})

const config = useRuntimeConfig()
const apiBase = config.public.apiBase as string

// Types
interface PointsUser {
  id: number
  name?: string
  username?: string
  email?: string
  avatar?: string
}

interface PointsTransaction {
  id: number
  user_id: number
  user?: PointsUser | null
  transaction_type: string
  amount: number
  balance_before?: number | string
  balance_after?: number | string
  description?: string | null
  status: string
  created_at: string
}

interface Summary {
  total_transactions: number
  total_earned: number
  total_spent: number
  total_users: number
}

// State
const pointsTransactions = ref<PointsTransaction[]>([])
const isLoading = ref(true)
const errorMessage = ref('')

const searchQuery = ref('')
const selectedType = ref('all')
const selectedStatus = ref('all')

const currentPage = ref(1)
const totalPages = ref(1)
const perPage = ref(20)

const summary = ref<Summary>({
  total_transactions: 0,
  total_earned: 0,
  total_spent: 0,
  total_users: 0
})

// Filter options
const transactionTypes = [
  { value: 'all', label: 'ทั้งหมด' },
  { value: 'earn', label: 'ได้แต้ม' },
  { value: 'spend', label: 'ใช้แต้ม' },
  { value: 'transfer_in', label: 'รับโอนแต้ม' },
  { value: 'transfer_out', label: 'โอนแต้มออก' },
  { value: 'transfer', label: 'โอนแต้ม' },
  { value: 'refund', label: 'คืนแต้ม' },
  { value: 'admin_adjust', label: 'ปรับโดยแอดมิน' },
  { value: 'conversion', label: 'แปลงแต้ม' }
]

const transactionStatuses = [
  { value: 'all', label: 'ทั้งหมด' },
  { value: 'pending', label: 'รอดำเนินการ' },
  { value: 'completed', label: 'เสร็จสิ้น' },
  { value: 'failed', label: 'ล้มเหลว' },
  { value: 'cancelled', label: 'ยกเลิก' }
]

// Direction: prefer the balance delta (correct even for admin_adjust /
// conversion, which can go either way); fall back to the type when the
// balance figures are missing or equal.
const debitTypes = ['spend', 'transfer_out', 'transfer']
const isDebit = (tx: PointsTransaction) => {
  const before = Number(tx.balance_before)
  const after = Number(tx.balance_after)
  if (!Number.isNaN(before) && !Number.isNaN(after) && before !== after) {
    return after < before
  }
  return debitTypes.includes(tx.transaction_type)
}

// Fetch points transactions
const fetchPointsTransactions = async () => {
  isLoading.value = true
  errorMessage.value = ''
  try {
    const token = useCookie('token')
    const params = new URLSearchParams({
      page: currentPage.value.toString(),
      per_page: perPage.value.toString(),
      ...(selectedType.value !== 'all' && { type: selectedType.value }),
      ...(selectedStatus.value !== 'all' && { status: selectedStatus.value }),
      ...(searchQuery.value.trim() && { search: searchQuery.value.trim() })
    })

    const response = await $fetch<any>(`${apiBase}/api/admin/points-transactions?${params}`, {
      headers: {
        Authorization: `Bearer ${token.value}`
      }
    })

    if (response.success) {
      pointsTransactions.value = response.data.data || []
      totalPages.value = response.data.last_page || 1
      if (response.summary) {
        summary.value = response.summary
      }
    }
  } catch (error) {
    console.error('Failed to fetch points transactions:', error)
    pointsTransactions.value = []
    totalPages.value = 1
    errorMessage.value = 'ไม่สามารถโหลดข้อมูลธุรกรรมได้ กรุณาลองใหม่อีกครั้ง'
  } finally {
    isLoading.value = false
  }
}

// Handle filter change
const handleFilter = () => {
  currentPage.value = 1
  fetchPointsTransactions()
}

// Debounced search
let searchTimer: ReturnType<typeof setTimeout> | null = null
watch(searchQuery, () => {
  if (searchTimer) clearTimeout(searchTimer)
  searchTimer = setTimeout(() => {
    currentPage.value = 1
    fetchPointsTransactions()
  }, 400)
})

// Pagination
const goToPage = (page: number) => {
  if (page < 1 || page > totalPages.value) return
  currentPage.value = page
  fetchPointsTransactions()
}

// Badges & labels
const getTypeBadge = (type: string) => {
  const badges: Record<string, string> = {
    earn: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
    transfer_in: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
    refund: 'bg-teal-100 text-teal-700 dark:bg-teal-900/30 dark:text-teal-400',
    spend: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
    transfer_out: 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400',
    transfer: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
    admin_adjust: 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400',
    conversion: 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400'
  }
  return badges[type] || 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300'
}

const getTypeLabel = (type: string) => {
  return transactionTypes.find(t => t.value === type)?.label || type
}

const getStatusBadge = (status: string) => {
  const badges: Record<string, string> = {
    pending: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
    completed: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
    failed: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
    cancelled: 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-400'
  }
  return badges[status] || badges.pending
}

const getStatusLabel = (status: string) => {
  return transactionStatuses.find(s => s.value === status)?.label || status
}

// Formatting
const formatPoints = (amount: number) => {
  return Number(amount || 0).toLocaleString('th-TH', {
    minimumFractionDigits: 0,
    maximumFractionDigits: 2
  })
}

const formatDate = (dateStr: string) => {
  if (!dateStr) return '-'
  const date = new Date(dateStr)
  if (Number.isNaN(date.getTime())) return '-'
  return date.toLocaleString('th-TH', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const userName = (tx: PointsTransaction) => {
  return tx.user?.name || tx.user?.username || tx.user?.email || `#${tx.user_id}`
}

const summaryCards = computed(() => [
  {
    label: 'ธุรกรรมทั้งหมด',
    value: summary.value.total_transactions.toLocaleString('th-TH'),
    icon: 'fluent:receipt-24-regular',
    wrap: 'bg-hopeui-info/10 dark:bg-hopeui-info/20',
    color: 'text-hopeui-info'
  },
  {
    label: 'แต้มเข้า (สำเร็จ)',
    value: formatPoints(summary.value.total_earned),
    icon: 'fluent:arrow-trending-24-regular',
    wrap: 'bg-green-100 dark:bg-green-900/30',
    color: 'text-green-600'
  },
  {
    label: 'แต้มออก (สำเร็จ)',
    value: formatPoints(summary.value.total_spent),
    icon: 'fluent:arrow-exit-20-regular',
    wrap: 'bg-orange-100 dark:bg-orange-900/30',
    color: 'text-orange-600'
  },
  {
    label: 'ผู้ใช้ที่มีธุรกรรม',
    value: summary.value.total_users.toLocaleString('th-TH'),
    icon: 'fluent:people-24-regular',
    wrap: 'bg-purple-100 dark:bg-purple-900/30',
    color: 'text-purple-600'
  }
])

onMounted(() => {
  fetchPointsTransactions()
})
</script>

<template>
  <div class="space-y-6">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div class="min-w-0">
        <h1 class="text-2xl font-bold text-slate-800 dark:text-white">จัดการ Points</h1>
        <p class="text-slate-500 dark:text-slate-400 mt-1">
          ดูธุรกรรมและการโอนแต้มของผู้ใช้ทั้งระบบ
        </p>
      </div>
      <button
        @click="fetchPointsTransactions"
        class="min-h-[44px] sm:min-h-0 inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 rounded-xl text-slate-700 dark:text-slate-300 transition-colors flex-shrink-0 whitespace-nowrap"
      >
        <Icon icon="fluent:arrow-sync-24-regular" class="w-5 h-5" :class="{ 'animate-spin': isLoading }" />
        รีเฟรช
      </button>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
      <div
        v-for="card in summaryCards"
        :key="card.label"
        class="bg-white dark:bg-slate-800 rounded-2xl p-4 sm:p-5 shadow-hopeui border border-slate-100 dark:border-slate-700"
      >
        <div class="flex items-center gap-3">
          <div class="p-3 rounded-xl flex-shrink-0" :class="card.wrap">
            <Icon :icon="card.icon" class="w-6 h-6" :class="card.color" />
          </div>
          <div class="min-w-0">
            <p class="text-sm text-slate-500 break-words">{{ card.label }}</p>
            <p class="text-lg sm:text-xl font-bold text-slate-800 dark:text-white break-words">{{ card.value }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-slate-800 rounded-2xl p-4 shadow-hopeui border border-slate-100 dark:border-slate-700">
      <div class="flex flex-col gap-4">
        <!-- Search -->
        <div class="relative">
          <Icon icon="fluent:search-24-regular" class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400" />
          <input
            v-model="searchQuery"
            type="text"
            placeholder="ค้นหาผู้ใช้ (ชื่อ / username / อีเมล)..."
            class="w-full pl-10 pr-4 py-2.5 min-h-[44px] sm:min-h-0 bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-800 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-hopeui-primary-500"
          />
        </div>

        <div class="flex flex-col sm:flex-row gap-3">
          <!-- Type Filter -->
          <select
            v-model="selectedType"
            class="flex-1 px-4 py-2.5 min-h-[44px] sm:min-h-0 bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-hopeui-primary-500"
            @change="handleFilter"
          >
            <option v-for="type in transactionTypes" :key="type.value" :value="type.value">
              ประเภท: {{ type.label }}
            </option>
          </select>

          <!-- Status Filter -->
          <select
            v-model="selectedStatus"
            class="flex-1 px-4 py-2.5 min-h-[44px] sm:min-h-0 bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-hopeui-primary-500"
            @change="handleFilter"
          >
            <option v-for="status in transactionStatuses" :key="status.value" :value="status.value">
              สถานะ: {{ status.label }}
            </option>
          </select>
        </div>
      </div>
    </div>

    <!-- Transactions Table -->
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-hopeui border border-slate-100 dark:border-slate-700 overflow-hidden">
      <!-- Loading State -->
      <div v-if="isLoading" class="p-8 text-center">
        <Icon icon="fluent:spinner-ios-20-regular" class="w-8 h-8 text-hopeui-primary-600 animate-spin mx-auto" />
        <p class="text-slate-500 mt-2">กำลังโหลดข้อมูล...</p>
      </div>

      <!-- Error State -->
      <div v-else-if="errorMessage" class="p-8 text-center">
        <Icon icon="fluent:error-circle-24-regular" class="w-12 h-12 text-red-400 mx-auto" />
        <p class="text-red-500 mt-2">{{ errorMessage }}</p>
        <button
          @click="fetchPointsTransactions"
          class="mt-4 min-h-[44px] px-4 py-2 bg-hopeui-primary-500 hover:bg-hopeui-primary-600 rounded-xl text-white transition-colors"
        >
          ลองใหม่
        </button>
      </div>

      <!-- Empty State -->
      <div v-else-if="pointsTransactions.length === 0" class="p-8 text-center">
        <Icon icon="fluent:coin-stack-24-regular" class="w-12 h-12 text-slate-300 mx-auto" />
        <p class="text-slate-500 mt-2">ไม่พบธุรกรรม</p>
      </div>

      <!-- Table -->
      <div v-else class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-slate-50 dark:bg-slate-700/50">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase whitespace-nowrap">ผู้ใช้</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase whitespace-nowrap">ประเภท</th>
              <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase whitespace-nowrap">แต้ม</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase whitespace-nowrap">รายละเอียด</th>
              <th class="px-4 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-400 uppercase whitespace-nowrap">สถานะ</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase whitespace-nowrap">วันที่</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
            <tr v-for="tx in pointsTransactions" :key="tx.id" class="hover:bg-slate-50 dark:hover:bg-slate-700/30">
              <td class="px-4 py-3 text-sm font-medium text-slate-800 dark:text-white whitespace-nowrap">{{ userName(tx) }}</td>
              <td class="px-4 py-3 whitespace-nowrap">
                <span :class="[getTypeBadge(tx.transaction_type), 'px-2.5 py-1 rounded-full text-xs font-medium']">
                  {{ getTypeLabel(tx.transaction_type) }}
                </span>
              </td>
              <td
                class="px-4 py-3 text-sm text-right font-medium whitespace-nowrap"
                :class="isDebit(tx) ? 'text-red-600' : 'text-green-600'"
              >
                {{ isDebit(tx) ? '-' : '+' }}{{ formatPoints(tx.amount) }}
              </td>
              <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-300 min-w-0 max-w-[280px] break-words">{{ tx.description || '-' }}</td>
              <td class="px-4 py-3 text-center whitespace-nowrap">
                <span class="px-2 py-1 text-xs rounded-lg" :class="getStatusBadge(tx.status)">
                  {{ getStatusLabel(tx.status) }}
                </span>
              </td>
              <td class="px-4 py-3 text-sm text-slate-500 dark:text-slate-400 whitespace-nowrap">{{ formatDate(tx.created_at) }}</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="totalPages > 1" class="p-4 border-t border-slate-100 dark:border-slate-700">
        <div class="flex items-center justify-center gap-1 flex-wrap">
          <button
            @click="goToPage(currentPage - 1)"
            :disabled="currentPage === 1"
            class="min-w-[44px] min-h-[44px] sm:min-w-[40px] sm:min-h-[40px] flex items-center justify-center rounded-lg text-sm font-medium bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-600 disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
          >
            <Icon icon="fluent:chevron-left-24-regular" class="w-5 h-5" />
          </button>
          <button
            v-for="page in Math.min(totalPages, 10)"
            :key="page"
            @click="goToPage(page)"
            class="min-w-[44px] min-h-[44px] sm:min-w-[40px] sm:min-h-[40px] rounded-lg text-sm font-medium transition-colors"
            :class="currentPage === page
              ? 'bg-hopeui-primary-500 text-white'
              : 'bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-600'"
          >
            {{ page }}
          </button>
          <button
            @click="goToPage(currentPage + 1)"
            :disabled="currentPage === totalPages"
            class="min-w-[44px] min-h-[44px] sm:min-w-[40px] sm:min-h-[40px] flex items-center justify-center rounded-lg text-sm font-medium bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-600 disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
          >
            <Icon icon="fluent:chevron-right-24-regular" class="w-5 h-5" />
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
