<script setup lang="ts">
/**
 * Classroom Detail - Student Management
 * หน้าจัดการนักเรียนในห้องเรียน
 */
import { Icon } from '@iconify/vue'
import StudentActionMenu from '~/components/academy/enrollment/StudentActionMenu.vue'
import StudentStatusActionModal from '~/components/academy/enrollment/StudentStatusActionModal.vue'
import EnrollmentHistoryDrawer from '~/components/academy/enrollment/EnrollmentHistoryDrawer.vue'
import StudentStatusBadge from '~/components/academy/enrollment/StudentStatusBadge.vue'
import type {
  ClassroomOptionDTO,
  ClassroomStudentDTO,
  AssignClassroomPayload,
  EnrollmentAction,
  EnrollmentActionPayload,
  StudentMenuAction,
  StudentSummaryDTO,
} from '~/types/enrollment'
import { ENROLLMENT_STATUS } from '~/types/enrollment'

definePageMeta({
  layout: 'main'
})

const route = useRoute()
const api = useApi()
const toast = useToast()
const academyName = computed(() => route.params.name as string)
const classroomId = computed(() => route.params.id as string)

// State
const academy = ref<any>(null)
const academyId = ref<number | null>(null)
const classroom = ref<any>(null)
const isLoading = ref(true)
const isSaving = ref(false)

// Students
const students = ref<ClassroomStudentDTO[]>([])
const inactiveStudents = ref<ClassroomStudentDTO[]>([])
const activeTab = ref<'active' | 'inactive'>('active')
const isStudentsLoading = ref(false)
const isInactiveLoading = ref(false)
const availableStudents = ref<any[]>([])
const searchQuery = ref('')

// Modal (add students)
const showAddModal = ref(false)
const selectedStudents = ref<number[]>([])
const searchAvailable = ref('')

// Phase 4.C — lifecycle actions state
const availableClassrooms = ref<ClassroomOptionDTO[]>([])
const currentAction = ref<StudentMenuAction | null>(null)
const currentStudent = ref<StudentSummaryDTO | null>(null)
const currentEnrollment = ref<ClassroomStudentDTO | null>(null)
const historyOpen = ref(false)
const historyStudent = ref<StudentSummaryDTO | null>(null)

const showActionModal = computed({
  get: () => currentAction.value !== null,
  set: (open: boolean) => {
    if (!open) {
      currentAction.value = null
      currentStudent.value = null
      currentEnrollment.value = null
    }
  },
})

const {
  execute: runEnrollmentAction,
  isLoading: isActionLoading,
  fieldErrors: actionFieldErrors,
  resetErrors: resetActionErrors,
  getErrorMessage: getActionErrorMessage,
} = useStudentEnrollmentActions(academyId)

const filteredStudents = computed(() => {
  const source = activeTab.value === 'active' ? students.value : inactiveStudents.value
  if (!searchQuery.value) return source
  
  const query = searchQuery.value.toLowerCase()
  return source.filter((s: any) => 
    s.student?.student_id?.toLowerCase().includes(query) ||
    s.student?.first_name_th?.toLowerCase().includes(query) ||
    s.student?.last_name_th?.toLowerCase().includes(query)
  )
})

const isInactiveTab = computed(() => activeTab.value === 'inactive')

const visibleStudentCount = computed(() => {
  return isInactiveTab.value ? inactiveStudents.value.length : students.value.length
})

const studentCountLabel = computed(() => {
  return isInactiveTab.value ? 'รายการออกจากห้อง' : 'นักเรียน'
})

const searchPlaceholder = computed(() => {
  return isInactiveTab.value
    ? 'ค้นหานักเรียนที่ออกจากห้อง...'
    : 'ค้นหานักเรียน...'
})

const emptyStateTitle = computed(() => {
  return isInactiveTab.value ? 'ยังไม่มีรายการออกจากห้อง' : 'ยังไม่มีนักเรียน'
})

const emptyStateDescription = computed(() => {
  return isInactiveTab.value
    ? 'เมื่อนักเรียนย้ายออก จบการศึกษา หรือเปลี่ยนสถานะ รายการจะปรากฏที่แท็บนี้'
    : 'เพิ่มนักเรียนเข้าห้องเรียน'
})

const filteredAvailable = computed(() => {
  if (!searchAvailable.value) return availableStudents.value
  
  const query = searchAvailable.value.toLowerCase()
  return availableStudents.value.filter((s: any) => 
    s.student_id?.toLowerCase().includes(query) ||
    s.first_name_th?.toLowerCase().includes(query) ||
    s.last_name_th?.toLowerCase().includes(query)
  )
})

const { isAdmin, fetchMyRole } = useAcademyRole(academyId)

onMounted(async () => {
  try {
    const response: any = await api.get(`/api/academies/${academyName.value}`)
    if (response.success) {
      academy.value = response.academy
      academyId.value = response.academy.id
      await fetchMyRole()
      
      if (!isAdmin.value) {
        navigateTo(`/academies/${academyName.value}`)
        return
      }

      await fetchData()
    }
  } catch (err) {
    console.error('Failed to load:', err)
  } finally {
    isLoading.value = false
  }
})

const fetchData = async () => {
  await Promise.all([fetchClassroom(), fetchAvailableClassrooms(), fetchActiveStudents()])
}

const fetchAvailableClassrooms = async () => {
  try {
    const res: any = await api.get(`/api/academies/${academyId.value}/classrooms`)
    const list = res?.data ?? res?.classrooms ?? []
    availableClassrooms.value = list.map((c: any): ClassroomOptionDTO => ({
      id: c.id,
      display_name: c.name,
      grade_level: c.grade_level ?? null,
      section: c.section ?? null,
      academic_year_id: c.academic_year_id ?? null,
      academic_year_name: c.academic_year_info?.name ?? c.academic_year ?? null,
    }))
  } catch (err) {
    console.error('Failed to load available classrooms:', err)
    availableClassrooms.value = []
  }
}

const fetchClassroom = async () => {
  try {
    const res: any = await api.get(`/api/academies/${academyId.value}/classrooms/${classroomId.value}`)
    if (res.success) {
      classroom.value = res.classroom
    }
  } catch (err) {
    console.error('Failed to fetch classroom:', err)
  }
}

const fetchEnrollments = async (statuses: string[]) => {
  const res: any = await api.get(
    `/api/academies/${academyId.value}/classrooms/${classroomId.value}/enrollments`,
    {
      query: {
        status: statuses,
      },
    },
  )

  return (res?.data ?? []) as ClassroomStudentDTO[]
}

const fetchActiveStudents = async () => {
  isStudentsLoading.value = true
  try {
    students.value = await fetchEnrollments([ENROLLMENT_STATUS.ACTIVE])
  } catch (err) {
    console.error('Failed to fetch active students:', err)
    students.value = []
  } finally {
    isStudentsLoading.value = false
  }
}

const inactiveStatuses = [
  ENROLLMENT_STATUS.TRANSFERRED,
  ENROLLMENT_STATUS.PROMOTED,
  ENROLLMENT_STATUS.GRADUATED,
  ENROLLMENT_STATUS.DROPPED,
  ENROLLMENT_STATUS.REPEATING,
  ENROLLMENT_STATUS.SUPERSEDED,
] as const

const fetchInactiveStudents = async () => {
  if (isInactiveLoading.value) return

  isInactiveLoading.value = true
  try {
    const rows = await fetchEnrollments([...inactiveStatuses])
    inactiveStudents.value = rows.slice(0, 200)
  } catch (err) {
    console.error('Failed to fetch inactive students:', err)
    inactiveStudents.value = []
  } finally {
    isInactiveLoading.value = false
  }
}

const fetchAvailableStudents = async () => {
  try {
    // Get all students from academy that are not in this classroom
    const res: any = await api.get(`/api/academies/${academyId.value}/students?not_in_classroom=${classroomId.value}`)
    if (res.success) {
      availableStudents.value = res.students || []
    }
  } catch (err) {
    console.error('Failed to fetch available students:', err)
  }
}

const openAddModal = async () => {
  selectedStudents.value = []
  searchAvailable.value = ''
  await fetchAvailableStudents()
  showAddModal.value = true
}

const addStudents = async () => {
  if (selectedStudents.value.length === 0) {
    alert('กรุณาเลือกนักเรียน')
    return
  }

  isSaving.value = true
  try {
    await api.post(`/api/academies/${academyId.value}/classrooms/${classroomId.value}/students`, {
      student_ids: selectedStudents.value,
    })
    
    showAddModal.value = false
    await fetchActiveStudents()
  } catch (err: any) {
    console.error('Failed to add students:', err)
    alert(err.message || 'เกิดข้อผิดพลาด')
  } finally {
    isSaving.value = false
  }
}

const removeStudent = async (studentId: number) => {
  if (!confirm('ต้องการลบนักเรียนออกจากห้องเรียนหรือไม่?')) return
  
  try {
    await api.delete(`/api/academies/${academyId.value}/classrooms/${classroomId.value}/students/${studentId}`)
    await fetchActiveStudents()
    if (activeTab.value === 'inactive' || inactiveStudents.value.length > 0) {
      await fetchInactiveStudents()
    }
  } catch (err) {
    console.error('Failed to remove student:', err)
    alert('ไม่สามารถลบได้')
  }
}

const updateStudentNumber = async (classroomStudent: any, number: number) => {
  try {
    await api.patch(`/api/academies/${academyId.value}/classrooms/${classroomId.value}/students/${classroomStudent.student_id}/number`, {
      student_number: number,
    })
  } catch (err) {
    console.error('Failed to update student number:', err)
  }
}

const toggleSelectStudent = (studentId: number) => {
  const index = selectedStudents.value.indexOf(studentId)
  if (index === -1) {
    selectedStudents.value.push(studentId)
  } else {
    selectedStudents.value.splice(index, 1)
  }
}

const selectAllFiltered = () => {
  selectedStudents.value = filteredAvailable.value.map((s: any) => s.id)
}

const deselectAll = () => {
  selectedStudents.value = []
}

// === Phase 4.C: per-student lifecycle handlers ===

const buildEnrollmentDTO = (cs: any): ClassroomStudentDTO => ({
  id: cs.id,
  student_id: cs.student_id,
  classroom_id: cs.classroom_id ?? Number(classroomId.value),
  academy_id: cs.academy_id ?? (academyId.value as number),
  academic_year_id: cs.academic_year_id ?? classroom.value?.academic_year_id ?? null,
  student_number: cs.student_number ?? null,
  status: cs.status,
  status_text: cs.status_text ?? null,
  enrolled_at: cs.enrolled_at ?? null,
  left_at: cs.left_at ?? null,
  leave_reason: cs.leave_reason ?? null,
  rollover_batch_id: cs.rollover_batch_id ?? null,
  classroom: {
    id: cs.classroom_id ?? Number(classroomId.value),
    display_name: classroom.value?.name ?? '',
    grade_level: classroom.value?.grade_level ?? null,
    section: classroom.value?.section ?? null,
  },
})

const buildStudentDTO = (cs: any): StudentSummaryDTO => ({
  id: cs.student?.id ?? cs.student_id,
  student_id: cs.student?.student_id ?? '',
  academy_id: cs.academy_id ?? (academyId.value as number),
  first_name_th: cs.student?.first_name_th ?? null,
  last_name_th: cs.student?.last_name_th ?? null,
  nickname: cs.student?.nickname ?? null,
  status: cs.student?.status ?? null,
  class_level: cs.student?.class_level ?? null,
  class_section: cs.student?.class_section ?? null,
})

const onActionSelect = (cs: any, action: StudentMenuAction) => {
  // assign จะไม่ถูก emit ในหน้านี้ เพราะทุกรายการมี enrollment
  if (action === 'assign') return
  resetActionErrors()
  currentStudent.value = buildStudentDTO(cs)
  currentEnrollment.value = buildEnrollmentDTO(cs)
  currentAction.value = action
}

const onActionSubmit = async (
  payload: EnrollmentActionPayload<EnrollmentAction> | AssignClassroomPayload,
) => {
  const action = currentAction.value
  if (!action || !currentStudent.value || action === 'assign') return
  try {
    await runEnrollmentAction(
      action,
      currentStudent.value.id,
      payload as EnrollmentActionPayload<EnrollmentAction>,
    )
    toast.success('อัปเดทสถานะนักเรียนเรียบร้อย', 'สำเร็จ', 3000)
    showActionModal.value = false
    await fetchActiveStudents()
    if (activeTab.value === 'inactive' || inactiveStudents.value.length > 0) {
      await fetchInactiveStudents()
    }
  } catch (err) {
    toast.error(getActionErrorMessage('ไม่สามารถดำเนินการได้'), 'ผิดพลาด', 5000)
  }
}

const openHistory = (cs: any) => {
  historyStudent.value = buildStudentDTO(cs)
  historyOpen.value = true
}

watch(activeTab, async (tab) => {
  if (tab === 'inactive' && inactiveStudents.value.length === 0) {
    await fetchInactiveStudents()
  }
})
</script>

<template>
  <div class="px-4 sm:px-0">
    <div v-if="isLoading" class="flex items-center justify-center py-20">
      <div class="animate-spin rounded-full h-10 w-10 border-4 border-primary-500 border-t-transparent"></div>
    </div>

    <div v-else class="space-y-6">
      <!-- Header -->
      <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
          <div class="flex items-center gap-3 mb-2">
            <NuxtLink :to="`/academies/${academyName}/admin/gradebook/classrooms`" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
              <Icon icon="fluent:arrow-left-24-filled" class="w-5 h-5" />
            </NuxtLink>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
              <Icon icon="fluent:building-24-filled" class="w-7 h-7 text-purple-500" />
              {{ classroom?.name }}
            </h1>
          </div>
          <p class="text-gray-600 dark:text-gray-400">
            ระดับชั้น {{ classroom?.grade_level }} | {{ visibleStudentCount }} {{ studentCountLabel }}
          </p>
        </div>

        <div v-if="!isInactiveTab" class="flex items-center gap-3">
          <button
            @click="openAddModal"
            class="min-h-[44px] sm:min-h-0 px-4 py-2 bg-primary-500 hover:bg-primary-600 text-white rounded-lg font-medium transition-colors flex items-center gap-2"
          >
            <Icon icon="fluent:person-add-24-filled" class="w-5 h-5" />
            เพิ่มนักเรียน
          </button>
        </div>
      </div>

      <!-- Classroom Info -->
      <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
          <div>
            <div class="text-sm text-gray-600 dark:text-gray-400">ปีการศึกษา</div>
            <div class="text-lg font-semibold text-gray-900 dark:text-white">{{ classroom?.academic_year_info?.name || classroom?.academic_year }}</div>
          </div>
          <div>
            <div class="text-sm text-gray-600 dark:text-gray-400">ครูประจำชั้น</div>
            <div class="text-lg font-semibold text-gray-900 dark:text-white">
              {{ classroom?.homeroom_teacher?.name || 'ยังไม่กำหนด' }}
            </div>
          </div>
          <div>
            <div class="text-sm text-gray-600 dark:text-gray-400">ห้องเรียน</div>
            <div class="text-lg font-semibold text-gray-900 dark:text-white">
              {{ classroom?.room_location || '-' }}
            </div>
          </div>
          <div>
            <div class="text-sm text-gray-600 dark:text-gray-400">จำนวนนักเรียน</div>
            <div class="text-lg font-semibold text-gray-900 dark:text-white">
              {{ students.length }} / {{ classroom?.capacity || '-' }}
            </div>
          </div>
        </div>
      </div>

      <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
        <div class="flex flex-wrap gap-2">
          <button class="min-h-[44px] sm:min-h-0"
            @click="activeTab = 'active'"
            :class="[
              'inline-flex items-center gap-2 rounded-full px-4 py-2 text-sm font-medium transition-colors',
              activeTab === 'active'
                ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300'
                : 'bg-gray-100 text-gray-600 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600',
            ]"
          >
            <span>กำลังศึกษา</span>
            <span class="rounded-full bg-white/80 px-2 py-0.5 text-xs dark:bg-black/20">{{ students.length }}</span>
          </button>
          <button class="min-h-[44px] sm:min-h-0"
            @click="activeTab = 'inactive'"
            :class="[
              'inline-flex items-center gap-2 rounded-full px-4 py-2 text-sm font-medium transition-colors',
              activeTab === 'inactive'
                ? 'bg-violet-100 text-violet-700 dark:bg-violet-900/30 dark:text-violet-300'
                : 'bg-gray-100 text-gray-600 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600',
            ]"
          >
            <span>ออกจากห้อง</span>
            <span class="rounded-full bg-white/80 px-2 py-0.5 text-xs dark:bg-black/20">{{ inactiveStudents.length }}</span>
          </button>
        </div>
      </div>

      <!-- Search -->
      <div class="relative">
        <Icon icon="fluent:search-24-regular" class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
        <input
          v-model="searchQuery"
          type="text"
          :placeholder="searchPlaceholder"
          class="w-full pl-10 pr-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent"
        />
      </div>

      <!-- Students List -->
      <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div v-if="isStudentsLoading || (activeTab === 'inactive' && isInactiveLoading)" class="p-12 text-center">
          <Icon icon="fluent:spinner-ios-20-filled" class="w-8 h-8 animate-spin text-primary-500 mx-auto mb-4" />
          <p class="text-sm text-gray-600 dark:text-gray-400">กำลังโหลดข้อมูลนักเรียน...</p>
        </div>

        <div v-else-if="filteredStudents.length === 0" class="p-12 text-center">
          <Icon icon="fluent:people-24-regular" class="w-16 h-16 text-gray-300 dark:text-gray-600 mx-auto mb-4" />
          <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">{{ emptyStateTitle }}</h3>
          <p class="text-gray-600 dark:text-gray-400 mb-6">{{ emptyStateDescription }}</p>
          <button
            v-if="activeTab === 'active'"
            @click="openAddModal"
            class="inline-flex items-center gap-2 px-6 py-3 bg-primary-500 hover:bg-primary-600 text-white rounded-lg font-medium transition-colors"
          >
            <Icon icon="fluent:person-add-24-filled" class="w-5 h-5" />
            เพิ่มนักเรียน
          </button>
        </div>

        <div v-else class="overflow-x-auto">
          <table class="w-full">
            <thead class="bg-gray-50 dark:bg-gray-700">
              <tr>
                <th v-if="activeTab === 'active'" class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                  เลขที่
                </th>
                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                  รหัสนักเรียน
                </th>
                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                  ชื่อ-นามสกุล
                </th>
                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                  สถานะ
                </th>
                <th v-if="activeTab === 'inactive'" class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                  วันที่ออก
                </th>
                <th v-if="activeTab === 'inactive'" class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                  เหตุผล
                </th>
                <th class="px-6 py-4 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                  จัดการ
                </th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
              <tr v-for="(cs, index) in filteredStudents" :key="cs.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                <td v-if="activeTab === 'active'" class="px-6 py-4 whitespace-nowrap">
                  <input
                    type="number"
                    :value="cs.student_number || index + 1"
                    @change="updateStudentNumber(cs, Number(($event.target as HTMLInputElement).value))"
                    class="w-16 px-2 py-1 rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-center focus:ring-2 focus:ring-primary-500"
                  />
                </td>
                <td
                  class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white cursor-pointer"
                  @click="openHistory(cs)"
                >
                  {{ cs.student?.student_id }}
                </td>
                <td
                  class="px-6 py-4 whitespace-nowrap cursor-pointer"
                  @click="openHistory(cs)"
                >
                  <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-primary-100 dark:bg-primary-900/50 flex items-center justify-center">
                      <span class="text-sm font-medium text-primary-600 dark:text-primary-400">
                        {{ cs.student?.first_name_th?.[0] }}
                      </span>
                    </div>
                    <div>
                      <div class="font-medium text-gray-900 dark:text-white">
                        {{ cs.student?.prefix }}{{ cs.student?.first_name_th }} {{ cs.student?.last_name_th }}
                      </div>
                      <div class="text-sm text-gray-500 dark:text-gray-400">
                        {{ cs.student?.first_name_en }} {{ cs.student?.last_name_en }}
                      </div>
                    </div>
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <StudentStatusBadge :status="cs.status" :status-text="cs.status_text" />
                </td>
                <td v-if="activeTab === 'inactive'" class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">
                  {{ cs.left_at || '-' }}
                </td>
                <td v-if="activeTab === 'inactive'" class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">
                  {{ cs.leave_reason || '-' }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right">
                  <div class="inline-flex items-center gap-1">
                    <button
                      @click="openHistory(cs)"
                      class="min-h-[44px] sm:min-h-0 min-w-[44px] sm:min-w-0 inline-flex items-center justify-center p-1.5 rounded-md text-zinc-500 hover:bg-zinc-100 hover:text-zinc-700 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-200 transition"
                      aria-label="ดูประวัติการลงห้อง"
                    >
                      <Icon icon="mdi:history" class="w-5 h-5" />
                    </button>
                    <StudentActionMenu v-if="activeTab === 'active'"
                      :student="buildStudentDTO(cs)"
                      :enrollment="buildEnrollmentDTO(cs)"
                      @select="(action) => onActionSelect(cs, action)"
                    />
                    <button
                      v-if="activeTab === 'active'"
                      @click="removeStudent(cs.student_id)"
                      class="min-h-[44px] sm:min-h-0 min-w-[44px] sm:min-w-0 inline-flex items-center justify-center p-1.5 rounded-md text-rose-600 hover:bg-rose-50 dark:text-rose-400 dark:hover:bg-rose-900/30 transition"
                      aria-label="ลบนักเรียนออกจากห้อง (legacy)"
                    >
                      <Icon icon="fluent:person-delete-24-regular" class="w-5 h-5" />
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Add Students Modal -->
    <Teleport to="body">
      <div v-if="showAddModal" class="fixed inset-0 z-50 flex items-center justify-center">
        <div class="absolute inset-0 bg-black/50" @click="showAddModal = false"></div>
        
        <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-xl w-full max-w-2xl mx-4 max-h-[90vh] flex flex-col">
          <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white">เพิ่มนักเรียน</h2>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
              เลือกนักเรียนที่ต้องการเพิ่มเข้าห้อง {{ classroom?.name }}
            </p>
          </div>
          
          <div class="p-6 flex-1 overflow-y-auto">
            <div class="mb-4">
              <div class="relative">
                <Icon icon="fluent:search-24-regular" class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
                <input
                  v-model="searchAvailable"
                  type="text"
                  placeholder="ค้นหานักเรียน..."
                  class="w-full pl-10 pr-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                />
              </div>
            </div>

            <div class="flex items-center justify-between mb-3">
              <span class="text-sm text-gray-600 dark:text-gray-400">
                เลือกแล้ว {{ selectedStudents.length }} คน
              </span>
              <div class="flex items-center gap-2">
                <button @click="selectAllFiltered" class="text-sm text-primary-600 hover:underline">
                  เลือกทั้งหมด
                </button>
                <button @click="deselectAll" class="text-sm text-gray-500 hover:underline">
                  ยกเลิกทั้งหมด
                </button>
              </div>
            </div>

            <div v-if="filteredAvailable.length === 0" class="text-center py-8 text-gray-500 dark:text-gray-400">
              ไม่พบนักเรียน
            </div>

            <div v-else class="space-y-2 max-h-80 overflow-y-auto">
              <div
                v-for="student in filteredAvailable"
                :key="student.id"
                @click="toggleSelectStudent(student.id)"
                :class="[
                  'p-4 rounded-lg border cursor-pointer transition-all',
                  selectedStudents.includes(student.id)
                    ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20'
                    : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600'
                ]"
              >
                <div class="flex items-center gap-3">
                  <div :class="[
                    'w-5 h-5 rounded border-2 flex items-center justify-center transition-colors',
                    selectedStudents.includes(student.id)
                      ? 'border-primary-500 bg-primary-500'
                      : 'border-gray-300 dark:border-gray-600'
                  ]">
                    <Icon v-if="selectedStudents.includes(student.id)" icon="fluent:checkmark-12-filled" class="w-3 h-3 text-white" />
                  </div>
                  <div class="flex-1">
                    <div class="font-medium text-gray-900 dark:text-white">
                      {{ student.prefix }}{{ student.first_name_th }} {{ student.last_name_th }}
                    </div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">
                      รหัส: {{ student.student_id }}
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          
          <div class="p-6 border-t border-gray-200 dark:border-gray-700 flex items-center gap-3">
            <button
              @click="showAddModal = false"
              class="min-h-[44px] sm:min-h-0 flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
            >
              ยกเลิก
            </button>
            <button
              @click="addStudents"
              :disabled="isSaving || selectedStudents.length === 0"
              class="min-h-[44px] sm:min-h-0 flex-1 px-4 py-2 bg-primary-500 hover:bg-primary-600 disabled:bg-gray-400 text-white rounded-lg font-medium transition-colors flex items-center justify-center gap-2"
            >
              <Icon v-if="isSaving" icon="fluent:spinner-ios-20-filled" class="w-5 h-5 animate-spin" />
              เพิ่มนักเรียน {{ selectedStudents.length > 0 ? `(${selectedStudents.length})` : '' }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- Phase 4.C — lifecycle modal + history drawer -->
    <StudentStatusActionModal
      :open="showActionModal"
      :action="currentAction"
      :student="currentStudent"
      :enrollment="currentEnrollment"
      :available-classrooms="availableClassrooms"
      :is-loading="isActionLoading"
      :field-errors="actionFieldErrors"
      @update:open="showActionModal = $event"
      @submit="onActionSubmit"
    />

    <EnrollmentHistoryDrawer
      :open="historyOpen"
      :academy-id="academyId"
      :student="historyStudent"
      @update:open="historyOpen = $event"
    />
  </div>
</template>
