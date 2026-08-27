<script setup lang="ts">
/**
 * Admin / Parent — Student Attendance History
 * ประวัติการเช็คชื่อรายนักเรียน
 */
import { Icon } from '@iconify/vue'

definePageMeta({ layout: 'main', middleware: ['auth'] })

const route = useRoute()
const schoolApi = useSchoolManagement()
const academyName = computed(() => route.params.name as string)
const studentId = computed(() => Number(route.params.studentId))
const academyId = ref<number | null>(null)

const student = ref<any>(null)
const records = ref<any[]>([])
const summary = ref<Record<string, number>>({})
const isLoading = ref(true)
const dateFrom = ref('')
const dateTo = ref('')

onMounted(async () => {
  const res: any = await useApi().get(`/api/academies/${academyName.value}`)
  if (res?.success) {
    academyId.value = res.academy.id
    await loadHistory()
  }
  isLoading.value = false
})

const loadHistory = async () => {
  if (!academyId.value) return
  isLoading.value = true
  try {
    const res: any = await schoolApi.getStudentAttendanceHistory(academyId.value, studentId.value, {
      date_from: dateFrom.value || undefined,
      date_to: dateTo.value || undefined,
    })
    if (res?.success) {
      records.value = res.data?.data || res.data || []
      summary.value = res.summary || {}
      if (records.value.length && !student.value) {
        student.value = records.value[0].student
      }
    }
  } finally {
    isLoading.value = false
  }
}

const attendanceRate = computed(() => {
  const total = Object.values(summary.value).reduce((a, b) => a + b, 0)
  const present = (summary.value.present || 0) + (summary.value.late || 0)
  return total > 0 ? Math.round((present / total) * 100) : 0
})

const rateColor = computed(() => {
  if (attendanceRate.value >= 80) return 'text-green-600 dark:text-green-400'
  if (attendanceRate.value >= 60) return 'text-orange-500 dark:text-orange-400'
  return 'text-red-500 dark:text-red-400'
})

const statusConfig: Record<string, { label: string; badge: string; icon: string }> = {
  present: {
    label: 'มา',
    badge: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300',
    icon: 'fluent:checkmark-circle-24-filled',
  },
  late: {
    label: 'สาย',
    badge: 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-300',
    icon: 'fluent:clock-alarm-24-filled',
  },
  leave: {
    label: 'ลา',
    badge: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300',
    icon: 'fluent:document-24-regular',
  },
  absent: {
    label: 'ขาด',
    badge: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300',
    icon: 'fluent:dismiss-circle-24-filled',
  },
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

const formatTime = (dt: string) => {
  if (!dt) return '-'
  return new Date(dt).toLocaleTimeString('th-TH', { hour: '2-digit', minute: '2-digit' })
}
</script>

<template>
  <div class="max-w-3xl mx-auto px-4 py-6 space-y-6">
    <!-- Back -->
    <NuxtLink
      :to="`/academies/${academyName}/admin/members`"
      class="inline-flex items-center gap-1.5 text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 text-sm transition-colors"
    >
      <Icon icon="fluent:arrow-left-24-regular" class="w-4 h-4" />
      กลับ
    </NuxtLink>

    <!-- Skeleton -->
    <div v-if="isLoading" class="space-y-4">
      <div class="h-24 rounded-vikinger bg-slate-200 dark:bg-slate-700 animate-pulse" />
      <div class="grid grid-cols-4 gap-3">
        <div v-for="i in 4" :key="i" class="h-20 rounded-vikinger bg-slate-200 dark:bg-slate-700 animate-pulse" />
      </div>
      <div v-for="i in 5" :key="'sk-' + i" class="h-16 rounded-vikinger bg-slate-200 dark:bg-slate-700 animate-pulse" />
    </div>

    <template v-else>
      <!-- Student profile header -->
      <div
        class="bg-white dark:bg-slate-800 rounded-vikinger shadow-card dark:shadow-card-dark border border-slate-200 dark:border-slate-700 p-5 flex items-center gap-4"
      >
        <img
          v-if="student?.profile_photo_path"
          :src="student.profile_photo_path"
          :alt="student?.name"
          class="w-16 h-16 rounded-full object-cover flex-shrink-0 ring-2 ring-purple-200 dark:ring-purple-700"
        />
        <div
          v-else
          class="w-16 h-16 rounded-full bg-gradient-vikinger flex items-center justify-center flex-shrink-0"
        >
          <Icon icon="fluent:person-24-filled" class="w-8 h-8 text-white" />
        </div>

        <div class="flex-1 min-w-0">
          <h1 class="font-heading text-xl font-bold text-slate-900 dark:text-white truncate">
            {{ student?.name || 'นักเรียน' }}
          </h1>
          <div class="flex flex-wrap gap-2 mt-1">
            <span
              v-if="student?.student_code"
              class="text-sm text-slate-500 dark:text-slate-400"
            >
              รหัส: {{ student.student_code }}
            </span>
            <span
              v-if="student?.classroom?.name"
              class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300"
            >
              {{ student.classroom.name }}
            </span>
          </div>
        </div>
      </div>

      <!-- Summary + progress -->
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <div
          class="bg-white dark:bg-slate-800 rounded-vikinger shadow-card dark:shadow-card-dark border border-slate-200 dark:border-slate-700 p-4 text-center"
        >
          <p class="text-2xl font-heading font-bold text-green-600 dark:text-green-400">{{ summary.present || 0 }}</p>
          <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">มา</p>
        </div>
        <div
          class="bg-white dark:bg-slate-800 rounded-vikinger shadow-card dark:shadow-card-dark border border-slate-200 dark:border-slate-700 p-4 text-center"
        >
          <p class="text-2xl font-heading font-bold text-orange-500 dark:text-orange-400">{{ summary.late || 0 }}</p>
          <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">สาย</p>
        </div>
        <div
          class="bg-white dark:bg-slate-800 rounded-vikinger shadow-card dark:shadow-card-dark border border-slate-200 dark:border-slate-700 p-4 text-center"
        >
          <p class="text-2xl font-heading font-bold text-blue-500 dark:text-blue-400">{{ summary.leave || 0 }}</p>
          <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">ลา</p>
        </div>
        <div
          class="bg-white dark:bg-slate-800 rounded-vikinger shadow-card dark:shadow-card-dark border border-slate-200 dark:border-slate-700 p-4 text-center"
        >
          <p class="text-2xl font-heading font-bold text-red-500 dark:text-red-400">{{ summary.absent || 0 }}</p>
          <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">ขาด</p>
        </div>
      </div>

      <!-- Attendance rate progress bar -->
      <div
        class="bg-white dark:bg-slate-800 rounded-vikinger shadow-card dark:shadow-card-dark border border-slate-200 dark:border-slate-700 p-5"
      >
        <div class="flex items-center justify-between mb-2">
          <span class="text-sm font-medium text-slate-700 dark:text-slate-300">อัตราการมาโรงเรียน</span>
          <span class="font-heading font-bold text-lg" :class="rateColor">{{ attendanceRate }}%</span>
        </div>
        <div class="w-full h-3 bg-slate-100 dark:bg-slate-700 rounded-full overflow-hidden">
          <div
            class="h-full rounded-full bg-gradient-vikinger transition-all duration-700"
            :style="{ width: attendanceRate + '%' }"
          />
        </div>
        <div class="flex justify-between mt-1.5 text-xs text-slate-400 dark:text-slate-500">
          <span>0%</span>
          <span
            class="font-medium"
            :class="attendanceRate >= 80 ? 'text-green-500' : attendanceRate >= 60 ? 'text-orange-400' : 'text-red-400'"
          >
            {{ attendanceRate >= 80 ? 'ดีมาก' : attendanceRate >= 60 ? 'ปานกลาง' : 'ต้องปรับปรุง' }}
          </span>
          <span>100%</span>
        </div>
      </div>

      <!-- Filter bar -->
      <div
        class="bg-white dark:bg-slate-800 rounded-vikinger shadow-card dark:shadow-card-dark border border-slate-200 dark:border-slate-700 p-4"
      >
        <div class="flex flex-col sm:flex-row gap-3">
          <div class="flex-1 flex flex-col sm:flex-row gap-3">
            <div class="flex-1">
              <label class="block text-xs text-slate-500 dark:text-slate-400 mb-1">ตั้งแต่</label>
              <input
                v-model="dateFrom"
                type="date"
                class="w-full px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:outline-none text-sm"
              />
            </div>
            <div class="flex-1">
              <label class="block text-xs text-slate-500 dark:text-slate-400 mb-1">ถึง</label>
              <input
                v-model="dateTo"
                type="date"
                class="w-full px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:outline-none text-sm"
              />
            </div>
          </div>
          <div class="flex items-end">
            <button
              class="min-h-[44px] sm:min-h-0 px-5 py-2 bg-gradient-vikinger text-white font-semibold rounded-lg shadow-vikinger hover:shadow-vikinger-lg hover:scale-105 transition-all text-sm"
              @click="loadHistory"
            >
              <Icon icon="fluent:search-24-regular" class="w-4 h-4 inline mr-1" />
              ค้นหา
            </button>
          </div>
        </div>
      </div>

      <!-- Empty state -->
      <div
        v-if="records.length === 0"
        class="bg-white dark:bg-slate-800 rounded-vikinger shadow-card dark:shadow-card-dark border border-slate-200 dark:border-slate-700 py-20 flex flex-col items-center gap-4"
      >
        <Icon icon="fluent:calendar-empty-24-regular" class="w-14 h-14 text-slate-300 dark:text-slate-600" />
        <p class="font-heading text-lg font-semibold text-slate-500 dark:text-slate-400">
          ยังไม่มีประวัติการเช็คชื่อ
        </p>
      </div>

      <!-- Timeline list -->
      <div
        v-else
        class="bg-white dark:bg-slate-800 rounded-vikinger shadow-card dark:shadow-card-dark border border-slate-200 dark:border-slate-700 divide-y divide-slate-100 dark:divide-slate-700"
      >
        <div
          v-for="rec in records"
          :key="rec.id"
          class="px-5 py-4 flex items-start gap-4 hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors"
        >
          <!-- Status icon circle -->
          <div
            class="w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5"
            :class="
              rec.status === 'present'
                ? 'bg-green-100 dark:bg-green-900/30'
                : rec.status === 'late'
                  ? 'bg-orange-100 dark:bg-orange-900/30'
                  : rec.status === 'leave'
                    ? 'bg-blue-100 dark:bg-blue-900/30'
                    : 'bg-red-100 dark:bg-red-900/30'
            "
          >
            <Icon
              :icon="statusConfig[rec.status]?.icon || 'fluent:calendar-24-regular'"
              class="w-5 h-5"
              :class="
                rec.status === 'present'
                  ? 'text-green-600 dark:text-green-400'
                  : rec.status === 'late'
                    ? 'text-orange-500 dark:text-orange-400'
                    : rec.status === 'leave'
                      ? 'text-blue-500 dark:text-blue-400'
                      : 'text-red-500 dark:text-red-400'
              "
            />
          </div>

          <!-- Content -->
          <div class="flex-1 min-w-0">
            <div class="flex flex-wrap items-center gap-2">
              <p class="font-heading font-semibold text-slate-900 dark:text-white text-sm">
                {{ formatDate(rec.session?.date || rec.date) }}
              </p>
              <span
                class="px-2 py-0.5 rounded-full text-xs font-medium"
                :class="statusConfig[rec.status]?.badge || 'bg-slate-100 text-slate-600'"
              >
                {{ statusConfig[rec.status]?.label || rec.status }}
              </span>
            </div>

            <p v-if="rec.session?.title" class="text-sm text-slate-500 dark:text-slate-400 mt-0.5 truncate">
              {{ rec.session.title }}
            </p>

            <div class="flex flex-wrap gap-3 mt-1">
              <span
                v-if="rec.checked_in_at || rec.created_at"
                class="text-xs text-slate-400 dark:text-slate-500"
              >
                <Icon icon="fluent:clock-24-regular" class="w-3.5 h-3.5 inline mr-0.5" />
                {{ formatTime(rec.checked_in_at || rec.created_at) }}
              </span>
              <span v-if="rec.check_in_method" class="text-xs text-slate-400 dark:text-slate-500">
                <Icon
                  :icon="rec.check_in_method === 'qr' ? 'fluent:qr-code-24-regular' : 'fluent:pen-24-regular'"
                  class="w-3.5 h-3.5 inline mr-0.5"
                />
                {{ rec.check_in_method === 'qr' ? 'QR Code' : 'Manual' }}
              </span>
            </div>

            <p v-if="rec.remarks" class="text-xs text-slate-500 dark:text-slate-400 mt-1 italic">
              "{{ rec.remarks }}"
            </p>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>
