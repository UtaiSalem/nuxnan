<script setup lang="ts">
import { ref, watch, onMounted } from 'vue'
import { Icon } from '@iconify/vue'

definePageMeta({
  layout: 'nuxnan-admin-layout',
  middleware: 'nuxnan-admin'
})

const config = useRuntimeConfig()
const apiBase = config.public.apiBase as string

interface AuditUser {
  id: number
  name?: string
  username?: string
  email?: string
}

interface AuditRecord {
  id: number
  user_id: number
  user_email?: string | null
  user?: AuditUser | null
  action: 'suspend' | 'restore'
  points_suspended: boolean
  wallet_suspended: boolean
  reason?: string | null
  performed_by?: AuditUser | null
  ip_address?: string | null
  created_at: string
}

const audits = ref<AuditRecord[]>([])
const isLoading = ref(true)
const errorMessage = ref('')
const searchQuery = ref('')
const selectedAction = ref('all')
const currentPage = ref(1)
const totalPages = ref(1)
const total = ref(0)

const actionOptions = [
  { value: 'all', label: 'ทั้งหมด' },
  { value: 'suspend', label: 'ระงับ' },
  { value: 'restore', label: 'ปลดระงับ' }
]

const fetchAudits = async () => {
  isLoading.value = true
  errorMessage.value = ''
  try {
    const token = useCookie('token')
    const params = new URLSearchParams({
      page: currentPage.value.toString(),
      per_page: '20',
      ...(selectedAction.value !== 'all' && { action: selectedAction.value }),
      ...(searchQuery.value.trim() && { search: searchQuery.value.trim() })
    })
    const response = await $fetch<any>(`${apiBase}/api/admin/suspension-audits?${params}`, {
      headers: { Authorization: `Bearer ${token.value}` }
    })
    if (response.success) {
      audits.value = response.data.data || []
      totalPages.value = response.data.last_page || 1
      total.value = response.data.total || audits.value.length
    }
  } catch (error) {
    console.error('Failed to fetch audit log:', error)
    audits.value = []
    errorMessage.value = 'ไม่สามารถโหลดประวัติได้ กรุณาลองใหม่'
  } finally {
    isLoading.value = false
  }
}

const handleFilter = () => {
  currentPage.value = 1
  fetchAudits()
}

let searchTimer: ReturnType<typeof setTimeout> | null = null
watch(searchQuery, () => {
  if (searchTimer) clearTimeout(searchTimer)
  searchTimer = setTimeout(handleFilter, 400)
})

const goToPage = (page: number) => {
  if (page < 1 || page > totalPages.value) return
  currentPage.value = page
  fetchAudits()
}

const auditUserName = (a: AuditRecord) =>
  a.user?.name || a.user?.username || a.user?.email || a.user_email || `#${a.user_id}`

const actorName = (a: AuditRecord) =>
  a.performed_by?.name || a.performed_by?.username || 'ระบบ'

const formatDate = (dateStr?: string) => {
  if (!dateStr) return '-'
  const date = new Date(dateStr)
  if (Number.isNaN(date.getTime())) return '-'
  return date.toLocaleString('th-TH', {
    year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit'
  })
}

onMounted(fetchAudits)
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div class="min-w-0">
        <div class="flex items-center gap-2">
          <NuxtLink to="/nuxnan-admin/blacklist" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
            <Icon icon="fluent:arrow-left-24-regular" class="w-6 h-6" />
          </NuxtLink>
          <h1 class="text-2xl font-bold text-slate-800 dark:text-white">ประวัติการระงับบัญชี</h1>
        </div>
        <p class="text-slate-500 dark:text-slate-400 mt-1">
          บันทึกการระงับ/ปลดระงับระบบแต้ม & Wallet ({{ total.toLocaleString() }} รายการ)
        </p>
      </div>
      <button
        @click="fetchAudits"
        class="min-h-[44px] sm:min-h-0 inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 rounded-xl text-slate-700 dark:text-slate-300 transition-colors flex-shrink-0 whitespace-nowrap"
      >
        <Icon icon="fluent:arrow-sync-24-regular" class="w-5 h-5" :class="{ 'animate-spin': isLoading }" />
        รีเฟรช
      </button>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-slate-800 rounded-2xl p-4 shadow-hopeui border border-slate-100 dark:border-slate-700">
      <div class="flex flex-col gap-3">
        <div class="relative">
          <Icon icon="fluent:search-24-regular" class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400" />
          <input
            v-model="searchQuery"
            type="text"
            placeholder="ค้นหาผู้ใช้ (ชื่อ / username / อีเมล)..."
            class="w-full pl-10 pr-4 py-2.5 min-h-[44px] sm:min-h-0 bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-800 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-hopeui-primary-500"
          />
        </div>
        <select
          v-model="selectedAction"
          class="px-4 py-2.5 min-h-[44px] sm:min-h-0 bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-hopeui-primary-500"
          @change="handleFilter"
        >
          <option v-for="opt in actionOptions" :key="opt.value" :value="opt.value">
            ประเภท: {{ opt.label }}
          </option>
        </select>
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
        <button @click="fetchAudits" class="mt-4 min-h-[44px] px-4 py-2 bg-hopeui-primary-500 hover:bg-hopeui-primary-600 rounded-xl text-white">ลองใหม่</button>
      </div>

      <div v-else-if="audits.length === 0" class="p-8 text-center">
        <Icon icon="fluent:history-24-regular" class="w-12 h-12 text-slate-300 mx-auto" />
        <p class="text-slate-500 mt-2">ยังไม่มีประวัติ</p>
      </div>

      <ul v-else class="divide-y divide-slate-100 dark:divide-slate-700">
        <li v-for="a in audits" :key="a.id" class="p-4 flex flex-col sm:flex-row sm:items-start gap-3">
          <div
            class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0"
            :class="a.action === 'suspend'
              ? 'bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400'
              : 'bg-green-100 text-green-600 dark:bg-green-900/30 dark:text-green-400'"
          >
            <Icon :icon="a.action === 'suspend' ? 'fluent:shield-error-24-regular' : 'fluent:arrow-undo-24-regular'" class="w-5 h-5" />
          </div>
          <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-center gap-2">
              <span class="font-medium text-slate-800 dark:text-white break-words">{{ auditUserName(a) }}</span>
              <span
                class="px-2 py-0.5 text-xs rounded-lg"
                :class="a.action === 'suspend'
                  ? 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'
                  : 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'"
              >
                {{ a.action === 'suspend' ? 'ระงับ' : 'ปลดระงับ' }}
              </span>
              <span v-if="a.action === 'suspend' && a.points_suspended" class="px-2 py-0.5 text-xs rounded-lg bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">แต้ม</span>
              <span v-if="a.action === 'suspend' && a.wallet_suspended" class="px-2 py-0.5 text-xs rounded-lg bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400">Wallet</span>
            </div>
            <p v-if="a.reason" class="text-sm text-slate-600 dark:text-slate-300 mt-1 break-words">เหตุผล: {{ a.reason }}</p>
            <p class="text-xs text-slate-400 mt-1">
              {{ formatDate(a.created_at) }} • โดย {{ actorName(a) }}
              <template v-if="a.ip_address"> • IP {{ a.ip_address }}</template>
            </p>
          </div>
          <NuxtLink
            :to="`/nuxnan-admin/users/${a.user_id}`"
            class="min-h-[44px] sm:min-h-0 inline-flex items-center gap-1 px-3 py-2 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 rounded-xl text-sm text-slate-700 dark:text-slate-300 flex-shrink-0"
          >
            <Icon icon="fluent:person-24-regular" class="w-4 h-4" />
            ดูบัญชี
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
