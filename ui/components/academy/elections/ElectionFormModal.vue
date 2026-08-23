<script setup lang="ts">
import { Dialog, DialogPanel, DialogTitle } from '@headlessui/vue'

const props = defineProps<{ open: boolean; academyId: number; election?: any }>()
const emit = defineEmits<{ close: []; saved: [] }>()
const { createElection, updateElection } = useElections()
const saving = ref(false)
const errors = ref<Record<string, string[]>>({})
const form = reactive({
  title: '',
  description: '',
  academic_year_id: '',
  education_level: '',
  nomination_opens_at: '',
  nomination_closes_at: '',
  voting_opens_at: '',
  voting_closes_at: '',
  allow_abstain: true,
  ballot_ttl_seconds: 180,
})
const reset = () => {
  Object.assign(form, {
    title: '',
    description: '',
    academic_year_id: '',
    education_level: '',
    nomination_opens_at: '',
    nomination_closes_at: '',
    voting_opens_at: '',
    voting_closes_at: '',
    allow_abstain: true,
    ballot_ttl_seconds: 180,
  })
  errors.value = {}
}
watch(
  () => props.election,
  (value) => {
    if (value)
      Object.assign(form, {
        ...value,
        academic_year_id: value.academic_year_id || '',
        education_level: value.education_level ?? '',
        nomination_opens_at: value.nomination_opens_at || '',
        nomination_closes_at: value.nomination_closes_at || '',
        voting_opens_at: value.voting_opens_at || '',
        voting_closes_at: value.voting_closes_at || '',
      })
  },
  { immediate: true }
)
const close = () => {
  if (!saving.value) {
    reset()
    emit('close')
  }
}
const submit = async () => {
  saving.value = true
  errors.value = {}
  try {
    const payload = {
      ...form,
      academic_year_id: form.academic_year_id ? Number(form.academic_year_id) : null,
      education_level: form.education_level ? Number(form.education_level) : null,
      ballot_ttl_seconds: Number(form.ballot_ttl_seconds),
    }
    if (props.election?.id) await updateElection(props.academyId, props.election.id, payload)
    else await createElection(props.academyId, payload)
    emit('saved')
    close()
  } catch (error: any) {
    errors.value = error?.data?.errors || error?.response?._data?.errors || {}
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <Dialog :open="props.open" as="div" class="relative z-50" @close="close">
    <div class="fixed inset-0 bg-black/40" aria-hidden="true" />
    <div class="fixed inset-0 overflow-y-auto p-3 sm:p-6">
      <div class="flex min-h-full items-center justify-center">
        <DialogPanel
          class="w-full max-w-2xl rounded-2xl bg-white p-4 shadow-xl dark:bg-gray-800 sm:p-6"
        >
          <DialogTitle class="text-lg font-bold text-gray-900 dark:text-white"
            >สร้างการเลือกตั้ง</DialogTitle
          >
          <form class="mt-5 space-y-4" @submit.prevent="submit">
            <div
              v-for="field in [
                { key: 'title', label: 'ชื่อการเลือกตั้ง', type: 'text' },
                { key: 'academic_year_id', label: 'ปีการศึกษา', type: 'number' },
                { key: 'nomination_opens_at', label: 'เปิดรับสมัคร', type: 'datetime-local' },
                { key: 'nomination_closes_at', label: 'ปิดรับสมัคร', type: 'datetime-local' },
                { key: 'voting_opens_at', label: 'เปิดลงคะแนน', type: 'datetime-local' },
                { key: 'voting_closes_at', label: 'ปิดลงคะแนน', type: 'datetime-local' },
                { key: 'ballot_ttl_seconds', label: 'อายุบัตร (วินาที)', type: 'number' },
              ]"
              :key="field.key"
            >
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-200"
                >{{ field.label }}
                <input
                  v-model="(form as any)[field.key]"
                  :type="field.type"
                  :maxlength="field.key === 'title' ? 150 : undefined"
                  class="mt-1 min-h-[44px] w-full rounded-lg border border-gray-300 px-3 py-2 dark:border-gray-600 dark:bg-gray-900 dark:text-white"
                />
              </label>
              <p v-for="message in errors[field.key]" :key="message" class="text-sm text-red-600">
                {{ message }}
              </p>
            </div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200"
              >คำอธิบาย<textarea
                v-model="form.description"
                rows="3"
                class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 dark:border-gray-600 dark:bg-gray-900 dark:text-white"
              />
            </label>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200"
              >ระดับการศึกษา<select
                v-model="form.education_level"
                class="mt-1 min-h-[44px] w-full rounded-lg border border-gray-300 px-3 py-2 dark:border-gray-600 dark:bg-gray-900 dark:text-white"
              >
                <option value="">ทั้งโรงเรียน</option>
                <option value="1">ประถม</option>
                <option value="2">มัธยม</option>
              </select></label
            >
            <label
              class="flex min-h-[44px] items-center gap-3 text-sm text-gray-700 dark:text-gray-200"
              ><input v-model="form.allow_abstain" type="checkbox" class="h-5 w-5" />
              อนุญาตไม่ประสงค์ลงคะแนน</label
            >
            <p v-for="message in errors._" :key="message" class="text-sm text-red-600">
              {{ message }}
            </p>
            <div class="flex flex-col gap-3 sm:flex-row sm:justify-end">
              <button
                type="button"
                class="min-h-[44px] rounded-lg px-4 py-2 text-gray-600"
                @click="close"
              >
                ยกเลิก</button
              ><button
                :disabled="saving"
                class="min-h-[44px] rounded-lg bg-primary-600 px-4 py-2 font-medium text-white disabled:opacity-50"
              >
                {{ saving ? 'กำลังบันทึก...' : 'สร้างการเลือกตั้ง' }}
              </button>
            </div>
          </form>
        </DialogPanel>
      </div>
    </div>
  </Dialog>
</template>
