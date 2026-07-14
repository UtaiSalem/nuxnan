<script setup lang="ts">
import { Icon } from '@iconify/vue'
import type { StudentCardRequest, StudentCardRequestStatus, ClassroomSummary } from '~/types/studentCardRequest'
import type { PaginationMeta, SubmitCardRequestPayload } from '~/composables/useStudentCardRequests'

definePageMeta({ layout: 'main' })

const academyId = inject<Ref<number | null>>('academyId', ref(null))
const academyName = inject<ComputedRef<string>>('academyName', computed(() => String(useRoute().params.name)))
const api = useStudentCardRequests(academyId)
const apiClient = useApi()

// List state
const rows = ref<StudentCardRequest[]>([])
const meta = ref<PaginationMeta | null>(null)
const counts = ref<Record<string, number>>({})
const loading = ref(true)
const error = ref('')
const notice = ref('')

// Filters
const status = ref<StudentCardRequestStatus | '' | 'active'>('active')
const academicYearId = ref<number | null>(null)
const gradeLevel = ref<string>('')
const classroomId = ref<number | null>(null)
const search = ref('')
const page = ref(1)

// Filter options
const academicYears = ref<{ id: number; name: string; is_current?: boolean }[]>([])
const gradeLevels = ref<string[]>([])
const allClassrooms = ref<any[]>([])
const yearClassrooms = computed(() =>
  academicYearId.value ? allClassrooms.value.filter(c => Number(c.academic_year_id) === academicYearId.value) : allClassrooms.value,
)
const filteredClassrooms = computed(() =>
  gradeLevel.value ? yearClassrooms.value.filter(c => String(c.grade_level) === gradeLevel.value) : yearClassrooms.value,
)
const gradeLevelsForYear = computed(() => {
  const set = new Set<string>()
  yearClassrooms.value.forEach(c => c.grade_level != null && set.add(String(c.grade_level)))
  const arr = Array.from(set).sort()
  return arr.length ? arr : gradeLevels.value
})

// Create modal state
const createOpen = ref(false)
const createGradeLevel = ref<string>('')
const createSearch = ref('')
const createClassroomId = ref<number | null>(null)
const createClassroomObj = ref<ClassroomSummary | null>(null)
const createStudents = ref<any[]>([])
const createLoading = ref(false)
const createSelectedIds = ref<Set<number>>(new Set())
const singleModalOpen = ref(false)
const bulkModalOpen = ref(false)
const selectedStudent = ref<any>(null)
const submittingBulk = ref(false)

const eligibleStudents = computed(() => createStudents.value.filter(s => !s.active_card_request))
const selectedStudents = computed(() => eligibleStudents.value.filter(s => createSelectedIds.value.has(s.student_id)))
const allSelected = computed(() => eligibleStudents.value.length > 0 && selectedStudents.value.length === eligibleStudents.value.length)

const loadOptions = async () => {
  if (!academyId.value) return
  try {
    const [gl, cl, ay, cur] = await Promise.all([
      apiClient.get(`/api/academies/${academyId.value}/classrooms/grade-levels`),
      apiClient.get(`/api/academies/${academyId.value}/classrooms?all_years=1`),
      apiClient.get(`/api/academies/${academyId.value}/academic-years`),
      apiClient.get(`/api/academies/${academyId.value}/academic-years/current`),
    ])
    gradeLevels.value = ((gl as any)?.gradeLevels || []).map((v: any) => String(v))
    allClassrooms.value = (cl as any)?.data || (cl as any)?.classrooms || []
    academicYears.value = ((ay as any)?.academicYears || []).map((y: any) => ({ id: y.id, name: y.name, is_current: y.is_current }))
    if (academicYearId.value == null) {
      const currentId = (cur as any)?.academicYear?.id
      academicYearId.value = currentId
        || academicYears.value.find(y => y.is_current)?.id
        || academicYears.value[0]?.id
        || null
    }
  }
  catch { /* ignore */ }
}

const loadList = async () => {
  if (!academyId.value) return
  loading.value = true
  try {
    const params = new URLSearchParams()
    if (status.value) params.set('status', status.value)
    if (academicYearId.value) params.set('academic_year_id', String(academicYearId.value))
    if (gradeLevel.value) params.set('grade_level', gradeLevel.value)
    if (classroomId.value) params.set('classroom_id', String(classroomId.value))
    if (search.value) params.set('search', search.value)
    params.set('page', String(page.value))
    const [list, count] = await Promise.all([api.list(params.toString()), api.counts()])
    rows.value = list.data || []
    meta.value = list.meta || null
    counts.value = count.data || {}
  }
  catch (e: any) {
    error.value = e?.data?.message || 'โหลดคำร้องไม่สำเร็จ'
  }
  finally { loading.value = false }
}

watch([status, academicYearId, gradeLevel, classroomId], () => { page.value = 1 })
watch(academicYearId, () => { gradeLevel.value = ''; classroomId.value = null })
watch(gradeLevel, () => { classroomId.value = null })
watch(academyId, () => { loadOptions(); loadList() }, { immediate: true })
watch([status, academicYearId, gradeLevel, classroomId, page], loadList)

let searchTimer: ReturnType<typeof setTimeout> | null = null
watch(search, () => {
  if (searchTimer) clearTimeout(searchTimer)
  searchTimer = setTimeout(() => { page.value = 1; loadList() }, 400)
})

const clearFilters = () => {
  status.value = ''
  gradeLevel.value = ''
  classroomId.value = null
  search.value = ''
  page.value = 1
  const currentId = academicYears.value.find(y => y.is_current)?.id || academicYears.value[0]?.id || null
  academicYearId.value = currentId
}

const createYearClassrooms = computed(() =>
  academicYearId.value
    ? allClassrooms.value.filter(c => Number(c.academic_year_id) === academicYearId.value)
    : allClassrooms.value,
)
const createGradeLevels = computed(() => {
  const set = new Set<string>()
  createYearClassrooms.value.forEach(c => c.grade_level != null && set.add(String(c.grade_level)))
  return Array.from(set).sort()
})
const createClassroomOptions = computed(() => {
  const q = createSearch.value.trim().toLowerCase()
  const list = createYearClassrooms.value.filter((c) => {
    if (createGradeLevel.value && String(c.grade_level) !== createGradeLevel.value) return false
    if (!q) return true
    const name = String(c.name || `${c.grade_level}/${c.section}`).toLowerCase()
    const teacher = String(c.homeroom_teacher?.name || '').toLowerCase()
    return name.includes(q) || teacher.includes(q)
  })
  return [...list].sort((a, b) => String(a.grade_level).localeCompare(String(b.grade_level)) || String(a.section).localeCompare(String(b.section)))
})
watch(createGradeLevel, () => { createClassroomId.value = null })
watch(createSearch, () => { createClassroomId.value = null })
const openCreate = () => {
  createOpen.value = true
  error.value = ''
}
const closeCreate = () => {
  createOpen.value = false
  createClassroomId.value = null
  createClassroomObj.value = null
  createStudents.value = []
  createSelectedIds.value = new Set()
}
const loadCreateStudents = async () => {
  if (!createClassroomId.value) return
  createLoading.value = true
  createSelectedIds.value = new Set()
  try {
    const response = await api.classroomStudents(createClassroomId.value)
    createClassroomObj.value = response.data.classroom
    createStudents.value = (response.data.students || []).map((row: any) => ({
      ...row.student,
      student_id: row.student_id,
      name: row.student?.full_name_th || `${row.student?.first_name_th || ''} ${row.student?.last_name_th || ''}`,
    }))
  }
  finally { createLoading.value = false }
}
const toggleSelect = (student: any) => {
  const next = new Set(createSelectedIds.value)
  if (next.has(student.student_id)) next.delete(student.student_id)
  else next.add(student.student_id)
  createSelectedIds.value = next
}
const toggleSelectAll = () => {
  createSelectedIds.value = allSelected.value ? new Set() : new Set(eligibleStudents.value.map(s => s.student_id))
}
const selectAllWithoutCard = () => {
  createSelectedIds.value = new Set(eligibleStudents.value.filter(s => !s.student_card).map(s => s.student_id))
}
const openSingle = (student: any) => { selectedStudent.value = student; singleModalOpen.value = true }
const submitSingle = async (payload: SubmitCardRequestPayload) => {
  error.value = ''
  try {
    await api.submit(payload)
    singleModalOpen.value = false
    notice.value = 'ส่งคำร้องสำเร็จ'
    await Promise.all([loadCreateStudents(), loadList()])
  }
  catch (e: any) { error.value = e?.data?.message || 'ส่งคำร้องไม่สำเร็จ' }
}
const submitBulk = async (payloads: SubmitCardRequestPayload[]) => {
  error.value = ''
  notice.value = ''
  submittingBulk.value = true
  try {
    const { data: results } = await api.submitBulk(payloads)
    bulkModalOpen.value = false
    const failed = results.filter(r => !r.success)
    const nameOf = (id: number) => createStudents.value.find(s => s.student_id === id)?.name || `รหัส ${id}`
    notice.value = `ส่งคำร้องสำเร็จ ${results.length - failed.length} จาก ${results.length} คน`
    if (failed.length) error.value = 'ส่งไม่สำเร็จ: ' + failed.map(f => `${nameOf(f.student_id)} (${f.message || 'ไม่ทราบสาเหตุ'})`).join(', ')
    await Promise.all([loadCreateStudents(), loadList()])
  }
  catch (e: any) {
    error.value = e?.data?.message || 'ส่งคำร้องไม่สำเร็จ'
  }
  finally { submittingBulk.value = false }
}

// Detail modal
const detailOpen = ref(false)
const detailItem = ref<StudentCardRequest | null>(null)
const detailLoading = ref(false)
const detailError = ref('')
const detailRejection = ref('')
const detailNotes = ref('')
const detailActing = ref(false)
const detailClassroomChanged = computed(() => Boolean(detailItem.value?.classroom && (
  detailItem.value.classroom.grade_level !== detailItem.value.grade_level
  || detailItem.value.classroom.section !== detailItem.value.section
)))
const openDetail = async (id: number) => {
  detailOpen.value = true
  detailItem.value = null
  detailError.value = ''
  detailRejection.value = ''
  detailNotes.value = ''
  detailLoading.value = true
  try { detailItem.value = (await api.show(id)).data }
  catch (e: any) { detailError.value = e?.data?.message || 'โหลดคำร้องไม่สำเร็จ' }
  finally { detailLoading.value = false }
}
const closeDetail = () => { detailOpen.value = false; detailItem.value = null }
const detailAct = async (action: 'approve' | 'reject' | 'start' | 'complete') => {
  if (!detailItem.value) return
  detailActing.value = true
  try {
    const updated = await api.transition(detailItem.value.id, action, { rejection_reason: detailRejection.value, admin_notes: detailNotes.value })
    detailItem.value = updated.data
    await loadList()
  }
  catch (e: any) { detailError.value = e?.data?.message || 'ดำเนินการไม่สำเร็จ' }
  finally { detailActing.value = false }
}

const statusTabs: { v: StudentCardRequestStatus | '' | 'active'; l: string }[] = [
  { v: 'active', l: 'กำลังดำเนินการ' },
  { v: 'pending', l: 'รออนุมัติ' },
  { v: 'approved', l: 'อนุมัติแล้ว' },
  { v: 'in_progress', l: 'กำลังจัดทำ' },
  { v: 'completed', l: 'เสร็จสิ้น' },
  { v: '', l: 'ทั้งหมด' },
]

const countFor = (v: StudentCardRequestStatus | '' | 'active') => {
  if (v === 'active') return (counts.value.pending || 0) + (counts.value.approved || 0) + (counts.value.in_progress || 0)
  if (!v) return Object.values(counts.value).reduce((s, n) => s + (n || 0), 0)
  return counts.value[v] || 0
}
</script>

<template>
  <div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
      <div>
        <h1 class="text-2xl font-bold dark:text-white">คำร้องทำบัตรนักเรียน</h1>
        <p class="text-gray-500">รายการคำร้องทั้งหมดสำหรับตรวจสอบและอนุมัติ</p>
      </div>
      <button class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-700" @click="openCreate">
        <Icon icon="fluent:add-24-regular" class="mr-1 inline" />สร้างคำร้องใหม่
      </button>
    </div>

    <div v-if="error" class="rounded-lg bg-red-50 p-3 text-red-700">{{ error }}</div>
    <div v-if="notice" class="rounded-lg bg-emerald-50 p-3 text-emerald-700">{{ notice }}</div>

    <!-- Status tabs (counts are academy-wide, not filtered) -->
    <div class="grid grid-cols-2 gap-3 md:grid-cols-5">
      <button
        v-for="tab in statusTabs" :key="tab.v"
        class="rounded-xl border bg-white p-4 text-left transition dark:border-gray-700 dark:bg-gray-800"
        :class="status === tab.v ? 'border-primary-500 ring-2 ring-primary-200 dark:ring-primary-900' : 'hover:border-gray-300'"
        @click="status = tab.v"
      >
        <div class="text-2xl font-bold dark:text-white">{{ countFor(tab.v) }}</div>
        <div class="text-sm text-gray-500">{{ tab.l }}</div>
      </button>
    </div>

    <!-- Filter bar -->
    <div class="rounded-xl border bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
      <div class="grid gap-3 md:grid-cols-4">
        <label class="text-sm">
          <span class="text-gray-600 dark:text-gray-300">ปีการศึกษา</span>
          <select v-model="academicYearId" class="mt-1 w-full rounded-lg border p-2 dark:bg-gray-900 dark:text-white">
            <option :value="null">ทุกปี</option>
            <option v-for="y in academicYears" :key="y.id" :value="y.id">
              {{ y.name }}{{ y.is_current ? ' (ปัจจุบัน)' : '' }}
            </option>
          </select>
        </label>
        <label class="text-sm">
          <span class="text-gray-600 dark:text-gray-300">ชั้นเรียน</span>
          <select v-model="gradeLevel" class="mt-1 w-full rounded-lg border p-2 dark:bg-gray-900 dark:text-white">
            <option value="">ทุกชั้น</option>
            <option v-for="g in gradeLevelsForYear" :key="g" :value="g">{{ g }}</option>
          </select>
        </label>
        <label class="text-sm">
          <span class="text-gray-600 dark:text-gray-300">ห้องเรียน</span>
          <select v-model="classroomId" class="mt-1 w-full rounded-lg border p-2 dark:bg-gray-900 dark:text-white">
            <option :value="null">ทุกห้อง</option>
            <option v-for="c in filteredClassrooms" :key="c.id" :value="c.id">{{ c.name || `${c.grade_level}/${c.section}` }}</option>
          </select>
        </label>
        <label class="text-sm">
          <span class="text-gray-600 dark:text-gray-300">ค้นหา (ชื่อ / เลขประจำตัว)</span>
          <div class="mt-1 flex gap-2">
            <input v-model="search" type="search" placeholder="พิมพ์เพื่อค้นหา..." class="w-full rounded-lg border p-2 dark:bg-gray-900 dark:text-white">
            <button class="whitespace-nowrap rounded-lg border px-3 text-sm text-gray-600 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-700" @click="clearFilters">
              ล้างตัวกรอง
            </button>
          </div>
        </label>
      </div>
    </div>

    <!-- List -->
    <div class="overflow-hidden rounded-xl border bg-white dark:border-gray-700 dark:bg-gray-800">
      <div v-if="loading" class="p-12 text-center text-gray-500">กำลังโหลด...</div>
      <table v-else class="w-full text-left">
        <thead class="bg-gray-50 text-sm dark:bg-gray-900">
          <tr>
            <th class="p-4">นักเรียน</th>
            <th class="p-4">ชั้น/ห้อง</th>
            <th class="p-4">ครูประจำชั้น</th>
            <th class="p-4">ประเภท</th>
            <th class="p-4">สถานะ</th>
            <th class="p-4" />
          </tr>
        </thead>
        <tbody>
          <tr v-for="row in rows" :key="row.id" class="border-t dark:border-gray-700">
            <td class="p-4">
              <div class="font-medium dark:text-white">{{ row.full_name }}</div>
              <div class="text-xs text-gray-500">{{ row.student_number }}</div>
            </td>
            <td class="p-4 dark:text-gray-200">{{ row.grade_level }}/{{ row.section }}</td>
            <td class="p-4 dark:text-gray-200">{{ row.classroom?.homeroom_teacher?.name || '—' }}</td>
            <td class="p-4 dark:text-gray-200">{{ row.request_type }}</td>
            <td class="p-4"><SchoolStudentCardRequestStatusBadge :status="row.status" /></td>
            <td class="p-4 text-right">
              <button class="text-primary-600 hover:underline" @click="openDetail(row.id)">ดูรายละเอียด</button>
            </td>
          </tr>
          <tr v-if="!rows.length">
            <td colspan="6" class="p-10 text-center text-gray-500">ไม่พบคำร้องตามตัวกรองที่เลือก</td>
          </tr>
        </tbody>
      </table>

      <!-- Pagination -->
      <div v-if="meta && meta.last_page > 1" class="flex items-center justify-between border-t p-3 text-sm dark:border-gray-700">
        <span class="text-gray-500">
          หน้า {{ meta.current_page }} จาก {{ meta.last_page }} · ทั้งหมด {{ meta.total }} รายการ
        </span>
        <div class="flex gap-2">
          <button class="rounded border px-3 py-1 disabled:opacity-40 dark:text-gray-200" :disabled="page <= 1" @click="page--">‹ ก่อนหน้า</button>
          <button class="rounded border px-3 py-1 disabled:opacity-40 dark:text-gray-200" :disabled="page >= meta.last_page" @click="page++">ถัดไป ›</button>
        </div>
      </div>
    </div>

    <!-- Create Request Modal (classroom → students → submit) -->
    <div v-if="createOpen" class="fixed inset-0 z-40 flex items-start justify-center overflow-y-auto bg-black/50 p-4" @click.self="closeCreate">
      <div class="my-8 w-full max-w-5xl space-y-5 rounded-2xl bg-white p-6 dark:bg-gray-900">
        <div class="flex items-start justify-between">
          <div>
            <h2 class="text-xl font-bold dark:text-white">สร้างคำร้องทำบัตรใหม่</h2>
            <p class="text-sm text-gray-500">เลือกห้องประจำชั้น แล้วเลือกนักเรียนที่จะส่งคำร้อง</p>
          </div>
          <button class="rounded-lg p-2 text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-800" @click="closeCreate">
            <Icon icon="fluent:dismiss-24-regular" />
          </button>
        </div>

        <div class="grid gap-3 md:grid-cols-3">
          <label class="text-sm">
            <span class="text-gray-600 dark:text-gray-300">ชั้น</span>
            <select v-model="createGradeLevel" class="mt-1 w-full rounded-lg border p-2 dark:bg-gray-800 dark:text-white">
              <option value="">ทุกชั้น</option>
              <option v-for="g in createGradeLevels" :key="g" :value="g">{{ g }}</option>
            </select>
          </label>
          <label class="text-sm md:col-span-2">
            <span class="text-gray-600 dark:text-gray-300">ค้นหาห้อง / ครูประจำชั้น</span>
            <input v-model="createSearch" type="search" placeholder="พิมพ์ชื่อห้องหรือชื่อครู..." class="mt-1 w-full rounded-lg border p-2 dark:bg-gray-800 dark:text-white">
          </label>
        </div>
        <label class="block text-sm font-medium dark:text-gray-200">
          ห้องเรียน <span class="text-xs font-normal text-gray-500">({{ createClassroomOptions.length }} ห้อง)</span>
          <select v-model="createClassroomId" class="mt-2 w-full rounded-lg border p-2.5 dark:bg-gray-800 dark:text-white" @change="loadCreateStudents">
            <option :value="null">เลือกห้องเรียน</option>
            <option v-for="room in createClassroomOptions" :key="room.id" :value="room.id">
              {{ room.name || `${room.grade_level}/${room.section}` }}<template v-if="room.homeroom_teacher"> — ครู {{ room.homeroom_teacher.name }}</template>
            </option>
          </select>
        </label>

        <SchoolStudentCardClassroomHeader :classroom="createClassroomObj" />

        <div v-if="createLoading" class="py-14 text-center text-gray-500">กำลังโหลด...</div>
        <template v-else-if="createClassroomId">
          <div class="flex flex-wrap items-center gap-2">
            <button class="rounded-lg border border-primary-200 bg-primary-50 px-3 py-2 text-sm font-medium text-primary-700 hover:bg-primary-100 dark:border-gray-700 dark:bg-gray-800 dark:text-primary-300" @click="selectAllWithoutCard">
              <Icon icon="fluent:people-add-24-regular" class="mr-1 inline" />เลือกทุกคนที่ยังไม่มีบัตร
            </button>
            <button class="rounded-lg border px-3 py-2 text-sm text-gray-600 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-700" @click="toggleSelectAll">
              {{ allSelected ? 'ล้างที่เลือก' : `เลือกทั้งหมด (${eligibleStudents.length})` }}
            </button>
            <div class="ml-auto flex items-center gap-3">
              <span v-if="createSelectedIds.size" class="text-sm text-gray-500">เลือกแล้ว <b class="text-primary-600">{{ selectedStudents.length }}</b> คน</span>
              <button
                class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white disabled:opacity-40"
                :disabled="!selectedStudents.length || submittingBulk"
                @click="bulkModalOpen = true"
              >
                <Icon icon="fluent:send-24-regular" class="mr-1 inline" />ส่งคำร้องที่เลือก
              </button>
            </div>
          </div>

          <div class="overflow-hidden rounded-xl border dark:border-gray-700">
            <table class="w-full text-left">
              <thead class="bg-gray-50 text-sm dark:bg-gray-800">
                <tr>
                  <th class="w-12 p-4"><input type="checkbox" class="h-4 w-4 rounded border-gray-300" :checked="allSelected" @change="toggleSelectAll"></th>
                  <th class="p-4">นักเรียน</th>
                  <th class="p-4">เลขประจำตัว</th>
                  <th class="p-4">บัตรปัจจุบัน</th>
                  <th class="p-4 text-right">คำร้อง</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="student in createStudents" :key="student.id" class="border-t dark:border-gray-700" :class="createSelectedIds.has(student.student_id) ? 'bg-primary-50/50 dark:bg-gray-800/40' : ''">
                  <td class="p-4">
                    <input v-if="!student.active_card_request" type="checkbox" class="h-4 w-4 rounded border-gray-300" :checked="createSelectedIds.has(student.student_id)" @change="toggleSelect(student)">
                  </td>
                  <td class="p-4 font-medium dark:text-white">{{ student.name }}</td>
                  <td class="p-4 text-gray-500">{{ student.student_id }}</td>
                  <td class="p-4"><span :class="student.student_card ? 'text-emerald-600' : 'text-gray-500'">{{ student.student_card ? 'มีบัตรแล้ว' : 'ยังไม่มีบัตร' }}</span></td>
                  <td class="p-4 text-right">
                    <SchoolStudentCardRequestStatusBadge v-if="student.active_card_request" :status="student.active_card_request.status" />
                    <button v-else class="rounded-lg bg-primary-600 px-3 py-2 text-sm text-white" @click="openSingle(student)">ส่งคำร้อง</button>
                  </td>
                </tr>
                <tr v-if="!createStudents.length">
                  <td colspan="5" class="p-10 text-center text-gray-500">ไม่พบนักเรียนในห้องนี้</td>
                </tr>
              </tbody>
            </table>
          </div>
        </template>
      </div>
    </div>

    <SchoolStudentCardSubmitRequestModal :open="singleModalOpen" :student="selectedStudent" :classroom-id="createClassroomId || 0" @close="singleModalOpen = false" @submit="submitSingle" />
    <SchoolStudentCardBulkSubmitRequestModal :open="bulkModalOpen" :students="selectedStudents" :classroom-id="createClassroomId || 0" @close="bulkModalOpen = false" @submit="submitBulk" />

    <!-- Detail Modal -->
    <div v-if="detailOpen" class="fixed inset-0 z-40 flex items-start justify-center overflow-y-auto bg-black/50 p-4" @click.self="closeDetail">
      <div class="my-8 w-full max-w-3xl space-y-5 rounded-2xl bg-white p-6 dark:bg-gray-900">
        <div class="flex items-start justify-between">
          <div>
            <h2 class="text-xl font-bold dark:text-white">รายละเอียดคำร้อง</h2>
            <p v-if="detailItem" class="text-sm text-gray-500">{{ detailItem.student_number }} · {{ detailItem.grade_level }}/{{ detailItem.section }}</p>
          </div>
          <button class="rounded-lg p-2 text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-800" @click="closeDetail">
            <Icon icon="fluent:dismiss-24-regular" />
          </button>
        </div>

        <div v-if="detailError" class="rounded-lg bg-red-50 p-3 text-red-700">{{ detailError }}</div>
        <div v-if="detailLoading" class="py-14 text-center text-gray-500">กำลังโหลด...</div>

        <template v-else-if="detailItem">
          <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
              <div class="text-lg font-semibold dark:text-white">{{ detailItem.full_name }}</div>
              <div class="text-sm text-gray-500">{{ detailItem.classroom?.homeroom_teacher?.name ? `ครูประจำชั้น: ${detailItem.classroom.homeroom_teacher.name}` : '' }}</div>
            </div>
            <SchoolStudentCardRequestStatusBadge :status="detailItem.status" />
          </div>

          <SchoolStudentCardClassroomHeader :classroom="detailItem.classroom || null" />

          <div v-if="detailClassroomChanged" class="rounded-lg bg-amber-50 p-3 text-sm text-amber-800 dark:bg-amber-950/40 dark:text-amber-200">
            ห้องมีการเปลี่ยนแปลงหลังส่งคำร้อง — ตอนส่ง: {{ detailItem.grade_level }}/{{ detailItem.section }}
          </div>

          <section class="rounded-xl border p-4 dark:border-gray-700">
            <h3 class="font-semibold dark:text-white">รายละเอียดคำร้อง</h3>
            <dl class="mt-3 grid gap-3 text-sm md:grid-cols-2">
              <div><dt class="text-gray-500">ประเภท</dt><dd class="dark:text-white">{{ detailItem.request_type }}</dd></div>
              <div><dt class="text-gray-500">ความเร่งด่วน</dt><dd class="dark:text-white">{{ detailItem.priority }}</dd></div>
              <div class="md:col-span-2"><dt class="text-gray-500">เหตุผล</dt><dd class="dark:text-white">{{ detailItem.reason_label || detailItem.reason || '-' }}<span v-if="detailItem.reason_label && detailItem.reason" class="block text-gray-500">{{ detailItem.reason }}</span></dd></div>
              <div class="md:col-span-2"><dt class="text-gray-500">ผู้แจ้งเรื่อง</dt><dd class="dark:text-white">{{ detailItem.requester_name || '-' }}<span v-if="detailItem.requester_phone" class="text-gray-500"> · {{ detailItem.requester_phone }}</span></dd></div>
            </dl>
          </section>

          <section class="rounded-xl border p-4 dark:border-gray-700">
            <h3 class="font-semibold dark:text-white">ดำเนินการ</h3>
            <textarea v-model="detailNotes" rows="3" placeholder="บันทึกภายใน" class="mt-3 w-full rounded-lg border p-2.5 dark:bg-gray-800 dark:text-white" />
            <textarea v-if="detailItem.status === 'pending'" v-model="detailRejection" rows="2" placeholder="เหตุผลเมื่อปฏิเสธ" class="mt-3 w-full rounded-lg border p-2.5 dark:bg-gray-800 dark:text-white" />
            <div class="mt-4 flex flex-wrap gap-2">
              <button v-if="detailItem.status === 'pending'" class="rounded-lg bg-blue-600 px-4 py-2 text-white disabled:opacity-40" :disabled="detailActing" @click="detailAct('approve')">อนุมัติ</button>
              <button v-if="detailItem.status === 'pending'" class="rounded-lg bg-red-600 px-4 py-2 text-white disabled:opacity-40" :disabled="!detailRejection || detailActing" @click="detailAct('reject')">ปฏิเสธ</button>
              <button v-if="detailItem.status === 'approved'" class="rounded-lg bg-violet-600 px-4 py-2 text-white disabled:opacity-40" :disabled="detailActing" @click="detailAct('start')">เริ่มจัดทำ</button>
              <button v-if="detailItem.status === 'in_progress'" class="rounded-lg bg-emerald-600 px-4 py-2 text-white disabled:opacity-40" :disabled="detailActing" @click="detailAct('complete')">จัดทำเสร็จ</button>
              <NuxtLink v-if="detailItem.result_card_id" :to="`/academies/${academyName}/admin/student-cards/${detailItem.result_card_id}`" class="rounded-lg border px-4 py-2 dark:text-white">
                เปิดบัตรที่สร้าง
              </NuxtLink>
              <NuxtLink :to="`/academies/${academyName}/admin/student-cards/requests/${detailItem.id}`" class="ml-auto text-sm text-gray-500 hover:underline dark:text-gray-400">
                เปิดหน้าเต็ม ↗
              </NuxtLink>
            </div>
          </section>
        </template>
      </div>
    </div>
  </div>
</template>
