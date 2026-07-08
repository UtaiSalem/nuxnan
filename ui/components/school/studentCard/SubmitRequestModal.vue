<script setup lang="ts">
import type { StudentCardRequestType } from '~/types/studentCardRequest'

const props = defineProps<{ open: boolean; student: any; classroomId: number }>()
const emit = defineEmits<{ close: []; submit: [payload: { student_id: number; classroom_id: number; request_type: StudentCardRequestType; reason?: string; priority: string }] }>()
const requestType = ref<StudentCardRequestType>('first_issue')
const reason = ref('')
const priority = ref('normal')

watch(() => props.student, student => { requestType.value = student?.student_card ? 'replacement' : 'first_issue'; reason.value = ''; priority.value = 'normal' })
const submit = () => emit('submit', { student_id: props.student.student_id, classroom_id: props.classroomId, request_type: requestType.value, reason: reason.value || undefined, priority: priority.value })
</script>

<template>
  <Teleport to="body"><div v-if="open" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" @click.self="emit('close')">
    <div class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-xl dark:bg-gray-800">
      <h2 class="text-xl font-bold dark:text-white">ส่งคำร้องทำบัตร</h2><p class="mt-1 text-sm text-gray-500">{{ student?.name }}</p>
      <div class="mt-5 space-y-4">
        <label class="block text-sm font-medium dark:text-gray-200">ประเภทคำร้อง<select v-model="requestType" class="mt-1 w-full rounded-lg border p-2.5 dark:bg-gray-900"><option value="first_issue">ออกบัตรครั้งแรก</option><option value="replacement">ออกบัตรทดแทน</option><option value="renewal">ต่ออายุบัตร</option></select></label>
        <label class="block text-sm font-medium dark:text-gray-200">ความเร่งด่วน<select v-model="priority" class="mt-1 w-full rounded-lg border p-2.5 dark:bg-gray-900"><option value="normal">ปกติ</option><option value="urgent">เร่งด่วน</option></select></label>
        <label class="block text-sm font-medium dark:text-gray-200">เหตุผล<textarea v-model="reason" rows="3" class="mt-1 w-full rounded-lg border p-2.5 dark:bg-gray-900" :required="requestType !== 'first_issue'" /></label>
      </div>
      <div class="mt-6 flex justify-end gap-2"><button class="rounded-lg border px-4 py-2" @click="emit('close')">ยกเลิก</button><button class="rounded-lg bg-primary-600 px-4 py-2 text-white" @click="submit">ยืนยันส่งคำร้อง</button></div>
    </div>
  </div></Teleport>
</template>
