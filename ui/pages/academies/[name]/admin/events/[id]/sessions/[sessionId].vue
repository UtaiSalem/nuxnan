<script setup lang="ts">
/**
 * Academy Admin — Activity Session Check-in Console
 * QR, สแกนบัตร, บันทึกด้วยตนเอง และรายชื่อผู้เช็คชื่อ ของคาบกิจกรรมหนึ่งคาบ
 */
import { Icon } from '@iconify/vue'
// Explicit import: the auto-import name for components/academy/activity/ would be
// AcademyActivitySessionQRDisplay, which reads nothing like the file.
import ActivitySessionQRDisplay from '~/components/academy/activity/ActivitySessionQRDisplay.vue'

definePageMeta({ layout: 'main' })

const route = useRoute()
const api = useApi()
const eventsApi = useSchoolEvents()

const academyName = computed(() => route.params.name as string)
const eventId = computed(() => Number(route.params.id))
const sessionId = computed(() => Number(route.params.sessionId))
const academyId = ref<number | null>(null)

const session = ref<any>(null)
const summary = ref<any>({ total: 0, present: 0, absent: 0, late: 0 })
const audienceCount = ref(0)
const isLoading = ref(true)
const isCompleting = ref(false)
const isSaving = ref(false)
const activeTab = ref<'qr' | 'scan' | 'manual' | 'records'>('qr')

const statusLabels: Record<string, string> = {
  scheduled: 'ตามกำหนด',
  completed: 'จบแล้ว',
  cancelled: 'ยกเลิก',
}

// ---- Roster (drives both the manual and records tabs) -----------------------

// The roster endpoint returns the target audience, not the attendance rows: each row is
// { user_id, name, student_number, classroom_name, attendance_status }. There is no
// "list attendances" endpoint — records = the rows whose attendance_status is set.
const ROSTER_PAGE_SIZE = 200

interface RosterRow {
  user_id: number
  name: string
  student_number: number | null
  classroom_name: string | null
  attendance_status: string | null
  status: string
  remarks: string
}

const roster = ref<RosterRow[]>([])
const rosterTotal = ref(0)

const checkedRecords = computed(() => roster.value.filter((row) => row.attendance_status))

const loadRoster = async () => {
  if (!academyId.value) return
  const res: any = await eventsApi.getEventRoster(academyId.value, eventId.value, {
    session_id: sessionId.value,
    per_page: ROSTER_PAGE_SIZE,
  })
  const paginator = res?.data || {}
  rosterTotal.value = paginator.total || 0
  roster.value = (paginator.data || []).map((row: any) => ({
    user_id: row.user_id,
    name: row.name,
    student_number: row.student_number ?? null,
    classroom_name: row.classroom_name ?? null,
    attendance_status: row.attendance_status ?? null,
    status: row.attendance_status || 'present',
    remarks: '',
  }))
}

const loadSession = async () => {
  if (!academyId.value) return
  const res: any = await eventsApi.getSession(academyId.value, eventId.value, sessionId.value)
  if (res?.success) {
    session.value = res.data
    summary.value = res.summary || {}
  }
}

onMounted(async () => {
  try {
    const res: any = await api.get(`/api/academies/${academyName.value}`)
    if (!res?.success) return
    academyId.value = res.academy.id

    await loadSession()
    if (!session.value) return

    const countRes: any = await eventsApi.getEventAudienceCount(academyId.value!, eventId.value)
    audienceCount.value = countRes?.count || 0
    await loadRoster()
  } finally {
    isLoading.value = false
  }
})

// ---- Header action ----------------------------------------------------------

// An activity session has no open/closed state — it moves scheduled → completed.
const completeSession = async () => {
  if (!academyId.value || isCompleting.value) return
  isCompleting.value = true
  try {
    await eventsApi.updateEventSession(academyId.value, eventId.value, sessionId.value, { status: 'completed' })
    await loadSession()
  } finally {
    isCompleting.value = false
  }
}

// ---- Scan tab ---------------------------------------------------------------

const scanInput = ref('')
const scanInputRef = ref<HTMLInputElement | null>(null)
const isScanning = ref(false)
const scanResult = ref<{
  type: 'success' | 'already' | 'error'
  message: string
  studentName?: string
  studentPhoto?: string
} | null>(null)
const recentScans = ref<Array<{
  identifier: string
  studentName: string
  studentPhoto?: string
  status: string
  time: string
}>>([])

const doScan = async () => {
  const identifier = scanInput.value.trim()
  if (!identifier || !academyId.value || isScanning.value) return

  isScanning.value = true
  scanResult.value = null

  try {
    const res: any = await eventsApi.scanSessionStudent(
      academyId.value,
      eventId.value,
      sessionId.value,
      identifier,
      'qr',
    )
    scanResult.value = {
      type: 'success',
      message: res?.message || 'เช็คชื่อสำเร็จ',
      studentName: res?.student_name,
      studentPhoto: res?.student_photo,
    }
    recentScans.value.unshift({
      identifier,
      studentName: res?.student_name || identifier,
      studentPhoto: res?.student_photo,
      status: res?.record?.status || 'present',
      time: new Date().toLocaleTimeString('th-TH', { hour: '2-digit', minute: '2-digit' }),
    })
    await Promise.all([loadSession(), loadRoster()])
  } catch (e: any) {
    // The API answers 422 for "already checked in" and for "outside the target audience",
    // and 404 for an unknown identifier — api.call throws on all of them.
    const data = e?.data || {}
    scanResult.value = {
      type: data.already_checked ? 'already' : 'error',
      message: data.message || 'เกิดข้อผิดพลาด',
      studentName: data.student_name,
      studentPhoto: data.student_photo,
    }
  } finally {
    isScanning.value = false
    scanInput.value = ''
    nextTick(() => scanInputRef.value?.focus())
  }
}

const onScanInput = (e: KeyboardEvent) => {
  if (e.key === 'Enter') doScan()
}

watch(activeTab, (tab) => {
  if (tab === 'scan') {
    nextTick(() => scanInputRef.value?.focus())
  }
})

// ---- Manual tab -------------------------------------------------------------

const skippedCount = ref(0)
const saveError = ref('')

const statusConfig: Record<string, { label: string; btn: string; selectedBtn: string }> = {
  present: {
    label: 'มา',
    btn: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300 border border-green-300 dark:border-green-700',
    selectedBtn: 'bg-green-500 text-white border border-green-500',
  },
  late: {
    label: 'สาย',
    btn: 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-300 border border-orange-300 dark:border-orange-700',
    selectedBtn: 'bg-orange-500 text-white border border-orange-500',
  },
  leave: {
    label: 'ลา',
    btn: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300 border border-blue-300 dark:border-blue-700',
    selectedBtn: 'bg-blue-500 text-white border border-blue-500',
  },
  activity_leave: {
    label: 'ลากิจกรรม',
    btn: 'bg-violet-100 text-violet-700 dark:bg-violet-900/30 dark:text-violet-300 border border-violet-300 dark:border-violet-700',
    selectedBtn: 'bg-violet-500 text-white border border-violet-500',
  },
  absent: {
    label: 'ขาด',
    btn: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300 border border-red-300 dark:border-red-700',
    selectedBtn: 'bg-red-500 text-white border border-red-500',
  },
}

const recordBadgeClass = (status: string) => {
  const map: Record<string, string> = {
    present: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300',
    late: 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-300',
    leave: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300',
    activity_leave: 'bg-violet-100 text-violet-700 dark:bg-violet-900/30 dark:text-violet-300',
    absent: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300',
  }
  return map[status] || 'bg-slate-100 text-slate-600'
}

const saveManualRecords = async () => {
  if (!academyId.value || isSaving.value) return
  isSaving.value = true
  saveError.value = ''
  skippedCount.value = 0

  try {
    // The activity endpoint reads user_id and remarks. Sending student_id or remark would be
    // accepted and then silently ignored — that is the live bug on the school-attendance page.
    const records = roster.value.map((row) => ({
      user_id: row.user_id,
      status: row.status,
      remarks: row.remarks || undefined,
    }))
    const res: any = await eventsApi.storeSessionRecords(academyId.value, eventId.value, sessionId.value, records)
    skippedCount.value = (res?.skipped_user_ids || []).length
    await Promise.all([loadSession(), loadRoster()])
    activeTab.value = 'records'
  } catch (e: any) {
    saveError.value = e?.data?.message || 'บันทึกไม่สำเร็จ'
  } finally {
    isSaving.value = false
  }
}

// ---- Display helpers --------------------------------------------------------

const sessionTitle = computed(
  () => session.value?.title || session.value?.slot_label || 'คาบกิจกรรม',
)

const formatDateTime = (value: string) => {
  if (!value) return '-'
  const d = new Date(value)
  return `${d.toLocaleDateString('th-TH', { year: 'numeric', month: 'short', day: 'numeric', weekday: 'short' })} ${d.toLocaleTimeString('th-TH', { hour: '2-digit', minute: '2-digit' })}`
}

const summaryPercent = (count: number) => {
  if (!audienceCount.value) return 0
  return Math.round((count / audienceCount.value) * 100)
}
</script>

<template>
  <div class="min-h-screen bg-slate-50 dark:bg-slate-900">
    <!-- Skeleton -->
    <div v-if="isLoading" class="space-y-6 p-6">
      <div class="h-32 rounded-vikinger bg-slate-200 dark:bg-slate-700 animate-pulse" />
      <div class="grid grid-cols-4 gap-3">
        <div v-for="i in 4" :key="i" class="h-20 rounded-vikinger bg-slate-200 dark:bg-slate-700 animate-pulse" />
      </div>
      <div class="h-96 rounded-vikinger bg-slate-200 dark:bg-slate-700 animate-pulse" />
    </div>

    <div v-else-if="session" class="space-y-6 pb-10">
      <!-- Header -->
      <div class="bg-gradient-to-br from-violet-600 via-purple-600 to-indigo-700 px-6 py-6">
        <div class="max-w-5xl mx-auto">
          <NuxtLink
            :to="`/academies/${academyName}/admin/events/${eventId}/sessions`"
            class="inline-flex items-center gap-1.5 text-purple-200 hover:text-white text-sm mb-4 transition-colors"
          >
            <Icon icon="fluent:arrow-left-24-regular" class="w-4 h-4" />
            กลับ
          </NuxtLink>

          <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
            <div>
              <div class="flex flex-wrap items-center gap-2 mb-2">
                <h1 class="font-heading text-2xl font-bold text-white">{{ sessionTitle }}</h1>
                <span
                  v-if="session.status === 'scheduled'"
                  class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-400/20 text-green-200 border border-green-400/40"
                >
                  <span class="w-1.5 h-1.5 rounded-full bg-green-400 animate-pulse" />
                  ตามกำหนด
                </span>
                <span
                  v-else
                  class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-white/10 text-purple-200"
                >
                  {{ statusLabels[session.status] || session.status }}
                </span>
              </div>
              <div class="flex flex-wrap gap-3">
                <span class="text-purple-200 text-sm">
                  <Icon icon="fluent:calendar-24-regular" class="w-4 h-4 inline mr-1" />
                  {{ formatDateTime(session.start_datetime) }}
                </span>
                <span v-if="session.location" class="text-purple-200 text-sm">
                  <Icon icon="fluent:location-24-regular" class="w-4 h-4 inline mr-1" />
                  {{ session.location }}
                </span>
                <span v-if="session.is_makeup_class" class="text-amber-200 text-sm">
                  <Icon icon="fluent:arrow-repeat-all-24-regular" class="w-4 h-4 inline mr-1" />
                  คาบชดเชย
                </span>
              </div>
            </div>

            <button
              v-if="session.status === 'scheduled'"
              class="min-h-[44px] sm:min-h-0 flex items-center gap-2 px-4 py-2.5 bg-green-500 hover:bg-green-600 text-white font-semibold rounded-vikinger transition-all shadow disabled:opacity-50 disabled:pointer-events-none"
              :disabled="isCompleting"
              @click="completeSession"
            >
              <Icon v-if="isCompleting" icon="fluent:spinner-ios-20-regular" class="w-4 h-4 animate-spin" />
              <Icon v-else icon="fluent:checkmark-circle-24-filled" class="w-4 h-4" />
              จบคาบนี้
            </button>
          </div>
        </div>
      </div>

      <div class="max-w-5xl mx-auto px-6 space-y-6">
        <!-- Summary stats. summary.total counts attendance rows, so the "ทั้งหมด" card
             uses the audience size instead — that is the number of people expected. -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
          <div
            class="bg-white dark:bg-slate-800 rounded-vikinger shadow-card dark:shadow-card-dark border border-slate-200 dark:border-slate-700 p-4 text-center"
          >
            <p class="text-2xl font-heading font-bold text-slate-900 dark:text-white">{{ audienceCount }}</p>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">กลุ่มเป้าหมาย</p>
          </div>
          <div
            class="bg-white dark:bg-slate-800 rounded-vikinger shadow-card dark:shadow-card-dark border border-slate-200 dark:border-slate-700 p-4 text-center"
          >
            <p class="text-2xl font-heading font-bold text-green-600 dark:text-green-400">{{ summary.present || 0 }}</p>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
              มา <span class="text-green-500">({{ summaryPercent(summary.present || 0) }}%)</span>
            </p>
          </div>
          <div
            class="bg-white dark:bg-slate-800 rounded-vikinger shadow-card dark:shadow-card-dark border border-slate-200 dark:border-slate-700 p-4 text-center"
          >
            <p class="text-2xl font-heading font-bold text-orange-500 dark:text-orange-400">{{ summary.late || 0 }}</p>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
              สาย <span class="text-orange-400">({{ summaryPercent(summary.late || 0) }}%)</span>
            </p>
          </div>
          <div
            class="bg-white dark:bg-slate-800 rounded-vikinger shadow-card dark:shadow-card-dark border border-slate-200 dark:border-slate-700 p-4 text-center"
          >
            <p class="text-2xl font-heading font-bold text-red-500 dark:text-red-400">{{ summary.absent || 0 }}</p>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
              ขาด <span class="text-red-400">({{ summaryPercent(summary.absent || 0) }}%)</span>
            </p>
          </div>
        </div>

        <!-- Tabs -->
        <div
          class="bg-white dark:bg-slate-800 rounded-vikinger shadow-card dark:shadow-card-dark border border-slate-200 dark:border-slate-700 overflow-hidden"
        >
          <div class="flex border-b border-slate-200 dark:border-slate-700 overflow-x-auto">
            <button
              v-for="tab in [
                { key: 'qr', icon: 'fluent:qr-code-24-regular', label: 'QR Code' },
                { key: 'scan', icon: 'fluent:barcode-scanner-24-regular', label: 'สแกน/รหัส' },
                { key: 'manual', icon: 'fluent:pen-24-regular', label: 'บันทึกด้วยตนเอง' },
                { key: 'records', icon: 'fluent:list-24-regular', label: 'รายชื่อผู้เช็คชื่อ' },
              ]"
              :key="tab.key"
              class="flex items-center gap-2 px-5 py-3.5 text-sm font-medium transition-all border-b-2 -mb-px whitespace-nowrap"
              :class="
                activeTab === tab.key
                  ? 'border-purple-600 text-purple-600 dark:text-purple-400 dark:border-purple-400'
                  : 'border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300'
              "
              @click="activeTab = tab.key as 'qr' | 'scan' | 'manual' | 'records'"
            >
              <Icon :icon="tab.icon" class="w-4 h-4" />
              {{ tab.label }}
            </button>
          </div>

          <!-- Tab: QR -->
          <div v-if="activeTab === 'qr'" class="p-6 flex flex-col items-center">
            <div
              v-if="session.status === 'cancelled'"
              class="w-full mb-6 flex items-center gap-2 px-4 py-3 rounded-lg bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-700 text-orange-700 dark:text-orange-300 text-sm"
            >
              <Icon icon="fluent:warning-24-filled" class="w-5 h-5 flex-shrink-0" />
              คาบนี้ถูกยกเลิก นักเรียนไม่สามารถสแกน QR ได้
            </div>

            <div v-if="session.status !== 'cancelled' && academyId" class="w-full max-w-lg">
              <ActivitySessionQRDisplay
                :academy-id="academyId"
                :event-id="eventId"
                :session-id="sessionId"
              />
            </div>

            <div v-else class="flex flex-col items-center gap-6">
              <div
                class="p-4 bg-white dark:bg-slate-700 rounded-vikinger border-2 border-slate-200 dark:border-slate-600 shadow-lg opacity-50"
              >
                <div class="w-64 h-64 flex items-center justify-center">
                  <Icon icon="fluent:lock-closed-24-regular" class="w-20 h-20 text-slate-300 dark:text-slate-600" />
                </div>
              </div>
              <p class="text-slate-500">คาบนี้ถูกยกเลิกแล้ว</p>
            </div>
          </div>

          <!-- Tab: สแกน/รหัส -->
          <div v-else-if="activeTab === 'scan'" class="p-6 space-y-6">
            <div class="bg-slate-50 dark:bg-slate-700/40 rounded-vikinger border border-slate-200 dark:border-slate-700 p-6">
              <div class="text-center mb-6">
                <div class="w-16 h-16 bg-gradient-vikinger rounded-full flex items-center justify-center mx-auto mb-3 shadow-vikinger">
                  <Icon icon="fluent:barcode-scanner-24-regular" class="w-8 h-8 text-white" />
                </div>
                <h3 class="text-lg font-bold font-heading text-slate-900 dark:text-slate-100">สแกนบัตรนักเรียน</h3>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                  สแกน QR บนบัตรนักเรียน หรือพิมพ์รหัสนักเรียน/รหัสสมาชิก แล้วกด Enter
                </p>
              </div>

              <div class="flex gap-3 max-w-md mx-auto">
                <div class="relative flex-1">
                  <Icon
                    icon="fluent:scan-24-regular"
                    class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400 pointer-events-none"
                  />
                  <input
                    ref="scanInputRef"
                    v-model="scanInput"
                    type="text"
                    inputmode="numeric"
                    placeholder="สแกน QR หรือพิมพ์รหัส..."
                    class="w-full pl-10 pr-4 py-3 rounded-lg border border-slate-300 dark:border-slate-600
                           bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100
                           placeholder-slate-400 text-base font-mono
                           focus:ring-2 focus:ring-purple-500 focus:border-transparent
                           transition-colors disabled:opacity-50"
                    :disabled="isScanning || session.status === 'cancelled'"
                    autocomplete="off"
                    @keydown="onScanInput"
                  />
                </div>
                <button
                  :disabled="!scanInput.trim() || isScanning || session.status === 'cancelled'"
                  class="px-5 py-3 bg-gradient-vikinger text-white rounded-lg shadow-vikinger
                         hover:shadow-vikinger-lg hover:scale-105 transition-all font-semibold text-sm
                         disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:scale-100"
                  aria-label="บันทึกการเช็คชื่อ"
                  @click="doScan"
                >
                  <Icon v-if="isScanning" icon="mdi:loading" class="w-5 h-5 animate-spin" />
                  <Icon v-else icon="fluent:checkmark-24-filled" class="w-5 h-5" />
                </button>
              </div>

              <div v-if="session.status === 'cancelled'" class="mt-4 text-center">
                <span class="text-sm text-slate-500 dark:text-slate-400">
                  <Icon icon="fluent:lock-closed-24-regular" class="w-4 h-4 inline mr-1" />
                  คาบนี้ถูกยกเลิกแล้ว
                </span>
              </div>
            </div>

            <!-- Scan result -->
            <Transition
              enter-active-class="transition-all duration-300"
              enter-from-class="opacity-0 translate-y-2"
              enter-to-class="opacity-100 translate-y-0"
            >
              <div
                v-if="scanResult"
                :class="[
                  'rounded-vikinger border p-4 flex items-center gap-4',
                  scanResult.type === 'success' ? 'bg-green-50 dark:bg-green-900/10 border-green-300 dark:border-green-700/50' :
                  scanResult.type === 'already' ? 'bg-blue-50 dark:bg-blue-900/10 border-blue-300 dark:border-blue-700/50' :
                                                  'bg-red-50 dark:bg-red-900/10 border-red-300 dark:border-red-700/50'
                ]"
              >
                <div class="shrink-0">
                  <img
                    v-if="scanResult.studentPhoto"
                    :src="scanResult.studentPhoto"
                    alt="student photo"
                    class="w-12 h-12 rounded-full object-cover border-2 border-white dark:border-slate-700 shadow-sm"
                  />
                  <div
                    v-else
                    :class="[
                      'w-12 h-12 rounded-full flex items-center justify-center',
                      scanResult.type === 'success' ? 'bg-green-100 dark:bg-green-900/30' :
                      scanResult.type === 'already' ? 'bg-blue-100 dark:bg-blue-900/30' : 'bg-red-100 dark:bg-red-900/30'
                    ]"
                  >
                    <Icon
                      :icon="
                        scanResult.type === 'success' ? 'mdi:check-circle' :
                        scanResult.type === 'already' ? 'fluent:info-24-filled' : 'fluent:error-circle-24-regular'
                      "
                      :class="[
                        'w-6 h-6',
                        scanResult.type === 'success' ? 'text-green-600 dark:text-green-400' :
                        scanResult.type === 'already' ? 'text-blue-600 dark:text-blue-400' : 'text-red-600 dark:text-red-400'
                      ]"
                    />
                  </div>
                </div>

                <div class="flex-1 min-w-0">
                  <p
                    :class="[
                      'font-semibold text-sm',
                      scanResult.type === 'success' ? 'text-green-700 dark:text-green-300' :
                      scanResult.type === 'already' ? 'text-blue-700 dark:text-blue-300' : 'text-red-700 dark:text-red-300'
                    ]"
                  >
                    {{ scanResult.studentName || 'นักเรียน' }}
                  </p>
                  <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">{{ scanResult.message }}</p>
                </div>

                <span
                  :class="[
                    'px-2.5 py-1 text-xs font-bold rounded-full shrink-0',
                    scanResult.type === 'success' ? 'bg-green-500 text-white' :
                    scanResult.type === 'already' ? 'bg-blue-500 text-white' : 'bg-red-500 text-white'
                  ]"
                >
                  {{
                    scanResult.type === 'success' ? 'เช็คชื่อแล้ว' :
                    scanResult.type === 'already' ? 'เช็คซ้ำ' : 'ไม่สำเร็จ'
                  }}
                </span>
              </div>
            </Transition>

            <!-- Recent scans -->
            <div
              v-if="recentScans.length"
              class="bg-white dark:bg-slate-800 rounded-vikinger border border-slate-200 dark:border-slate-700 overflow-hidden shadow-card dark:shadow-card-dark"
            >
              <div class="px-4 py-3 border-b border-slate-100 dark:border-slate-700 flex items-center justify-between">
                <h4 class="text-sm font-semibold text-slate-700 dark:text-slate-300 flex items-center gap-2">
                  <Icon icon="fluent:history-24-regular" class="w-4 h-4" />
                  สแกนล่าสุดในคาบนี้
                </h4>
                <span class="text-xs text-slate-400">{{ recentScans.length }} รายการ</span>
              </div>
              <ul class="divide-y divide-slate-100 dark:divide-slate-700 max-h-64 overflow-y-auto">
                <li v-for="(scan, i) in recentScans" :key="i" class="flex items-center gap-3 px-4 py-3">
                  <img
                    v-if="scan.studentPhoto"
                    :src="scan.studentPhoto"
                    class="w-8 h-8 rounded-full object-cover shrink-0"
                    alt=""
                  />
                  <div
                    v-else
                    class="w-8 h-8 rounded-full bg-slate-100 dark:bg-slate-700 flex items-center justify-center shrink-0"
                  >
                    <Icon icon="fluent:person-24-regular" class="w-4 h-4 text-slate-400" />
                  </div>
                  <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-slate-800 dark:text-slate-200 truncate">{{ scan.studentName }}</p>
                    <span class="text-xs text-slate-400 font-mono">{{ scan.identifier }}</span>
                  </div>
                  <div class="flex items-center gap-2 shrink-0">
                    <span class="px-2 py-0.5 rounded-full text-xs font-medium" :class="recordBadgeClass(scan.status)">
                      {{ statusConfig[scan.status]?.label || scan.status }}
                    </span>
                    <span class="text-xs text-slate-500 dark:text-slate-400">{{ scan.time }}</span>
                  </div>
                </li>
              </ul>
            </div>

            <div v-else class="text-center py-8 text-slate-400 dark:text-slate-500">
              <Icon icon="fluent:scan-24-regular" class="w-10 h-10 mx-auto mb-2 opacity-40" />
              <p class="text-sm">ยังไม่มีการสแกนในคาบนี้</p>
              <p class="text-xs mt-1">สแกน QR บนบัตรนักเรียน หรือพิมพ์รหัสนักเรียนด้านบน</p>
            </div>
          </div>

          <!-- Tab: Manual -->
          <div v-else-if="activeTab === 'manual'" class="relative">
            <div v-if="roster.length === 0" class="py-16 flex flex-col items-center gap-3">
              <Icon icon="fluent:people-24-regular" class="w-12 h-12 text-slate-300 dark:text-slate-600" />
              <p class="text-slate-500 dark:text-slate-400">ไม่มีรายชื่อในกลุ่มเป้าหมาย</p>
              <p class="text-xs text-slate-400">ตั้งกลุ่มเป้าหมายของกิจกรรมนี้ก่อน จึงจะเช็คชื่อได้</p>
            </div>

            <div v-else>
              <div
                v-if="rosterTotal > roster.length"
                class="px-5 py-2.5 bg-amber-50 dark:bg-amber-900/20 border-b border-amber-200 dark:border-amber-700 text-amber-700 dark:text-amber-300 text-sm"
              >
                <Icon icon="fluent:info-24-regular" class="w-4 h-4 inline mr-1" />
                แสดง {{ roster.length }} จาก {{ rosterTotal }} คน — ใช้แท็บสแกนสำหรับคนที่เหลือ
              </div>

              <div class="divide-y divide-slate-100 dark:divide-slate-700">
                <div
                  v-for="row in roster"
                  :key="row.user_id"
                  class="px-5 py-3 flex flex-col sm:flex-row sm:items-center gap-3"
                >
                  <div class="flex items-center gap-3 flex-1 min-w-0">
                    <div
                      class="w-9 h-9 rounded-full bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center flex-shrink-0"
                    >
                      <Icon icon="fluent:person-24-regular" class="w-5 h-5 text-purple-500 dark:text-purple-400" />
                    </div>
                    <div class="min-w-0">
                      <p class="font-medium text-slate-900 dark:text-white truncate text-sm">{{ row.name }}</p>
                      <div class="flex items-center gap-2 mt-0.5">
                        <span v-if="row.student_number" class="text-xs text-slate-400 font-mono">
                          #{{ row.student_number }}
                        </span>
                        <span v-if="row.classroom_name" class="text-xs text-sky-600 dark:text-sky-400 font-medium">
                          {{ row.classroom_name }}
                        </span>
                        <span v-if="row.attendance_status" class="text-xs text-slate-400">
                          <Icon icon="fluent:checkmark-24-regular" class="w-3 h-3 inline" />
                          บันทึกแล้ว
                        </span>
                      </div>
                    </div>
                  </div>

                  <div class="flex flex-wrap gap-1.5">
                    <button
                      v-for="(cfg, key) in statusConfig"
                      :key="key"
                      class="min-h-[44px] sm:min-h-0 px-3 py-1 text-xs font-medium rounded-lg transition-all"
                      :class="row.status === key ? cfg.selectedBtn : cfg.btn"
                      @click="row.status = key"
                    >
                      {{ cfg.label }}
                    </button>
                  </div>

                  <input
                    v-model="row.remarks"
                    type="text"
                    placeholder="หมายเหตุ"
                    class="w-full sm:w-32 px-3 py-1 text-sm rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:outline-none"
                  />
                </div>
              </div>

              <!-- Sticky save bar -->
              <div
                class="sticky bottom-0 bg-white dark:bg-slate-800 border-t border-slate-200 dark:border-slate-700 px-5 py-3 flex items-center justify-between gap-3"
              >
                <span class="text-sm text-slate-500 dark:text-slate-400">
                  บันทึก {{ roster.length }} รายการ
                </span>
                <button
                  class="min-h-[44px] sm:min-h-0 flex items-center gap-2 px-5 py-2 bg-gradient-vikinger text-white font-semibold rounded-lg shadow-vikinger hover:shadow-vikinger-lg hover:scale-105 transition-all disabled:opacity-50 disabled:pointer-events-none"
                  :disabled="isSaving"
                  @click="saveManualRecords"
                >
                  <Icon v-if="isSaving" icon="fluent:spinner-ios-20-regular" class="w-4 h-4 animate-spin" />
                  <Icon v-else icon="fluent:save-24-regular" class="w-4 h-4" />
                  บันทึก
                </button>
              </div>
            </div>

            <p v-if="saveError" class="px-5 py-3 text-sm text-red-600 dark:text-red-400">{{ saveError }}</p>
          </div>

          <!-- Tab: Records -->
          <div v-else-if="activeTab === 'records'" class="p-5">
            <!-- storeRecords answers with skipped_user_ids for anyone who is not an approved
                 member of this school — those rows cannot be enrolled, so they were not saved. -->
            <div
              v-if="skippedCount"
              class="mb-4 flex items-center gap-2 px-4 py-3 rounded-lg bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700 text-amber-700 dark:text-amber-300 text-sm"
            >
              <Icon icon="fluent:warning-24-filled" class="w-5 h-5 flex-shrink-0" />
              ข้าม {{ skippedCount }} รายการ เพราะไม่ใช่สมาชิกที่อนุมัติแล้วของโรงเรียนนี้
            </div>

            <div v-if="checkedRecords.length === 0" class="py-16 flex flex-col items-center gap-3">
              <Icon icon="fluent:people-24-regular" class="w-12 h-12 text-slate-300 dark:text-slate-600" />
              <p class="text-slate-500 dark:text-slate-400">ยังไม่มีใครเช็คชื่อ</p>
            </div>

            <div v-else class="space-y-2">
              <div
                v-for="row in checkedRecords"
                :key="row.user_id"
                class="flex items-center gap-3 p-3 rounded-lg bg-slate-50 dark:bg-slate-700/50"
              >
                <div
                  class="w-9 h-9 rounded-full bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center flex-shrink-0"
                >
                  <Icon icon="fluent:person-24-regular" class="w-5 h-5 text-purple-500 dark:text-purple-400" />
                </div>

                <div class="flex-1 min-w-0">
                  <p class="font-medium text-slate-900 dark:text-white text-sm truncate">{{ row.name }}</p>
                  <div class="flex flex-wrap items-center gap-x-2 gap-y-0.5 mt-0.5">
                    <span
                      v-if="row.student_number"
                      class="inline-flex items-center gap-1 text-xs text-slate-500 dark:text-slate-400 font-mono"
                    >
                      <Icon icon="fluent:number-symbol-24-regular" class="w-3 h-3" />
                      {{ row.student_number }}
                    </span>
                    <span
                      v-if="row.classroom_name"
                      class="inline-flex items-center gap-1 text-xs text-sky-600 dark:text-sky-400 font-medium"
                    >
                      <Icon icon="fluent:building-24-regular" class="w-3 h-3" />
                      {{ row.classroom_name }}
                    </span>
                  </div>
                </div>

                <span
                  class="px-2.5 py-0.5 rounded-full text-xs font-medium shrink-0"
                  :class="recordBadgeClass(row.attendance_status!)"
                >
                  {{ statusConfig[row.attendance_status!]?.label || row.attendance_status }}
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Not found -->
    <div v-else class="flex flex-col items-center justify-center py-32 gap-4">
      <Icon icon="fluent:calendar-empty-24-regular" class="w-16 h-16 text-slate-300 dark:text-slate-600" />
      <p class="text-slate-500 dark:text-slate-400">ไม่พบคาบกิจกรรมนี้</p>
      <NuxtLink
        :to="`/academies/${academyName}/admin/events/${eventId}/sessions`"
        class="px-4 py-2 bg-gradient-vikinger text-white rounded-lg shadow-vikinger hover:shadow-vikinger-lg transition-all"
      >
        กลับหน้ารายการคาบ
      </NuxtLink>
    </div>
  </div>
</template>
