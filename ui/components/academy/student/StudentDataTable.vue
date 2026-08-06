<script setup lang="ts">
import { Icon } from '@iconify/vue'
import type { StudentListItem } from '~/types/studentIntake'
import { useStudentIntakeService } from '~/services/studentIntakeService'
import { useStudentEnrollmentActions } from '~/composables/useStudentEnrollmentActions'
import StudentActionMenu from '~/components/academy/enrollment/StudentActionMenu.vue'
import StudentStatusActionModal from '~/components/academy/enrollment/StudentStatusActionModal.vue'
import EnrollmentHistoryDrawer from '~/components/academy/enrollment/EnrollmentHistoryDrawer.vue'
import StudentAccountActivationModal from '~/components/academy/student/StudentAccountActivationModal.vue'
import type {
  AssignClassroomPayload,
  ClassroomOptionDTO,
  ClassroomStudentDTO,
  EnrollmentAction,
  EnrollmentActionPayload,
  StudentMenuAction,
  StudentSummaryDTO,
} from '~/types/enrollment'

const props = defineProps<{
  academyId: number | null
}>()

const { listStudents } = useStudentIntakeService()
const api = useApi()
const route = useRoute()

// Enrollment lifecycle state
const {
  execute: executeLifecycleAction,
  isLoading: lifecycleLoading,
  fieldErrors: lifecycleFieldErrors,
  getErrorMessage,
  resetErrors,
} = useStudentEnrollmentActions(toRef(props, 'academyId'))

const actionModalOpen = ref(false)
const selectedAction = ref<StudentMenuAction | null>(null)
const selectedStudent = ref<StudentSummaryDTO | null>(null)
const selectedEnrollment = ref<ClassroomStudentDTO | null>(null)
const assignError = ref('')
const availableClassrooms = ref<ClassroomOptionDTO[]>([])
const historyDrawerOpen = ref(false)
const historyStudent = ref<StudentSummaryDTO | null>(null)
const activationModalOpen = ref(false)
const activationStudent = ref<StudentListItem | null>(null)

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
  accountStatus: '',
  gradeLevel: '',
  classroomId: '' as '' | 'none' | number,
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

const gradeLevelOptions = computed(() => {
  const grades = [...new Set(availableClassrooms.value.map(c => c.grade_level).filter((g): g is string => !!g))]
  grades.sort((a, b) => a.localeCompare(b, 'th', { numeric: true }))
  return grades
})

const classroomOptions = computed(() => {
  const list = lazyParams.value.gradeLevel
    ? availableClassrooms.value.filter(c => c.grade_level === lazyParams.value.gradeLevel)
    : availableClassrooms.value
  return [...list].sort((a, b) => a.display_name.localeCompare(b.display_name, 'th', { numeric: true }))
})

const academicYearNames = computed(() => {
  const entries = availableClassrooms.value
    .filter(classroom => !!classroom.academic_year_name)
    .map(classroom => [classroom.id, classroom.academic_year_name as string] as const)
  return new Map<number, string>(entries)
})

const hasActiveFilters = computed(() => Boolean(
  lazyParams.value.search
  || searchInput.value
  || lazyParams.value.status
  || lazyParams.value.accountStatus
  || lazyParams.value.gradeLevel
  || lazyParams.value.classroomId,
))

const onGradeChange = () => {
  const selected = lazyParams.value.classroomId
  if (typeof selected === 'number' && !classroomOptions.value.some(c => c.id === selected)) {
    lazyParams.value.classroomId = ''
  }
  onFilterChange()
}

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
      grade_level: lazyParams.value.gradeLevel || undefined,
      classroom_id: typeof lazyParams.value.classroomId === 'number' ? lazyParams.value.classroomId : undefined,
      unassigned: lazyParams.value.classroomId === 'none' ? 1 : undefined,
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

const resetFilters = () => {
  if (searchTimeout) {
    clearTimeout(searchTimeout)
    searchTimeout = null
  }
  searchInput.value = ''
  lazyParams.value.search = ''
  lazyParams.value.status = ''
  lazyParams.value.accountStatus = ''
  lazyParams.value.gradeLevel = ''
  lazyParams.value.classroomId = ''
  lazyParams.value.page = 1
  lazyParams.value.first = 0
  fetchData()
}

const getActiveEnrollment = (student: StudentListItem) =>
  student.classroom_enrollments?.find(cs => cs.status === 'active')

const getClassroomName = (student: StudentListItem): string => {
  const active = getActiveEnrollment(student)
  if (!active?.classroom) return ''
  const c = active.classroom
  if (c.grade_level && c.section) return `${c.grade_level}/${c.section}`
  return c.name || '-'
}

const getStudentNumber = (student: StudentListItem) => getActiveEnrollment(student)?.student_number ?? null

const getAcademicYearName = (student: StudentListItem) => {
  const active = getActiveEnrollment(student)
  return active ? academicYearNames.value.get(active.classroom_id) ?? null : null
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

// Enrollment lifecycle handlers
const toStudentSummary = (s: StudentListItem): StudentSummaryDTO => ({
  id: s.id,
  student_id: s.student_id || '',
  academy_id: props.academyId || 0,
  first_name_th: s.first_name_th,
  last_name_th: s.last_name_th,
  nickname: null,
  status: s.status,
  class_level: null,
  class_section: null,
})

const toCurrentEnrollment = (s: StudentListItem): ClassroomStudentDTO | null => {
  const active = s.classroom_enrollments?.find(cs => cs.status === 'active')
  if (!active) return null
  return {
    id: active.id,
    student_id: s.id,
    classroom_id: active.classroom_id,
    academy_id: props.academyId || 0,
    academic_year_id: active.academic_year_id ?? null,
    student_number: null,
    status: active.status,
    status_text: null,
    enrolled_at: null,
    left_at: null,
    leave_reason: null,
    rollover_batch_id: null,
    classroom: active.classroom ? {
      id: active.classroom.id,
      display_name: active.classroom.name || `${active.classroom.grade_level}/${active.classroom.section}`,
      grade_level: active.classroom.grade_level ?? null,
      section: active.classroom.section ?? null,
    } : undefined,
  }
}

const fetchClassrooms = async () => {
  if (!props.academyId) return
  try {
    const res: any = await api.get(`/api/academies/${props.academyId}/classrooms?all_years=1`)
    if (res.success && res.classrooms) {
      availableClassrooms.value = res.classrooms.map((c: any) => ({
        id: c.id,
        display_name: c.name || `${c.grade_level}/${c.section}`,
        grade_level: c.grade_level,
        section: c.section,
        academic_year_id: c.academic_year_id ?? null,
        academic_year_name: c.academic_year?.name ?? null,
      }))
    }
  } catch (e) {
    console.error('Failed to fetch classrooms', e)
  }
}

const onActionSelect = (student: StudentListItem, action: StudentMenuAction) => {
  selectedStudent.value = toStudentSummary(student)
  selectedEnrollment.value = toCurrentEnrollment(student)
  selectedAction.value = action
  assignError.value = ''
  resetErrors()
  actionModalOpen.value = true
  if (!availableClassrooms.value.length) fetchClassrooms()
}

const onActionSubmit = async (
  payload: EnrollmentActionPayload<EnrollmentAction> | AssignClassroomPayload,
) => {
  const action = selectedAction.value
  const student = selectedStudent.value
  if (!action || !student) return
  try {
    if (action === 'assign') {
      await api.post(`/api/academies/${props.academyId}/classrooms/${(payload as AssignClassroomPayload).to_classroom_id}/students`, {
        student_ids: [student.id],
      })
    } else {
      await executeLifecycleAction(action, student.id, payload as EnrollmentActionPayload<EnrollmentAction>)
    }
    actionModalOpen.value = false
    assignError.value = ''
    fetchData()
  } catch (error: any) {
    if (action === 'assign') {
      assignError.value = error?.data?.message ?? error?.message ?? 'ไม่สามารถลงห้องเรียนได้'
    } else {
      // errors are in lifecycleFieldErrors
    }
  }
}

const onViewProfile = (student: StudentListItem) => {
  navigateTo(`/academies/${route.params.name}/students/${student.id}/profile`)
}

const onViewHistory = (student: StudentListItem) => {
  historyStudent.value = toStudentSummary(student)
  historyDrawerOpen.value = true
}

const onActivateAccount = (student: StudentListItem) => {
  activationStudent.value = student
  activationModalOpen.value = true
}

const filterUnassigned = () => {
  lazyParams.value.gradeLevel = ''
  lazyParams.value.classroomId = 'none'
  onFilterChange()
}

defineExpose({ filterUnassigned })

watch(() => props.academyId, (id) => {
  if (id) {
    fetchData()
    fetchClassrooms()
  }
}, { immediate: true })
</script>

<template>
  <div class="space-y-4">
    <!-- Filters -->
    <div class="flex flex-col sm:flex-row flex-wrap gap-3">
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
        v-model="lazyParams.gradeLevel"
        @change="onGradeChange"
        class="px-3 py-2.5 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm"
      >
        <option value="">ทุกระดับชั้น</option>
        <option v-for="grade in gradeLevelOptions" :key="grade" :value="grade">{{ grade }}</option>
      </select>
      <select
        v-model="lazyParams.classroomId"
        @change="onFilterChange"
        class="px-3 py-2.5 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm"
      >
        <option value="">ทุกห้องเรียน</option>
        <option value="none">ยังไม่มีห้องเรียน</option>
        <option v-for="c in classroomOptions" :key="c.id" :value="c.id">{{ c.display_name }}</option>
      </select>
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
      <button
        v-if="hasActiveFilters"
        type="button"
        @click="resetFilters"
        class="px-3 py-2.5 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
      >
        ล้างตัวกรอง
      </button>
    </div>

    <!-- Table -->
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-700 text-sm">
          <thead>
            <tr class="bg-gray-100 dark:bg-gray-900/50">
              <th class="px-6 py-4 text-left text-xs font-semibold tracking-wide text-gray-500 dark:text-gray-400">รหัส</th>
              <th class="px-6 py-4 text-left text-xs font-semibold tracking-wide text-gray-500 dark:text-gray-400 cursor-pointer select-none" @click="onSort({ sortField: 'first_name_th', sortOrder: lazyParams.sortField === 'first_name_th' && lazyParams.sortOrder === 1 ? -1 : 1 })">
                <span class="flex items-center gap-1">
                  ชื่อ-สกุล
                  <Icon v-if="lazyParams.sortField === 'first_name_th'" :icon="lazyParams.sortOrder === 1 ? 'fluent:arrow-up-24-regular' : 'fluent:arrow-down-24-regular'" class="w-4 h-4" />
                </span>
              </th>
              <th class="px-6 py-4 text-left text-xs font-semibold tracking-wide text-gray-500 dark:text-gray-400">ห้องเรียน</th>
              <th class="px-6 py-4 text-right text-xs font-semibold tracking-wide text-gray-500 dark:text-gray-400">เลขที่</th>
              <th class="px-6 py-4 text-left text-xs font-semibold tracking-wide text-gray-500 dark:text-gray-400">ปีการศึกษา</th>
              <th class="px-6 py-4 text-left text-xs font-semibold tracking-wide text-gray-500 dark:text-gray-400">สถานะ</th>
              <th class="px-6 py-4 text-left text-xs font-semibold tracking-wide text-gray-500 dark:text-gray-400">บัญชี</th>
              <th class="sticky right-0 z-10 border-l border-gray-100 dark:border-gray-700 bg-gray-100 dark:bg-gray-900/50 px-6 py-4 text-right text-xs font-semibold tracking-wide text-gray-500 dark:text-gray-400">จัดการ</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
            <template v-if="loading">
              <tr v-for="i in lazyParams.rows" :key="i">
                <td colspan="8" class="px-6 py-4 whitespace-nowrap">
                  <div class="h-5 bg-gray-200 dark:bg-gray-700 rounded animate-pulse"></div>
                </td>
              </tr>
            </template>
            <tr v-else-if="students.length === 0">
              <td colspan="8" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                <Icon icon="fluent:people-24-regular" class="w-10 h-10 mx-auto mb-2 text-gray-300 dark:text-gray-600" />
                <template v-if="hasActiveFilters">
                  <p>ไม่พบนักเรียนที่ตรงกับตัวกรอง</p>
                  <button
                    type="button"
                    @click="resetFilters"
                    class="mt-3 inline-flex items-center rounded-lg border border-gray-200 dark:border-gray-700 px-3 py-2 text-sm text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                  >
                    ล้างตัวกรองทั้งหมด
                  </button>
                </template>
                <template v-else>
                  <p>ไม่พบข้อมูลนักเรียน</p>
                  <a
                    :href="`/academies/${route.params.name}/admin/students/intake`"
                    class="mt-3 inline-flex items-center rounded-lg border border-gray-200 dark:border-gray-700 px-3 py-2 text-sm text-primary-600 dark:text-primary-400 hover:bg-primary-50 dark:hover:bg-primary-900/20 transition-colors"
                  >
                    รับนักเรียนใหม่
                  </a>
                </template>
              </td>
            </tr>
            <tr
              v-else
              v-for="student in students"
              :key="student.id"
              class="group hover:bg-gray-50 dark:hover:bg-gray-700/50 cursor-pointer transition-colors"
            >
              <td class="px-6 py-4 whitespace-nowrap font-mono text-xs text-gray-700 dark:text-gray-300">{{ student.student_id }}</td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="font-medium text-gray-900 dark:text-white">
                  {{ student.title_prefix_th }}{{ student.first_name_th }} {{ student.last_name_th }}
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-gray-600 dark:text-gray-400">
                <span
                  v-if="getClassroomName(student)"
                  class="inline-block whitespace-nowrap px-2 py-1 text-xs text-center font-medium leading-none rounded bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-200"
                >
                  {{ getClassroomName(student) }}
                </span>
                <span
                  v-else
                  class="inline-block whitespace-nowrap px-2 py-1 text-xs text-center font-medium leading-none rounded bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400"
                >
                  ยังไม่มีห้อง
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-right tabular-nums text-gray-700 dark:text-gray-300">
                {{ getStudentNumber(student) ?? '—' }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-gray-600 dark:text-gray-400">
                {{ getAcademicYearName(student) ?? '—' }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span :class="['inline-block whitespace-nowrap px-2 py-1 text-xs text-center font-bold leading-none rounded', getStatusClass(student.status)]">
                  {{ getStatusLabel(student.status) }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span :class="['text-xs font-medium', getAccountBadge(student.account_status).class]">
                  {{ getAccountBadge(student.account_status).label }}
                </span>
              </td>
              <td class="sticky right-0 z-10 border-l border-gray-100 dark:border-gray-700 bg-white dark:bg-gray-800 group-hover:bg-gray-50 dark:group-hover:bg-gray-700/50 px-6 py-4 whitespace-nowrap text-right">
                <div class="flex items-center justify-end gap-1 list-user-action">
                  <button
                    @click.stop="onViewProfile(student)"
                    class="p-1.5 rounded-md text-zinc-400 hover:text-primary-600 hover:bg-primary-50 dark:hover:bg-primary-900/20 transition"
                    title="ดูโปรไฟล์นักเรียน"
                  >
                    <Icon icon="fluent:person-card-profile-24-regular" class="w-4 h-4" />
                  </button>
                  <button
                    v-if="student.account_status !== 'active'"
                    @click.stop="onActivateAccount(student)"
                    class="p-1.5 rounded-md text-zinc-400 hover:text-primary-600 hover:bg-primary-50 dark:hover:bg-primary-900/20 transition"
                    title="สร้างลิงก์เปิดบัญชี"
                  >
                    <Icon icon="fluent:person-key-24-regular" class="w-4 h-4" />
                  </button>
                  <button
                    @click.stop="onViewHistory(student)"
                    class="p-1.5 rounded-md text-zinc-400 hover:text-zinc-600 hover:bg-zinc-100 dark:hover:bg-zinc-800 dark:hover:text-zinc-200 transition"
                    title="ดูประวัติการลงห้อง"
                  >
                    <Icon icon="mdi:history" class="w-4 h-4" />
                  </button>
                  <StudentActionMenu
                    :student="toStudentSummary(student)"
                    :enrollment="toCurrentEnrollment(student)"
                    @select="onActionSelect(student, $event)"
                  />
                </div>
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

    <!-- Enrollment Lifecycle Modal -->
    <StudentStatusActionModal
      :open="actionModalOpen"
      :action="selectedAction"
      :student="selectedStudent"
      :enrollment="selectedEnrollment"
      :available-classrooms="availableClassrooms"
      :is-loading="lifecycleLoading"
      :field-errors="lifecycleFieldErrors"
      :assign-error="assignError"
      @update:open="actionModalOpen = $event"
      @submit="onActionSubmit"
    />

    <!-- Enrollment History Drawer -->
    <EnrollmentHistoryDrawer
      :open="historyDrawerOpen"
      :academy-id="academyId"
      :student="historyStudent"
      @update:open="historyDrawerOpen = $event"
    />

    <StudentAccountActivationModal
      v-model="activationModalOpen"
      :academy-id="academyId"
      :student="activationStudent"
      @invited="fetchData"
    />
  </div>
</template>
