<script setup lang="ts">
/**
 * Academy Admin — Activity Sessions
 * รายการคาบของกิจกรรมต่อเนื่อง (ชมรม, ลูกเสือ, ละหมาดรายวัน) + สร้าง/แก้ไข/ลบคาบ
 */
import { Icon } from '@iconify/vue'

definePageMeta({ layout: 'main' })

const route = useRoute()
const api = useApi()
const eventsApi = useSchoolEvents()

const academyName = computed(() => route.params.name as string)
const eventId = computed(() => Number(route.params.id))
const academyId = ref<number | null>(null)

const event = ref<any>(null)
const sessions = ref<any[]>([])
const isLoading = ref(true)
const isSaving = ref(false)
const isExporting = ref(false)
const errorMessage = ref('')

const filters = ref({ from: '', to: '', status: '', q: '' })

const statusLabels: Record<string, string> = {
  scheduled: 'ตามกำหนด',
  completed: 'จบแล้ว',
  cancelled: 'ยกเลิก',
}

const statusBadgeClass = (status: string) => {
  const map: Record<string, string> = {
    scheduled: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300',
    completed: 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-400',
    cancelled: 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300',
  }
  return map[status] || 'bg-slate-100 text-slate-600'
}

// ---- Create / edit modal ----------------------------------------------------

const emptyForm = () => ({
  title: '',
  slot_label: '',
  start_datetime: '',
  end_datetime: '',
  location: '',
  status: 'scheduled',
  is_makeup_class: false,
})

const showFormModal = ref(false)
const editingSession = ref<any>(null)
const form = ref(emptyForm())
const formError = ref('')

// <input type="datetime-local"> wants 'YYYY-MM-DDTHH:mm'; the API wants 'YYYY-MM-DD HH:mm:ss'
const toInputValue = (value: string | null) => (value ? value.replace(' ', 'T').slice(0, 16) : '')
const toApiValue = (value: string) => (value ? `${value.replace('T', ' ')}:00` : null)

const openCreateModal = () => {
  editingSession.value = null
  form.value = emptyForm()
  formError.value = ''
  showFormModal.value = true
}

const openEditModal = (session: any) => {
  editingSession.value = session
  form.value = {
    title: session.title || '',
    slot_label: session.slot_label || '',
    start_datetime: toInputValue(session.start_datetime),
    end_datetime: toInputValue(session.end_datetime),
    location: session.location || '',
    status: session.status || 'scheduled',
    is_makeup_class: Boolean(session.is_makeup_class),
  }
  formError.value = ''
  showFormModal.value = true
}

const saveSession = async () => {
  if (!academyId.value || isSaving.value) return
  isSaving.value = true
  formError.value = ''

  const payload = {
    ...form.value,
    start_datetime: toApiValue(form.value.start_datetime),
    end_datetime: toApiValue(form.value.end_datetime),
  }

  try {
    if (editingSession.value) {
      await eventsApi.updateEventSession(academyId.value, eventId.value, editingSession.value.id, payload)
    } else {
      await eventsApi.createEventSession(academyId.value, eventId.value, payload)
    }
    showFormModal.value = false
    await loadSessions()
  } catch (e: any) {
    // 422 = เวลาสิ้นสุดมาก่อนเวลาเริ่ม หรือค่าที่ส่งไม่ผ่าน validation
    formError.value = e?.data?.message || 'บันทึกไม่สำเร็จ กรุณาตรวจสอบข้อมูล'
  } finally {
    isSaving.value = false
  }
}

const deleteSession = async (session: any) => {
  if (!academyId.value) return
  errorMessage.value = ''
  try {
    await eventsApi.deleteEventSession(academyId.value, eventId.value, session.id)
    await loadSessions()
  } catch (e: any) {
    // 409 = คาบนี้มีการเช็คชื่อแล้ว ข้อความจาก API บอกให้เปลี่ยนสถานะเป็น "ยกเลิก" แทน
    errorMessage.value = e?.data?.message || 'ลบคาบนี้ไม่สำเร็จ'
  }
}

// ---- Loading ----------------------------------------------------------------

const loadSessions = async () => {
  if (!academyId.value) return
  const params: Record<string, any> = { per_page: 200 }
  if (filters.value.from) params.from = filters.value.from
  if (filters.value.to) params.to = filters.value.to
  if (filters.value.status) params.status = filters.value.status
  if (filters.value.q) params.q = filters.value.q

  const res: any = await eventsApi.getEventSessions(academyId.value, eventId.value, params)
  sessions.value = res?.data?.data || []
}

const exportAttendance = async () => {
  if (!academyId.value || isExporting.value) return
  isExporting.value = true
  errorMessage.value = ''
  try {
    const { blob, filename } = await eventsApi.getEventAttendanceReport(academyId.value, eventId.value, {
      from: filters.value.from || undefined,
      to: filters.value.to || undefined,
      format: 'xlsx',
    })
    const url = URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = filename || `activity-attendance-${eventId.value}.xlsx`
    document.body.appendChild(link)
    link.click()
    link.remove()
    URL.revokeObjectURL(url)
  } catch (e: any) {
    errorMessage.value = e?.data?.message || 'ส่งออก Excel ไม่สำเร็จ'
  } finally {
    isExporting.value = false
  }
}

onMounted(async () => {
  try {
    const res: any = await api.get(`/api/academies/${academyName.value}`)
    if (!res?.success) return
    academyId.value = res.academy.id

    const eventRes: any = await eventsApi.getEvent(academyId.value!, eventId.value)
    event.value = eventRes?.data || null
    await loadSessions()
  } finally {
    isLoading.value = false
  }
})

// ---- Display helpers --------------------------------------------------------

const sessionTitle = (session: any) =>
  session.title || session.slot_label || formatDate(session.start_datetime)

const formatDate = (value: string) => {
  if (!value) return '-'
  return new Date(value).toLocaleDateString('th-TH', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    weekday: 'short',
  })
}

const formatTime = (value: string) => {
  if (!value) return ''
  return new Date(value).toLocaleTimeString('th-TH', { hour: '2-digit', minute: '2-digit' })
}

const formatRange = (start: string, end: string | null) => {
  if (!start) return '-'
  const base = `${formatDate(start)} ${formatTime(start)}`
  return end ? `${base} - ${formatTime(end)}` : base
}
</script>

<template>
  <div class="min-h-screen bg-slate-50 dark:bg-slate-900">
    <!-- Skeleton -->
    <div v-if="isLoading" class="space-y-6 p-6">
      <div class="h-32 rounded-vikinger bg-slate-200 dark:bg-slate-700 animate-pulse" />
      <div class="h-16 rounded-vikinger bg-slate-200 dark:bg-slate-700 animate-pulse" />
      <div v-for="i in 3" :key="i" class="h-24 rounded-vikinger bg-slate-200 dark:bg-slate-700 animate-pulse" />
    </div>

    <div v-else class="space-y-6 pb-10">
      <!-- Header -->
      <div class="bg-gradient-to-br from-violet-600 via-purple-600 to-indigo-700 px-6 py-6">
        <div class="max-w-5xl mx-auto">
          <NuxtLink
            :to="`/academies/${academyName}/admin/events`"
            class="inline-flex items-center gap-1.5 text-purple-200 hover:text-white text-sm mb-4 transition-colors"
          >
            <Icon icon="fluent:arrow-left-24-regular" class="w-4 h-4" />
            กลับ
          </NuxtLink>

          <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
            <div>
              <h1 class="font-heading text-2xl font-bold text-white">
                {{ event?.title || 'กิจกรรม' }}
              </h1>
              <p class="text-purple-200 text-sm mt-1">
                <Icon icon="fluent:calendar-clock-24-regular" class="w-4 h-4 inline mr-1" />
                คาบเช็คชื่อทั้งหมด {{ sessions.length }} คาบ
              </p>
            </div>

            <button
              :disabled="isExporting"
              class="mr-2 flex items-center gap-2 px-4 py-2.5 bg-white/15 text-white font-semibold rounded-vikinger transition-all hover:bg-white/25 disabled:opacity-60"
              @click="exportAttendance"
            >
              <Icon v-if="isExporting" icon="fluent:spinner-ios-20-regular" class="w-4 h-4 animate-spin" />
              <Icon v-else icon="fluent:arrow-download-24-regular" class="w-4 h-4" />
              ส่งออก Excel
            </button>
            <button
              class="flex items-center gap-2 px-4 py-2.5 bg-white text-purple-700 font-semibold rounded-vikinger transition-all shadow hover:shadow-lg"
              @click="openCreateModal"
            >
              <Icon icon="fluent:add-24-filled" class="w-4 h-4" />
              สร้างคาบใหม่
            </button>
          </div>
        </div>
      </div>

      <div class="max-w-5xl mx-auto px-6 space-y-6">
        <!-- Filters -->
        <div
          class="bg-white dark:bg-slate-800 rounded-vikinger shadow-card dark:shadow-card-dark border border-slate-200 dark:border-slate-700 p-4"
        >
          <div class="flex flex-wrap items-end gap-3">
            <div>
              <label class="block text-xs text-slate-500 dark:text-slate-400 mb-1">ตั้งแต่วันที่</label>
              <input
                v-model="filters.from"
                type="date"
                class="px-3 py-2 text-sm rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:outline-none"
              />
            </div>
            <div>
              <label class="block text-xs text-slate-500 dark:text-slate-400 mb-1">ถึงวันที่</label>
              <input
                v-model="filters.to"
                type="date"
                class="px-3 py-2 text-sm rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:outline-none"
              />
            </div>
            <div>
              <label class="block text-xs text-slate-500 dark:text-slate-400 mb-1">สถานะ</label>
              <select
                v-model="filters.status"
                class="px-3 py-2 text-sm rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:outline-none"
              >
                <option value="">ทุกสถานะ</option>
                <option value="scheduled">ตามกำหนด</option>
                <option value="completed">จบแล้ว</option>
                <option value="cancelled">ยกเลิก</option>
              </select>
            </div>
            <div class="flex-1 min-w-[10rem]">
              <label class="block text-xs text-slate-500 dark:text-slate-400 mb-1">ค้นหา</label>
              <input
                v-model="filters.q"
                type="text"
                placeholder="ชื่อคาบ หรือช่วงเวลา"
                class="w-full px-3 py-2 text-sm rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-purple-500 focus:outline-none"
              />
            </div>
            <button
              class="px-4 py-2 bg-gradient-vikinger text-white text-sm font-semibold rounded-lg shadow-vikinger hover:shadow-vikinger-lg transition-all"
              @click="loadSessions"
            >
              <Icon icon="fluent:search-24-regular" class="w-4 h-4 inline mr-1" />
              ค้นหา
            </button>
          </div>
        </div>

        <!-- Delete error (409 = มีการเช็คชื่อแล้ว) -->
        <div
          v-if="errorMessage"
          class="flex items-start gap-2 px-4 py-3 rounded-vikinger bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-700 text-orange-700 dark:text-orange-300 text-sm"
        >
          <Icon icon="fluent:warning-24-filled" class="w-5 h-5 flex-shrink-0" />
          <span class="flex-1">{{ errorMessage }}</span>
          <button class="text-orange-400 hover:text-orange-600" @click="errorMessage = ''">
            <Icon icon="fluent:dismiss-24-regular" class="w-4 h-4" />
          </button>
        </div>

        <!-- Empty state -->
        <div
          v-if="sessions.length === 0"
          class="bg-white dark:bg-slate-800 rounded-vikinger shadow-card dark:shadow-card-dark border border-slate-200 dark:border-slate-700 py-20 flex flex-col items-center gap-4"
        >
          <Icon icon="fluent:calendar-empty-24-regular" class="w-16 h-16 text-slate-300 dark:text-slate-600" />
          <p class="font-heading text-lg font-semibold text-slate-500 dark:text-slate-400">ยังไม่มีคาบเช็คชื่อ</p>
          <button
            class="px-5 py-2.5 bg-gradient-vikinger text-white font-semibold rounded-vikinger shadow-vikinger hover:shadow-vikinger-lg hover:scale-105 transition-all"
            @click="openCreateModal"
          >
            <Icon icon="fluent:add-24-filled" class="w-4 h-4 inline mr-1" />
            สร้างคาบแรก
          </button>
        </div>

        <!-- Session list -->
        <div v-else class="space-y-3">
          <div
            v-for="session in sessions"
            :key="session.id"
            class="bg-white dark:bg-slate-800 rounded-vikinger shadow-card dark:shadow-card-dark border border-slate-200 dark:border-slate-700 transition-all hover:shadow-lg"
          >
            <div class="p-5 flex flex-col sm:flex-row sm:items-center gap-4">
              <div class="flex-1 min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                  <h3 class="font-heading font-bold text-slate-900 dark:text-white truncate">
                    {{ sessionTitle(session) }}
                  </h3>
                  <span
                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                    :class="statusBadgeClass(session.status)"
                  >
                    {{ statusLabels[session.status] || session.status }}
                  </span>
                  <span
                    v-if="session.is_makeup_class"
                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300"
                  >
                    คาบชดเชย
                  </span>
                </div>

                <div class="flex flex-wrap gap-3 mt-1.5">
                  <span class="text-sm text-slate-500 dark:text-slate-400">
                    <Icon icon="fluent:calendar-24-regular" class="w-4 h-4 inline mr-1" />
                    {{ formatRange(session.start_datetime, session.end_datetime) }}
                  </span>
                  <span v-if="session.location" class="text-sm text-slate-500 dark:text-slate-400">
                    <Icon icon="fluent:location-24-regular" class="w-4 h-4 inline mr-1" />
                    {{ session.location }}
                  </span>
                  <span class="text-sm text-slate-500 dark:text-slate-400">
                    <Icon icon="fluent:people-checkmark-24-regular" class="w-4 h-4 inline mr-1" />
                    เช็คชื่อแล้ว {{ session.attendances_count || 0 }} คน
                    <span class="text-green-600 dark:text-green-400">(มา {{ session.present_count || 0 }})</span>
                  </span>
                </div>
              </div>

              <div class="flex gap-2 flex-shrink-0">
                <button
                  class="px-4 py-2 bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-200 text-sm font-semibold rounded-lg hover:bg-slate-200 dark:hover:bg-slate-600 transition-all"
                  @click="openEditModal(session)"
                >
                  <Icon icon="fluent:edit-24-regular" class="w-4 h-4 inline mr-1" />
                  แก้ไข
                </button>
                <button
                  class="px-4 py-2 bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 text-sm font-semibold rounded-lg hover:bg-red-100 dark:hover:bg-red-900/40 transition-all"
                  @click="deleteSession(session)"
                >
                  <Icon icon="fluent:delete-24-regular" class="w-4 h-4 inline mr-1" />
                  ลบ
                </button>
                <NuxtLink
                  :to="`/academies/${academyName}/admin/events/${eventId}/sessions/${session.id}`"
                  class="px-4 py-2 bg-gradient-vikinger text-white text-sm font-semibold rounded-lg shadow-vikinger hover:shadow-vikinger-lg hover:scale-105 transition-all"
                >
                  <Icon icon="fluent:checkmark-circle-24-regular" class="w-4 h-4 inline mr-1" />
                  เช็คชื่อ
                </NuxtLink>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Create / edit modal -->
    <div
      v-if="showFormModal"
      class="fixed inset-0 z-50 bg-black/50 backdrop-blur-sm flex items-center justify-center p-4"
      @click.self="showFormModal = false"
    >
      <form
        class="bg-white dark:bg-slate-800 rounded-vikinger shadow-xl border border-slate-200 dark:border-slate-700 w-full max-w-lg max-h-[90vh] overflow-y-auto"
        @submit.prevent="saveSession"
      >
        <div class="px-5 py-4 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
          <h2 class="font-heading text-lg font-bold text-slate-900 dark:text-white">
            {{ editingSession ? 'แก้ไขคาบ' : 'สร้างคาบใหม่' }}
          </h2>
          <button type="button" class="text-slate-400 hover:text-slate-600" @click="showFormModal = false">
            <Icon icon="fluent:dismiss-24-regular" class="w-5 h-5" />
          </button>
        </div>

        <div class="p-5 space-y-4">
          <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">ชื่อคาบ</label>
            <input
              v-model="form.title"
              type="text"
              placeholder="เช่น ประชุมชมรมครั้งที่ 3"
              class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-purple-500 focus:outline-none"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">ป้ายช่วงเวลา</label>
            <input
              v-model="form.slot_label"
              type="text"
              placeholder="เช่น ซุฮ์รี, คาบ 5"
              class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-purple-500 focus:outline-none"
            />
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                เริ่ม <span class="text-red-500">*</span>
              </label>
              <input
                v-model="form.start_datetime"
                type="datetime-local"
                required
                class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:outline-none"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">สิ้นสุด</label>
              <input
                v-model="form.end_datetime"
                type="datetime-local"
                class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:outline-none"
              />
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">สถานที่</label>
            <input
              v-model="form.location"
              type="text"
              placeholder="เช่น ห้องประชุม 1"
              class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-purple-500 focus:outline-none"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">สถานะ</label>
            <select
              v-model="form.status"
              class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:outline-none"
            >
              <option value="scheduled">ตามกำหนด</option>
              <option value="completed">จบแล้ว</option>
              <option value="cancelled">ยกเลิก</option>
            </select>
          </div>

          <label class="flex items-center gap-2 cursor-pointer">
            <input
              v-model="form.is_makeup_class"
              type="checkbox"
              class="w-4 h-4 rounded border-slate-300 text-purple-600 focus:ring-purple-500"
            />
            <span class="text-sm text-slate-700 dark:text-slate-300">เป็นคาบชดเชย</span>
          </label>

          <p v-if="formError" class="text-sm text-red-600 dark:text-red-400">{{ formError }}</p>
        </div>

        <div class="px-5 py-4 border-t border-slate-200 dark:border-slate-700 flex justify-end gap-2">
          <button
            type="button"
            class="px-4 py-2 text-sm font-semibold text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white transition-colors"
            @click="showFormModal = false"
          >
            ยกเลิก
          </button>
          <button
            type="submit"
            :disabled="isSaving"
            class="flex items-center gap-2 px-5 py-2 bg-gradient-vikinger text-white text-sm font-semibold rounded-lg shadow-vikinger hover:shadow-vikinger-lg transition-all disabled:opacity-50 disabled:pointer-events-none"
          >
            <Icon v-if="isSaving" icon="fluent:spinner-ios-20-regular" class="w-4 h-4 animate-spin" />
            <Icon v-else icon="fluent:save-24-regular" class="w-4 h-4" />
            บันทึก
          </button>
        </div>
      </form>
    </div>
  </div>
</template>
