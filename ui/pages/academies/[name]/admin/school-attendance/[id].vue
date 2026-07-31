<script setup lang="ts">
/**
 * Academy Admin — School Attendance Session Detail
 * แสดง QR Code, บันทึกด้วยตนเอง, และรายชื่อผู้เช็คชื่อ
 */
import { Icon } from '@iconify/vue'

definePageMeta({ layout: 'main' })

const route = useRoute()
const api = useApi()
const schoolApi = useSchoolManagement()
const academyName = computed(() => route.params.name as string)
const sessionId = computed(() => Number(route.params.id))
const academyId = ref<number | null>(null)

const session = ref<any>(null)
const summary = ref<any>({ total: 0, present: 0, absent: 0, late: 0, leave: 0 })
const students = ref<any[]>([])
const isLoading = ref(true)
const activeTab = ref<'qr' | 'scan' | 'manual' | 'records'>('qr')
const isClosing = ref(false)
const isSaving = ref(false)

// Scan tab state
const scanInput = ref('')
const scanInputRef = ref<HTMLInputElement | null>(null)
const isScanning = ref(false)
const scanResult = ref<{
  type: 'success' | 'late' | 'already' | 'error'
  message: string
  studentName?: string
  studentPhoto?: string
} | null>(null)
const recentScans = ref<Array<{
  identifier: string
  studentName: string
  studentPhoto?: string
  studentNumber?: number | null
  classroomName?: string | null
  status: string
  time: string
}>>([])

const doScan = async () => {
  const identifier = scanInput.value.trim()
  if (!identifier || !academyId.value || isScanning.value) return

  isScanning.value = true
  scanResult.value = null

  try {
    const res: any = await schoolApi.scanStudentAttendance(
      academyId.value,
      sessionId.value,
      identifier,
    )

    if (res?.success) {
      scanResult.value = {
        type: res.status === 'late' ? 'late' : 'success',
        message: res.message,
        studentName: res.student_name,
        studentPhoto: res.student_photo,
      }
      recentScans.value.unshift({
        identifier,
        studentName: res.student_name || identifier,
        studentPhoto: res.student_photo,
        studentNumber: res.student_number ?? null,
        classroomName: res.classroom_name ?? null,
        status: res.status,
        time: new Date().toLocaleTimeString('th-TH', { hour: '2-digit', minute: '2-digit' }),
      })
      await loadSession()
    } else if (res?.already_checked) {
      scanResult.value = {
        type: 'already',
        message: res.message,
        studentName: res.student_name,
        studentPhoto: res.student_photo,
      }
    } else {
      scanResult.value = {
        type: 'error',
        message: res?.message || 'ไม่พบนักเรียน',
      }
    }
  } catch (e: any) {
    scanResult.value = {
      type: 'error',
      message: e?.data?.message || 'เกิดข้อผิดพลาด',
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

const statusIcon = (status: string) => {
  switch (status) {
    case 'present': return { icon: 'mdi:check-circle', cls: 'text-green-500' }
    case 'late': return { icon: 'fluent:clock-alarm-24-filled', cls: 'text-orange-500' }
    default: return { icon: 'mdi:check-circle', cls: 'text-green-500' }
  }
}

interface ManualRecord {
  student_id: number
  name: string
  photo: string
  student_number?: number | null
  classroom_name?: string | null
  status: string
  remark: string
}
const manualRecords = ref<ManualRecord[]>([])

onMounted(async () => {
  try {
    const res: any = await api.get(`/api/academies/${academyName.value}`)
    if (res.success) {
      academyId.value = res.academy.id
      await Promise.all([loadSession(), loadStudents()])
    }
  } finally {
    isLoading.value = false
  }
})

const loadSession = async () => {
  if (!academyId.value) return
  const res: any = await schoolApi.getSchoolAttendance(academyId.value, sessionId.value)
  if (res?.success) {
    session.value = res.data
    summary.value = res.summary || {}
  }
}

const loadStudents = async () => {
  if (!academyId.value) return
  const res: any = await api.get(`/api/academies/${academyId.value}/classrooms/students`)
  if (res?.success) {
    const studentList = [
      res.data,
      res.data?.data,
      res.students,
      res.students?.data,
    ].find((value) => Array.isArray(value)) || []

    students.value = studentList
    manualRecords.value = studentList
      .map((s: any) => ({
        student_id: s.user_id || s.user?.id || s.id,
        name: s.name || s.user?.name || [s.title_prefix_th, s.first_name_th, s.last_name_th].filter(Boolean).join(' ') || s.student_id || '',
        photo: s.profile_photo_path || s.user?.profile_photo_path || s.profile_image_url || s.profile_image || '',
        student_number: s.student_number ?? null,
        classroom_name: s.classroom_name ?? s.classroom?.name ?? null,
        status: 'present',
        remark: '',
      }))
      .filter((record: any) => Boolean(record.student_id))
  }
}

const closeSession = async () => {
  if (!academyId.value || isClosing.value) return
  isClosing.value = true
  try {
    const res: any = await schoolApi.closeSchoolAttendance(academyId.value, sessionId.value)
    if (res?.success) {
      session.value.status = 'closed'
      session.value.closed_at = new Date().toISOString()
    }
  } finally {
    isClosing.value = false
  }
}

const saveManualRecords = async () => {
  if (!academyId.value || isSaving.value) return
  isSaving.value = true
  try {
    const records = manualRecords.value.map((r) => ({
      student_id: r.student_id,
      status: r.status,
      remarks: r.remark || undefined,
    }))
    const res: any = await schoolApi.recordSchoolAttendances(academyId.value, sessionId.value, records)
    if (res?.success) {
      await loadSession()
      activeTab.value = 'records'
    }
  } finally {
    isSaving.value = false
  }
}

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
    absent: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300',
  }
  return map[status] || 'bg-slate-100 text-slate-600'
}

const formatTime = (dt: string) => {
  if (!dt) return '-'
  return new Date(dt).toLocaleTimeString('th-TH', { hour: '2-digit', minute: '2-digit' })
}

const formatDate = (date: string) => {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('th-TH', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    weekday: 'short',
  })
}

const totalSummary = computed(() => {
  return (
    (summary.value.total || 0) ||
    Object.values(summary.value as Record<string, number>).reduce((a: number, b: number) => a + b, 0)
  )
})

const summaryPercent = (count: number) => {
  if (!totalSummary.value) return 0
  return Math.round((count / totalSummary.value) * 100)
}
</script>

<template>
  <div class="min-h-screen bg-slate-50 dark:bg-slate-900">
    <!-- Skeleton -->
    <div v-if="isLoading" class="space-y-6 p-6">
      <div class="h-32 rounded-vikinger bg-slate-200 dark:bg-slate-700 animate-pulse" />
      <div class="grid grid-cols-5 gap-3">
        <div v-for="i in 5" :key="i" class="h-20 rounded-vikinger bg-slate-200 dark:bg-slate-700 animate-pulse" />
      </div>
      <div class="h-96 rounded-vikinger bg-slate-200 dark:bg-slate-700 animate-pulse" />
    </div>

    <div v-else-if="session" class="space-y-6 pb-10">
      <!-- Header -->
      <div class="bg-gradient-to-br from-violet-600 via-purple-600 to-indigo-700 px-6 py-6">
        <div class="max-w-5xl mx-auto">
          <!-- Back -->
          <NuxtLink
            :to="`/academies/${academyName}/admin/school-attendance`"
            class="inline-flex items-center gap-1.5 text-purple-200 hover:text-white text-sm mb-4 transition-colors"
          >
            <Icon icon="fluent:arrow-left-24-regular" class="w-4 h-4" />
            กลับ
          </NuxtLink>

          <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
            <div>
              <div class="flex flex-wrap items-center gap-2 mb-2">
                <h1 class="font-heading text-2xl font-bold text-white">
                  {{ session.title || 'เช็คชื่อการมาโรงเรียน' }}
                </h1>
                <!-- Status badge -->
                <span
                  v-if="session.status === 'open'"
                  class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-400/20 text-green-200 border border-green-400/40"
                >
                  <span class="w-1.5 h-1.5 rounded-full bg-green-400 animate-pulse" />
                  กำลังเปิด
                </span>
                <span
                  v-else
                  class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-white/10 text-purple-200"
                >
                  ปิดแล้ว
                </span>
              </div>
              <div class="flex flex-wrap gap-3">
                <span class="text-purple-200 text-sm">
                  <Icon icon="fluent:calendar-24-regular" class="w-4 h-4 inline mr-1" />
                  {{ formatDate(session.date) }}
                </span>
                <span v-if="session.start_time" class="text-purple-200 text-sm">
                  <Icon icon="fluent:clock-24-regular" class="w-4 h-4 inline mr-1" />
                  เริ่ม {{ session.start_time }}
                </span>
                <span v-if="session.late_minutes" class="text-orange-200 text-sm">
                  <Icon icon="fluent:warning-24-regular" class="w-4 h-4 inline mr-1" />
                  สายหลัง {{ session.late_minutes }} นาที
                </span>
              </div>
            </div>

            <button
              v-if="session.status === 'open'"
              class="flex items-center gap-2 px-4 py-2.5 bg-red-500 hover:bg-red-600 text-white font-semibold rounded-vikinger transition-all shadow disabled:opacity-50 disabled:pointer-events-none"
              :disabled="isClosing"
              @click="closeSession"
            >
              <Icon v-if="isClosing" icon="fluent:spinner-ios-20-regular" class="w-4 h-4 animate-spin" />
              <Icon v-else icon="fluent:lock-closed-24-filled" class="w-4 h-4" />
              ปิดการเช็คชื่อ
            </button>
          </div>
        </div>
      </div>

      <div class="max-w-5xl mx-auto px-6 space-y-6">
        <!-- Summary stats -->
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
          <div
            class="bg-white dark:bg-slate-800 rounded-vikinger shadow-card dark:shadow-card-dark border border-slate-200 dark:border-slate-700 p-4 text-center"
          >
            <p class="text-2xl font-heading font-bold text-slate-900 dark:text-white">{{ totalSummary }}</p>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">รวม</p>
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
            <p class="text-2xl font-heading font-bold text-blue-500 dark:text-blue-400">{{ summary.leave || 0 }}</p>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
              ลา <span class="text-blue-400">({{ summaryPercent(summary.leave || 0) }}%)</span>
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
        <div class="bg-white dark:bg-slate-800 rounded-vikinger shadow-card dark:shadow-card-dark border border-slate-200 dark:border-slate-700 overflow-hidden">
          <!-- Tab nav -->
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
              v-if="session.status === 'closed'"
              class="w-full mb-6 flex items-center gap-2 px-4 py-3 rounded-lg bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-700 text-orange-700 dark:text-orange-300 text-sm"
            >
              <Icon icon="fluent:warning-24-filled" class="w-5 h-5 flex-shrink-0" />
              การเช็คชื่อนี้ปิดแล้ว นักเรียนไม่สามารถสแกน QR ได้อีก
            </div>

            <div v-if="session.status === 'open' && academyId" class="w-full max-w-lg">
              <SchoolAttendanceQRDisplay 
                :academy-id="academyId" 
                :attendance-id="sessionId" 
              />
            </div>
            
            <div v-else-if="session.status === 'closed'" class="flex flex-col items-center gap-6">
              <div
                class="p-4 bg-white dark:bg-slate-700 rounded-vikinger border-2 border-slate-200 dark:border-slate-600 shadow-lg opacity-50"
              >
                <div class="w-64 h-64 flex items-center justify-center">
                  <Icon icon="fluent:lock-closed-24-regular" class="w-20 h-20 text-slate-300 dark:text-slate-600" />
                </div>
              </div>
              <p class="text-slate-500">เซสชันนี้ปิดแล้ว</p>
            </div>
          </div>

          <!-- Tab: สแกน/รหัส -->
          <div v-else-if="activeTab === 'scan'" class="p-6 space-y-6">

            <!-- Input Area -->
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
                    :disabled="isScanning || session?.status === 'closed'"
                    autocomplete="off"
                    @keydown="onScanInput"
                  />
                </div>
                <button
                  :disabled="!scanInput.trim() || isScanning || session?.status === 'closed'"
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

              <!-- Closed warning -->
              <div v-if="session?.status === 'closed'" class="mt-4 text-center">
                <span class="text-sm text-slate-500 dark:text-slate-400">
                  <Icon icon="fluent:lock-closed-24-regular" class="w-4 h-4 inline mr-1" />
                  การเช็คชื่อนี้ปิดแล้ว
                </span>
              </div>
            </div>

            <!-- Scan Result Feedback -->
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
                  scanResult.type === 'late'    ? 'bg-orange-50 dark:bg-orange-900/10 border-orange-300 dark:border-orange-700/50' :
                  scanResult.type === 'already' ? 'bg-blue-50 dark:bg-blue-900/10 border-blue-300 dark:border-blue-700/50' :
                                                  'bg-red-50 dark:bg-red-900/10 border-red-300 dark:border-red-700/50'
                ]"
              >
                <!-- Photo or Icon -->
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
                      scanResult.type === 'late'    ? 'bg-orange-100 dark:bg-orange-900/30' :
                      scanResult.type === 'already' ? 'bg-blue-100 dark:bg-blue-900/30' : 'bg-red-100 dark:bg-red-900/30'
                    ]"
                  >
                    <Icon
                      :icon="
                        scanResult.type === 'success' ? 'mdi:check-circle' :
                        scanResult.type === 'late'    ? 'fluent:clock-alarm-24-filled' :
                        scanResult.type === 'already' ? 'fluent:info-24-filled' : 'fluent:error-circle-24-regular'
                      "
                      :class="[
                        'w-6 h-6',
                        scanResult.type === 'success' ? 'text-green-600 dark:text-green-400' :
                        scanResult.type === 'late'    ? 'text-orange-600 dark:text-orange-400' :
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
                      scanResult.type === 'late'    ? 'text-orange-700 dark:text-orange-300' :
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
                    scanResult.type === 'late'    ? 'bg-orange-500 text-white' :
                    scanResult.type === 'already' ? 'bg-blue-500 text-white' : 'bg-red-500 text-white'
                  ]"
                >
                  {{
                    scanResult.type === 'success' ? 'มาเรียน' :
                    scanResult.type === 'late'    ? 'มาสาย' :
                    scanResult.type === 'already' ? 'เช็คแล้ว' : 'ไม่พบ'
                  }}
                </span>
              </div>
            </Transition>

            <!-- Recent Scans History -->
            <div
              v-if="recentScans.length"
              class="bg-white dark:bg-slate-800 rounded-vikinger border border-slate-200 dark:border-slate-700 overflow-hidden shadow-card dark:shadow-card-dark"
            >
              <div class="px-4 py-3 border-b border-slate-100 dark:border-slate-700 flex items-center justify-between">
                <h4 class="text-sm font-semibold text-slate-700 dark:text-slate-300 flex items-center gap-2">
                  <Icon icon="fluent:history-24-regular" class="w-4 h-4" />
                  สแกนล่าสุดในเซสชันนี้
                </h4>
                <span class="text-xs text-slate-400">{{ recentScans.length }} รายการ</span>
              </div>
              <ul class="divide-y divide-slate-100 dark:divide-slate-700 max-h-64 overflow-y-auto">
                <li
                  v-for="(scan, i) in recentScans"
                  :key="i"
                  class="flex items-center gap-3 px-4 py-3"
                >
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
                    <div class="flex items-center gap-2">
                      <span class="text-xs text-slate-400 font-mono">{{ scan.identifier }}</span>
                      <span v-if="scan.studentNumber" class="text-xs text-slate-400 font-mono">#{{ scan.studentNumber }}</span>
                      <span v-if="scan.classroomName" class="text-xs text-sky-600 dark:text-sky-400 font-medium">{{ scan.classroomName }}</span>
                    </div>
                  </div>
                  <div class="flex items-center gap-2 shrink-0">
                    <Icon :icon="statusIcon(scan.status).icon" :class="['w-4 h-4', statusIcon(scan.status).cls]" />
                    <span class="text-xs text-slate-500 dark:text-slate-400">{{ scan.time }}</span>
                  </div>
                </li>
              </ul>
            </div>

            <!-- Empty recent scans -->
            <div v-if="!recentScans.length" class="text-center py-8 text-slate-400 dark:text-slate-500">
              <Icon icon="fluent:scan-24-regular" class="w-10 h-10 mx-auto mb-2 opacity-40" />
              <p class="text-sm">ยังไม่มีการสแกนในเซสชันนี้</p>
              <p class="text-xs mt-1">สแกน QR บนบัตรนักเรียน หรือพิมพ์รหัสนักเรียนด้านบน</p>
            </div>

          </div>

          <!-- Tab: Manual -->
          <div v-else-if="activeTab === 'manual'" class="relative">
            <div v-if="manualRecords.length === 0" class="py-16 flex flex-col items-center gap-3">
              <Icon icon="fluent:people-24-regular" class="w-12 h-12 text-slate-300 dark:text-slate-600" />
              <p class="text-slate-500 dark:text-slate-400">ไม่มีรายชื่อนักเรียน</p>
            </div>

            <div v-else class="divide-y divide-slate-100 dark:divide-slate-700">
              <div
                v-for="record in manualRecords"
                :key="record.student_id"
                class="px-5 py-3 flex flex-col sm:flex-row sm:items-center gap-3"
              >
                <!-- Student info -->
                <div class="flex items-center gap-3 flex-1 min-w-0">
                  <img
                    v-if="record.photo"
                    :src="record.photo"
                    :alt="record.name"
                    class="w-9 h-9 rounded-full object-cover flex-shrink-0"
                  />
                  <div
                    v-else
                    class="w-9 h-9 rounded-full bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center flex-shrink-0"
                  >
                    <Icon icon="fluent:person-24-regular" class="w-5 h-5 text-purple-500 dark:text-purple-400" />
                  </div>
                  <div class="min-w-0">
                    <p class="font-medium text-slate-900 dark:text-white truncate text-sm">{{ record.name }}</p>
                    <div class="flex items-center gap-2 mt-0.5">
                      <span v-if="record.student_number" class="text-xs text-slate-400 font-mono">
                        #{{ record.student_number }}
                      </span>
                      <span v-if="record.classroom_name" class="text-xs text-sky-600 dark:text-sky-400 font-medium">
                        {{ record.classroom_name }}
                      </span>
                    </div>
                  </div>
                </div>

                <!-- Status buttons -->
                <div class="flex flex-wrap gap-1.5">
                  <button
                    v-for="(cfg, key) in statusConfig"
                    :key="key"
                    class="px-3 py-1 text-xs font-medium rounded-lg transition-all"
                    :class="record.status === key ? cfg.selectedBtn : cfg.btn"
                    @click="record.status = key"
                  >
                    {{ cfg.label }}
                  </button>
                </div>

                <!-- Remark -->
                <input
                  v-model="record.remark"
                  type="text"
                  placeholder="หมายเหตุ"
                  class="w-full sm:w-32 px-3 py-1 text-sm rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:outline-none"
                />
              </div>
            </div>

            <!-- Sticky save bar -->
            <div
              v-if="manualRecords.length > 0"
              class="sticky bottom-0 bg-white dark:bg-slate-800 border-t border-slate-200 dark:border-slate-700 px-5 py-3 flex items-center justify-between"
            >
              <span class="text-sm text-slate-500 dark:text-slate-400">
                บันทึก {{ manualRecords.length }} รายการ
              </span>
              <button
                class="flex items-center gap-2 px-5 py-2 bg-gradient-vikinger text-white font-semibold rounded-lg shadow-vikinger hover:shadow-vikinger-lg hover:scale-105 transition-all disabled:opacity-50 disabled:pointer-events-none"
                :disabled="isSaving"
                @click="saveManualRecords"
              >
                <Icon v-if="isSaving" icon="fluent:spinner-ios-20-regular" class="w-4 h-4 animate-spin" />
                <Icon v-else icon="fluent:save-24-regular" class="w-4 h-4" />
                บันทึก
              </button>
            </div>
          </div>

          <!-- Tab: Records -->
          <div v-else-if="activeTab === 'records'" class="p-5">
            <div
              v-if="!session.records || session.records.length === 0"
              class="py-16 flex flex-col items-center gap-3"
            >
              <Icon icon="fluent:people-24-regular" class="w-12 h-12 text-slate-300 dark:text-slate-600" />
              <p class="text-slate-500 dark:text-slate-400">ยังไม่มีใครเช็คชื่อ</p>
            </div>

            <div v-else class="space-y-2">
              <div
                v-for="rec in session.records"
                :key="rec.id"
                class="flex items-center gap-3 p-3 rounded-lg bg-slate-50 dark:bg-slate-700/50"
              >
                <img
                  v-if="rec.student?.profile_photo_path"
                  :src="rec.student.profile_photo_path"
                  :alt="rec.student?.name"
                  class="w-9 h-9 rounded-full object-cover flex-shrink-0"
                />
                <div
                  v-else
                  class="w-9 h-9 rounded-full bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center flex-shrink-0"
                >
                  <Icon icon="fluent:person-24-regular" class="w-5 h-5 text-purple-500 dark:text-purple-400" />
                </div>

                <div class="flex-1 min-w-0">
                  <p class="font-medium text-slate-900 dark:text-white text-sm truncate">
                    {{ rec.student?.name || 'ไม่ระบุ' }}
                  </p>
                  <div class="flex flex-wrap items-center gap-x-2 gap-y-0.5 mt-0.5">
                    <span v-if="rec.student_number" class="inline-flex items-center gap-1 text-xs text-slate-500 dark:text-slate-400 font-mono">
                      <Icon icon="fluent:number-symbol-24-regular" class="w-3 h-3" />
                      {{ rec.student_number }}
                    </span>
                    <span v-if="rec.classroom_name" class="inline-flex items-center gap-1 text-xs text-sky-600 dark:text-sky-400 font-medium">
                      <Icon icon="fluent:building-24-regular" class="w-3 h-3" />
                      {{ rec.classroom_name }}
                    </span>
                    <span class="text-xs text-slate-400">
                      {{ formatTime(rec.checked_in_at || rec.created_at) }}
                    </span>
                    <span v-if="rec.check_in_method" class="inline-flex items-center gap-0.5 text-xs text-slate-400">
                      <Icon
                        :icon="rec.check_in_method === 'qr' ? 'fluent:qr-code-24-regular' : 'fluent:pen-24-regular'"
                        class="w-3 h-3"
                      />
                      {{ rec.check_in_method === 'qr' ? 'QR' : 'Manual' }}
                    </span>
                  </div>
                </div>

                <span class="px-2.5 py-0.5 rounded-full text-xs font-medium shrink-0" :class="recordBadgeClass(rec.status)">
                  {{ statusConfig[rec.status]?.label || rec.status }}
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
      <p class="text-slate-500 dark:text-slate-400">ไม่พบข้อมูล session นี้</p>
      <NuxtLink
        :to="`/academies/${academyName}/admin/school-attendance`"
        class="px-4 py-2 bg-gradient-vikinger text-white rounded-lg shadow-vikinger hover:shadow-vikinger-lg transition-all"
      >
        กลับหน้ารายการ
      </NuxtLink>
    </div>
  </div>
</template>
