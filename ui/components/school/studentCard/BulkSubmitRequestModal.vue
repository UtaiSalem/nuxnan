<script setup lang="ts">
import { STUDENT_CARD_REQUEST_REASONS, type StudentCardRequestReason } from '~/types/studentCardRequest'
import type { SubmitCardRequestPayload } from '~/composables/useStudentCardRequests'

const props = defineProps<{ open: boolean; students: any[]; classroomId: number }>()
const emit = defineEmits<{ close: []; submit: [payloads: SubmitCardRequestPayload[]] }>()

const reasonCode = ref<StudentCardRequestReason>('new_student')
const reasonDetail = ref('')
const priority = ref('normal')
const showAll = ref(false)

const detailRequired = computed(() => reasonCode.value === 'other')
const preview = computed(() => showAll.value ? props.students : props.students.slice(0, 5))

watch(() => props.open, (open) => {
  if (open) {
    reasonCode.value = 'new_student'
    reasonDetail.value = ''
    priority.value = 'normal'
    showAll.value = false
  }
})

const submit = () => {
  if (detailRequired.value && !reasonDetail.value.trim()) return
  emit('submit', props.students.map(student => ({
    student_id: student.student_id,
    classroom_id: props.classroomId,
    reason_code: reasonCode.value,
    reason: reasonDetail.value.trim() || undefined,
    priority: priority.value,
  })))
}
</script>

<template>
  <Teleport to="body"><div v-if="open" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" @click.self="emit('close')">
    <div class="max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-2xl bg-white p-4 sm:p-6 shadow-xl dark:bg-gray-800">
      <h2 class="text-xl font-bold dark:text-white">ส่งคำร้องทำบัตรหลายคน</h2>
      <p class="mt-1 text-sm text-gray-500">เหตุผลชุดเดียวกันจะใช้กับนักเรียนทุกคนที่เลือก</p>

      <div class="mt-4 rounded-xl border border-primary-100 bg-primary-50/50 p-3 dark:border-gray-700 dark:bg-gray-900/50">
        <div class="flex items-center justify-between text-xs font-semibold uppercase tracking-wider text-primary-500">
          นักเรียนที่เลือก
          <span class="rounded-full bg-primary-600 px-2 py-0.5 text-xs font-bold text-white">{{ students.length }} คน</span>
        </div>
        <ul class="mt-2 space-y-0.5 text-sm text-gray-700 dark:text-gray-200">
          <li v-for="s in preview" :key="s.student_id">• {{ s.name }}</li>
        </ul>
        <button v-if="students.length > 5" class="mt-1 text-xs font-medium text-primary-600 hover:underline" @click="showAll = !showAll">
          {{ showAll ? 'ย่อรายชื่อ' : `ดูอีก ${students.length - 5} คน` }}
        </button>
      </div>

      <div class="mt-5 space-y-4">
        <label class="block text-sm font-medium dark:text-gray-200">
          <span class="inline-flex items-center gap-2">เหตุผลการขอทำบัตร<span class="rounded border border-red-200 bg-red-50 px-1.5 py-0.5 text-[10px] font-medium text-red-600 dark:border-red-800 dark:bg-red-900/40 dark:text-red-300">จำเป็น</span></span>
          <select v-model="reasonCode" class="mt-1 w-full rounded-lg border p-2.5 dark:bg-gray-900">
            <option v-for="option in STUDENT_CARD_REQUEST_REASONS" :key="option.value" :value="option.value">{{ option.label }}</option>
          </select>
        </label>
        <label class="block text-sm font-medium dark:text-gray-200">
          <span class="inline-flex items-center gap-2">ความเร่งด่วน<span class="rounded border border-gray-200 bg-gray-100 px-1.5 py-0.5 text-[10px] font-medium text-gray-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300">ไม่จำเป็น</span></span>
          <select v-model="priority" class="mt-1 w-full rounded-lg border p-2.5 dark:bg-gray-900"><option value="normal">ปกติ</option><option value="urgent">เร่งด่วน</option></select>
        </label>
        <label class="block text-sm font-medium dark:text-gray-200">
          <span class="inline-flex items-center gap-2">รายละเอียดเพิ่มเติม
            <span v-if="detailRequired" class="rounded border border-red-200 bg-red-50 px-1.5 py-0.5 text-[10px] font-medium text-red-600 dark:border-red-800 dark:bg-red-900/40 dark:text-red-300">จำเป็น</span>
            <span v-else class="rounded border border-gray-200 bg-gray-100 px-1.5 py-0.5 text-[10px] font-medium text-gray-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300">ไม่จำเป็น</span>
          </span>
          <textarea v-model="reasonDetail" rows="2" :placeholder="detailRequired ? 'โปรดระบุเหตุผล...' : 'เว้นว่างได้ ถ้าไม่มีรายละเอียดเพิ่มเติม'" class="mt-1 w-full rounded-lg border p-2.5 dark:bg-gray-900" />
        </label>
      </div>

      <div class="mt-6 flex justify-end gap-2">
        <button class="min-h-[44px] sm:min-h-0 rounded-lg border px-4 py-2 dark:text-gray-200" @click="emit('close')">ยกเลิก</button>
        <button class="min-h-[44px] sm:min-h-0 rounded-lg bg-primary-600 px-4 py-2 text-white disabled:opacity-50" :disabled="!students.length || (detailRequired && !reasonDetail.trim())" @click="submit">ส่งคำร้อง {{ students.length }} คน</button>
      </div>
    </div>
  </div></Teleport>
</template>
