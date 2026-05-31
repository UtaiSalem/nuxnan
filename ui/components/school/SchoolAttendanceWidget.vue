<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { storeToRefs } from 'pinia'
import { Icon } from '@iconify/vue'

const props = defineProps<{
  academyId: number
  academyName: string
}>()

const schoolApi = useSchoolManagement()
const { myRole, isAdmin, isTeacher, isStudent, isParent, fetchMyRole } = useAcademyRole(
  computed(() => props.academyId)
)
const { user } = storeToRefs(useAuthStore())

const todaySession = ref<any>(null)
const myRecord = ref<any>(null)
const isLoading = ref(true)

// Computed: วันนี้เป็น string YYYY-MM-DD
const today = new Date().toISOString().split('T')[0]

// Format วันที่แบบไทย dd/mm/yyyy
const todayFormatted = computed(() => {
  const d = new Date()
  return d.toLocaleDateString('th-TH', { day: '2-digit', month: '2-digit', year: 'numeric' })
})

// Fetch open session วันนี้
const loadTodaySession = async () => {
  try {
    const res: any = await schoolApi.getSchoolAttendances(props.academyId, {
      date: today,
      status: 'open',
      per_page: 1,
    })
    if (res.success) {
      const sessions = res.data?.data || []
      todaySession.value = sessions[0] || null
    }
  } catch (e) {
    console.error('Failed to load today session', e)
  }
}

// Check if student already checked in (look up in session records)
const checkMyRecord = async () => {
  if (!todaySession.value || !isStudent.value) return
  try {
    const res: any = await schoolApi.getSchoolAttendance(props.academyId, todaySession.value.id)
    if (res.success) {
      myRecord.value = res.data?.records?.find((r: any) => r.student_id === user.value?.id) || null
    }
  } catch (e) {
    console.error('Failed to check my record', e)
  }
}

onMounted(async () => {
  await fetchMyRole()
  await loadTodaySession()
  await checkMyRecord()
  isLoading.value = false
})

const emits = defineEmits(['open-qr-scanner'])

// Student: open Universal QR Scanner
const openScanner = () => {
  if (!todaySession.value) return
  emits('open-qr-scanner')
}

// Admin/Teacher: navigate to manage session
const goManage = () => {
  if (!todaySession.value) return
  navigateTo(`/academies/${props.academyName}/admin/school-attendance/${todaySession.value.id}`)
}

// Admin: navigate to create session
const goCreate = () => {
  navigateTo(`/academies/${props.academyName}/admin/school-attendance`)
}

// Format checked_in_at time
const formatTime = (dateStr: string | null | undefined) => {
  if (!dateStr) return ''
  const d = new Date(dateStr)
  return d.toLocaleTimeString('th-TH', { hour: '2-digit', minute: '2-digit' })
}

// Status badge helper
const statusLabel = (status: string) => {
  switch (status) {
    case 'present': return { label: 'มาเรียน',     cls: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300' }
    case 'late':    return { label: 'มาสาย',        cls: 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-300' }
    case 'leave':   return { label: 'ลาป่วย/กิจ',  cls: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300' }
    case 'absent':  return { label: 'ขาดเรียน',    cls: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300' }
    default:        return { label: status,          cls: 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300' }
  }
}
</script>

<template>
  <!-- Skeleton Loading -->
  <div
    v-if="isLoading"
    class="animate-pulse bg-white dark:bg-slate-800 rounded-vikinger border border-slate-200 dark:border-slate-700 p-4 mb-4 flex items-center gap-4"
  >
    <div class="w-10 h-10 rounded-full bg-slate-200 dark:bg-slate-700 shrink-0"></div>
    <div class="flex-1 space-y-2">
      <div class="h-4 bg-slate-200 dark:bg-slate-700 rounded w-1/3"></div>
      <div class="h-3 bg-slate-200 dark:bg-slate-700 rounded w-1/2"></div>
    </div>
    <div class="w-28 h-9 bg-slate-200 dark:bg-slate-700 rounded-lg"></div>
  </div>

  <!-- ADMIN/TEACHER — ยังไม่มี session วันนี้ -->
  <div
    v-else-if="(isAdmin || isTeacher) && !todaySession"
    class="bg-amber-50 dark:bg-amber-900/10 rounded-vikinger border border-amber-200 dark:border-amber-800/30 p-4 mb-4 flex items-center gap-4 transition-all duration-200"
  >
    <div class="shrink-0">
      <Icon icon="fluent:person-clock-24-regular" class="w-8 h-8 text-amber-500" />
    </div>
    <div class="flex-1 min-w-0">
      <p class="font-semibold text-amber-800 dark:text-amber-200 text-sm">วันนี้ยังไม่ได้เปิดการเช็คชื่อ</p>
      <p class="text-xs text-amber-600 dark:text-amber-400 mt-0.5">{{ todayFormatted }}</p>
    </div>
    <button
      @click="goCreate"
      class="shrink-0 bg-gradient-vikinger text-white px-4 py-2 rounded-lg shadow-vikinger hover:shadow-vikinger-lg hover:scale-105 transition-all text-sm font-semibold whitespace-nowrap"
    >
      + สร้างการเช็คชื่อ
    </button>
  </div>

  <!-- ADMIN/TEACHER — มี session เปิดอยู่ -->
  <div
    v-else-if="(isAdmin || isTeacher) && todaySession"
    class="bg-green-50 dark:bg-green-900/10 rounded-vikinger border border-green-300 dark:border-green-700/40 p-4 mb-4 flex items-center gap-4 transition-all duration-200"
  >
    <div class="shrink-0 relative">
      <Icon icon="fluent:person-available-24-regular" class="w-8 h-8 text-green-500" />
      <span class="absolute -top-0.5 -right-0.5 w-2.5 h-2.5 rounded-full bg-green-500 animate-pulse ring-2 ring-white dark:ring-slate-900"></span>
    </div>
    <div class="flex-1 min-w-0">
      <div class="flex items-center gap-2 flex-wrap">
        <p class="font-semibold text-green-800 dark:text-green-200 text-sm">เช็คชื่อเปิดอยู่</p>
        <span class="text-xs text-green-600 dark:text-green-400 truncate">{{ todaySession.title }}</span>
      </div>
      <p class="text-xs text-green-600 dark:text-green-400 mt-0.5">
        {{ todaySession.records_count || 0 }} คนเช็คชื่อแล้ว
      </p>
    </div>
    <button
      @click="goManage"
      class="shrink-0 bg-gradient-vikinger text-white px-4 py-2 rounded-lg shadow-vikinger hover:shadow-vikinger-lg transition-all text-sm font-semibold whitespace-nowrap"
    >
      จัดการ →
    </button>
  </div>

  <!-- STUDENT — มี session + ยังไม่ได้เช็ค -->
  <div
    v-else-if="isStudent && todaySession && !myRecord"
    class="bg-gradient-to-r from-teal-600 to-emerald-600 rounded-vikinger shadow-vikinger p-4 mb-4 transition-all duration-200"
  >
    <div class="flex items-center gap-4">
      <div class="shrink-0">
        <Icon icon="fluent:qr-code-24-filled" class="w-10 h-10 text-white/80" />
      </div>
      <div class="flex-1 min-w-0">
        <p class="text-white text-lg font-bold font-heading leading-tight">เช็คชื่อมาโรงเรียน</p>
        <p class="text-white/70 text-sm mt-0.5 truncate">สแกน QR ที่ครูฉายบนจอ • {{ todayFormatted }}</p>
      </div>
    </div>
    <button
      @click="openScanner"
      class="w-full mt-3 bg-white/20 hover:bg-white/30 backdrop-blur-sm text-white font-bold py-3 rounded-lg transition-all hover:scale-[1.02] text-base border border-white/30 flex items-center justify-center gap-2"
    >
      <Icon icon="fluent:scan-qr-code-24-filled" class="text-xl" />
      สแกน QR ตอนนี้
    </button>
  </div>

  <!-- STUDENT — เช็คชื่อแล้ว -->
  <div
    v-else-if="isStudent && todaySession && myRecord"
    class="bg-green-50 dark:bg-green-900/10 rounded-vikinger border border-green-300 dark:border-green-700/40 p-4 mb-4 flex items-center gap-4 transition-all duration-200"
  >
    <div class="shrink-0">
      <Icon icon="mdi:check-circle" class="w-8 h-8 text-green-500" />
    </div>
    <div class="flex-1 min-w-0">
      <p class="font-semibold text-green-700 dark:text-green-300 text-sm">เช็คชื่อเรียบร้อยแล้ว 🎉</p>
      <div class="flex items-center gap-2 mt-1 flex-wrap">
        <span
          :class="['px-2 py-0.5 rounded-full text-xs font-medium', statusLabel(myRecord.status).cls]"
        >
          {{ statusLabel(myRecord.status).label }}
        </span>
        <span v-if="myRecord.checked_in_at" class="text-xs text-green-600 dark:text-green-400">
          เวลา {{ formatTime(myRecord.checked_in_at) }}
        </span>
        <span
          class="text-xs px-2 py-0.5 rounded-full bg-slate-100 dark:bg-slate-700 text-slate-500 dark:text-slate-400"
        >
          {{ myRecord.check_in_method === 'qr' ? 'QR' : 'บันทึกโดยครู' }}
        </span>
      </div>
    </div>
  </div>

  <!-- STUDENT — ไม่มี session วันนี้ -->
  <div
    v-else-if="isStudent && !todaySession"
    class="bg-slate-50 dark:bg-slate-800/50 rounded-vikinger border border-slate-200 dark:border-slate-700 p-3 mb-4 flex items-center gap-3 transition-all duration-200"
  >
    <Icon icon="fluent:calendar-empty-24-regular" class="w-6 h-6 text-slate-400 shrink-0" />
    <p class="text-slate-500 dark:text-slate-400 text-sm">วันนี้ยังไม่มีการเช็คชื่อ</p>
  </div>

  <!-- PARENT -->
  <div
    v-else-if="isParent"
    class="bg-white dark:bg-slate-800 rounded-vikinger border border-slate-200 dark:border-slate-700 p-4 mb-4 flex items-center gap-4 transition-all duration-200"
  >
    <div class="shrink-0">
      <Icon icon="fluent:person-heart-24-regular" class="w-8 h-8 text-blue-500" />
    </div>
    <div class="flex-1 min-w-0">
      <p class="font-semibold text-slate-700 dark:text-slate-100 text-sm">สถานะการมาโรงเรียนวันนี้</p>
      <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
        <template v-if="todaySession">เปิดรับเช็คชื่อแล้ว — {{ todaySession.title }}</template>
        <template v-else>ยังไม่มีการเช็คชื่อวันนี้</template>
      </p>
    </div>
  </div>

  <!-- default: role ไม่รู้จัก หรือ guest — ไม่แสดงอะไร -->
  <!-- hidden intentionally -->
</template>
