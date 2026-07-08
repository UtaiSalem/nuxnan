<script setup lang="ts">
import { Icon } from '@iconify/vue'

definePageMeta({ layout: false })
const academyId = inject<Ref<number | null>>('academyId', ref(null))
const academyName = inject<ComputedRef<string>>('academyName', computed(() => String(useRoute().params.name)))
const requests = useStudentCardRequests(academyId)
const classrooms = ref<any[]>([])
const students = ref<any[]>([])
const selectedClassroom = ref<number | null>(null)
const selectedStudent = ref<any>(null)
const modalOpen = ref(false)
const loading = ref(true)
const error = ref('')

const loadClassrooms = async () => {
  if (!academyId.value) return
  loading.value = true
  try { classrooms.value = (await requests.myClassrooms()).data || [] } catch (e: any) { error.value = e?.data?.message || 'โหลดห้องเรียนไม่สำเร็จ' } finally { loading.value = false }
}
const loadStudents = async () => {
  if (!selectedClassroom.value) return
  loading.value = true
  try { students.value = ((await requests.classroomStudents(selectedClassroom.value)).data || []).map((row: any) => ({ ...row.student, student_id: row.student_id, name: row.student?.full_name_th || `${row.student?.first_name_th || ''} ${row.student?.last_name_th || ''}` })) } finally { loading.value = false }
}
const openRequest = (student: any) => { selectedStudent.value = student; modalOpen.value = true }
const submit = async (payload: any) => {
  try { await requests.submit(payload); modalOpen.value = false; await loadStudents() } catch (e: any) { error.value = e?.data?.message || 'ส่งคำร้องไม่สำเร็จ' }
}
watch(academyId, loadClassrooms, { immediate: true })
</script>

<template><div class="space-y-6">
  <div class="flex flex-wrap items-center justify-between gap-3"><div><h1 class="text-2xl font-bold dark:text-white">คำร้องทำบัตรนักเรียน</h1><p class="text-gray-500">เลือกห้องประจำชั้นและส่งคำร้องให้ฝ่ายจัดทำบัตร</p></div><NuxtLink :to="`/academies/${academyName}/admin/student-cards/requests/queue`" class="rounded-lg border px-4 py-2 dark:text-white"><Icon icon="fluent:clipboard-task-list-24-regular" class="mr-2 inline"/>คิวงาน</NuxtLink></div>
  <div v-if="error" class="rounded-lg bg-red-50 p-3 text-red-700">{{ error }}</div>
  <div class="rounded-xl border bg-white p-5 dark:border-gray-700 dark:bg-gray-800"><label class="text-sm font-medium dark:text-gray-200">ห้องเรียน<select v-model="selectedClassroom" class="mt-2 w-full max-w-md rounded-lg border p-2.5 dark:bg-gray-900" @change="loadStudents"><option :value="null">เลือกห้องเรียน</option><option v-for="room in classrooms" :key="room.id" :value="room.id">{{ room.name || `${room.grade_level}/${room.section}` }}</option></select></label></div>
  <div v-if="loading" class="py-14 text-center text-gray-500">กำลังโหลด...</div>
  <div v-else-if="selectedClassroom" class="overflow-hidden rounded-xl border bg-white dark:border-gray-700 dark:bg-gray-800"><table class="w-full text-left"><thead class="bg-gray-50 text-sm dark:bg-gray-900"><tr><th class="p-4">นักเรียน</th><th class="p-4">เลขประจำตัว</th><th class="p-4">บัตรปัจจุบัน</th><th class="p-4 text-right">คำร้อง</th></tr></thead><tbody><tr v-for="student in students" :key="student.id" class="border-t dark:border-gray-700"><td class="p-4 font-medium dark:text-white">{{ student.name }}</td><td class="p-4 text-gray-500">{{ student.student_id }}</td><td class="p-4"><span :class="student.student_card ? 'text-emerald-600' : 'text-gray-500'">{{ student.student_card ? 'มีบัตรแล้ว' : 'ยังไม่มีบัตร' }}</span></td><td class="p-4 text-right"><button class="rounded-lg bg-primary-600 px-3 py-2 text-sm text-white" @click="openRequest(student)">ส่งคำร้อง</button></td></tr><tr v-if="!students.length"><td colspan="4" class="p-10 text-center text-gray-500">ไม่พบนักเรียนในห้องนี้</td></tr></tbody></table></div>
  <SchoolStudentCardSubmitRequestModal :open="modalOpen" :student="selectedStudent" :classroom-id="selectedClassroom || 0" @close="modalOpen = false" @submit="submit" />
</div></template>
