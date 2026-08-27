<script setup lang="ts">
import { STUDENT_CARD_REQUEST_REASONS, type StudentCardRequestReason } from '~/types/studentCardRequest'
import type { SubmitCardRequestPayload } from '~/composables/useStudentCardRequests'

const props = defineProps<{ open: boolean; student: any; classroomId: number }>()
const emit = defineEmits<{ close: []; submit: [payload: SubmitCardRequestPayload] }>()

const reasonCode = ref<StudentCardRequestReason>('lost')
const reasonDetail = ref('')
const priority = ref('normal')

const detailRequired = computed(() => reasonCode.value === 'other')

// บอกผู้ใช้ล่วงหน้าว่าระบบจะจัดเป็นคำร้องประเภทไหน (backend เป็นคนตัดสินจริง)
const derivedTypeLabel = computed(() => {
  if (!props.student?.student_card) return 'ออกบัตรครั้งแรก'
  return reasonCode.value === 'expired' ? 'ต่ออายุบัตร' : 'ออกบัตรทดแทน'
})

watch(() => props.student, (student) => {
  reasonCode.value = student?.student_card ? 'lost' : 'new_student'
  reasonDetail.value = ''
  priority.value = 'normal'
})

const submit = () => {
  if (detailRequired.value && !reasonDetail.value.trim()) return
  emit('submit', {
    student_id: props.student.student_id,
    classroom_id: props.classroomId,
    reason_code: reasonCode.value,
    reason: reasonDetail.value.trim() || undefined,
    priority: priority.value,
  })
}
</script>

<template>
  <Teleport to="body"><div v-if="open" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" @click.self="emit('close')">
    <div class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-xl dark:bg-gray-800">
      <h2 class="text-xl font-bold dark:text-white">ส่งคำร้องทำบัตร</h2>
      <p class="mt-1 text-sm text-gray-500">{{ student?.name }} · ประเภทคำร้อง: <span class="font-medium text-primary-600 dark:text-primary-400">{{ derivedTypeLabel }}</span> (ระบบจัดให้อัตโนมัติ)</p>
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
          <textarea v-model="reasonDetail" rows="3" :placeholder="detailRequired ? 'โปรดระบุเหตุผล...' : 'เว้นว่างได้ ถ้าไม่มีรายละเอียดเพิ่มเติม'" class="mt-1 w-full rounded-lg border p-2.5 dark:bg-gray-900" />
        </label>
      </div>
      <div class="mt-6 flex justify-end gap-2"><button class="min-h-[44px] sm:min-h-0 rounded-lg border px-4 py-2 dark:text-gray-200" @click="emit('close')">ยกเลิก</button><button class="min-h-[44px] sm:min-h-0 rounded-lg bg-primary-600 px-4 py-2 text-white disabled:opacity-50" :disabled="detailRequired && !reasonDetail.trim()" @click="submit">ยืนยันส่งคำร้อง</button></div>
    </div>
  </div></Teleport>
</template>
