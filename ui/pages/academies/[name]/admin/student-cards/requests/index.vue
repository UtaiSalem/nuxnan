<script setup lang="ts">
import { Icon } from '@iconify/vue'
import type { ClassroomSummary } from '~/types/studentCardRequest'
import type { SubmitCardRequestPayload } from '~/composables/useStudentCardRequests'

definePageMeta({ layout: 'main' })
const academyId = inject<Ref<number | null>>('academyId', ref(null))
const academyName = inject<ComputedRef<string>>('academyName', computed(() => String(useRoute().params.name)))
const requests = useStudentCardRequests(academyId)
const classrooms = ref<ClassroomSummary[]>([])
const selectedClassroomObj = ref<ClassroomSummary | null>(null)
const students = ref<any[]>([])
const selectedClassroom = ref<number | null>(null)
const selectedStudent = ref<any>(null)
const modalOpen = ref(false)
const bulkModalOpen = ref(false)
const loading = ref(true)
const submittingBulk = ref(false)
const error = ref('')
const notice = ref('')
const selectedIds = ref<Set<number>>(new Set())

// ส่งคำร้องได้เฉพาะคนที่ยังไม่มีคำร้องค้างอยู่
const eligibleStudents = computed(() => students.value.filter(s => !s.active_card_request))
const selectedStudents = computed(() => eligibleStudents.value.filter(s => selectedIds.value.has(s.student_id)))
const allSelected = computed(() => eligibleStudents.value.length > 0 && selectedStudents.value.length === eligibleStudents.value.length)

const loadClassrooms = async () => {
  if (!academyId.value) return
  loading.value = true
  try { classrooms.value = (await requests.myClassrooms()).data || [] } catch (e: any) { error.value = e?.data?.message || 'โหลดห้องเรียนไม่สำเร็จ' } finally { loading.value = false }
}
const loadStudents = async () => {
  if (!selectedClassroom.value) return
  loading.value = true
  selectedIds.value = new Set()
  try {
    const response = await requests.classroomStudents(selectedClassroom.value)
    selectedClassroomObj.value = response.data.classroom
    students.value = (response.data.students || []).map((row: any) => ({ ...row.student, student_id: row.student_id, name: row.student?.full_name_th || `${row.student?.first_name_th || ''} ${row.student?.last_name_th || ''}` }))
  } finally { loading.value = false }
}
const toggleSelect = (student: any) => {
  const next = new Set(selectedIds.value)
  if (next.has(student.student_id)) next.delete(student.student_id)
  else next.add(student.student_id)
  selectedIds.value = next
}
const toggleSelectAll = () => {
  selectedIds.value = allSelected.value ? new Set() : new Set(eligibleStudents.value.map(s => s.student_id))
}
const selectAllWithoutCard = () => {
  selectedIds.value = new Set(eligibleStudents.value.filter(s => !s.student_card).map(s => s.student_id))
}
const openRequest = (student: any) => { selectedStudent.value = student; modalOpen.value = true }
const submit = async (payload: SubmitCardRequestPayload) => {
  error.value = ''
  try { await requests.submit(payload); modalOpen.value = false; notice.value = 'ส่งคำร้องสำเร็จ'; await loadStudents() } catch (e: any) { error.value = e?.data?.message || 'ส่งคำร้องไม่สำเร็จ' }
}
const submitBulk = async (payloads: SubmitCardRequestPayload[]) => {
  error.value = ''
  notice.value = ''
  submittingBulk.value = true
  try {
    const { data: results } = await requests.submitBulk(payloads)
    bulkModalOpen.value = false
    const failed = results.filter(r => !r.success)
    const nameOf = (id: number) => students.value.find(s => s.student_id === id)?.name || `รหัส ${id}`
    notice.value = `ส่งคำร้องสำเร็จ ${results.length - failed.length} จาก ${results.length} คน`
    if (failed.length) error.value = 'ส่งไม่สำเร็จ: ' + failed.map(f => `${nameOf(f.student_id)} (${f.message || 'ไม่ทราบสาเหตุ'})`).join(', ')
    await loadStudents()
  } catch (e: any) {
    error.value = e?.data?.message || 'ส่งคำร้องไม่สำเร็จ'
  } finally { submittingBulk.value = false }
}
watch(academyId, loadClassrooms, { immediate: true })
</script>

<template><div class="space-y-6">
  <div class="flex flex-wrap items-center justify-between gap-3"><div><h1 class="text-2xl font-bold dark:text-white">คำร้องทำบัตรนักเรียน</h1><p class="text-gray-500">เลือกห้องประจำชั้นและส่งคำร้องให้ฝ่ายจัดทำบัตร</p></div><NuxtLink :to="`/academies/${academyName}/admin/student-cards/requests/queue`" class="rounded-lg border px-4 py-2 dark:text-white"><Icon icon="fluent:clipboard-task-list-24-regular" class="mr-2 inline"/>คิวงาน</NuxtLink></div>
  <div v-if="error" class="rounded-lg bg-red-50 p-3 text-red-700">{{ error }}</div>
  <div v-if="notice" class="rounded-lg bg-emerald-50 p-3 text-emerald-700">{{ notice }}</div>
  <div class="rounded-xl border bg-white p-5 dark:border-gray-700 dark:bg-gray-800"><label class="text-sm font-medium dark:text-gray-200">ห้องเรียน<select v-model="selectedClassroom" class="mt-2 w-full max-w-md rounded-lg border p-2.5 dark:bg-gray-900" @change="loadStudents"><option :value="null">เลือกห้องเรียน</option><option v-for="room in classrooms" :key="room.id" :value="room.id">{{ room.name }} — {{ room.homeroom_teacher ? `ครู ${room.homeroom_teacher.name}` : 'ยังไม่มีครูประจำชั้น' }}</option></select></label></div>
  <SchoolStudentCardClassroomHeader :classroom="selectedClassroomObj" />
  <div v-if="loading" class="py-14 text-center text-gray-500">กำลังโหลด...</div>
  <template v-else-if="selectedClassroom">
    <!-- Bulk controls -->
    <div class="flex flex-wrap items-center gap-2">
      <button class="rounded-lg border border-primary-200 bg-primary-50 px-3 py-2 text-sm font-medium text-primary-700 hover:bg-primary-100 dark:border-gray-600 dark:bg-gray-800 dark:text-primary-300" @click="selectAllWithoutCard">
        <Icon icon="fluent:people-add-24-regular" class="mr-1 inline"/>เลือกทุกคนที่ยังไม่มีบัตร
      </button>
      <button class="rounded-lg border px-3 py-2 text-sm text-gray-600 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-700" @click="toggleSelectAll">{{ allSelected ? 'ล้างที่เลือก' : `เลือกทั้งหมด (${eligibleStudents.length})` }}</button>
      <div class="ml-auto flex items-center gap-3">
        <span v-if="selectedIds.size" class="text-sm text-gray-500">เลือกแล้ว <b class="text-primary-600">{{ selectedStudents.length }}</b> คน</span>
        <button class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white disabled:opacity-40" :disabled="!selectedStudents.length || submittingBulk" @click="bulkModalOpen = true">
          <Icon icon="fluent:send-24-regular" class="mr-1 inline"/>ส่งคำร้องที่เลือก
        </button>
      </div>
    </div>

    <div class="overflow-hidden rounded-xl border bg-white dark:border-gray-700 dark:bg-gray-800"><table class="w-full text-left"><thead class="bg-gray-50 text-sm dark:bg-gray-900"><tr><th class="w-12 p-4"><input type="checkbox" class="h-4 w-4 rounded border-gray-300" :checked="allSelected" @change="toggleSelectAll" /></th><th class="p-4">นักเรียน</th><th class="p-4">เลขประจำตัว</th><th class="p-4">บัตรปัจจุบัน</th><th class="p-4 text-right">คำร้อง</th></tr></thead><tbody>
      <tr v-for="student in students" :key="student.id" class="border-t dark:border-gray-700" :class="selectedIds.has(student.student_id) ? 'bg-primary-50/50 dark:bg-gray-700/40' : ''">
        <td class="p-4"><input v-if="!student.active_card_request" type="checkbox" class="h-4 w-4 rounded border-gray-300" :checked="selectedIds.has(student.student_id)" @change="toggleSelect(student)" /></td>
        <td class="p-4 font-medium dark:text-white">{{ student.name }}</td>
        <td class="p-4 text-gray-500">{{ student.student_id }}</td>
        <td class="p-4"><span :class="student.student_card ? 'text-emerald-600' : 'text-gray-500'">{{ student.student_card ? 'มีบัตรแล้ว' : 'ยังไม่มีบัตร' }}</span></td>
        <td class="p-4 text-right">
          <SchoolStudentCardRequestStatusBadge v-if="student.active_card_request" :status="student.active_card_request.status" />
          <button v-else class="rounded-lg bg-primary-600 px-3 py-2 text-sm text-white" @click="openRequest(student)">ส่งคำร้อง</button>
        </td>
      </tr>
      <tr v-if="!students.length"><td colspan="5" class="p-10 text-center text-gray-500">ไม่พบนักเรียนในห้องนี้</td></tr>
    </tbody></table></div>
  </template>
  <SchoolStudentCardSubmitRequestModal :open="modalOpen" :student="selectedStudent" :classroom-id="selectedClassroom || 0" @close="modalOpen = false" @submit="submit" />
  <SchoolStudentCardBulkSubmitRequestModal :open="bulkModalOpen" :students="selectedStudents" :classroom-id="selectedClassroom || 0" @close="bulkModalOpen = false" @submit="submitBulk" />
</div></template>
