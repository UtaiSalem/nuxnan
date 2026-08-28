<script setup lang="ts">
/**
 * Academy Admin — School Attendance Sessions
 * หน้าจัดการ sessions การเช็คชื่อนักเรียน
 */
import { Icon } from '@iconify/vue'

definePageMeta({ layout: 'main' })

const route = useRoute()
const api = useApi()
const schoolApi = useSchoolManagement()
const academyName = computed(() => route.params.name as string)

const academy = ref<any>(null)
const academyId = ref<number | null>(null)
const { isAdmin, can, fetchMyRole } = useAcademyRole(academyId)

const sessions = ref<any[]>([])
const isLoading = ref(true)
const showCreateModal = ref(false)
const isCreating = ref(false)

const filterDate = ref('')
const filterStatus = ref('')

const createForm = ref({
  date: new Date().toISOString().split('T')[0],
  title: 'เช็คชื่อการมาโรงเรียน',
  start_time: '08:00',
  late_minutes: 15,
  notes: '',
})

const stats = computed(() => ({
  total: sessions.value.length,
  open: sessions.value.filter((s) => s.status === 'open').length,
  closed: sessions.value.filter((s) => s.status === 'closed').length,
  today: sessions.value.filter((s) => s.date === new Date().toISOString().split('T')[0]).length,
}))

onMounted(async () => {
  try {
    const res: any = await api.get(`/api/academies/${academyName.value}`)
    if (res.success) {
      academy.value = res.academy
      academyId.value = res.academy.id
      await fetchMyRole()
      if (!isAdmin.value && !can('school_attendance.manage')) {
        navigateTo(`/academies/${academyName.value}`)
        return
      }
      await loadSessions()
    }
  } finally {
    isLoading.value = false
  }
})

const loadSessions = async () => {
  if (!academyId.value) return
  const params: Record<string, any> = {}
  if (filterDate.value) params.date = filterDate.value
  if (filterStatus.value) params.status = filterStatus.value
  const res: any = await schoolApi.getSchoolAttendances(academyId.value, params)
  if (res?.success) sessions.value = res.data?.data || res.data || []
}

const createSession = async () => {
  if (!academyId.value || isCreating.value) return
  isCreating.value = true
  try {
    const res: any = await schoolApi.createSchoolAttendance(academyId.value, createForm.value)
    if (res?.success) {
      showCreateModal.value = false
      navigateTo(`/academies/${academyName.value}/admin/school-attendance/${res.data.id}`)
    }
  } finally {
    isCreating.value = false
  }
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
</script>

<template>
  <div class="min-h-screen bg-slate-50 dark:bg-slate-900">
    <!-- Skeleton -->
    <div v-if="isLoading" class="space-y-6 px-0 py-6 sm:px-6">
      <div class="h-40 rounded-vikinger bg-slate-200 dark:bg-slate-700 animate-pulse" />
      <div class="grid grid-cols-4 gap-4">
        <div
          v-for="i in 4"
          :key="i"
          class="h-24 rounded-vikinger bg-slate-200 dark:bg-slate-700 animate-pulse"
        />
      </div>
      <div v-for="i in 3" :key="'sk-' + i" class="h-20 rounded-vikinger bg-slate-200 dark:bg-slate-700 animate-pulse" />
    </div>

    <div v-else class="space-y-6">
      <!-- Header gradient -->
      <div class="bg-gradient-to-br from-violet-600 via-purple-600 to-indigo-700 px-6 py-8">
        <div class="max-w-6xl mx-auto flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
          <div class="flex items-center gap-4">
            <div class="w-14 h-14 rounded-vikinger bg-white/20 flex items-center justify-center shadow-vikinger">
              <Icon icon="fluent:person-clock-24-filled" class="w-8 h-8 text-white" />
            </div>
            <div>
              <h1 class="font-heading text-2xl font-bold text-white">เช็คชื่อการมาโรงเรียน</h1>
              <p class="text-purple-200 text-sm mt-0.5">จัดการ sessions การเช็คชื่อ — {{ academy?.name }}</p>
            </div>
          </div>
          <button
            class="min-h-[44px] sm:min-h-0 flex items-center gap-2 px-5 py-2.5 bg-white text-purple-700 font-semibold rounded-vikinger hover:bg-purple-50 transition-all shadow"
            @click="showCreateModal = true"
          >
            <Icon icon="fluent:add-24-filled" class="w-5 h-5" />
            สร้างการเช็คชื่อ
          </button>
        </div>
      </div>

      <div class="max-w-6xl mx-auto px-6 space-y-6 pb-10">
        <!-- Stats row -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
          <div
            class="bg-white dark:bg-slate-800 rounded-vikinger shadow-card dark:shadow-card-dark border border-slate-200 dark:border-slate-700 p-5 flex items-center gap-4"
          >
            <div class="w-11 h-11 rounded-xl bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center">
              <Icon icon="fluent:calendar-multiple-24-regular" class="w-6 h-6 text-purple-600 dark:text-purple-400" />
            </div>
            <div>
              <p class="text-2xl font-heading font-bold text-slate-900 dark:text-white">{{ stats.total }}</p>
              <p class="text-sm text-slate-500 dark:text-slate-400">ทั้งหมด</p>
            </div>
          </div>

          <div
            class="bg-white dark:bg-slate-800 rounded-vikinger shadow-card dark:shadow-card-dark border border-slate-200 dark:border-slate-700 p-5 flex items-center gap-4"
          >
            <div class="w-11 h-11 rounded-xl bg-green-100 dark:bg-green-900/30 flex items-center justify-center">
              <Icon icon="fluent:radio-button-24-filled" class="w-6 h-6 text-green-600 dark:text-green-400" />
            </div>
            <div>
              <p class="text-2xl font-heading font-bold text-slate-900 dark:text-white">{{ stats.open }}</p>
              <p class="text-sm text-slate-500 dark:text-slate-400">กำลังเปิด</p>
            </div>
          </div>

          <div
            class="bg-white dark:bg-slate-800 rounded-vikinger shadow-card dark:shadow-card-dark border border-slate-200 dark:border-slate-700 p-5 flex items-center gap-4"
          >
            <div class="w-11 h-11 rounded-xl bg-slate-100 dark:bg-slate-700 flex items-center justify-center">
              <Icon icon="fluent:checkmark-circle-24-filled" class="w-6 h-6 text-slate-600 dark:text-slate-400" />
            </div>
            <div>
              <p class="text-2xl font-heading font-bold text-slate-900 dark:text-white">{{ stats.closed }}</p>
              <p class="text-sm text-slate-500 dark:text-slate-400">ปิดแล้ว</p>
            </div>
          </div>

          <div
            class="bg-white dark:bg-slate-800 rounded-vikinger shadow-card dark:shadow-card-dark border border-slate-200 dark:border-slate-700 p-5 flex items-center gap-4"
          >
            <div class="w-11 h-11 rounded-xl bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
              <Icon icon="fluent:calendar-today-24-regular" class="w-6 h-6 text-blue-600 dark:text-blue-400" />
            </div>
            <div>
              <p class="text-2xl font-heading font-bold text-slate-900 dark:text-white">{{ stats.today }}</p>
              <p class="text-sm text-slate-500 dark:text-slate-400">วันนี้</p>
            </div>
          </div>
        </div>

        <!-- Filter bar -->
        <div
          class="bg-white dark:bg-slate-800 rounded-vikinger shadow-card dark:shadow-card-dark border border-slate-200 dark:border-slate-700 p-4"
        >
          <div class="flex flex-col sm:flex-row gap-3">
            <input
              v-model="filterDate"
              type="date"
              class="px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:outline-none"
            />
            <select
              v-model="filterStatus"
              class="px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:outline-none"
            >
              <option value="">ทุกสถานะ</option>
              <option value="open">กำลังเปิด</option>
              <option value="closed">ปิดแล้ว</option>
            </select>
            <button
              class="min-h-[44px] sm:min-h-0 px-5 py-2 bg-gradient-vikinger text-white font-semibold rounded-lg shadow-vikinger hover:shadow-vikinger-lg hover:scale-105 transition-all"
              @click="loadSessions"
            >
              <Icon icon="fluent:search-24-regular" class="w-4 h-4 inline mr-1" />
              ค้นหา
            </button>
          </div>
        </div>

        <!-- Empty state -->
        <div
          v-if="sessions.length === 0"
          class="bg-white dark:bg-slate-800 rounded-vikinger shadow-card dark:shadow-card-dark border border-slate-200 dark:border-slate-700 py-20 flex flex-col items-center gap-4"
        >
          <Icon icon="fluent:calendar-empty-24-regular" class="w-16 h-16 text-slate-300 dark:text-slate-600" />
          <p class="font-heading text-lg font-semibold text-slate-500 dark:text-slate-400">ยังไม่มีการเช็คชื่อ</p>
          <button
            class="min-h-[44px] sm:min-h-0 px-5 py-2.5 bg-gradient-vikinger text-white font-semibold rounded-vikinger shadow-vikinger hover:shadow-vikinger-lg hover:scale-105 transition-all"
            @click="showCreateModal = true"
          >
            <Icon icon="fluent:add-24-filled" class="w-4 h-4 inline mr-1" />
            สร้างการเช็คชื่อแรก
          </button>
        </div>

        <!-- Sessions list -->
        <div v-else class="space-y-3">
          <div
            v-for="session in sessions"
            :key="session.id"
            class="bg-white dark:bg-slate-800 rounded-vikinger shadow-card dark:shadow-card-dark border transition-all hover:shadow-lg"
            :class="
              session.status === 'open'
                ? 'border-green-400 dark:border-green-500'
                : 'border-slate-200 dark:border-slate-700'
            "
          >
            <div class="p-5 flex flex-col sm:flex-row sm:items-center gap-4">
              <!-- Icon + date -->
              <div class="flex items-center gap-4 flex-1">
                <div
                  class="w-12 h-12 rounded-xl flex items-center justify-center flex-shrink-0"
                  :class="
                    session.status === 'open'
                      ? 'bg-green-100 dark:bg-green-900/30'
                      : 'bg-slate-100 dark:bg-slate-700'
                  "
                >
                  <Icon
                    icon="fluent:person-clock-24-filled"
                    class="w-6 h-6"
                    :class="
                      session.status === 'open'
                        ? 'text-green-600 dark:text-green-400'
                        : 'text-slate-500 dark:text-slate-400'
                    "
                  />
                </div>
                <div class="min-w-0">
                  <div class="flex flex-wrap items-center gap-2">
                    <h3 class="font-heading font-bold text-slate-900 dark:text-white truncate">
                      {{ session.title || 'เช็คชื่อการมาโรงเรียน' }}
                    </h3>
                    <span
                      v-if="session.status === 'open'"
                      class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300"
                    >
                      <span class="w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse" />
                      กำลังเปิด
                    </span>
                    <span
                      v-else
                      class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-400"
                    >
                      ปิดแล้ว
                    </span>
                  </div>
                  <div class="flex flex-wrap gap-3 mt-1">
                    <span class="text-sm text-slate-500 dark:text-slate-400">
                      <Icon icon="fluent:calendar-24-regular" class="w-4 h-4 inline mr-1" />
                      {{ formatDate(session.date) }}
                    </span>
                    <span v-if="session.start_time" class="text-sm text-slate-500 dark:text-slate-400">
                      <Icon icon="fluent:clock-24-regular" class="w-4 h-4 inline mr-1" />
                      {{ session.start_time }}
                    </span>
                    <span v-if="session.late_minutes" class="text-sm text-orange-500 dark:text-orange-400">
                      <Icon icon="fluent:warning-24-regular" class="w-4 h-4 inline mr-1" />
                      สาย {{ session.late_minutes }} นาที
                    </span>
                    <span v-if="session.records_count !== undefined" class="text-sm text-slate-500 dark:text-slate-400">
                      <Icon icon="fluent:people-24-regular" class="w-4 h-4 inline mr-1" />
                      {{ session.records_count }} คน
                    </span>
                  </div>
                </div>
              </div>

              <!-- Action button -->
              <button
                class="min-h-[44px] sm:min-h-0 px-4 py-2 bg-gradient-vikinger text-white text-sm font-semibold rounded-lg shadow-vikinger hover:shadow-vikinger-lg hover:scale-105 transition-all flex-shrink-0"
                @click="navigateTo(`/academies/${academyName}/admin/school-attendance/${session.id}`)"
              >
                <Icon icon="fluent:arrow-right-24-regular" class="w-4 h-4 inline mr-1" />
                จัดการ
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Create Modal -->
    <Teleport to="body">
      <div
        v-if="showCreateModal"
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
        @click.self="showCreateModal = false"
      >
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="showCreateModal = false" />
        <div
          class="relative w-full max-w-md bg-white dark:bg-slate-800 rounded-vikinger shadow-xl border border-slate-200 dark:border-slate-700 z-10"
        >
          <!-- Modal header -->
          <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 dark:border-slate-700">
            <div class="flex items-center gap-3">
              <div class="w-9 h-9 rounded-lg bg-gradient-vikinger flex items-center justify-center">
                <Icon icon="fluent:person-clock-24-filled" class="w-5 h-5 text-white" />
              </div>
              <h2 class="font-heading font-bold text-slate-900 dark:text-white">สร้างการเช็คชื่อ</h2>
            </div>
            <button
              class="min-h-[44px] sm:min-h-0 min-w-[44px] sm:min-w-0 inline-flex items-center justify-center p-2 rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 transition-all"
              aria-label="ปิด"
              @click="showCreateModal = false"
            >
              <Icon icon="fluent:dismiss-24-regular" class="w-5 h-5" />
            </button>
          </div>

          <!-- Modal body -->
          <div class="px-6 py-5 space-y-4">
            <div>
              <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">วันที่ <span class="text-red-500">*</span></label>
              <input
                v-model="createForm.date"
                type="date"
                required
                class="w-full px-4 py-2.5 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:outline-none"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">ชื่อการเช็คชื่อ</label>
              <input
                v-model="createForm.title"
                type="text"
                placeholder="เช็คชื่อการมาโรงเรียน"
                class="w-full px-4 py-2.5 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:outline-none"
              />
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">เวลาเริ่ม</label>
                <input
                  v-model="createForm.start_time"
                  type="time"
                  class="w-full px-4 py-2.5 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:outline-none"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">สายหลัง (นาที)</label>
                <input
                  v-model.number="createForm.late_minutes"
                  type="number"
                  min="1"
                  max="120"
                  class="w-full px-4 py-2.5 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:outline-none"
                />
              </div>
            </div>

            <div>
              <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">หมายเหตุ</label>
              <textarea
                v-model="createForm.notes"
                rows="3"
                placeholder="หมายเหตุเพิ่มเติม (ถ้ามี)"
                class="w-full px-4 py-2.5 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:outline-none resize-none"
              />
            </div>
          </div>

          <!-- Modal footer -->
          <div class="px-6 py-4 border-t border-slate-200 dark:border-slate-700 flex justify-end gap-3">
            <button
              class="min-h-[44px] sm:min-h-0 px-4 py-2 rounded-lg text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 transition-all font-medium"
              @click="showCreateModal = false"
            >
              ยกเลิก
            </button>
            <button
              class="min-h-[44px] sm:min-h-0 px-5 py-2 bg-gradient-vikinger text-white font-semibold rounded-lg shadow-vikinger hover:shadow-vikinger-lg hover:scale-105 transition-all disabled:opacity-50 disabled:pointer-events-none flex items-center gap-2"
              :disabled="isCreating || !createForm.date"
              @click="createSession"
            >
              <Icon v-if="isCreating" icon="fluent:spinner-ios-20-regular" class="w-4 h-4 animate-spin" />
              <Icon v-else icon="fluent:add-24-filled" class="w-4 h-4" />
              สร้าง
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>
