<template>
  <div class="container mx-auto px-0 sm:px-4 py-6 sm:px-6">
    <!-- Header -->
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 flex items-center">
          <Icon icon="mdi:account-child" class="w-7 h-7 mr-2 text-blue-600 dark:text-blue-400" />
          จัดการผู้ปกครอง
        </h1>
        <p class="text-gray-500 dark:text-gray-400 mt-1 text-sm sm:text-base">รายการผู้ปกครองทั้งหมดของนักเรียนในโรงเรียน</p>
      </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4 mb-6">
      <div
        v-for="stat in statCards"
        :key="stat.key"
        class="rounded-xl border border-gray-200 bg-white p-3 dark:border-gray-700 dark:bg-gray-800 sm:p-4"
      >
        <div class="flex items-center justify-between gap-2">
          <div class="min-w-0">
            <p class="truncate text-xs text-gray-500 dark:text-gray-400 sm:text-sm">{{ stat.label }}</p>
            <div v-if="isLoadingStats" class="mt-1 h-7 w-16 animate-pulse rounded bg-gray-200 dark:bg-gray-700"></div>
            <p v-else class="text-lg font-bold sm:text-2xl" :class="stat.textColorClass">{{ stat.value }}</p>
          </div>
          <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-lg text-xl sm:h-12 sm:w-12" :class="stat.bgClass">
            <Icon v-if="stat.icon" :icon="stat.icon" class="h-5 w-5 sm:h-6 sm:w-6" :class="stat.iconColorClass" />
            <span v-else>{{ stat.emoji }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- A failed statistics call must not read as a wall of real zeroes. -->
    <div v-if="statsError" class="mb-6 flex flex-col gap-2 rounded-xl border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800 dark:border-amber-900/50 dark:bg-amber-900/20 dark:text-amber-300 sm:flex-row sm:items-center">
      <p class="min-w-0 flex-1 break-words">{{ statsError }} — ตัวเลขด้านบนอาจไม่ใช่ข้อมูลล่าสุด</p>
      <button
        @click="loadStatsData"
        class="min-h-[44px] flex-shrink-0 whitespace-nowrap rounded-lg border border-amber-300 px-3 text-sm font-medium hover:bg-amber-100 dark:border-amber-700 dark:hover:bg-amber-900/40 sm:min-h-0 sm:py-1.5"
      >
        โหลดสถิติใหม่
      </button>
    </div>

    <!-- Search & Filters -->
    <div class="rounded-xl border border-gray-200 bg-white p-3 dark:border-gray-700 dark:bg-gray-800 sm:p-4 mb-6">
      <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
        <div class="relative flex-1 min-w-0">
          <Icon icon="mdi:magnify" class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 w-5 h-5" />
          <input
            v-model="searchQuery"
            type="text"
            placeholder="ค้นหาชื่อผู้ปกครอง..."
            class="block w-full rounded-lg border border-gray-300 bg-white py-2 pl-10 pr-4 text-gray-900 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 dark:placeholder-gray-400"
            @input="debouncedSearch"
          />
        </div>
        
        <select
          v-model="filterType"
          class="block w-full sm:w-auto rounded-lg border border-gray-300 bg-white px-4 py-2 text-gray-900 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
          @change="loadGuardiansData"
        >
          <option value="">ทุกประเภท</option>
          <option value="father">บิดา</option>
          <option value="mother">มารดา</option>
          <option value="grandfather">ปู่/ตา</option>
          <option value="grandmother">ย่า/ยาย</option>
          <option value="uncle">ลุง/อา</option>
          <option value="aunt">ป้า/น้า</option>
          <option value="sibling">พี่/น้อง</option>
          <option value="other">อื่นๆ</option>
        </select>
      </div>
    </div>

    <!-- Main Content -->
    <div class="min-h-[300px]">
      
      <!-- Error State -->
      <!-- Kept OUT of the v-if/v-else-if chain below: inserting it between the loading branch
           and the list branch made the list a v-else of THIS panel, so the cards vanished
           whenever a pending request existed. -->
      <!-- Pending Requests Panel -->
      <div v-if="pendingRequests.length > 0" class="mb-6 rounded-xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-900/50 dark:bg-amber-900/20">
        <div class="mb-3 flex items-center gap-2 text-amber-800 dark:text-amber-300">
          <Icon icon="mdi:account-clock" class="h-5 w-5" />
          <h3 class="font-semibold text-sm sm:text-base">คำขอผูกบัญชีที่รอดำเนินการ ({{ pendingRequests.length }})</h3>
        </div>
        <div class="space-y-2">
          <div v-for="req in pendingRequests" :key="req.id" class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 rounded-lg bg-white p-3 shadow-sm border border-amber-100 dark:border-amber-800 dark:bg-gray-800">
            <div class="min-w-0">
              <p class="truncate text-sm font-medium text-gray-900 dark:text-gray-100">
                นักเรียน: {{ req.student_name || 'ไม่ระบุ' }}
              </p>
              <p class="truncate text-xs text-gray-500 dark:text-gray-400 mt-1">
                บัญชีผู้ใช้: {{ req.user_name || 'ไม่ทราบ' }} • ส่งเมื่อ: {{ new Date(req.created_at).toLocaleDateString('th-TH') }}
              </p>
            </div>
            <button
              @click="cancelPanelRequest(req.id)"
              class="flex min-h-[44px] sm:min-h-0 sm:py-1.5 flex-shrink-0 items-center justify-center rounded-lg border border-amber-200 px-3 text-xs font-medium text-amber-700 hover:bg-amber-50 dark:border-amber-700 dark:text-amber-400 dark:hover:bg-amber-900/30"
            >
              ยกเลิกคำขอ
            </button>
          </div>
        </div>
      </div>

      <div v-if="globalError" class="rounded-xl border border-red-200 bg-red-50 p-6 text-center dark:border-red-900/50 dark:bg-red-900/20">
        <Icon icon="mdi:alert-circle-outline" class="mx-auto mb-3 h-12 w-12 text-red-500 dark:text-red-400" />
        <p class="mb-4 text-red-700 dark:text-red-400">{{ globalError }}</p>
        <button
          @click="initPage"
          class="inline-flex min-h-[44px] items-center justify-center rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 dark:ring-offset-gray-900"
        >
          ลองใหม่
        </button>
      </div>

      <!-- Loading State -->
      <div v-else-if="isLoading" class="space-y-3">
        <div v-for="i in 5" :key="i" class="h-32 animate-pulse rounded-xl bg-gray-200 dark:bg-gray-700 sm:h-24"></div>
      </div>

      <!-- Data List -->
      <div v-else-if="guardians.length === 0" class="rounded-xl border border-dashed border-gray-300 bg-gray-50 p-8 text-center dark:border-gray-600 dark:bg-gray-800/50">
        <Icon icon="mdi:account-search-outline" class="mx-auto mb-4 h-16 w-16 text-gray-400 dark:text-gray-500" />
        <p class="text-gray-900 dark:text-gray-100 font-medium mb-1">ไม่พบข้อมูลผู้ปกครอง</p>
        <p v-if="searchQuery || filterType" class="text-sm text-gray-500 dark:text-gray-400">
          ไม่พบผลลัพธ์ที่ตรงกับคำค้นหาหรือตัวกรองของคุณ
        </p>
      </div>

      <div v-else class="space-y-3">
        <GuardianDirectoryCard
          v-for="guardian in guardians"
          :key="guardian.id"
          :guardian="guardian"
          :academy-name="route.params.name as string"
          @manage-contacts="openManageContacts"
          @link-account="openLinkAccount"
          @cancel-request="confirmCancelRequest"
          @unlink-account="openUnlinkAccount"
        />

        <!-- Pagination -->
        <div v-if="pagination.total > pagination.per_page" class="mt-6 flex flex-col items-center justify-between gap-4 rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800 sm:flex-row">
          <div class="text-sm text-gray-500 dark:text-gray-400 text-center sm:text-left">
            แสดง {{ (pagination.current_page - 1) * pagination.per_page + 1 }} - {{ Math.min(pagination.current_page * pagination.per_page, pagination.total) }} จาก {{ pagination.total }} รายการ
          </div>
          <div class="flex items-center gap-2">
            <button
              @click="changePage(pagination.current_page - 1)"
              :disabled="pagination.current_page === 1"
              class="flex min-h-[44px] items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600"
            >
              ก่อนหน้า
            </button>
            <button
              @click="changePage(pagination.current_page + 1)"
              :disabled="pagination.current_page === pagination.last_page"
              class="flex min-h-[44px] items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600"
            >
              ถัดไป
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal for Contacts -->
    <GuardianContactsModal
      v-if="selectedGuardian"
      :academy-name="String(academyId)"
      :guardian="selectedGuardian"
      :can-manage="canManageGuardians"
      @close="selectedGuardian = null"
      @changed="handleContactsChanged"
    />

    <!-- Modal for Link Account -->
    <GuardianLinkAccountModal
      v-if="showLinkModal && linkGuardianData"
      :academy-id="academyId as number"
      :student-id="linkGuardianData.children[0].id"
      :guardian-id="linkGuardianData.id"
      :guardian-name="linkGuardianData.full_name"
      :children="linkGuardianData.children.map(c => ({ id: c.id, name: c.name }))"
      @close="showLinkModal = false"
      @requested="handleAccountLinked"
    />

    <!-- Confirm Unlink Modal -->
    <div v-if="showUnlinkConfirm && unlinkGuardianData" class="fixed inset-0 z-50 overflow-y-auto">
      <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity" @click="showUnlinkConfirm = false"></div>
      <div class="flex min-h-full items-center justify-center p-4">
        <div class="relative w-full max-w-md rounded-xl bg-white p-6 shadow-xl dark:bg-gray-800">
          <div class="mb-5 flex items-center gap-3">
            <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400">
              <Icon icon="mdi:alert" class="h-6 w-6" />
            </div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">ยืนยันการปลดผูกบัญชี</h3>
          </div>
          <p class="mb-6 text-sm text-gray-600 dark:text-gray-300">
            ปลดการผูกบัญชีของ <span class="font-bold">{{ unlinkGuardianData.full_name }}</span>? ผู้ปกครองจะไม่เห็นข้อมูลบุตรหลานอีกจนกว่าจะผูกใหม่
          </p>
          <div class="flex flex-col sm:flex-row justify-end gap-3">
            <button @click="showUnlinkConfirm = false" class="min-h-[44px] sm:min-h-0 sm:py-2 rounded-lg border border-gray-300 px-4 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">
              ยกเลิก
            </button>
            <button @click="doUnlinkAccount" class="min-h-[44px] sm:min-h-0 sm:py-2 rounded-lg bg-red-600 px-4 text-sm font-medium text-white hover:bg-red-700">
              ปลดบัญชี
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, type Ref } from 'vue'
import { useRoute } from '#imports'
import { useApi } from '../../../composables/useApi'
import { Icon } from '@iconify/vue'
import { useGuardianDirectory } from '../../../composables/useGuardianDirectory'
import { errorStatus, errorMessage } from '../../../composables/useGuardianAppointment'
import { useAcademyRole } from '../../../composables/useAcademyRole'
import GuardianDirectoryCard from '../../../components/academy/guardians/GuardianDirectoryCard.vue'
import GuardianContactsModal from '../../../components/academy/guardians/GuardianContactsModal.vue'

import GuardianLinkAccountModal from '../../../components/learn/student/profile-cards/GuardianLinkAccountModal.vue'
import { useGuardianAccount } from '../../../composables/useGuardianAccount'
import { useToast } from '#imports'

definePageMeta({ middleware: 'auth' })

const route = useRoute()
const api = useApi()
const toast = useToast()

/**
 * The {academy} route parameter binds by id — the Academy model declares no route key — so the
 * slug sitting in the URL has to be exchanged for an id once before anything else can load.
 * Passing the slug straight through would 404 on every request.
 */
const academyId = ref<number | null>(null)

const {
  isLoading,
  isLoadingStats,
  fetchGuardians,
  fetchStatistics
} = useGuardianDirectory(academyId as unknown as Ref<string | number>)

// The account endpoints bind {academy} by id; route.params.name is the academy NAME,
// which resolves only on /api/academies/{name}. Reuse the id initPage() already resolves.
const { fetchRequests, cancelRequest, unlinkAccount } = useGuardianAccount(academyId as unknown as Ref<string | number>)

const { can, fetchMyRole } = useAcademyRole(academyId)
const canManageGuardians = computed(() => can('guardians.manage'))

const searchQuery = ref('')
const filterType = ref('')
const guardians = ref<any[]>([])
const stats = ref<any>({ total: 0, by_type: {}, with_contact: 0, without_contact: 0, linked_accounts: 0, pending_account_requests: 0 })
const pagination = ref({ current_page: 1, last_page: 1, per_page: 20, total: 0 })
const globalError = ref<string | null>(null)
const statsError = ref<string | null>(null)
const selectedGuardian = ref<any>(null)
const pendingRequests = ref<any[]>([])

// Link Account state
const showLinkModal = ref(false)
const linkGuardianData = ref<any>(null)

// Unlink Account state
const unlinkGuardianData = ref<any>(null)
const showUnlinkConfirm = ref(false)

let searchTimeout: any = null

const debouncedSearch = () => {
  if (searchTimeout) clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    loadGuardiansData(1)
  }, 400)
}

const changePage = (page: number) => {
  if (page < 1 || page > pagination.value.last_page) return
  loadGuardiansData(page)
}

const loadGuardiansData = async (page = 1) => {
  if (!academyId.value) return
  globalError.value = null
  try {
    const res = await fetchGuardians({
      page,
      per_page: 20,
      search: searchQuery.value,
      type: filterType.value
    })
    // useApi returns the parsed body itself, not an axios-style { data } envelope.
    if (res?.success) {
      guardians.value = res.guardians || []
      pagination.value = res.pagination || { current_page: 1, last_page: 1, per_page: 20, total: 0 }
    }
  } catch (err: any) {
    globalError.value = errorStatus(err) === 403
      ? 'ไม่มีสิทธิ์ดูทะเบียนผู้ปกครองของโรงเรียนนี้'
      : (errorMessage(err) || 'เกิดข้อผิดพลาดในการโหลดข้อมูลผู้ปกครอง')
  }
}

const loadStatsData = async () => {
  if (!academyId.value) return
  statsError.value = null
  try {
    const res = await fetchStatistics()
    if (res?.success) {
      stats.value = res.statistics || { total: 0, by_type: {}, with_contact: 0, without_contact: 0, linked_accounts: 0, pending_account_requests: 0 }
    }
  } catch (err: any) {
    // The list is still worth showing without the counters, but a silent zero would read as real data.
    statsError.value = errorMessage(err) || 'โหลดสถิติไม่สำเร็จ'
  }
}

const loadPendingRequests = async () => {
  try {
    const res: any = await fetchRequests({ scope: 'academy', status: 'pending' })
    if (res?.success) {
      // The endpoint answers { incoming, outgoing } — never `data`. Academy scope lands almost
      // everything in `outgoing` because the admin is neither party, so read both buckets.
      pendingRequests.value = [...(res.incoming || []), ...(res.outgoing || [])]
    }
  } catch (err: any) {
    if (errorStatus(err) === 403) {
      pendingRequests.value = []
    } else {
      console.error(err)
    }
  }
}

const initPage = async () => {
  globalError.value = null
  try {
    const res = await api.get(`/api/academies/${route.params.name}`)
    // /api/academies/{name} answers { success, academy: {...} } — res.id is always undefined.
    academyId.value = res?.academy?.id ?? res?.id ?? null
  } catch (err: any) {
    academyId.value = null
  }

  if (!academyId.value) {
    globalError.value = 'ไม่พบข้อมูลโรงเรียนนี้'
    return
  }

  await Promise.all([
    loadGuardiansData(1),
    loadStatsData(),
    fetchMyRole(),
    loadPendingRequests()
  ])
}

const openManageContacts = (guardian: any) => {
  selectedGuardian.value = guardian
}

const handleContactsChanged = () => {
  loadGuardiansData(pagination.value.current_page)
  loadStatsData()
}

const openLinkAccount = (guardian: any) => {
  if (!guardian.children || guardian.children.length === 0) {
    toast.add({ severity: 'error', summary: 'ไม่สามารถส่งคำขอได้', detail: 'ต้องมีนักเรียนในความปกครองก่อน', life: 3000 })
    return
  }
  linkGuardianData.value = guardian
  showLinkModal.value = true
}

const handleAccountLinked = () => {
  showLinkModal.value = false
  loadGuardiansData(pagination.value.current_page)
  loadStatsData()
  loadPendingRequests()
}

const confirmCancelRequest = async (guardian: any) => {
  if (!guardian.pending_account_request) return
  try {
    await cancelRequest(guardian.pending_account_request.id)
    toast.add({ severity: 'success', summary: 'สำเร็จ', detail: 'ยกเลิกคำขอผูกบัญชีแล้ว', life: 3000 })
    loadGuardiansData(pagination.value.current_page)
    loadStatsData()
    loadPendingRequests()
  } catch (err: any) {
    toast.add({ severity: 'error', summary: 'เกิดข้อผิดพลาด', detail: errorMessage(err) || 'ไม่สามารถยกเลิกคำขอได้', life: 3000 })
  }
}

const cancelPanelRequest = async (id: number) => {
  try {
    await cancelRequest(id)
    toast.add({ severity: 'success', summary: 'สำเร็จ', detail: 'ยกเลิกคำขอผูกบัญชีแล้ว', life: 3000 })
    loadGuardiansData(pagination.value.current_page)
    loadStatsData()
    loadPendingRequests()
  } catch (err: any) {
    toast.add({ severity: 'error', summary: 'เกิดข้อผิดพลาด', detail: errorMessage(err) || 'ไม่สามารถยกเลิกคำขอได้', life: 3000 })
  }
}

const openUnlinkAccount = (guardian: any) => {
  unlinkGuardianData.value = guardian
  showUnlinkConfirm.value = true
}

const doUnlinkAccount = async () => {
  if (!unlinkGuardianData.value) return
  try {
    await unlinkAccount(unlinkGuardianData.value.id)
    toast.add({ severity: 'success', summary: 'สำเร็จ', detail: 'ปลดการผูกบัญชีเรียบร้อยแล้ว', life: 3000 })
    showUnlinkConfirm.value = false
    loadGuardiansData(pagination.value.current_page)
    loadStatsData()
  } catch (err: any) {
    toast.add({ severity: 'error', summary: 'เกิดข้อผิดพลาด', detail: errorMessage(err) || 'ไม่สามารถปลดการผูกบัญชีได้', life: 3000 })
  }
}

onMounted(() => {
  initPage()
})

const getStatValue = (type: string) => {
  return stats.value.by_type?.[type] || 0
}

const statCards = computed(() => [
  { key: 'total', label: 'ผู้ปกครองทั้งหมด', value: stats.value.total || 0, textColorClass: 'text-gray-900 dark:text-gray-100', bgClass: 'bg-blue-100 dark:bg-blue-900/30', icon: 'mdi:account-group', iconColorClass: 'text-blue-600 dark:text-blue-400' },
  { key: 'father', label: 'บิดา', value: getStatValue('father'), textColorClass: 'text-blue-600 dark:text-blue-400', bgClass: 'bg-blue-100 dark:bg-blue-900/30', emoji: '👨' },
  { key: 'mother', label: 'มารดา', value: getStatValue('mother'), textColorClass: 'text-pink-600 dark:text-pink-400', bgClass: 'bg-pink-100 dark:bg-pink-900/30', emoji: '👩' },
  { key: 'grandfather', label: 'ปู่/ตา', value: getStatValue('grandfather'), textColorClass: 'text-gray-700 dark:text-gray-300', bgClass: 'bg-gray-200 dark:bg-gray-700', emoji: '👴' },
  { key: 'grandmother', label: 'ย่า/ยาย', value: getStatValue('grandmother'), textColorClass: 'text-purple-600 dark:text-purple-400', bgClass: 'bg-purple-100 dark:bg-purple-900/30', emoji: '👵' },
  { key: 'uncle', label: 'ลุง/อา', value: getStatValue('uncle'), textColorClass: 'text-indigo-600 dark:text-indigo-400', bgClass: 'bg-indigo-100 dark:bg-indigo-900/30', emoji: '👨' },
  { key: 'aunt', label: 'ป้า/น้า', value: getStatValue('aunt'), textColorClass: 'text-rose-600 dark:text-rose-400', bgClass: 'bg-rose-100 dark:bg-rose-900/30', emoji: '👩' },
  { key: 'sibling', label: 'พี่/น้อง', value: getStatValue('sibling'), textColorClass: 'text-teal-600 dark:text-teal-400', bgClass: 'bg-teal-100 dark:bg-teal-900/30', emoji: '🧑' },
  { key: 'other', label: 'อื่นๆ', value: getStatValue('other'), textColorClass: 'text-gray-600 dark:text-gray-400', bgClass: 'bg-gray-100 dark:bg-gray-800', emoji: '👤' },
  { key: 'with_contact', label: 'มีข้อมูลติดต่อ', value: stats.value.with_contact || 0, textColorClass: 'text-green-600 dark:text-green-400', bgClass: 'bg-green-100 dark:bg-green-900/30', icon: 'mdi:phone-check', iconColorClass: 'text-green-600 dark:text-green-400' },
  { key: 'without_contact', label: 'ไม่มีข้อมูลติดต่อ', value: stats.value.without_contact || 0, textColorClass: 'text-orange-600 dark:text-orange-400', bgClass: 'bg-orange-100 dark:bg-orange-900/30', icon: 'mdi:phone-off', iconColorClass: 'text-orange-600 dark:text-orange-400' },
  { key: 'linked_accounts', label: 'ผูกบัญชีแล้ว', value: stats.value.linked_accounts || 0, textColorClass: 'text-emerald-600 dark:text-emerald-400', bgClass: 'bg-emerald-100 dark:bg-emerald-900/30', icon: 'mdi:account-check', iconColorClass: 'text-emerald-600 dark:text-emerald-400' },
  { key: 'pending_account_requests', label: 'รอกดรับ', value: stats.value.pending_account_requests || 0, textColorClass: 'text-amber-600 dark:text-amber-400', bgClass: 'bg-amber-100 dark:bg-amber-900/30', icon: 'mdi:account-clock', iconColorClass: 'text-amber-600 dark:text-amber-400' },
])
</script>
