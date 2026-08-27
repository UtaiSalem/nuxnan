<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
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
const selectedTransaction = ref<any>(null)

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
    const response = await $fetch(`${apiBase}/api/admin/wallet/stats`, {
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

// Keep the legacy withdrawal option compatible while exposing all persisted types.
types.push(
  { value: 'withdraw', label: 'ถอนเงิน' },
  { value: 'conversion', label: 'แปลงแต้ม' },
  { value: 'course_income', label: 'รายได้รายวิชา' },
  { value: 'refund', label: 'คืนเงิน' },
  { value: 'admin_adjust', label: 'ปรับยอดโดย Admin' },
  { value: 'opening_balance', label: 'ยอดยกมา' },
)

// Fetch transactions
const fetchTransactions = async () => {
  isLoading.value = true
  try {
    const token = useCookie('token')
    const query = new URLSearchParams({ page: String(currentPage.value), per_page: '20' })
    if (selectedType.value !== 'all') query.set('transaction_type', selectedType.value)
    const response = await $fetch(`${apiBase}/api/admin/wallet-transactions?${query.toString()}`, {
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
    withdraw: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
    withdrawal: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
    transfer: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
    purchase: 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400',
    opening_balance: 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300'
  }
  return badges[type] || 'bg-slate-100 text-slate-700'
}

const getTypeLabel = (type: string) => {
  const labels = { deposit: 'เติมเงิน', withdrawal: 'ถอนเงิน', transfer: 'โอนเงิน', purchase: 'ซื้อสินค้า' }
  const extraLabels: Record<string, string> = {
    withdraw: 'ถอนเงิน',
    conversion: 'แปลงแต้ม',
    course_income: 'รายได้รายวิชา',
    refund: 'คืนเงิน',
    admin_adjust: 'ปรับยอดโดย Admin',
    opening_balance: 'ยอดยกมา',
  }
  return extraLabels[type] || labels[type] || type
}

const getTransactionType = (tx: any) => tx.transaction_type || tx.type || ''

const isMoneyOut = (tx: any) => {
  if (getTransactionType(tx) === 'opening_balance') return false
  if (tx.balance_before != null && tx.balance_after != null) {
    return Number(tx.balance_after) < Number(tx.balance_before)
  }
  return ['withdraw', 'withdrawal', 'purchase'].includes(getTransactionType(tx))
}

const getAmountPrefix = (tx: any) => getTransactionType(tx) === 'opening_balance' ? '' : (isMoneyOut(tx) ? '-' : '+')

const formatTransactionDate = (value: string | null | undefined) => {
  if (!value) return '-'
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return value
  return date.toLocaleString('th-TH', {
    dateStyle: 'medium',
    timeStyle: 'short',
  })
}

const openTransaction = (transaction: any) => {
  selectedTransaction.value = transaction
}

const closeTransaction = () => {
  selectedTransaction.value = null
}

const isDownloadingProof = ref(false)
const downloadProof = async (txId: number) => {
  isDownloadingProof.value = true
  try {
    const token = useCookie('token')
    const response = await $fetch(`${apiBase}/api/admin/wallet/withdrawals/${txId}/proof`, {
      headers: {
        Authorization: `Bearer ${token.value}`
      },
      responseType: 'blob'
    })
    const blob = response as unknown as Blob
    const url = window.URL.createObjectURL(blob)
    window.open(url, '_blank')
  } catch (error) {
    console.error('Failed to download proof:', error)
    alert('ไม่สามารถดาวน์โหลดหลักฐานการโอนได้')
  } finally {
    isDownloadingProof.value = false
  }
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

watch(selectedType, () => {
  currentPage.value = 1
  fetchTransactions()
})
</script>

<template>
  <div class="space-y-6">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-slate-800 dark:text-white">จัดการ Wallet</h1>
        <p class="text-slate-500 dark:text-slate-400 mt-1">จัดการธุรกรรม Wallet และอนุมัติรายการ</p>
      </div>
      <NuxtLink
        to="/nuxnan-admin/wallet/pending"
        class="inline-flex items-center gap-2 px-4 py-2 bg-hopeui-warning hover:opacity-90 rounded-xl text-white transition-colors"
      >
        <Icon icon="fluent:clock-24-regular" class="w-5 h-5" />
        รอการอนุมัติ ({{ summary.pendingApprovals }})
      </NuxtLink>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
      <div class="bg-white dark:bg-slate-800 rounded-2xl p-5 shadow-hopeui border border-slate-100 dark:border-slate-700">
        <div class="flex items-center gap-3">
          <div class="p-3 bg-green-100 dark:bg-green-900/30 rounded-xl">
            <Icon icon="fluent:arrow-download-24-regular" class="w-6 h-6 text-green-600" />
          </div>
          <div>
            <p class="text-sm text-slate-500">เติมเงินทั้งหมด</p>
            <p class="text-xl font-bold text-slate-800 dark:text-white">฿{{ summary.totalDeposits.toLocaleString() }}</p>
          </div>
        </div>
      </div>

      <div class="bg-white dark:bg-slate-800 rounded-2xl p-5 shadow-hopeui border border-slate-100 dark:border-slate-700">
        <div class="flex items-center gap-3">
          <div class="p-3 bg-red-100 dark:bg-red-900/30 rounded-xl">
            <Icon icon="fluent:arrow-upload-24-regular" class="w-6 h-6 text-red-600" />
          </div>
          <div>
            <p class="text-sm text-slate-500">ถอนเงินทั้งหมด</p>
            <p class="text-xl font-bold text-slate-800 dark:text-white">฿{{ summary.totalWithdrawals.toLocaleString() }}</p>
          </div>
        </div>
      </div>

      <div class="bg-white dark:bg-slate-800 rounded-2xl p-5 shadow-hopeui border border-slate-100 dark:border-slate-700">
        <div class="flex items-center gap-3">
          <div class="p-3 bg-hopeui-warning/10 dark:bg-hopeui-warning/20 rounded-xl">
            <Icon icon="fluent:clock-24-regular" class="w-6 h-6 text-hopeui-warning" />
          </div>
          <div>
            <p class="text-sm text-slate-500">รอการอนุมัติ</p>
            <p class="text-xl font-bold text-slate-800 dark:text-white">{{ summary.pendingApprovals }}</p>
          </div>
        </div>
      </div>

      <div class="bg-white dark:bg-slate-800 rounded-2xl p-5 shadow-hopeui border border-slate-100 dark:border-slate-700">
        <div class="flex items-center gap-3">
          <div class="p-3 bg-hopeui-info/10 dark:bg-hopeui-info/20 rounded-xl">
            <Icon icon="fluent:wallet-24-regular" class="w-6 h-6 text-hopeui-info" />
          </div>
          <div>
            <p class="text-sm text-slate-500">Wallet ที่ใช้งาน</p>
            <p class="text-xl font-bold text-slate-800 dark:text-white">{{ summary.activeWallets.toLocaleString() }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-slate-800 rounded-2xl p-4 shadow-hopeui border border-slate-100 dark:border-slate-700">
      <div class="flex flex-col sm:flex-row gap-4">
        <div class="flex-1 relative">
          <Icon icon="fluent:search-24-regular" class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400" />
          <input
            v-model="searchQuery"
            type="text"
            placeholder="ค้นหาผู้ใช้หรือรหัสธุรกรรม..."
            class="w-full pl-10 pr-4 py-2.5 bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-800 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-hopeui-primary-500"
          />
        </div>
        <select
          v-model="selectedType"
          class="px-4 py-2.5 bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-hopeui-primary-500"
        >
          <option v-for="type in types" :key="type.value" :value="type.value">
            {{ type.label }}
          </option>
        </select>
      </div>
    </div>

    <!-- Transactions Table -->
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-hopeui border border-slate-100 dark:border-slate-700 overflow-hidden">
      <div v-if="isLoading" class="p-8 text-center">
        <Icon icon="fluent:spinner-ios-20-regular" class="w-8 h-8 text-hopeui-primary-600 animate-spin mx-auto" />
        <p class="text-slate-500 mt-2">กำลังโหลดข้อมูล...</p>
      </div>

      <div v-else class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-slate-50 dark:bg-slate-700/50">
            <tr>
              <th class="px-6 py-4 text-left text-xs font-medium text-slate-500 uppercase">รหัส</th>
              <th class="px-6 py-4 text-left text-xs font-medium text-slate-500 uppercase">ผู้ใช้</th>
              <th class="px-6 py-4 text-left text-xs font-medium text-slate-500 uppercase">ประเภท</th>
              <th class="px-6 py-4 text-right text-xs font-medium text-slate-500 uppercase">จำนวน</th>
              <th class="px-6 py-4 text-center text-xs font-medium text-slate-500 uppercase">สถานะ</th>
              <th class="px-6 py-4 text-right text-xs font-medium text-slate-500 uppercase">วันที่</th>
              <th class="px-6 py-4 text-right text-xs font-medium text-slate-500 uppercase">จัดการ</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
            <tr v-for="tx in walletTransactions" :key="tx.id" class="hover:bg-slate-50 dark:hover:bg-slate-700/30">
              <td class="px-6 py-4 text-slate-600 dark:text-slate-300">#{{ tx.id }}</td>
              <td class="px-6 py-4 font-medium text-slate-800 dark:text-white">
                <div class="flex flex-col">
                  <span class="font-medium">{{ typeof tx.user === 'object' ? tx.user.name : tx.user }}</span>
                  <span v-if="typeof tx.user === 'object' && tx.user.email" class="text-xs text-slate-500">{{ tx.user.email }}</span>
                </div>
              </td>
              <td class="px-6 py-4">
                <span :class="[getTypeBadge(getTransactionType(tx)), 'px-2.5 py-1 rounded-full text-xs font-medium']">
                  {{ getTypeLabel(getTransactionType(tx)) }}
                </span>
              </td>
              <td class="px-6 py-4 text-right font-medium" :class="isMoneyOut(tx) ? 'text-red-600' : 'text-green-600'">
                {{ getAmountPrefix(tx) }}฿{{ Number(tx.amount).toLocaleString() }}
              </td>
              <td class="px-6 py-4 text-center">
                <span :class="[getStatusBadge(tx.status), 'px-2.5 py-1 rounded-full text-xs font-medium']">
                  {{ tx.status === 'completed' ? 'สำเร็จ' : tx.status === 'pending' ? 'รอดำเนินการ' : 'ล้มเหลว' }}
                </span>
              </td>
              <td class="px-6 py-4 text-right text-slate-500">{{ formatTransactionDate(tx.created_at || tx.date) }}</td>
              <td class="px-6 py-4 text-right">
                <button
                  type="button"
                  aria-label="ดูรายละเอียดธุรกรรม"
                  @click="openTransaction(tx)"
                  class="min-h-[44px] sm:min-h-0 min-w-[44px] sm:min-w-0 inline-flex items-center justify-center p-2 text-slate-500 hover:text-hopeui-primary-600 hover:bg-hopeui-primary-100 dark:hover:bg-hopeui-primary-900/30 rounded-lg transition-colors"
                >
                  <Icon icon="fluent:eye-24-regular" class="w-5 h-5" />
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <div
      v-if="selectedTransaction"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
      @click.self="closeTransaction"
    >
      <div class="w-full max-w-2xl max-h-[90vh] overflow-y-auto rounded-2xl bg-white dark:bg-slate-800 shadow-2xl">
        <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-700 p-4 sm:p-6">
          <div>
            <h2 class="text-lg font-bold text-slate-800 dark:text-white">รายละเอียดธุรกรรม #{{ selectedTransaction.id }}</h2>
            <p class="mt-1 text-sm text-slate-500">{{ formatTransactionDate(selectedTransaction.created_at || selectedTransaction.date) }}</p>
          </div>
          <button type="button" aria-label="ปิดรายละเอียด" class="rounded-lg p-2 text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-700" @click="closeTransaction">
            <Icon icon="fluent:dismiss-24-regular" class="h-5 w-5" />
          </button>
        </div>
        <div class="grid grid-cols-1 gap-4 p-4 sm:grid-cols-2 sm:p-6">
          <div class="rounded-xl bg-slate-50 p-4 dark:bg-slate-700/50">
            <p class="text-xs text-slate-500">ประเภท</p>
            <p class="mt-1 font-semibold text-slate-800 dark:text-white">{{ getTypeLabel(getTransactionType(selectedTransaction)) }}</p>
          </div>
          <div class="rounded-xl bg-slate-50 p-4 dark:bg-slate-700/50">
            <p class="text-xs text-slate-500">สถานะ</p>
            <p class="mt-1 font-semibold text-slate-800 dark:text-white">{{ selectedTransaction.status || '-' }}</p>
          </div>
          <div class="rounded-xl border border-slate-200 p-4 dark:border-slate-700">
            <p class="text-xs text-slate-500">ยอดก่อนทำรายการ</p>
            <p class="mt-1 text-lg font-bold text-slate-800 dark:text-white">฿{{ Number(selectedTransaction.balance_before || 0).toLocaleString() }}</p>
          </div>
          <div class="rounded-xl border border-slate-200 p-4 dark:border-slate-700">
            <p class="text-xs text-slate-500">ยอดหลังทำรายการ</p>
            <p class="mt-1 text-lg font-bold text-slate-800 dark:text-white">฿{{ Number(selectedTransaction.balance_after || 0).toLocaleString() }}</p>
          </div>
          <div class="sm:col-span-2 rounded-xl bg-slate-50 p-4 dark:bg-slate-700/50">
            <p class="text-xs text-slate-500">รายละเอียด</p>
            <p class="mt-1 text-sm text-slate-800 dark:text-slate-200">{{ selectedTransaction.description || '-' }}</p>
          </div>
          <div v-if="selectedTransaction.reference_number" class="rounded-xl bg-slate-50 p-4 dark:bg-slate-700/50">
            <p class="text-xs text-slate-500">เลขอ้างอิง</p>
            <p class="mt-1 break-all text-sm text-slate-800 dark:text-slate-200">{{ selectedTransaction.reference_number }}</p>
          </div>
          <div v-if="selectedTransaction.payment_reference" class="rounded-xl bg-slate-50 p-4 dark:bg-slate-700/50">
            <p class="text-xs text-slate-500">เลขอ้างอิงการจ่าย</p>
            <p class="mt-1 break-all text-sm text-slate-800 dark:text-slate-200">{{ selectedTransaction.payment_reference }}</p>
          </div>
          <div v-if="selectedTransaction.rejection_reason || selectedTransaction.admin_note" class="sm:col-span-2 rounded-xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-900/50 dark:bg-amber-900/20">
            <p class="text-xs text-amber-700 dark:text-amber-300">หมายเหตุผู้ดูแล</p>
            <p class="mt-1 text-sm text-amber-900 dark:text-amber-100">{{ selectedTransaction.rejection_reason || selectedTransaction.admin_note }}</p>
          </div>
          <div v-if="selectedTransaction.has_payout_proof" class="sm:col-span-2 rounded-xl bg-slate-50 p-4 dark:bg-slate-700/50 flex justify-between items-center">
            <div>
              <p class="text-xs text-slate-500">หลักฐานการโอนเงิน (สลิป)</p>
              <p class="mt-1 text-sm text-slate-800 dark:text-slate-200">มีไฟล์หลักฐานอัปโหลดในระบบ</p>
            </div>
            <button
              type="button"
              :disabled="isDownloadingProof"
              @click="downloadProof(selectedTransaction.id)"
              class="min-h-[44px] sm:min-h-0 px-4 py-2 bg-hopeui-primary-500 hover:bg-hopeui-primary-600 disabled:bg-slate-300 text-white rounded-xl text-xs font-semibold flex items-center gap-1.5 transition-colors"
            >
              <Icon v-if="isDownloadingProof" icon="svg-spinners:ring-resize" class="w-4 h-4 animate-spin" />
              <Icon v-else icon="fluent:document-pdf-24-regular" class="w-4 h-4" />
              <span>ดูหลักฐานการโอน</span>
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
