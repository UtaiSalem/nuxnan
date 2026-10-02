<script setup lang="ts">
import { ref, watch, onMounted, computed } from 'vue'
import { Icon } from '@iconify/vue'

definePageMeta({
  layout: 'nuxnan-admin-layout',
  middleware: 'nuxnan-admin'
})

const config = useRuntimeConfig()
const apiBase = config.public.apiBase as string

interface MiniUser {
  id: number
  name?: string
  username?: string
  email?: string
}

interface FraudReport {
  id: number
  category: string
  description: string
  status: string
  created_at?: string
  reporter?: MiniUser | null
  reported_user?: (MiniUser & { points_suspended?: boolean; wallet_suspended?: boolean }) | null
  handled_by?: MiniUser | null
}

const CATEGORY_LABELS: Record<string, string> = {
  money_fraud: 'ฉ้อโกงเงิน / Wallet',
  point_fraud: 'ทุจริตแต้ม',
  scam: 'หลอกลวง',
  phishing: 'ฟิชชิง',
  fake_account: 'บัญชีปลอม',
  other: 'อื่น ๆ'
}

const STATUS_META: Record<string, { label: string; class: string }> = {
  pending: { label: 'รอตรวจสอบ', class: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400' },
  reviewing: { label: 'กำลังตรวจสอบ', class: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400' },
  action_taken: { label: 'ดำเนินการแล้ว', class: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' },
  dismissed: { label: 'ยกเลิก', class: 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300' }
}

const STATUS_TABS = [
  { value: '', label: 'ทั้งหมด' },
  { value: 'pending', label: 'รอตรวจสอบ' },
  { value: 'reviewing', label: 'กำลังตรวจสอบ' },
  { value: 'action_taken', label: 'ดำเนินการแล้ว' },
  { value: 'dismissed', label: 'ยกเลิก' }
]

const reports = ref<FraudReport[]>([])
const stats = ref<Record<string, number>>({ pending: 0, reviewing: 0, action_taken: 0, dismissed: 0, total: 0 })
const isLoading = ref(true)
const errorMessage = ref('')
const searchQuery = ref('')
const activeStatus = ref('')
const currentPage = ref(1)
const totalPages = ref(1)
const total = ref(0)

const authHeaders = () => {
  const token = useCookie('token')
  return { Authorization: `Bearer ${token.value}` }
}

const categoryLabel = (c: string) => CATEGORY_LABELS[c] || c
const statusMeta = (s: string) => STATUS_META[s] || { label: s, class: 'bg-slate-100 text-slate-600' }
const userName = (u?: MiniUser | null) => u?.name || u?.username || u?.email || (u ? `#${u.id}` : '-')

const fetchStats = async () => {
  try {
    const response = await $fetch<any>(`${apiBase}/api/admin/fraud-reports/stats`, { headers: authHeaders() })
    if (response.success) stats.value = response.data
  } catch (error) {
    console.error('Failed to fetch fraud-report stats:', error)
  }
}

const fetchReports = async () => {
  isLoading.value = true
  errorMessage.value = ''
  try {
    const params = new URLSearchParams({
      page: currentPage.value.toString(),
      per_page: '20',
      ...(activeStatus.value && { status: activeStatus.value }),
      ...(searchQuery.value.trim() && { search: searchQuery.value.trim() })
    })
    const response = await $fetch<any>(`${apiBase}/api/admin/fraud-reports?${params}`, { headers: authHeaders() })
    if (response.success) {
      reports.value = response.data.data || []
      totalPages.value = response.data.last_page || 1
      total.value = response.data.total || reports.value.length
    }
  } catch (error) {
    console.error('Failed to fetch fraud reports:', error)
    reports.value = []
    errorMessage.value = 'ไม่สามารถโหลดข้อมูลได้ กรุณาลองใหม่'
  } finally {
    isLoading.value = false
  }
}

const refresh = () => {
  fetchStats()
  fetchReports()
}

const selectStatus = (status: string) => {
  activeStatus.value = status
  currentPage.value = 1
  fetchReports()
}

let searchTimer: ReturnType<typeof setTimeout> | null = null
watch(searchQuery, () => {
  if (searchTimer) clearTimeout(searchTimer)
  searchTimer = setTimeout(() => {
    currentPage.value = 1
    fetchReports()
  }, 400)
})

const goToPage = (page: number) => {
  if (page < 1 || page > totalPages.value) return
  currentPage.value = page
  fetchReports()
}

const formatDate = (dateStr?: string | null) => {
  if (!dateStr) return '-'
  const date = new Date(dateStr)
  if (Number.isNaN(date.getTime())) return '-'
  return date.toLocaleString('th-TH', {
    year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit'
  })
}

const pendingCount = computed(() => stats.value.pending || 0)

onMounted(refresh)
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div class="min-w-0">
        <h1 class="text-2xl font-bold text-slate-800 dark:text-white">ร้องเรียนบัญชีทุจริต</h1>
        <p class="text-slate-500 dark:text-slate-400 mt-1">
          คำร้องเรียนจากสมาชิก ({{ total.toLocaleString() }} รายการ)
        </p>
      </div>
      <button
        @click="refresh"
        class="min-h-[44px] sm:min-h-0 inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 rounded-xl text-slate-700 dark:text-slate-300 transition-colors whitespace-nowrap flex-shrink-0"
      >
        <Icon icon="fluent:arrow-sync-24-regular" class="w-5 h-5" :class="{ 'animate-spin': isLoading }" />
        <span class="hidden sm:inline">รีเฟรช</span>
      </button>
    </div>

    <!-- Stat cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
      <div class="bg-white dark:bg-slate-800 rounded-2xl p-4 shadow-hopeui border border-slate-100 dark:border-slate-700">
        <p class="text-xs text-slate-500 dark:text-slate-400">รอตรวจสอบ</p>
        <p class="text-2xl font-bold text-amber-600 dark:text-amber-400 mt-1">{{ pendingCount.toLocaleString() }}</p>
      </div>
      <div class="bg-white dark:bg-slate-800 rounded-2xl p-4 shadow-hopeui border border-slate-100 dark:border-slate-700">
        <p class="text-xs text-slate-500 dark:text-slate-400">กำลังตรวจสอบ</p>
        <p class="text-2xl font-bold text-blue-600 dark:text-blue-400 mt-1">{{ (stats.reviewing || 0).toLocaleString() }}</p>
      </div>
      <div class="bg-white dark:bg-slate-800 rounded-2xl p-4 shadow-hopeui border border-slate-100 dark:border-slate-700">
        <p class="text-xs text-slate-500 dark:text-slate-400">ดำเนินการแล้ว</p>
        <p class="text-2xl font-bold text-green-600 dark:text-green-400 mt-1">{{ (stats.action_taken || 0).toLocaleString() }}</p>
      </div>
      <div class="bg-white dark:bg-slate-800 rounded-2xl p-4 shadow-hopeui border border-slate-100 dark:border-slate-700">
        <p class="text-xs text-slate-500 dark:text-slate-400">ยกเลิก</p>
        <p class="text-2xl font-bold text-slate-500 dark:text-slate-300 mt-1">{{ (stats.dismissed || 0).toLocaleString() }}</p>
      </div>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-slate-800 rounded-2xl p-4 shadow-hopeui border border-slate-100 dark:border-slate-700 space-y-3">
      <div class="flex gap-2 overflow-x-auto pb-1 -mb-1">
        <button
          v-for="tab in STATUS_TABS"
          :key="tab.value"
          @click="selectStatus(tab.value)"
          class="min-h-[40px] px-4 py-2 rounded-xl text-sm font-medium whitespace-nowrap flex-shrink-0 transition-colors"
          :class="activeStatus === tab.value
            ? 'bg-hopeui-primary-500 text-white'
            : 'bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-600'"
        >
          {{ tab.label }}
        </button>
      </div>
      <div class="relative">
        <Icon icon="fluent:search-24-regular" class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400" />
        <input
          v-model="searchQuery"
          type="text"
          placeholder="ค้นหาบัญชีที่ถูกร้องเรียน (ชื่อ / username / อีเมล)..."
          class="w-full pl-10 pr-4 py-2.5 min-h-[44px] sm:min-h-0 bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-800 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-hopeui-primary-500"
        />
      </div>
    </div>

    <!-- List -->
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-hopeui border border-slate-100 dark:border-slate-700 overflow-hidden">
      <div v-if="isLoading" class="p-8 text-center">
        <Icon icon="fluent:spinner-ios-20-regular" class="w-8 h-8 text-hopeui-primary-600 animate-spin mx-auto" />
        <p class="text-slate-500 mt-2">กำลังโหลดข้อมูล...</p>
      </div>

      <div v-else-if="errorMessage" class="p-8 text-center">
        <Icon icon="fluent:error-circle-24-regular" class="w-12 h-12 text-red-400 mx-auto" />
        <p class="text-red-500 mt-2">{{ errorMessage }}</p>
        <button @click="refresh" class="mt-4 min-h-[44px] px-4 py-2 bg-hopeui-primary-500 hover:bg-hopeui-primary-600 rounded-xl text-white">ลองใหม่</button>
      </div>

      <div v-else-if="reports.length === 0" class="p-8 text-center">
        <Icon icon="fluent:shield-checkmark-24-regular" class="w-12 h-12 text-slate-300 mx-auto" />
        <p class="text-slate-500 mt-2">ไม่มีคำร้องเรียน</p>
      </div>

      <ul v-else class="divide-y divide-slate-100 dark:divide-slate-700">
        <li v-for="r in reports" :key="r.id">
          <NuxtLink
            :to="`/nuxnan-admin/fraud-reports/${r.id}`"
            class="block p-4 hover:bg-slate-50 dark:hover:bg-slate-700/40 transition-colors"
          >
            <div class="flex flex-col sm:flex-row sm:items-start gap-3">
              <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-2">
                  <span class="font-semibold text-slate-800 dark:text-white break-words">{{ userName(r.reported_user) }}</span>
                  <span class="px-2 py-0.5 text-xs rounded-lg bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300">
                    {{ categoryLabel(r.category) }}
                  </span>
                  <span v-if="r.reported_user?.points_suspended" class="px-2 py-0.5 text-xs rounded-lg bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">ระงับแต้ม</span>
                  <span v-if="r.reported_user?.wallet_suspended" class="px-2 py-0.5 text-xs rounded-lg bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400">ระงับ Wallet</span>
                </div>
                <p class="text-sm text-slate-600 dark:text-slate-300 mt-1 line-clamp-2 break-words">{{ r.description }}</p>
                <p class="text-xs text-slate-400 mt-1">
                  โดย {{ userName(r.reporter) }} • {{ formatDate(r.created_at) }}
                </p>
              </div>
              <div class="flex items-center gap-2 flex-shrink-0">
                <span class="px-2.5 py-1 text-xs font-medium rounded-lg" :class="statusMeta(r.status).class">
                  {{ statusMeta(r.status).label }}
                </span>
                <Icon icon="fluent:chevron-right-24-regular" class="w-5 h-5 text-slate-300" />
              </div>
            </div>
          </NuxtLink>
        </li>
      </ul>

      <!-- Pagination -->
      <div v-if="totalPages > 1" class="p-4 border-t border-slate-100 dark:border-slate-700">
        <div class="flex items-center justify-center gap-1 flex-wrap">
          <button
            @click="goToPage(currentPage - 1)"
            :disabled="currentPage === 1"
            class="min-w-[44px] min-h-[44px] sm:min-w-[40px] sm:min-h-[40px] flex items-center justify-center rounded-lg text-sm font-medium bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-600 disabled:opacity-40 disabled:cursor-not-allowed"
          >
            <Icon icon="fluent:chevron-left-24-regular" class="w-5 h-5" />
          </button>
          <button
            v-for="page in Math.min(totalPages, 10)"
            :key="page"
            @click="goToPage(page)"
            class="min-w-[44px] min-h-[44px] sm:min-w-[40px] sm:min-h-[40px] rounded-lg text-sm font-medium"
            :class="currentPage === page
              ? 'bg-hopeui-primary-500 text-white'
              : 'bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-600'"
          >
            {{ page }}
          </button>
          <button
            @click="goToPage(currentPage + 1)"
            :disabled="currentPage === totalPages"
            class="min-w-[44px] min-h-[44px] sm:min-w-[40px] sm:min-h-[40px] flex items-center justify-center rounded-lg text-sm font-medium bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-600 disabled:opacity-40 disabled:cursor-not-allowed"
          >
            <Icon icon="fluent:chevron-right-24-regular" class="w-5 h-5" />
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
