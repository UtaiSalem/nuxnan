<script setup lang="ts">
import { Icon } from '@iconify/vue'
import { useApi } from '~/composables/useApi'
import StudentAttendanceTable from '~/components/learn/course/StudentAttendanceTable.vue'

interface Props {
  courseId: string | number
  groupId: string | number
  group: any
  isCourseAdmin?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  isCourseAdmin: false
})

const api = useApi()

const attendances = ref<any[]>([])
const loading = ref(true)
const error = ref<string | null>(null)

// Transform attendance data for student view (flatten authAttendanceStatus into each record)
const studentAttendances = computed(() => {
  return attendances.value.map((a: any) => ({
    id: a.id,
    date: a.date || a.start_at,
    start_at: a.start_at,
    finish_at: a.finish_at,
    late_time: a.late_time,
    description: a.description,
    status: a.authAtttendanceStatus?.status ?? null,
    time_in: a.authAtttendanceStatus?.created_at || null,
    details: a.details || []
  }))
})

// Summary stats
const totalSessions = computed(() => attendances.value.length)
const presentCount = computed(() => studentAttendances.value.filter(a => a.status === 1).length)
const lateCount = computed(() => studentAttendances.value.filter(a => a.status === 2).length)
const absentCount = computed(() => studentAttendances.value.filter(a => a.status === null || a.status === 0).length)
const leaveCount = computed(() => studentAttendances.value.filter(a => a.status === 3).length)

const fetchAttendances = async () => {
  loading.value = true
  error.value = null
  try {
    const response = await api.get(`/api/courses/${props.courseId}/groups/${props.groupId}/attendances`) as any
    attendances.value = response.attendances || response.data || []
  } catch (err: any) {
    console.error('Failed to load group attendances:', err)
    error.value = err?.data?.message || 'ไม่สามารถโหลดข้อมูลการเข้าเรียนได้'
    attendances.value = []
  } finally {
    loading.value = false
  }
}

const handleReload = () => {
  fetchAttendances()
}

onMounted(() => {
  fetchAttendances()
})
</script>

<template>
  <div class="space-y-4">
    <!-- Summary Stats (for students) -->
    <div v-if="!isCourseAdmin && !loading && attendances.length > 0" class="grid grid-cols-2 sm:grid-cols-4 gap-3">
      <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 text-center">
        <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ totalSessions }}</div>
        <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">ทั้งหมด</div>
      </div>
      <div class="bg-white dark:bg-gray-800 rounded-xl border border-green-200 dark:border-green-800 p-4 text-center">
        <div class="text-2xl font-bold text-green-600 dark:text-green-400">{{ presentCount }}</div>
        <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">มาเรียน</div>
      </div>
      <div class="bg-white dark:bg-gray-800 rounded-xl border border-amber-200 dark:border-amber-800 p-4 text-center">
        <div class="text-2xl font-bold text-amber-600 dark:text-amber-400">{{ lateCount }}</div>
        <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">มาสาย</div>
      </div>
      <div class="bg-white dark:bg-gray-800 rounded-xl border border-red-200 dark:border-red-800 p-4 text-center">
        <div class="text-2xl font-bold text-red-600 dark:text-red-400">{{ absentCount }}</div>
        <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">ขาดเรียน</div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-8 text-center">
      <Icon icon="svg-spinners:ring-resize" class="w-8 h-8 text-blue-500 mx-auto mb-3" />
      <p class="text-gray-500 dark:text-gray-400">กำลังโหลดข้อมูลการเข้าเรียน...</p>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="bg-white dark:bg-gray-800 rounded-xl border border-red-200 dark:border-red-700 p-8 text-center">
      <Icon icon="heroicons:exclamation-triangle" class="w-10 h-10 text-red-400 mx-auto mb-3" />
      <p class="text-red-600 dark:text-red-400 mb-4">{{ error }}</p>
      <button 
        @click="handleReload"
        class="min-h-[44px] sm:min-h-0 px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-lg transition-colors"
      >
        ลองใหม่
      </button>
    </div>

    <!-- Empty State -->
    <div v-else-if="attendances.length === 0" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-8 text-center">
      <Icon icon="heroicons:calendar-days" class="w-12 h-12 text-gray-300 dark:text-gray-600 mx-auto mb-3" />
      <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-1">ยังไม่มีข้อมูลการเข้าเรียน</h3>
      <p class="text-sm text-gray-500 dark:text-gray-400">ยังไม่มีการบันทึกการเข้าเรียนในกลุ่มนี้</p>
    </div>

    <!-- Attendance Table (Student View) -->
    <StudentAttendanceTable
      v-else
      :attendances="studentAttendances"
      :loading="loading"
      @reload="handleReload"
    />
  </div>
</template>
