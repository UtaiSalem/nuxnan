<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, watch, nextTick } from 'vue'
import { Icon } from '@iconify/vue'
import Swal from 'sweetalert2'
import { useApi } from '~/composables/useApi'
import {
  type SeatData,
  type SimulatorData,
  parseServerTime,
  isSessionActive,
  ATTENDANCE_STATUS,
} from '~/composables/useAttendanceStatus'
import AttendancePhaserScene from '~/components/learn/course/attendances/AttendancePhaserScene.vue'
import ClassroomSeatGrid from '~/components/learn/course/attendances/ClassroomSeatGrid.vue'

interface Props {
  attendanceId: number
  isCourseAdmin: boolean
}

const props = defineProps<Props>()

const api = useApi()

// ─── State ────────────────────────────────────────────
const loading = ref(true)
const error = ref<string | null>(null)
const simulatorData = ref<SimulatorData | null>(null)

// Server-synced clock: offset captured on each poll, ticking locally every second
const serverTimeMs = ref(Date.now())
const serverOffset = ref(0)
const clockTimer = ref<ReturnType<typeof setInterval> | null>(null)

// Polling
const pollTimer = ref<ReturnType<typeof setInterval> | null>(null)
const isPolling = ref(false)

// UI state
const selectedMemberId = ref<number | null>(null)
const editingSeat = ref<SeatData | null>(null)
const savingStatus = ref(false)
const checkingIn = ref(false)
// Default to the DOM-based farm-game classroom (cleaner, smoother).
// Phaser scene kept as opt-in fallback path.
const phaserFailed = ref(true)
const phaserSceneReady = ref(false)
let initialScrollDone = false

function scrollToMySeat() {
  if (initialScrollDone || props.isCourseAdmin || !authMemberId.value || usePhaserRenderer.value) return
  nextTick(() => {
    const el = document.querySelector(`[data-member-id="${authMemberId.value}"]`)
    if (el) {
      el.scrollIntoView({ behavior: 'smooth', block: 'center', inline: 'center' })
      initialScrollDone = true
    }
  })
}

const emit = defineEmits<{
  (e: 'checked-in', res: any): void
}>()

// ─── Computed ─────────────────────────────────────────
const seats = computed<SeatData[]>(() => simulatorData.value?.seats ?? [])
const layout = computed(() => simulatorData.value?.layout ?? { cols: 4, rows: 5, total_seats: 20 })
const attendanceInfo = computed(() => simulatorData.value?.attendance)
const teacherInfo = computed(() => simulatorData.value?.attendance?.instructor ?? null)

const authMemberId = computed(() => simulatorData.value?.meta?.auth_member_id ?? null)
const mySeat = computed<SeatData | null>(() => {
  if (!authMemberId.value) return null
  return seats.value.find(s => s.course_member_id === authMemberId.value) ?? null
})
const usePhaserRenderer = computed(() => !phaserFailed.value)

const checkInState = computed(() => {
  if (!attendanceInfo.value || !mySeat.value) return { canCheckIn: false, reason: 'no-data' }
  const now = serverTimeMs.value
  const start = new Date(attendanceInfo.value.start_at).getTime()
  const finish = new Date(attendanceInfo.value.finish_at).getTime()
  const lateDeadline = start + (attendanceInfo.value.late_time ?? 15) * 60000

  // Already checked in / late / on leave
  if (mySeat.value.status === 1 || mySeat.value.status === 2) {
    return { canCheckIn: false, reason: 'already-checked-in', status: mySeat.value.status }
  }
  if (mySeat.value.status === 3) return { canCheckIn: false, reason: 'on-leave' }

  // Session timing
  if (now < start) return { canCheckIn: false, reason: 'not-started', countdown: start - now }
  if (now > finish) return { canCheckIn: false, reason: 'ended' }

  // Active window
  return { canCheckIn: true, willBeLate: now > lateDeadline }
})
const doorState = computed(() => ({
  canCheckIn: !!checkInState.value.canCheckIn,
  willBeLate: !!checkInState.value.willBeLate,
  reason: 'reason' in checkInState.value ? checkInState.value.reason : undefined,
  countdown: 'countdown' in checkInState.value ? checkInState.value.countdown : undefined,
}))

// Board content shown on the classroom blackboard — group name leads the title line
const boardTitle = computed(() => {
  const info = attendanceInfo.value
  const session = info?.title || info?.description || 'เช็คชื่อ'
  return [info?.group?.name, session].filter(Boolean).join(' • ')
})
const boardSubtitle = computed(() => {
  const info = attendanceInfo.value
  if (!info?.start_at) return ''
  // Date only — the time range is shown on the board's clock line
  return new Date(info.start_at).toLocaleDateString('th-TH', { dateStyle: 'medium' })
})

const sessionActive = computed(() => {
  if (!attendanceInfo.value) return false
  return isSessionActive(
    attendanceInfo.value.start_at,
    attendanceInfo.value.finish_at,
    serverTimeMs.value,
  )
})

// Polling interval adapts to session state
const effectivePollInterval = computed(() =>
  sessionActive.value ? 5 : 30,
)

// ─── Session clock + countdown for the blackboard ─────
function fmtTime(dateStr: string) {
  return new Date(dateStr).toLocaleTimeString('th-TH', { hour: '2-digit', minute: '2-digit' }) + ' น.'
}

function fmtDuration(ms: number) {
  const total = Math.max(0, Math.floor(ms / 1000))
  const h = Math.floor(total / 3600)
  const m = Math.floor((total % 3600) / 60)
  const s = total % 60
  const mm = String(m).padStart(2, '0')
  const ss = String(s).padStart(2, '0')
  return h > 0 ? `${h}:${mm}:${ss}` : `${mm}:${ss}`
}

const boardClock = computed(() => {
  const info = attendanceInfo.value
  if (!info?.start_at || !info?.finish_at) return null

  const start = new Date(info.start_at).getTime()
  const finish = new Date(info.finish_at).getTime()
  const lateDeadline = start + (info.late_time ?? 15) * 60000
  const now = serverTimeMs.value
  const range = `${fmtTime(info.start_at)} – ${fmtTime(info.finish_at)}`

  if (now < start) {
    return { range, label: 'เริ่มคาบในอีก', value: fmtDuration(start - now), tone: 'sky' }
  }
  if (now <= finish) {
    if (now < lateDeadline) {
      return { range, label: 'เช็คชื่อทันเวลาได้อีก', value: fmtDuration(lateDeadline - now), tone: 'emerald' }
    }
    const left = finish - now
    return { range, label: 'เหลือเวลาคาบเรียน', value: fmtDuration(left), tone: left < 5 * 60000 ? 'red' : 'amber' }
  }
  return { range, label: 'จบคาบแล้ว', value: '', tone: 'gray' }
})

// ─── Data Fetching ────────────────────────────────────
let fetchId = 0

async function fetchSimulator(silent = false) {
  const thisFetch = ++fetchId

  if (!silent) loading.value = true
  error.value = null

  try {
    const data = await api.get<SimulatorData>(`/api/attendances/${props.attendanceId}/simulator`)
    if (thisFetch !== fetchId) return // stale

    simulatorData.value = data

    if (data.meta.server_time) {
      serverOffset.value = parseServerTime(data.meta.server_time) - Date.now()
      serverTimeMs.value = Date.now() + serverOffset.value
    }

    if (!props.isCourseAdmin && authMemberId.value) {
      scrollToMySeat()
    }
  } catch (err: any) {
    if (thisFetch !== fetchId) return
    error.value = err?.message || 'โหลดข้อมูลไม่สำเร็จ'
  } finally {
    if (thisFetch === fetchId) loading.value = false
  }
}

// ─── Polling ──────────────────────────────────────────
function startPolling() {
  stopPolling()
  isPolling.value = true

  const tick = async () => {
    if (document.hidden) return
    await fetchSimulator(true)
  }

  pollTimer.value = setInterval(tick, effectivePollInterval.value * 1000)
}

function stopPolling() {
  if (pollTimer.value) {
    clearInterval(pollTimer.value)
    pollTimer.value = null
  }
  isPolling.value = false
}

// Restart polling when interval changes
watch(effectivePollInterval, () => {
  if (isPolling.value) {
    startPolling()
  }
})

// ─── Visibility change ────────────────────────────────
function onVisibilityChange() {
  if (document.hidden) {
    stopPolling()
  } else {
    fetchSimulator(true)
    startPolling()
  }
}

// ─── Seat selection + admin status editor ─────────────
function handleSeatSelect(memberId: number) {
  selectedMemberId.value = memberId
  if (props.isCourseAdmin) {
    editingSeat.value = seats.value.find(s => s.course_member_id === memberId) ?? null
  }
}

function handlePhaserLoadError(error: unknown) {
  console.error('Phaser attendance scene failed to load:', error)
  phaserFailed.value = true
}

const statusOptions = [
  { value: 1, label: 'มา', icon: 'heroicons:check-circle-20-solid', activeClass: 'bg-emerald-500 text-white', idleClass: 'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-300 hover:bg-emerald-100 dark:hover:bg-emerald-900/40' },
  { value: 2, label: 'สาย', icon: 'heroicons:clock-20-solid', activeClass: 'bg-amber-500 text-white', idleClass: 'bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-300 hover:bg-amber-100 dark:hover:bg-amber-900/40' },
  { value: 3, label: 'ลา', icon: 'heroicons:document-text-20-solid', activeClass: 'bg-sky-500 text-white', idleClass: 'bg-sky-50 dark:bg-sky-900/20 text-sky-700 dark:text-sky-300 hover:bg-sky-100 dark:hover:bg-sky-900/40' },
  { value: 0, label: 'ขาด', icon: 'heroicons:x-circle-20-solid', activeClass: 'bg-red-500 text-white', idleClass: 'bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-300 hover:bg-red-100 dark:hover:bg-red-900/40' },
]

async function applyStatus(status: number) {
  const seat = editingSeat.value
  if (!seat || savingStatus.value) return

  savingStatus.value = true
  try {
    await api.post(
      `/api/attendances/${props.attendanceId}/member/${seat.course_member_id}/update-status`,
      { status },
    )

    // Optimistic local update, then sync from server
    seat.status = status as 0 | 1 | 2 | 3
    if (status === ATTENDANCE_STATUS.ABSENT) {
      seat.time_in = null
      seat.checked_in_at = null
    }
    editingSeat.value = null
    fetchSimulator(true)
  } catch {
    error.value = 'บันทึกสถานะไม่สำเร็จ'
  } finally {
    savingStatus.value = false
  }
}

async function handleSelfCheckIn() {
  if (!checkInState.value.canCheckIn || checkingIn.value) return
  const willBeLate = checkInState.value.willBeLate
  
  checkingIn.value = true
  try {
    const res = await api.post(`/api/attendances/${props.attendanceId}/check-in`, {})
    
    // Reset scroll guard so it scrolls to the seat again after state update
    initialScrollDone = false
    
    await fetchSimulator(true)
    await nextTick()
    scrollToMySeat()
    
    // Highlight my seat
    selectedMemberId.value = authMemberId.value
    emit('checked-in', res)

    // Success toast
    Swal.fire({
      toast: true,
      position: 'top-end',
      icon: willBeLate ? 'warning' : 'success',
      title: willBeLate ? 'รายงานตัวสาย' : 'รายงานตัวสำเร็จ',
      text: willBeLate ? 'คุณเข้าเรียนสายกว่าเวลาที่กำหนด' : 'คุณเข้าเรียนตรงเวลา',
      showConfirmButton: false,
      timer: 3000,
      timerProgressBar: true,
    })
  } catch (err: any) {
    error.value = err?.message || 'รายงานตัวไม่สำเร็จ'
  } finally {
    checkingIn.value = false
  }
}

function closeEditor() {
  editingSeat.value = null
}

// ─── Lifecycle ────────────────────────────────────────
watch(authMemberId, (id) => {
  if (id && selectedMemberId.value === null && !props.isCourseAdmin) {
    selectedMemberId.value = id
  }
}, { immediate: true })

onMounted(async () => {
  document.addEventListener('visibilitychange', onVisibilityChange)
  clockTimer.value = setInterval(() => {
    serverTimeMs.value = Date.now() + serverOffset.value
  }, 1000)
  await fetchSimulator()
  startPolling()
})

onUnmounted(() => {
  stopPolling()
  if (clockTimer.value) clearInterval(clockTimer.value)
  document.removeEventListener('visibilitychange', onVisibilityChange)
})
</script>

<template>
  <div class="space-y-3">
    <!-- Slim toolbar: live indicator + refresh (session info lives on the blackboard) -->
    <div class="flex items-center justify-end gap-3">
      <div v-if="isPolling" class="flex items-center gap-2 text-xs text-gray-400 dark:text-gray-500">
        <span class="relative flex h-2 w-2">
          <span
            v-if="sessionActive"
            class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"
          ></span>
          <span
            :class="['relative inline-flex rounded-full h-2 w-2', sessionActive ? 'bg-emerald-500' : 'bg-gray-400']"
          ></span>
        </span>
        <span>{{ sessionActive ? 'Live' : 'จบคาบแล้ว' }} • ทุก {{ effectivePollInterval }} วินาที</span>
      </div>

      <button
        @click="fetchSimulator(false)"
        :disabled="loading"
        class="p-2 text-gray-400 dark:text-gray-500 hover:text-blue-600 dark:hover:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-colors"
        title="รีเฟรช"
        aria-label="รีเฟรชข้อมูล"
      >
        <Icon
          icon="heroicons:arrow-path-20-solid"
          class="w-5 h-5"
          :class="{ 'animate-spin': loading }"
        />
      </button>
    </div>

    <!-- Loading state -->
    <div v-if="loading && seats.length === 0" class="flex justify-center py-12">
      <Icon icon="svg-spinners:ring-resize" class="w-10 h-10 text-blue-500" />
    </div>

    <!-- Error state -->
    <div
      v-else-if="error && seats.length === 0"
      class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-2xl p-6 text-center"
    >
      <Icon icon="heroicons:exclamation-triangle-20-solid" class="w-12 h-12 text-red-400 mx-auto mb-3" />
      <p class="text-red-700 dark:text-red-300 font-medium">{{ error }}</p>
      <button
        @click="fetchSimulator()"
        class="mt-3 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 text-sm"
      >
        ลองอีกครั้ง
      </button>
    </div>

    <!-- Classroom (full width — summary lives on the blackboard) -->
    <template v-else-if="seats.length > 0">
      <ClientOnly>
        <AttendancePhaserScene
          v-if="usePhaserRenderer"
          :seats="seats"
          :total-seats="layout.total_seats"
          :attendance="attendanceInfo"
          :auth-member-id="authMemberId"
          :selected-member-id="selectedMemberId"
          :is-course-admin="isCourseAdmin"
          :clock="boardClock"
          :server-time-ms="serverTimeMs"
          :door-state="doorState"
          :teacher="teacherInfo"
          @seat-select="handleSeatSelect"
          @door-click="handleSelfCheckIn"
          @scene-ready="phaserSceneReady = true"
          @load-error="handlePhaserLoadError"
        />
        <ClassroomSeatGrid
          v-else
          :seats="seats"
          :cols="layout.cols"
          :rows="layout.rows"
          :total-seats="layout.total_seats"
          :is-course-admin="isCourseAdmin"
          :server-time-ms="serverTimeMs"
          :selected-member-id="selectedMemberId"
          :auth-member-id="authMemberId"
          :title="boardTitle"
          :subtitle="boardSubtitle"
          :clock="boardClock"
          :teacher="teacherInfo"
          :door-state="!isCourseAdmin && mySeat ? doorState : null"
          @select="handleSeatSelect"
          @door-click="handleSelfCheckIn"
        >
      <template #door>
        <!-- "My seat" indicator badge below the door (when student) -->
        <div
          v-if="!isCourseAdmin && mySeat"
          class="flex items-center gap-2 bg-white/90 dark:bg-slate-800/90 backdrop-blur-sm px-3 py-1.5 rounded-full border border-slate-200 dark:border-slate-700 shadow-sm transition-all"
          :class="{ 'ring-2 ring-emerald-500 scale-105': selectedMemberId === authMemberId }"
        >
          <span
            class="w-6 h-6 rounded-lg flex items-center justify-center text-xs font-bold text-white shadow-sm"
            :class="mySeat.status === 1 || mySeat.status === 2 ? 'bg-emerald-500' : 'bg-slate-400'"
          >
            {{ mySeat.seat_number }}
          </span>
          <span class="text-xs font-bold text-slate-700 dark:text-slate-300 truncate max-w-[120px]">
            {{ mySeat.name }}
          </span>
          <Icon
            v-if="checkingIn"
            icon="svg-spinners:ring-resize"
            class="w-4 h-4 text-emerald-600"
          />
        </div>
      </template>
        </ClassroomSeatGrid>
      </ClientOnly>
    </template>

    <!-- Empty state -->
    <div
      v-if="!loading && !error && seats.length === 0"
      class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm p-12 text-center"
    >
      <Icon icon="fluent:people-24-regular" class="w-16 h-16 text-gray-300 dark:text-gray-600 mx-auto mb-4" />
      <h3 class="text-lg font-semibold text-gray-500 dark:text-gray-400">ไม่มีสมาชิกในกลุ่มนี้</h3>
    </div>

    <!-- No seat for student message -->
    <div
      v-else-if="!loading && !error && !isCourseAdmin && !mySeat"
      class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm p-8 text-center"
    >
      <Icon icon="fluent:person-prohibited-24-regular" class="w-12 h-12 text-slate-300 dark:text-slate-600 mx-auto mb-3" />
      <p class="text-slate-500 dark:text-slate-400 font-medium">คุณไม่ได้ถูกจัดที่นั่งในห้องเรียนนี้</p>
    </div>

    <!-- Admin status editor popup -->
    <div
      v-if="editingSeat"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
      @click.self="closeEditor"
    >
      <div class="bg-white dark:bg-slate-800 rounded-vikinger-lg shadow-xl border border-slate-200 dark:border-slate-700 w-full max-w-xs p-5">
        <div class="flex items-center gap-3 mb-4">
          <span class="w-8 h-8 rounded-full bg-vikinger-purple/10 text-vikinger-purple flex items-center justify-center text-sm font-bold">
            {{ editingSeat.seat_number }}
          </span>
          <div class="flex-1 min-w-0">
            <p class="text-sm font-semibold text-slate-900 dark:text-slate-100 truncate">
              {{ editingSeat.name }}
            </p>
            <p v-if="editingSeat.time_in" class="text-xs text-slate-500 dark:text-slate-400 font-mono">
              เข้าเรียน {{ editingSeat.time_in }}
            </p>
          </div>
          <button
            @click="closeEditor"
            class="p-1.5 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors"
            aria-label="ปิด"
          >
            <Icon icon="heroicons:x-mark-20-solid" class="w-4 h-4" />
          </button>
        </div>

        <div class="grid grid-cols-2 gap-2">
          <button
            v-for="option in statusOptions"
            :key="option.value"
            :disabled="savingStatus"
            class="flex items-center justify-center gap-1.5 px-3 py-2.5 rounded-lg text-sm font-semibold transition-colors disabled:opacity-50"
            :class="editingSeat.status === option.value ? option.activeClass : option.idleClass"
            @click="applyStatus(option.value)"
          >
            <Icon
              :icon="savingStatus ? 'svg-spinners:ring-resize' : option.icon"
              class="w-4 h-4"
            />
            <span>{{ option.label }}</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
