<script setup lang="ts">
/**
 * Course Eligibility - Exam Eligibility Status
 * หน้าแสดงสถานะสิทธิ์สอบของนักเรียน
 */
import { Icon } from '@iconify/vue'

const route = useRoute()
const api = useApi()
const courseId = computed(() => route.params.id as string)

// Inject from parent layout
const course = inject('course') as Ref<any>
const isCourseAdmin = inject('isCourseAdmin') as Ref<boolean>

// State
const eligibilitySummary = ref<any>(null)
const members = ref<any[]>([])
const isLoading = ref(true)
const isRefreshing = ref(false)

// Filters
const searchQuery = ref('')
const statusFilter = ref<string>('all')

// Pagination
const currentPage = ref(1)
const perPage = ref(20)

// Unlock modal
const showUnlockModal = ref(false)
const selectedMember = ref<any>(null)
const unlockReason = ref('')
const isUnlocking = ref(false)

// Bulk unlock
const isBulkUnlocking = ref(false)

const ineligibleCount = computed(() =>
  members.value.filter((m: any) => m.stats?.eligibility_status === 'ineligible').length
)

const showBulkBar = computed(() =>
  statusFilter.value === 'ineligible'
)

onMounted(() => {
  if (courseId.value) {
    fetchEligibility()
  }
})

const fetchEligibility = async () => {
  try {
    const res: any = await api.get(`/api/courses/${courseId.value}/eligibility/summary`)
    if (res.success && res.data) {
      eligibilitySummary.value = res.data
      members.value = res.data.members || []
    }
  } catch (err) {
    console.error('Failed to fetch eligibility:', err)
  } finally {
    isLoading.value = false
  }
}

const refreshEligibility = async () => {
  isRefreshing.value = true
  try {
    const res: any = await api.post(`/api/courses/${courseId.value}/eligibility/refresh`)
    if (res.success) {
      await fetchEligibility()
      useToast().success('อัพเดทสถานะสิทธิ์สอบแล้ว')
    }
  } catch (err) {
    console.error('Failed to refresh:', err)
    useToast().error('ไม่สามารถอัพเดทได้')
  } finally {
    isRefreshing.value = false
  }
}

// Filtered members
const filteredMembers = computed(() => {
  let result = [...members.value]

  // Search
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    result = result.filter((m: any) =>
      m.user?.name?.toLowerCase().includes(query) ||
      m.member_code?.toLowerCase().includes(query)
    )
  }

  // Status filter
  if (statusFilter.value !== 'all') {
    result = result.filter((m: any) => m.stats?.eligibility_status === statusFilter.value)
  }

  return result
})

// Paginated members
const paginatedMembers = computed(() => {
  const start = (currentPage.value - 1) * perPage.value
  return filteredMembers.value.slice(start, start + perPage.value)
})

const totalPages = computed(() => Math.ceil(filteredMembers.value.length / perPage.value))

// Status helpers
const getStatusColor = (status: string) => {
  switch (status) {
    case 'eligible': return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'
    case 'at_risk': return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200'
    case 'ineligible': return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'
    case 'unlocked': return 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200'
    default: return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200'
  }
}

const getStatusLabel = (status: string) => {
  switch (status) {
    case 'eligible': return 'มีสิทธิ์สอบ'
    case 'at_risk': return 'เสี่ยง'
    case 'ineligible': return 'หมดสิทธิ์'
    case 'unlocked': return 'ปลดล็อคแล้ว'
    default: return status
  }
}

const getStatusIcon = (status: string) => {
  switch (status) {
    case 'eligible': return 'heroicons:check-circle'
    case 'at_risk': return 'heroicons:exclamation-triangle'
    case 'ineligible': return 'heroicons:x-circle'
    case 'unlocked': return 'heroicons:lock-open'
    default: return 'heroicons:question-mark-circle'
  }
}

// Unlock actions
const openUnlockModal = (member: any) => {
  selectedMember.value = member
  unlockReason.value = ''
  showUnlockModal.value = true
}

const bulkUnlockIneligible = async () => {
  isBulkUnlocking.value = true
  try {
    const res: any = await api.post(
      `/api/courses/${courseId.value}/eligibility/bulk-unlock`,
      { only_ineligible: true, reason: 'admin bulk unlock' }
    )
    if (res.success) {
      useToast().success(`ปลดล็อคสิทธิ์สอบ ${ineligibleCount.value} คนแล้ว`)
      await fetchEligibility()
    }
  } catch (err) {
    console.error('Failed to bulk unlock:', err)
    useToast().error('ไม่สามารถปลดล็อคแบบกลุ่มได้')
  } finally {
    isBulkUnlocking.value = false
  }
}

const unlockMember = async () => {
  if (!selectedMember.value || !unlockReason.value.trim()) return

  isUnlocking.value = true
  try {
    const res: any = await api.post(
      `/api/courses/${courseId.value}/eligibility/members/${selectedMember.value.id}/unlock`,
      { reason: unlockReason.value }
    )
    if (res.success) {
      useToast().success('ปลดล็อคสิทธิ์สอบแล้ว')
      showUnlockModal.value = false
      await fetchEligibility()
    }
  } catch (err) {
    console.error('Failed to unlock:', err)
    useToast().error('ไม่สามารถปลดล็อคได้')
  } finally {
    isUnlocking.value = false
  }
}
</script>

<template>
  <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
    <!-- Header -->
    <div class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
          <div>
            <h1 class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white">
              สิทธิ์การสอบ
            </h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
              ตรวจสอบและจัดการสิทธิ์สอบของนักเรียน
            </p>
          </div>
          <div class="flex items-center gap-2 sm:gap-3">
            <button
              @click="refreshEligibility"
              :disabled="isRefreshing"
              class="inline-flex items-center px-3 sm:px-4 py-2 text-sm font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700 disabled:opacity-50"
            >
              <Icon
                :icon="isRefreshing ? 'heroicons:arrow-path' : 'heroicons:arrow-path'"
                class="w-4 h-4 mr-2"
                :class="{ 'animate-spin': isRefreshing }"
              />
              <span class="hidden sm:inline">อัพเดทสถานะ</span>
              <span class="sm:hidden">อัพเดท</span>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-6">
      <!-- Loading -->
      <div v-if="isLoading" class="flex justify-center py-12">
        <Icon icon="heroicons:arrow-path" class="w-8 h-8 animate-spin text-primary-600" />
      </div>

      <template v-else>
        <!-- Summary Cards -->
        <div class="grid grid-cols-2 lg:grid-cols-5 gap-3 sm:gap-4 mb-6">
          <div class="bg-white dark:bg-gray-800 rounded-xl p-4 sm:p-5 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center gap-3">
              <div class="p-2 sm:p-3 bg-gray-100 dark:bg-gray-700 rounded-lg">
                <Icon icon="heroicons:users" class="w-5 h-5 sm:w-6 sm:h-6 text-gray-600 dark:text-gray-300" />
              </div>
              <div>
                <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400">ทั้งหมด</p>
                <p class="text-lg sm:text-2xl font-bold text-gray-900 dark:text-white">
                  {{ eligibilitySummary?.total || 0 }}
                </p>
              </div>
            </div>
          </div>

          <div class="bg-white dark:bg-gray-800 rounded-xl p-4 sm:p-5 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center gap-3">
              <div class="p-2 sm:p-3 bg-green-100 dark:bg-green-900 rounded-lg">
                <Icon icon="heroicons:check-circle" class="w-5 h-5 sm:w-6 sm:h-6 text-green-600 dark:text-green-400" />
              </div>
              <div>
                <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400">มีสิทธิ์</p>
                <p class="text-lg sm:text-2xl font-bold text-green-600 dark:text-green-400">
                  {{ eligibilitySummary?.eligible || 0 }}
                </p>
              </div>
            </div>
          </div>

          <div class="bg-white dark:bg-gray-800 rounded-xl p-4 sm:p-5 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center gap-3">
              <div class="p-2 sm:p-3 bg-yellow-100 dark:bg-yellow-900 rounded-lg">
                <Icon icon="heroicons:exclamation-triangle" class="w-5 h-5 sm:w-6 sm:h-6 text-yellow-600 dark:text-yellow-400" />
              </div>
              <div>
                <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400">เสี่ยง</p>
                <p class="text-lg sm:text-2xl font-bold text-yellow-600 dark:text-yellow-400">
                  {{ eligibilitySummary?.at_risk || 0 }}
                </p>
              </div>
            </div>
          </div>

          <div class="bg-white dark:bg-gray-800 rounded-xl p-4 sm:p-5 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center gap-3">
              <div class="p-2 sm:p-3 bg-red-100 dark:bg-red-900 rounded-lg">
                <Icon icon="heroicons:x-circle" class="w-5 h-5 sm:w-6 sm:h-6 text-red-600 dark:text-red-400" />
              </div>
              <div>
                <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400">หมดสิทธิ์</p>
                <p class="text-lg sm:text-2xl font-bold text-red-600 dark:text-red-400">
                  {{ eligibilitySummary?.ineligible || 0 }}
                </p>
              </div>
            </div>
          </div>

          <div class="bg-white dark:bg-gray-800 rounded-xl p-4 sm:p-5 border border-gray-200 dark:border-gray-700 col-span-2 lg:col-span-1">
            <div class="flex items-center gap-3">
              <div class="p-2 sm:p-3 bg-blue-100 dark:bg-blue-900 rounded-lg">
                <Icon icon="heroicons:lock-open" class="w-5 h-5 sm:w-6 sm:h-6 text-blue-600 dark:text-blue-400" />
              </div>
              <div>
                <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400">ปลดล็อค</p>
                <p class="text-lg sm:text-2xl font-bold text-blue-600 dark:text-blue-400">
                  {{ eligibilitySummary?.unlocked || 0 }}
                </p>
              </div>
            </div>
          </div>
        </div>

        <!-- Filter Button Bar -->
        <div class="flex flex-wrap items-center gap-2 mb-4">
          <button
            v-for="f in [
              { value: 'all', label: 'ทั้งหมด', color: 'gray' },
              { value: 'ineligible', label: 'หมดสิทธิ์', color: 'red' },
              { value: 'at_risk', label: 'กลุ่มเสี่ยง', color: 'yellow' },
              { value: 'eligible', label: 'มีสิทธิ์', color: 'green' },
              { value: 'unlocked', label: 'ปลดล็อคแล้ว', color: 'blue' },
            ]"
            :key="f.value"
            @click="statusFilter = f.value; currentPage = 1"
            class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-medium border transition-colors"
            :class="statusFilter === f.value
              ? {
                  gray: 'bg-gray-700 text-white border-gray-700 dark:bg-gray-200 dark:text-gray-900 dark:border-gray-200',
                  red: 'bg-red-600 text-white border-red-600',
                  yellow: 'bg-yellow-500 text-white border-yellow-500',
                  green: 'bg-green-600 text-white border-green-600',
                  blue: 'bg-blue-600 text-white border-blue-600',
                }[f.color]
              : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700'"
          >
            {{ f.label }}
            <span
              v-if="f.value !== 'all'"
              class="ml-2 text-xs font-bold px-1.5 py-0.5 rounded-full"
              :class="statusFilter === f.value ? 'bg-white/30 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400'"
            >
              {{ members.filter((m: any) => m.stats?.eligibility_status === f.value).length }}
            </span>
          </button>
        </div>

        <!-- Bulk Action Bar (visible when filtering ineligible) -->
        <div
          v-if="showBulkBar && isCourseAdmin && ineligibleCount > 0"
          class="flex items-center justify-between bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl px-4 py-3 mb-4"
        >
          <div class="flex items-center gap-2 text-sm text-red-700 dark:text-red-300">
            <Icon icon="heroicons:x-circle" class="w-5 h-5" />
            <span>พบนักเรียนหมดสิทธิ์สอบ <strong>{{ ineligibleCount }}</strong> คน</span>
          </div>
          <button
            @click="bulkUnlockIneligible"
            :disabled="isBulkUnlocking"
            class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 disabled:opacity-50 transition-colors"
          >
            <Icon
              :icon="isBulkUnlocking ? 'heroicons:arrow-path' : 'heroicons:lock-open'"
              class="w-4 h-4 mr-2"
              :class="{ 'animate-spin': isBulkUnlocking }"
            />
            ปลดล็อคทั้งหมดที่หมดสิทธิ์ ({{ ineligibleCount }} คน)
          </button>
        </div>

        <!-- Filters -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 mb-6">
          <div class="flex flex-col sm:flex-row gap-3 sm:gap-4">
            <!-- Search -->
            <div class="flex-1">
              <div class="relative">
                <Icon icon="heroicons:magnifying-glass" class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
                <input
                  v-model="searchQuery"
                  type="text"
                  placeholder="ค้นหาชื่อหรือรหัสนักเรียน..."
                  class="w-full pl-10 pr-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                />
              </div>
            </div>
          </div>
        </div>

        <!-- Members Table -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
          <!-- Desktop Table -->
          <div class="hidden md:block overflow-x-auto">
            <table class="w-full">
              <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                    นักเรียน
                  </th>
                  <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                    จำนวนครั้งเรียน
                  </th>
                  <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                    เข้าเรียน
                  </th>
                  <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                    ขาด
                  </th>
                  <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                    อัตราขาด
                  </th>
                  <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                    สถานะ
                  </th>
                  <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                    จัดการ
                  </th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-200 dark:divide-gray-600">
                <tr
                  v-for="member in paginatedMembers"
                  :key="member.id"
                  class="hover:bg-gray-50 dark:hover:bg-gray-700/50"
                >
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex items-center">
                      <img
                        :src="member.user?.avatar || '/images/default-avatar.png'"
                        :alt="member.user?.name"
                        class="w-10 h-10 rounded-full object-cover"
                      />
                      <div class="ml-4">
                        <div class="text-sm font-medium text-gray-900 dark:text-white">
                          {{ member.user?.name }}
                        </div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">
                          {{ member.member_code || '-' }}
                        </div>
                      </div>
                    </div>
                  </td>
                  <td class="px-6 py-4 text-center text-sm text-gray-900 dark:text-white">
                    {{ member.stats?.total_sessions || 0 }}
                  </td>
                  <td class="px-6 py-4 text-center text-sm text-green-600 dark:text-green-400">
                    {{ (member.stats?.present || 0) + (member.stats?.late || 0) }}
                  </td>
                  <td class="px-6 py-4 text-center text-sm text-red-600 dark:text-red-400">
                    {{ member.stats?.absent || 0 }}
                  </td>
                  <td class="px-6 py-4 text-center">
                    <span
                      class="text-sm font-medium"
                      :class="(member.stats?.absence_rate || 0) > 20 ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-white'"
                    >
                      {{ (member.stats?.absence_rate || 0).toFixed(1) }}%
                    </span>
                  </td>
                  <td class="px-6 py-4 text-center">
                    <span
                      :class="getStatusColor(member.stats?.eligibility_status)"
                      class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                    >
                      <Icon :icon="getStatusIcon(member.stats?.eligibility_status)" class="w-3.5 h-3.5 mr-1" />
                      {{ getStatusLabel(member.stats?.eligibility_status) }}
                    </span>
                  </td>
                  <td class="px-6 py-4 text-right">
                    <button
                      v-if="member.stats?.eligibility_status === 'ineligible'"
                      @click="openUnlockModal(member)"
                      class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/20 rounded-lg hover:bg-blue-100 dark:hover:bg-blue-900/40"
                    >
                      <Icon icon="heroicons:lock-open" class="w-4 h-4 mr-1" />
                      ปลดล็อค
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Mobile Cards -->
          <div class="md:hidden divide-y divide-gray-200 dark:divide-gray-600">
            <div
              v-for="member in paginatedMembers"
              :key="member.id"
              class="p-4"
            >
              <div class="flex items-start justify-between">
                <div class="flex items-center">
                  <img
                    :src="member.user?.avatar || '/images/default-avatar.png'"
                    :alt="member.user?.name"
                    class="w-12 h-12 rounded-full object-cover"
                  />
                  <div class="ml-3">
                    <p class="text-sm font-medium text-gray-900 dark:text-white">
                      {{ member.user?.name }}
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                      {{ member.member_code || '-' }}
                    </p>
                  </div>
                </div>
                <span
                  :class="getStatusColor(member.stats?.eligibility_status)"
                  class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium"
                >
                  <Icon :icon="getStatusIcon(member.stats?.eligibility_status)" class="w-3 h-3 mr-1" />
                  {{ getStatusLabel(member.stats?.eligibility_status) }}
                </span>
              </div>

              <div class="mt-3 grid grid-cols-4 gap-2 text-center text-xs">
                <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-2">
                  <p class="text-gray-500 dark:text-gray-400">เรียน</p>
                  <p class="font-semibold text-gray-900 dark:text-white">{{ member.stats?.total_sessions || 0 }}</p>
                </div>
                <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-2">
                  <p class="text-gray-500 dark:text-gray-400">เข้า</p>
                  <p class="font-semibold text-green-600 dark:text-green-400">{{ (member.stats?.present || 0) + (member.stats?.late || 0) }}</p>
                </div>
                <div class="bg-red-50 dark:bg-red-900/20 rounded-lg p-2">
                  <p class="text-gray-500 dark:text-gray-400">ขาด</p>
                  <p class="font-semibold text-red-600 dark:text-red-400">{{ member.stats?.absent || 0 }}</p>
                </div>
                <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-2">
                  <p class="text-gray-500 dark:text-gray-400">อัตราขาด</p>
                  <p
                    class="font-semibold"
                    :class="(member.stats?.absence_rate || 0) > 20 ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-white'"
                  >
                    {{ (member.stats?.absence_rate || 0).toFixed(1) }}%
                  </p>
                </div>
              </div>

              <div v-if="member.stats?.eligibility_status === 'ineligible'" class="mt-3">
                <button
                  @click="openUnlockModal(member)"
                  class="w-full inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/20 rounded-lg hover:bg-blue-100"
                >
                  <Icon icon="heroicons:lock-open" class="w-4 h-4 mr-2" />
                  ปลดล็อคสิทธิ์สอบ
                </button>
              </div>
            </div>
          </div>

          <!-- Empty State -->
          <div v-if="paginatedMembers.length === 0" class="py-12 text-center">
            <Icon icon="heroicons:users" class="w-12 h-12 mx-auto text-gray-400" />
            <p class="mt-2 text-gray-500 dark:text-gray-400">ไม่พบข้อมูลนักเรียน</p>
          </div>

          <!-- Pagination -->
          <div v-if="totalPages > 1" class="px-4 py-3 border-t border-gray-200 dark:border-gray-600">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
              <p class="text-sm text-gray-700 dark:text-gray-300">
                แสดง {{ (currentPage - 1) * perPage + 1 }} - {{ Math.min(currentPage * perPage, filteredMembers.length) }} จาก {{ filteredMembers.length }} รายการ
              </p>
              <div class="flex items-center gap-2">
                <button
                  @click="currentPage--"
                  :disabled="currentPage === 1"
                  class="px-3 py-1.5 text-sm border border-gray-300 dark:border-gray-600 rounded-lg disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50 dark:hover:bg-gray-700"
                >
                  ก่อนหน้า
                </button>
                <span class="text-sm text-gray-700 dark:text-gray-300">
                  {{ currentPage }} / {{ totalPages }}
                </span>
                <button
                  @click="currentPage++"
                  :disabled="currentPage === totalPages"
                  class="px-3 py-1.5 text-sm border border-gray-300 dark:border-gray-600 rounded-lg disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50 dark:hover:bg-gray-700"
                >
                  ถัดไป
                </button>
              </div>
            </div>
          </div>
        </div>
      </template>
    </div>

    <!-- Unlock Modal -->
    <Teleport to="body">
      <div v-if="showUnlockModal" class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex min-h-screen items-center justify-center p-4">
          <div class="fixed inset-0 bg-black/50" @click="showUnlockModal = false"></div>
          <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-md w-full p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
              ปลดล็อคสิทธิ์สอบ
            </h3>

            <div class="mb-4">
              <div class="flex items-center p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                <img
                  :src="selectedMember?.user?.avatar || '/images/default-avatar.png'"
                  class="w-10 h-10 rounded-full"
                />
                <div class="ml-3">
                  <p class="font-medium text-gray-900 dark:text-white">
                    {{ selectedMember?.user?.name }}
                  </p>
                  <p class="text-sm text-gray-500 dark:text-gray-400">
                    ขาด {{ (selectedMember?.stats?.absence_rate || 0).toFixed(1) }}%
                  </p>
                </div>
              </div>
            </div>

            <div class="mb-4">
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                เหตุผลในการปลดล็อค <span class="text-red-500">*</span>
              </label>
              <textarea
                v-model="unlockReason"
                rows="3"
                placeholder="ระบุเหตุผล..."
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500"
              ></textarea>
            </div>

            <div class="flex gap-3">
              <button
                @click="showUnlockModal = false"
                class="flex-1 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600"
              >
                ยกเลิก
              </button>
              <button
                @click="unlockMember"
                :disabled="!unlockReason.trim() || isUnlocking"
                class="flex-1 px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 disabled:opacity-50"
              >
                <Icon v-if="isUnlocking" icon="heroicons:arrow-path" class="w-4 h-4 mr-2 animate-spin inline" />
                ปลดล็อค
              </button>
            </div>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>
