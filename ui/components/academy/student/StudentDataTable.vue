<script setup lang="ts">
import { Icon } from '@iconify/vue'
import type { StudentListItem } from '~/types/studentIntake'
import { useStudentIntakeService } from '~/services/studentIntakeService'

const props = defineProps<{
  academyId: number | null
}>()

const { listStudents } = useStudentIntakeService()

const students = ref<StudentListItem[]>([])
const totalRecords = ref(0)
const loading = ref(false)

const lazyParams = ref({
  first: 0,
  rows: 15,
  page: 1,
  sortField: 'created_at',
  sortOrder: -1,
  search: '',
  status: '',
  accountStatus: ''
})

const searchInput = ref('')
let searchTimeout: ReturnType<typeof setTimeout> | null = null

const statusOptions = [
  { label: 'ทั้งหมด', value: '' },
  { label: 'กำลังเรียน', value: 'active' },
  { label: 'จบการศึกษา', value: 'graduated' },
  { label: 'ลาออก', value: 'inactive' },
  { label: 'ย้ายสถานศึกษา', value: 'transferred' },
]

const accountStatusOptions = [
  { label: 'ทั้งหมด', value: '' },
  { label: 'รอเปิดบัญชี', value: 'pending_activation' },
  { label: 'เปิดแล้ว', value: 'active' },
]

const fetchData = async () => {
  if (!props.academyId) return
  loading.value = true
  try {
    const res = await listStudents(String(props.academyId), {
      page: lazyParams.value.page,
      per_page: lazyParams.value.rows,
      search: lazyParams.value.search || undefined,
      status: lazyParams.value.status || undefined,
      account_status: lazyParams.value.accountStatus || undefined,
      sort_field: lazyParams.value.sortField,
      sort_order: lazyParams.value.sortOrder === 1 ? 'asc' : 'desc',
    })
    students.value = res.data
    totalRecords.value = res.total
  } catch (error) {
    console.error('Failed to fetch students', error)
  } finally {
    loading.value = false
  }
}

const onPage = (event: any) => {
  lazyParams.value.first = event.first
  lazyParams.value.rows = event.rows
  lazyParams.value.page = event.page + 1
  fetchData()
}

const onSort = (event: any) => {
  lazyParams.value.sortField = event.sortField
  lazyParams.value.sortOrder = event.sortOrder
  fetchData()
}

const onSearch = () => {
  if (searchTimeout) clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    lazyParams.value.search = searchInput.value
    lazyParams.value.page = 1
    lazyParams.value.first = 0
    fetchData()
  }, 400)
}

const onFilterChange = () => {
  lazyParams.value.page = 1
  lazyParams.value.first = 0
  fetchData()
}

const getClassroomName = (student: StudentListItem): string => {
  const active = student.classroom_students?.find(cs => cs.status === 'active')
  if (!active?.classroom) return '-'
  const c = active.classroom
  if (c.grade_level && c.section) return `${c.grade_level}/${c.section}`
  return c.name || '-'
}

const getStatusLabel = (status: string) => {
  const map: Record<string, string> = {
    active: 'กำลังเรียน',
    graduated: 'จบการศึกษา',
    inactive: 'ลาออก',
    transferred: 'ย้ายสถานศึกษา',
  }
  return map[status] || status
}

const getStatusClass = (status: string) => {
  const map: Record<string, string> = {
    active: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
    graduated: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
    inactive: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
    transferred: 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400',
  }
  return map[status] || 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'
}

const getAccountBadge = (status: string | null) => {
  if (status === 'active') return { label: 'เปิดแล้ว', class: 'text-green-600 dark:text-green-400' }
  if (status === 'pending_activation') return { label: 'รอเปิด', class: 'text-orange-600 dark:text-orange-400' }
  return { label: 'ยังไม่มี', class: 'text-gray-400 dark:text-gray-500' }
}

watch(() => props.academyId, (id) => {
  if (id) fetchData()
}, { immediate: true })
</script>

<template>
  <div class="space-y-4">
    <!-- Filters -->
    <div class="flex flex-col sm:flex-row gap-3">
      <div class="relative flex-1">
        <Icon icon="fluent:search-24-regular" class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
        <input
          v-model="searchInput"
          @input="onSearch"
          type="text"
          placeholder="ค้นหาชื่อ, รหัส, เลขประชาชน..."
          class="w-full pl-10 pr-4 py-2.5 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm focus:ring-2 focus:ring-primary-500 focus:border-transparent"
        />
      </div>
      <select
        v-model="lazyParams.status"
        @change="onFilterChange"
        class="px-3 py-2.5 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm"
      >
        <option v-for="opt in statusOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
      </select>
      <select
        v-model="lazyParams.accountStatus"
        @change="onFilterChange"
        class="px-3 py-2.5 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm"
      >
        <option v-for="opt in accountStatusOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
      </select>
    </div>

    <!-- Table -->
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="bg-gray-50 dark:bg-gray-900/50">
            <tr>
              <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">รหัส</th>
              <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400 cursor-pointer select-none" @click="onSort({ sortField: 'first_name_th', sortOrder: lazyParams.sortField === 'first_name_th' && lazyParams.sortOrder === 1 ? -1 : 1 })">
                <span class="flex items-center gap-1">
                  ชื่อ-สกุล
                  <Icon v-if="lazyParams.sortField === 'first_name_th'" :icon="lazyParams.sortOrder === 1 ? 'fluent:arrow-up-24-regular' : 'fluent:arrow-down-24-regular'" class="w-4 h-4" />
                </span>
              </th>
              <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">ห้องเรียน</th>
              <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">สถานะ</th>
              <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">บัญชี</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
            <tr v-if="loading" v-for="i in lazyParams.rows" :key="i">
              <td colspan="5" class="px-4 py-3">
                <div class="h-5 bg-gray-200 dark:bg-gray-700 rounded animate-pulse"></div>
              </td>
            </tr>
            <tr v-else-if="students.length === 0">
              <td colspan="5" class="px-4 py-12 text-center text-gray-500 dark:text-gray-400">
                <Icon icon="fluent:people-24-regular" class="w-10 h-10 mx-auto mb-2 text-gray-300 dark:text-gray-600" />
                <p>ไม่พบข้อมูลนักเรียน</p>
              </td>
            </tr>
            <tr
              v-else
              v-for="student in students"
              :key="student.id"
              class="hover:bg-gray-50 dark:hover:bg-gray-700/50 cursor-pointer transition-colors"
            >
              <td class="px-4 py-3 font-mono text-xs text-gray-700 dark:text-gray-300">{{ student.student_id }}</td>
              <td class="px-4 py-3">
                <div class="font-medium text-gray-900 dark:text-white">
                  {{ student.title_prefix_th }}{{ student.first_name_th }} {{ student.last_name_th }}
                </div>
              </td>
              <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ getClassroomName(student) }}</td>
              <td class="px-4 py-3">
                <span :class="['px-2 py-0.5 rounded-full text-xs font-medium', getStatusClass(student.status)]">
                  {{ getStatusLabel(student.status) }}
                </span>
              </td>
              <td class="px-4 py-3">
                <span :class="['text-xs font-medium', getAccountBadge(student.account_status).class]">
                  {{ getAccountBadge(student.account_status).label }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="totalRecords > 0" class="flex items-center justify-between px-4 py-3 border-t border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900/30">
        <p class="text-sm text-gray-500 dark:text-gray-400">
          แสดง {{ lazyParams.first + 1 }}-{{ Math.min(lazyParams.first + lazyParams.rows, totalRecords) }} จาก {{ totalRecords }} รายการ
        </p>
        <div class="flex gap-1">
          <button
            :disabled="lazyParams.page <= 1"
            @click="onPage({ first: (lazyParams.page - 2) * lazyParams.rows, rows: lazyParams.rows, page: lazyParams.page - 2 })"
            class="px-3 py-1.5 rounded-md text-sm border border-gray-200 dark:border-gray-700 disabled:opacity-40 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
          >
            ก่อนหน้า
          </button>
          <button
            :disabled="lazyParams.page >= Math.ceil(totalRecords / lazyParams.rows)"
            @click="onPage({ first: lazyParams.page * lazyParams.rows, rows: lazyParams.rows, page: lazyParams.page })"
            class="px-3 py-1.5 rounded-md text-sm border border-gray-200 dark:border-gray-700 disabled:opacity-40 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
          >
            ถัดไป
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
