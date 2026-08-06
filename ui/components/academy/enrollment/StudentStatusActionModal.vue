<script setup lang="ts">
import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from '@headlessui/vue'
import { Icon } from '@iconify/vue'
import { computed, reactive, watch } from 'vue'
import type {
  ClassroomOptionDTO,
  ClassroomStudentDTO,
  AssignClassroomPayload,
  DropStudentPayload,
  EnrollmentActionPayload,
  EnrollmentFieldErrors,
  GraduateStudentPayload,
  PromoteStudentPayload,
  RepeatStudentPayload,
  StudentMenuAction,
  StudentSummaryDTO,
  TransferStudentPayload,
} from '~/types/enrollment'

interface Props {
  open: boolean
  action: StudentMenuAction | null
  student: StudentSummaryDTO | null
  enrollment: ClassroomStudentDTO | null
  availableClassrooms: ClassroomOptionDTO[]
  isLoading: boolean
  fieldErrors: EnrollmentFieldErrors
  assignError?: string
}

const props = defineProps<Props>()
const emit = defineEmits<{
  'update:open': [v: boolean]
  submit: [payload: EnrollmentActionPayload | AssignClassroomPayload]
}>()

interface FormState {
  reason: string
  effective_at: string
  new_classroom_id: number | null
  to_classroom_id: number | null
  student_number: number | null
}

const blankForm = (): FormState => ({
  reason: '',
  effective_at: '',
  new_classroom_id: null,
  to_classroom_id: null,
  student_number: null,
})

const form = reactive<FormState>(blankForm())

watch(
  () => [props.open, props.action] as const,
  ([open]) => {
    if (open) Object.assign(form, blankForm())
  },
  { immediate: true },
)

const todayIso = computed(() => new Date().toISOString().slice(0, 10))

const titleMap: Record<StudentMenuAction, string> = {
  assign: 'ลงห้องเรียน',
  graduate: 'จบการศึกษา',
  drop: 'ลาออก / พ้นสภาพ',
  repeat: 'ซ้ำชั้น',
  promote: 'เลื่อนชั้น (ปีถัดไป)',
  transfer: 'ย้ายห้อง (ในปีนี้)',
}

const confirmLabelMap: Record<StudentMenuAction, string> = {
  assign: 'ยืนยันการลงห้องเรียน',
  graduate: 'ยืนยันจบการศึกษา',
  drop: 'ยืนยันการพ้นสภาพ',
  repeat: 'ยืนยันการซ้ำชั้น',
  promote: 'ยืนยันการเลื่อนชั้น',
  transfer: 'ยืนยันการย้ายห้อง',
}

const currentGrade = computed(() => props.enrollment?.classroom?.grade_level ?? null)
const currentClassroomId = computed(() => props.enrollment?.classroom_id ?? null)

const sameGradeOptions = computed<ClassroomOptionDTO[]>(() => {
  if (!currentGrade.value) return []
  return props.availableClassrooms.filter(
    (c) => c.grade_level === currentGrade.value && c.id !== currentClassroomId.value,
  )
})

const sameYearOtherClassrooms = computed<ClassroomOptionDTO[]>(() => {
  const yearId = props.enrollment?.academic_year_id ?? null
  if (!yearId) return []
  return props.availableClassrooms.filter(
    (c) => c.academic_year_id === yearId && c.id !== currentClassroomId.value,
  )
})

const promoteOptions = computed<ClassroomOptionDTO[]>(() => {
  return props.availableClassrooms.filter((c) => c.id !== currentClassroomId.value)
})

const assignOptions = computed<ClassroomOptionDTO[]>(() => props.availableClassrooms)

interface ClassroomGroup {
  key: string
  label: string
  options: ClassroomOptionDTO[]
}

const groupedTargetClassrooms = computed<ClassroomGroup[]>(() => {
  let source: ClassroomOptionDTO[] = []
  if (props.action === 'repeat') source = sameGradeOptions.value
  else if (props.action === 'transfer') source = sameYearOtherClassrooms.value
  else if (props.action === 'promote') source = promoteOptions.value
  else if (props.action === 'assign') source = assignOptions.value

  const groups = new Map<string, ClassroomGroup>()
  for (const option of source) {
    const yearKey = option.academic_year_id != null ? String(option.academic_year_id) : 'unknown'
    const label = option.academic_year_name ? `ปี ${option.academic_year_name}` : 'ไม่ระบุปี'
    if (!groups.has(yearKey)) {
      groups.set(yearKey, { key: yearKey, label, options: [] })
    }
    groups.get(yearKey)!.options.push(option)
  }
  return [...groups.values()].sort((a, b) => a.label.localeCompare(b.label, 'th'))
})

const noTargetClassrooms = computed(() => groupedTargetClassrooms.value.length === 0)

const requiresReason = computed(() => props.action === 'drop')
const requiresNewClassroom = computed(() => props.action === 'repeat')
const requiresToClassroom = computed(() => props.action === 'promote' || props.action === 'transfer' || props.action === 'assign')

const canSubmit = computed(() => {
  if (!props.action || !props.student) return false
  if (requiresReason.value && !form.reason.trim()) return false
  if (requiresNewClassroom.value && !form.new_classroom_id) return false
  if (requiresToClassroom.value && !form.to_classroom_id) return false
  return true
})

const isDanger = computed(() => props.action === 'drop')

function close() {
  if (props.isLoading) return
  emit('update:open', false)
}

function buildPayload(): EnrollmentActionPayload | AssignClassroomPayload | null {
  if (!props.action) return null
  const reason = form.reason.trim() || undefined
  const effective_at = form.effective_at || undefined

  switch (props.action) {
    case 'assign':
      return { to_classroom_id: form.to_classroom_id as number } as AssignClassroomPayload
    case 'graduate':
      return { reason, effective_at } as GraduateStudentPayload & { effective_at?: string }
    case 'drop':
      return { reason: form.reason.trim(), effective_at } as DropStudentPayload & { effective_at?: string }
    case 'repeat':
      return {
        new_classroom_id: form.new_classroom_id as number,
        student_number: form.student_number ?? undefined,
        reason,
      } as RepeatStudentPayload
    case 'promote':
      return {
        from_classroom_id: currentClassroomId.value as number,
        to_classroom_id: form.to_classroom_id as number,
        student_number: form.student_number ?? undefined,
        reason,
      } as PromoteStudentPayload
    case 'transfer':
      return {
        from_classroom_id: currentClassroomId.value as number,
        to_classroom_id: form.to_classroom_id as number,
        reason,
      } as TransferStudentPayload
    default:
      return null
  }
}

function onSubmit() {
  if (!canSubmit.value) return
  const payload = buildPayload()
  if (!payload) return
  emit('submit', payload)
}
</script>

<template>
  <TransitionRoot :show="open" as="template">
    <Dialog class="relative z-50" @close="close">
      <TransitionChild
        as="template"
        enter="ease-out duration-200"
        enter-from="opacity-0"
        enter-to="opacity-100"
        leave="ease-in duration-150"
        leave-from="opacity-100"
        leave-to="opacity-0"
      >
        <div class="fixed inset-0 bg-black/40 backdrop-blur-sm" aria-hidden="true" />
      </TransitionChild>

      <div class="fixed inset-0 flex items-center justify-center p-4">
        <TransitionChild
          as="template"
          enter="ease-out duration-200"
          enter-from="opacity-0 scale-95"
          enter-to="opacity-100 scale-100"
          leave="ease-in duration-150"
          leave-from="opacity-100 scale-100"
          leave-to="opacity-0 scale-95"
        >
          <DialogPanel
            class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl dark:bg-zinc-900"
          >
            <DialogTitle class="text-lg font-semibold mb-4 text-zinc-900 dark:text-zinc-100">
              {{ action ? titleMap[action] : '' }}
            </DialogTitle>

            <div v-if="student" class="mb-4 rounded-lg bg-zinc-50 p-3 dark:bg-zinc-800">
              <div class="text-sm font-medium text-zinc-900 dark:text-zinc-100">
                {{ student.first_name_th }} {{ student.last_name_th }}
              </div>
              <div class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">
                รหัส {{ student.student_id }} ·
                {{ enrollment?.classroom?.display_name ?? 'ไม่มีห้องปัจจุบัน' }}
              </div>
            </div>

            <form class="space-y-3" @submit.prevent="onSubmit">
              <p v-if="assignError" class="text-sm text-rose-600 dark:text-rose-400">
                {{ assignError }}
              </p>
              <template v-if="action === 'graduate' || action === 'drop'">
                <div>
                  <label class="mb-1 block text-sm text-zinc-700 dark:text-zinc-300">
                    เหตุผล
                    <span v-if="requiresReason" class="text-rose-500">*</span>
                  </label>
                  <input
                    v-model="form.reason"
                    type="text"
                    maxlength="255"
                    :placeholder="action === 'graduate' ? 'จบการศึกษา (default)' : 'ระบุเหตุผล'"
                    class="w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 placeholder-zinc-400 focus:border-zinc-500 focus:outline-none focus:ring-1 focus:ring-zinc-500 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100"
                  />
                  <p v-if="fieldErrors.reason" class="mt-1 text-xs text-rose-600 dark:text-rose-400">
                    {{ fieldErrors.reason }}
                  </p>
                </div>

                <div>
                  <label class="mb-1 block text-sm text-zinc-700 dark:text-zinc-300">
                    วันที่มีผล (optional)
                  </label>
                  <input
                    v-model="form.effective_at"
                    type="date"
                    :max="todayIso"
                    class="w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-500 focus:outline-none focus:ring-1 focus:ring-zinc-500 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100"
                  />
                  <p v-if="fieldErrors.effective_at" class="mt-1 text-xs text-rose-600 dark:text-rose-400">
                    {{ fieldErrors.effective_at }}
                  </p>
                </div>
              </template>

              <template v-else-if="action === 'repeat'">
                <div>
                  <label class="mb-1 block text-sm text-zinc-700 dark:text-zinc-300">
                    ห้องใหม่ (ระดับเดียวกัน) <span class="text-rose-500">*</span>
                  </label>
                  <select
                    v-model="form.new_classroom_id"
                    class="w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-500 focus:outline-none focus:ring-1 focus:ring-zinc-500 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100"
                  >
                    <option :value="null">-- เลือกห้อง --</option>
                    <optgroup
                      v-for="group in groupedTargetClassrooms"
                      :key="group.key"
                      :label="group.label"
                    >
                      <option v-for="c in group.options" :key="c.id" :value="c.id">
                        {{ c.display_name }}
                      </option>
                    </optgroup>
                  </select>
                  <p v-if="noTargetClassrooms" class="mt-1 text-xs text-amber-600 dark:text-amber-400">
                    ไม่พบห้องระดับเดียวกันให้เลือก
                  </p>
                  <p v-if="fieldErrors.new_classroom_id" class="mt-1 text-xs text-rose-600 dark:text-rose-400">
                    {{ fieldErrors.new_classroom_id }}
                  </p>
                </div>

                <div>
                  <label class="mb-1 block text-sm text-zinc-700 dark:text-zinc-300">
                    เลขที่ใหม่ (optional)
                  </label>
                  <input
                    v-model.number="form.student_number"
                    type="number"
                    min="1"
                    class="w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-500 focus:outline-none focus:ring-1 focus:ring-zinc-500 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100"
                  />
                  <p v-if="fieldErrors.student_number" class="mt-1 text-xs text-rose-600 dark:text-rose-400">
                    {{ fieldErrors.student_number }}
                  </p>
                </div>

                <div>
                  <label class="mb-1 block text-sm text-zinc-700 dark:text-zinc-300">
                    เหตุผล (optional)
                  </label>
                  <input
                    v-model="form.reason"
                    type="text"
                    maxlength="255"
                    class="w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-500 focus:outline-none focus:ring-1 focus:ring-zinc-500 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100"
                  />
                  <p v-if="fieldErrors.reason" class="mt-1 text-xs text-rose-600 dark:text-rose-400">
                    {{ fieldErrors.reason }}
                  </p>
                </div>
              </template>

              <template v-else-if="action === 'promote' || action === 'transfer' || action === 'assign'">
                <div>
                  <label class="mb-1 block text-sm text-zinc-700 dark:text-zinc-300">
                    ห้องปลายทาง <span class="text-rose-500">*</span>
                  </label>
                  <select
                    v-model="form.to_classroom_id"
                    class="w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-500 focus:outline-none focus:ring-1 focus:ring-zinc-500 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100"
                  >
                    <option :value="null">-- เลือกห้อง --</option>
                    <optgroup
                      v-for="group in groupedTargetClassrooms"
                      :key="group.key"
                      :label="group.label"
                    >
                      <option v-for="c in group.options" :key="c.id" :value="c.id">
                        {{ c.display_name }}
                      </option>
                    </optgroup>
                  </select>
                  <p v-if="noTargetClassrooms" class="mt-1 text-xs text-amber-600 dark:text-amber-400">
                    ไม่พบห้องเป้าหมายให้เลือก
                  </p>
                  <p v-if="fieldErrors.to_classroom_id" class="mt-1 text-xs text-rose-600 dark:text-rose-400">
                    {{ fieldErrors.to_classroom_id }}
                  </p>
                </div>

                <div v-if="action === 'promote'">
                  <label class="mb-1 block text-sm text-zinc-700 dark:text-zinc-300">
                    เลขที่ใหม่ (optional)
                  </label>
                  <input
                    v-model.number="form.student_number"
                    type="number"
                    min="1"
                    class="w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-500 focus:outline-none focus:ring-1 focus:ring-zinc-500 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100"
                  />
                  <p v-if="fieldErrors.student_number" class="mt-1 text-xs text-rose-600 dark:text-rose-400">
                    {{ fieldErrors.student_number }}
                  </p>
                </div>

                <div v-if="action !== 'assign'">
                  <label class="mb-1 block text-sm text-zinc-700 dark:text-zinc-300">
                    เหตุผล (optional)
                  </label>
                  <input
                    v-model="form.reason"
                    type="text"
                    maxlength="255"
                    class="w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-500 focus:outline-none focus:ring-1 focus:ring-zinc-500 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100"
                  />
                  <p v-if="fieldErrors.reason" class="mt-1 text-xs text-rose-600 dark:text-rose-400">
                    {{ fieldErrors.reason }}
                  </p>
                </div>
              </template>

              <div
                class="mt-2 flex justify-end gap-2 border-t border-zinc-200 pt-3 dark:border-zinc-700"
              >
                <button
                  type="button"
                  :disabled="isLoading"
                  class="rounded-md px-4 py-2 text-sm text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800 disabled:opacity-50"
                  @click="close"
                >
                  ยกเลิก
                </button>
                <button
                  type="submit"
                  :disabled="isLoading || !canSubmit"
                  :class="[
                    'inline-flex items-center gap-1.5 rounded-md px-4 py-2 text-sm font-medium text-white transition disabled:opacity-50',
                    isDanger
                      ? 'bg-rose-600 hover:bg-rose-700'
                      : 'bg-indigo-600 hover:bg-indigo-700',
                  ]"
                >
                  <Icon
                    v-if="isLoading"
                    icon="mdi:loading"
                    class="w-4 h-4 animate-spin"
                  />
                  {{ action ? confirmLabelMap[action] : '' }}
                </button>
              </div>
            </form>
          </DialogPanel>
        </TransitionChild>
      </div>
    </Dialog>
  </TransitionRoot>
</template>
