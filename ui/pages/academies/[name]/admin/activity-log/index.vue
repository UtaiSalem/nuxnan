<script setup lang="ts">
import { Icon } from '@iconify/vue'

definePageMeta({ layout: 'main' })

const academyId = inject<Ref<number | null>>('academyId', ref(null))
const api = useApi()
const { getAvatarUrl } = useAvatar()

type Tab = 'members' | 'audit'
const activeTab = ref<Tab>('members')

// ----- Member activity state -----
const logs = ref<any[]>([])
const stats = ref<any>({})
const availableActions = ref<any[]>([])
const loading = ref(false)

const filterAction = ref('')
const filterFromDate = ref('')
const filterToDate = ref('')

// drill-down / ค้นหาตามผู้ทำ-เป้าหมาย (ใช้ filter ของ index เดิม)
const focusUser = ref<{ id: number; name: string; mode: 'performer' | 'target' } | null>(null)

const pagination = ref({ current_page: 1, last_page: 1, per_page: 20, total: 0 })
const exporting = ref(false)

// ----- Audit (ประวัติการแก้ไข) state -----
const auditLogs = ref<any[]>([])
const auditLoading = ref(false)
const auditFromDate = ref('')
const auditToDate = ref('')
const auditPagination = ref({ current_page: 1, last_page: 1, per_page: 20, total: 0 })
const auditLoaded = ref(false)

const hasMemberFilter = computed(() => !!(filterAction.value || filterFromDate.value || filterToDate.value || focusUser.value))

// ----- Member activity loaders -----
const loadLogs = async (page = 1) => {
  if (!academyId.value) return
  loading.value = true
  try {
    const params = new URLSearchParams()
    params.append('page', page.toString())
    if (filterAction.value) params.append('action', filterAction.value)
    if (filterFromDate.value) params.append('from_date', filterFromDate.value)
    if (filterToDate.value) params.append('to_date', filterToDate.value)
    if (focusUser.value) {
      params.append(focusUser.value.mode === 'performer' ? 'user_id' : 'target_user_id', focusUser.value.id.toString())
    }
    const res = await api.get<any>(`/api/academies/${academyId.value}/activity-log?${params.toString()}`)
    if (res?.success) {
      logs.value = res.logs
      pagination.value = res.pagination
    }
  } catch (e) {
    console.error('Error loading logs:', e)
  } finally {
    loading.value = false
  }
}

const loadStats = async () => {
  if (!academyId.value) return
  try {
    const res = await api.get<any>(`/api/academies/${academyId.value}/activity-log/statistics`)
    if (res?.success) stats.value = res.statistics
  } catch (e) {
    console.error('Error loading stats:', e)
  }
}

const loadAvailableActions = async () => {
  try {
    const res = await api.get<any>('/api/academies/activity-log/actions')
    if (res?.success) availableActions.value = res.actions
  } catch (e) {
    console.error('Error loading actions:', e)
  }
}

const resetFilters = () => {
  filterAction.value = ''
  filterFromDate.value = ''
  filterToDate.value = ''
  focusUser.value = null
  loadLogs()
}

const focusOnUser = (user: { id: number; name: string } | null | undefined, mode: 'performer' | 'target') => {
  if (!user?.id) return
  focusUser.value = { id: user.id, name: user.name, mode }
  loadLogs()
}

const clearFocus = () => {
  focusUser.value = null
  loadLogs()
}

const changePage = (page: number) => loadLogs(page)

const exportLogs = async (format: 'xlsx' | 'csv') => {
  if (!academyId.value || exporting.value) return
  exporting.value = true
  try {
    const params = new URLSearchParams()
    if (filterAction.value) params.append('action', filterAction.value)
    if (filterFromDate.value) params.append('from_date', filterFromDate.value)
    if (filterToDate.value) params.append('to_date', filterToDate.value)
    if (format === 'csv') params.append('format', 'csv')
    const { blob, filename } = await api.getBlob(`/api/academies/${academyId.value}/activity-log/export?${params.toString()}`)
    const objectUrl = URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = objectUrl
    a.download = filename || `activity-log.${format}`
    document.body.appendChild(a)
    a.click()
    a.remove()
    URL.revokeObjectURL(objectUrl)
  } catch (e) {
    console.error('Export failed:', e)
  } finally {
    exporting.value = false
  }
}

// ----- Audit loaders -----
const loadAudit = async (page = 1) => {
  if (!academyId.value) return
  auditLoading.value = true
  try {
    const params = new URLSearchParams()
    params.append('page', page.toString())
    if (auditFromDate.value) params.append('from_date', auditFromDate.value)
    if (auditToDate.value) params.append('to_date', auditToDate.value)
    const res = await api.get<any>(`/api/academies/${academyId.value}/audit-logs?${params.toString()}`)
    if (res?.success) {
      auditLogs.value = res.logs
      auditPagination.value = res.pagination
      auditLoaded.value = true
    }
  } catch (e) {
    console.error('Error loading audit logs:', e)
  } finally {
    auditLoading.value = false
  }
}

const resetAuditFilters = () => {
  auditFromDate.value = ''
  auditToDate.value = ''
  loadAudit()
}

const switchTab = (tab: Tab) => {
  activeTab.value = tab
  if (tab === 'audit' && !auditLoaded.value) loadAudit()
}

// ----- Derived: by_day trend -----
const trend = computed(() => {
  const byDay = stats.value?.by_day || {}
  const entries = Object.entries(byDay).map(([date, count]) => ({ date, count: Number(count) }))
  const max = entries.reduce((m, e) => Math.max(m, e.count), 0)
  return { entries, max }
})

const activeUsers = computed<any[]>(() => stats.value?.active_users || [])

// ----- Helpers -----
const formatDate = (dateStr: string) =>
  new Date(dateStr).toLocaleDateString('th-TH', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' })

const formatDay = (dateStr: string) =>
  new Date(dateStr).toLocaleDateString('th-TH', { month: 'short', day: 'numeric' })

const iconBg: Record<string, string> = {
  blue: 'bg-blue-100 dark:bg-blue-900/30', green: 'bg-green-100 dark:bg-green-900/30',
  red: 'bg-red-100 dark:bg-red-900/30', orange: 'bg-orange-100 dark:bg-orange-900/30',
  purple: 'bg-purple-100 dark:bg-purple-900/30', indigo: 'bg-indigo-100 dark:bg-indigo-900/30',
  teal: 'bg-teal-100 dark:bg-teal-900/30', gray: 'bg-gray-100 dark:bg-gray-700',
}
const iconColor: Record<string, string> = {
  blue: 'text-blue-600 dark:text-blue-400', green: 'text-green-600 dark:text-green-400',
  red: 'text-red-600 dark:text-red-400', orange: 'text-orange-600 dark:text-orange-400',
  purple: 'text-purple-600 dark:text-purple-400', indigo: 'text-indigo-600 dark:text-indigo-400',
  teal: 'text-teal-600 dark:text-teal-400', gray: 'text-gray-600 dark:text-gray-300',
}
const getIconBgClass = (color: string) => iconBg[color] || iconBg.gray
const getIconColorClass = (color: string) => iconColor[color] || iconColor.gray

watch(academyId, (id) => {
  if (id) {
    loadAvailableActions()
    loadLogs()
    loadStats()
  }
}, { immediate: true })
</script>

<template>
  <div class="container mx-auto px-0 sm:px-4 py-6 space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 px-4 sm:px-0">
      <div class="min-w-0">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
          <Icon icon="mdi:history" class="w-7 h-7 text-purple-600 dark:text-purple-400 flex-shrink-0" />
          ประวัติกิจกรรม
        </h1>
        <p class="text-gray-500 dark:text-gray-400 text-sm mt-1">ติดตามกิจกรรมสมาชิกและประวัติการแก้ไขข้อมูลของโรงเรียน</p>
      </div>
      <div v-if="activeTab === 'members'" class="flex flex-wrap gap-2 flex-shrink-0">
        <button
          @click="exportLogs('xlsx')"
          :disabled="exporting"
          class="min-h-[44px] sm:min-h-0 inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-medium bg-green-600 text-white hover:bg-green-700 disabled:opacity-50 transition-colors"
        >
          <Icon icon="mdi:microsoft-excel" class="w-4 h-4" /> Excel
        </button>
        <button
          @click="exportLogs('csv')"
          :disabled="exporting"
          class="min-h-[44px] sm:min-h-0 inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-medium border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-50 transition-colors"
        >
          <Icon icon="mdi:file-delimited-outline" class="w-4 h-4" /> CSV
        </button>
      </div>
    </div>

    <!-- Tabs -->
    <div class="flex gap-2 px-4 sm:px-0 border-b border-gray-200 dark:border-gray-700">
      <button
        @click="switchTab('members')"
        :class="[
          'min-h-[44px] px-4 py-2 text-sm font-medium border-b-2 -mb-px transition-colors',
          activeTab === 'members'
            ? 'border-purple-600 text-purple-600 dark:text-purple-400 dark:border-purple-400'
            : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200',
        ]"
      >
        กิจกรรมสมาชิก
      </button>
      <button
        @click="switchTab('audit')"
        :class="[
          'min-h-[44px] px-4 py-2 text-sm font-medium border-b-2 -mb-px transition-colors',
          activeTab === 'audit'
            ? 'border-purple-600 text-purple-600 dark:text-purple-400 dark:border-purple-400'
            : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200',
        ]"
      >
        ประวัติการแก้ไข
      </button>
    </div>

    <!-- ===================== MEMBERS TAB ===================== -->
    <div v-show="activeTab === 'members'" class="space-y-6 px-4 sm:px-0">
      <!-- Statistics Cards (30-day window) -->
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-4">
          <div class="flex items-center justify-between gap-2">
            <div class="min-w-0">
              <p class="text-sm text-gray-500 dark:text-gray-400">กิจกรรม (30 วัน)</p>
              <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.total || 0 }}</p>
            </div>
            <div class="w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0 bg-purple-100 dark:bg-purple-900/30">
              <Icon icon="mdi:history" class="w-6 h-6 text-purple-600 dark:text-purple-400" />
            </div>
          </div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-4">
          <div class="flex items-center justify-between gap-2">
            <div class="min-w-0">
              <p class="text-sm text-gray-500 dark:text-gray-400">อนุมัติสมาชิก</p>
              <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ stats.by_action?.approve || 0 }}</p>
            </div>
            <div class="w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0 bg-green-100 dark:bg-green-900/30">
              <Icon icon="mdi:account-plus" class="w-6 h-6 text-green-600 dark:text-green-400" />
            </div>
          </div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-4">
          <div class="flex items-center justify-between gap-2">
            <div class="min-w-0">
              <p class="text-sm text-gray-500 dark:text-gray-400">เปลี่ยนบทบาท</p>
              <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ stats.by_action?.role_change || 0 }}</p>
            </div>
            <div class="w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0 bg-blue-100 dark:bg-blue-900/30">
              <Icon icon="mdi:badge-account" class="w-6 h-6 text-blue-600 dark:text-blue-400" />
            </div>
          </div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-4">
          <div class="flex items-center justify-between gap-2">
            <div class="min-w-0">
              <p class="text-sm text-gray-500 dark:text-gray-400">ถูกระงับ</p>
              <p class="text-2xl font-bold text-orange-600 dark:text-orange-400">{{ stats.by_action?.suspend || 0 }}</p>
            </div>
            <div class="w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0 bg-orange-100 dark:bg-orange-900/30">
              <Icon icon="mdi:account-lock" class="w-6 h-6 text-orange-600 dark:text-orange-400" />
            </div>
          </div>
        </div>
      </div>

      <!-- Trend + Active users -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <!-- by_day trend -->
        <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-4">
          <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200 mb-3">แนวโน้มกิจกรรม (30 วันล่าสุด)</h3>
          <div v-if="trend.entries.length" class="flex items-end gap-1 h-28 overflow-x-auto">
            <div
              v-for="e in trend.entries"
              :key="e.date"
              class="flex-1 min-w-[8px] flex flex-col items-center justify-end group"
              :title="`${formatDay(e.date)}: ${e.count}`"
            >
              <div
                class="w-full rounded-t bg-purple-400 dark:bg-purple-500 group-hover:bg-purple-600 transition-colors"
                :style="{ height: trend.max ? Math.max(4, (e.count / trend.max) * 100) + '%' : '4px' }"
              ></div>
            </div>
          </div>
          <p v-else class="text-sm text-gray-400 dark:text-gray-500 py-8 text-center">ยังไม่มีข้อมูลในช่วง 30 วัน</p>
        </div>

        <!-- active users -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-4">
          <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200 mb-3">ผู้ใช้ที่เคลื่อนไหวมากสุด</h3>
          <ul v-if="activeUsers.length" class="space-y-2">
            <li v-for="(a, i) in activeUsers" :key="a.user?.id || i">
              <button
                @click="focusOnUser(a.user, 'performer')"
                class="w-full flex items-center gap-2 text-left rounded-lg px-2 py-1.5 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"
              >
                <img :src="getAvatarUrl(a.user, 40)" :alt="a.user?.name" class="w-8 h-8 rounded-full object-cover flex-shrink-0" />
                <span class="flex-1 min-w-0 truncate text-sm text-gray-700 dark:text-gray-200">{{ a.user?.name || 'ไม่ทราบ' }}</span>
                <span class="flex-shrink-0 text-xs font-medium text-purple-600 dark:text-purple-400">{{ a.count }}</span>
              </button>
            </li>
          </ul>
          <p v-else class="text-sm text-gray-400 dark:text-gray-500 py-6 text-center">ไม่มีข้อมูล</p>
        </div>
      </div>

      <!-- Filters -->
      <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-4">
        <div class="flex flex-col sm:flex-row flex-wrap gap-3">
          <select
            v-model="filterAction"
            class="min-h-[44px] sm:min-h-0 px-4 py-2 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
            @change="loadLogs()"
          >
            <option value="">ทุกกิจกรรม</option>
            <option v-for="action in availableActions" :key="action.value" :value="action.value">{{ action.label }}</option>
          </select>
          <input v-model="filterFromDate" type="date" class="min-h-[44px] sm:min-h-0 px-4 py-2 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500" @change="loadLogs()" />
          <input v-model="filterToDate" type="date" class="min-h-[44px] sm:min-h-0 px-4 py-2 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500" @change="loadLogs()" />
          <button
            v-if="hasMemberFilter"
            @click="resetFilters"
            class="min-h-[44px] sm:min-h-0 px-4 py-2 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
          >
            <Icon icon="mdi:filter-remove" class="w-5 h-5 mr-1 inline" /> ล้างตัวกรอง
          </button>
        </div>
        <!-- focus chip -->
        <div v-if="focusUser" class="mt-3">
          <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-sm bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300">
            {{ focusUser.mode === 'performer' ? 'ผู้ทำ' : 'เป้าหมาย' }}: {{ focusUser.name }}
            <button @click="clearFocus" class="hover:text-purple-900 dark:hover:text-purple-100"><Icon icon="mdi:close" class="w-4 h-4" /></button>
          </span>
        </div>
      </div>

      <!-- Timeline -->
      <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700">
        <div v-if="loading" class="flex justify-center py-12">
          <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-purple-600"></div>
        </div>
        <div v-else-if="logs.length === 0" class="text-center py-12">
          <Icon icon="mdi:timeline-clock-outline" class="w-16 h-16 mx-auto text-gray-300 dark:text-gray-600 mb-4" />
          <p class="text-gray-500 dark:text-gray-400">ไม่พบประวัติกิจกรรม</p>
        </div>
        <div v-else class="divide-y divide-gray-100 dark:divide-gray-700">
          <div v-for="log in logs" :key="log.id" class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
            <div class="flex items-start gap-3 sm:gap-4">
              <div class="flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center" :class="getIconBgClass(log.color)">
                <Icon :icon="log.icon" class="w-5 h-5" :class="getIconColorClass(log.color)" />
              </div>
              <div class="flex-1 min-w-0">
                <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                  <button v-if="log.user" @click="focusOnUser(log.user, 'performer')" class="font-medium text-gray-900 dark:text-white hover:text-purple-600 dark:hover:text-purple-400 break-words">
                    {{ log.user.name }}
                  </button>
                  <span v-else class="text-gray-500 dark:text-gray-400">ระบบ</span>
                  <span class="text-gray-600 dark:text-gray-300 break-words">{{ log.description }}</span>
                  <button v-if="log.target_user && log.target_user.id !== log.user?.id" @click="focusOnUser(log.target_user, 'target')" class="font-medium text-gray-900 dark:text-white hover:text-purple-600 dark:hover:text-purple-400 break-words">
                    {{ log.target_user.name }}
                  </button>
                </div>
                <div v-if="log.action === 'role_change' && log.old_values && log.new_values" class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                  <span class="bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded">{{ log.old_values.role || 'ไม่มี' }}</span>
                  <Icon icon="mdi:arrow-right" class="w-4 h-4 mx-1 inline" />
                  <span class="bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300 px-2 py-0.5 rounded">{{ log.new_values.role }}</span>
                </div>
                <div v-if="log.action === 'bulk_action' && log.new_values" class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                  ดำเนินการกับสมาชิก {{ log.new_values.count }} คน
                </div>
                <div class="mt-1 text-xs text-gray-400 dark:text-gray-500">
                  <Icon icon="mdi:clock-outline" class="w-3 h-3 inline mr-1" />
                  {{ log.created_at_human }}<span class="mx-1">·</span>{{ formatDate(log.created_at) }}
                </div>
              </div>
              <div class="hidden sm:flex -space-x-2 flex-shrink-0">
                <img :src="getAvatarUrl(log.user, 40)" :alt="log.user?.name" class="w-8 h-8 rounded-full border-2 border-white dark:border-gray-800 object-cover" />
                <img v-if="log.target_user && log.target_user.id !== log.user?.id" :src="getAvatarUrl(log.target_user, 40)" :alt="log.target_user.name" class="w-8 h-8 rounded-full border-2 border-white dark:border-gray-800 object-cover" />
              </div>
            </div>
          </div>
        </div>

        <div v-if="pagination.total > pagination.per_page" class="flex items-center justify-between gap-2 px-4 py-3 border-t border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
          <div class="text-sm text-gray-500 dark:text-gray-400">หน้า {{ pagination.current_page }} / {{ pagination.last_page }} ({{ pagination.total }})</div>
          <div class="flex gap-2">
            <button @click="changePage(pagination.current_page - 1)" :disabled="pagination.current_page === 1" class="min-h-[44px] sm:min-h-0 px-3 py-1 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 rounded-lg text-sm hover:bg-gray-100 dark:hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed">ก่อนหน้า</button>
            <button @click="changePage(pagination.current_page + 1)" :disabled="pagination.current_page === pagination.last_page" class="min-h-[44px] sm:min-h-0 px-3 py-1 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 rounded-lg text-sm hover:bg-gray-100 dark:hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed">ถัดไป</button>
          </div>
        </div>
      </div>
    </div>

    <!-- ===================== AUDIT TAB ===================== -->
    <div v-show="activeTab === 'audit'" class="space-y-4 px-4 sm:px-0">
      <div class="rounded-lg bg-blue-50 dark:bg-blue-900/20 border border-blue-100 dark:border-blue-800 p-3 text-sm text-blue-700 dark:text-blue-300">
        <Icon icon="mdi:information-outline" class="w-4 h-4 inline mr-1" />
        ประวัติการแก้ไขข้อมูล entity ของโรงเรียน (เริ่มบันทึกตั้งแต่ระบบรองรับ — รายการก่อนหน้าอาจไม่ปรากฏ)
      </div>

      <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-4">
        <div class="flex flex-col sm:flex-row flex-wrap gap-3">
          <input v-model="auditFromDate" type="date" class="min-h-[44px] sm:min-h-0 px-4 py-2 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500" @change="loadAudit()" />
          <input v-model="auditToDate" type="date" class="min-h-[44px] sm:min-h-0 px-4 py-2 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500" @change="loadAudit()" />
          <button v-if="auditFromDate || auditToDate" @click="resetAuditFilters" class="min-h-[44px] sm:min-h-0 px-4 py-2 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
            <Icon icon="mdi:filter-remove" class="w-5 h-5 mr-1 inline" /> ล้างตัวกรอง
          </button>
        </div>
      </div>

      <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700">
        <div v-if="auditLoading" class="flex justify-center py-12">
          <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-purple-600"></div>
        </div>
        <div v-else-if="auditLogs.length === 0" class="text-center py-12">
          <Icon icon="mdi:file-document-edit-outline" class="w-16 h-16 mx-auto text-gray-300 dark:text-gray-600 mb-4" />
          <p class="text-gray-500 dark:text-gray-400">ยังไม่มีประวัติการแก้ไข</p>
        </div>
        <div v-else class="divide-y divide-gray-100 dark:divide-gray-700">
          <div v-for="log in auditLogs" :key="log.id" class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
            <div class="flex items-start gap-3">
              <img :src="getAvatarUrl(log.user, 40)" :alt="log.user?.name" class="w-9 h-9 rounded-full object-cover flex-shrink-0" />
              <div class="flex-1 min-w-0">
                <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                  <span class="font-medium text-gray-900 dark:text-white break-words">{{ log.user?.name || 'ระบบ' }}</span>
                  <span class="text-gray-600 dark:text-gray-300 break-words">{{ log.description }}</span>
                  <span v-if="log.entity_type" class="text-xs px-2 py-0.5 rounded bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">{{ log.entity_type }}<span v-if="log.entity_id">#{{ log.entity_id }}</span></span>
                  <span v-if="log.module" class="text-xs px-2 py-0.5 rounded bg-indigo-100 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-300">{{ log.module }}</span>
                </div>
                <div class="mt-1 text-xs text-gray-400 dark:text-gray-500">
                  <Icon icon="mdi:clock-outline" class="w-3 h-3 inline mr-1" />
                  {{ log.created_at_human }}<span class="mx-1">·</span>{{ formatDate(log.created_at) }}
                </div>
              </div>
            </div>
          </div>
        </div>

        <div v-if="auditPagination.total > auditPagination.per_page" class="flex items-center justify-between gap-2 px-4 py-3 border-t border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
          <div class="text-sm text-gray-500 dark:text-gray-400">หน้า {{ auditPagination.current_page }} / {{ auditPagination.last_page }} ({{ auditPagination.total }})</div>
          <div class="flex gap-2">
            <button @click="loadAudit(auditPagination.current_page - 1)" :disabled="auditPagination.current_page === 1" class="min-h-[44px] sm:min-h-0 px-3 py-1 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 rounded-lg text-sm hover:bg-gray-100 dark:hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed">ก่อนหน้า</button>
            <button @click="loadAudit(auditPagination.current_page + 1)" :disabled="auditPagination.current_page === auditPagination.last_page" class="min-h-[44px] sm:min-h-0 px-3 py-1 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 rounded-lg text-sm hover:bg-gray-100 dark:hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed">ถัดไป</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
