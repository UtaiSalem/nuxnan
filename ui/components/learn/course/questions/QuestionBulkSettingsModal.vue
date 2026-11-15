<script setup lang="ts">
import { ref, watch } from 'vue'
import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from '@headlessui/vue'
import { Icon } from '@iconify/vue'

/**
 * ตั้งค่าคะแนน และ/หรือ แต้มที่หักเมื่อแก้คำตอบ/ดูเฉลย ให้คำถาม "ทุกข้อ" ในชุดเดียวในคลิกเดียว
 * ใช้ได้ทั้งชุดคำถามของบทเรียน และข้อสอบของแบบทดสอบ (ส่ง endpoint ให้ตรงชุด)
 */
interface Props {
  show: boolean
  /** เช่น /api/lessons/12/questions/bulk-settings หรือ /api/courses/3/quizzes/7/questions/bulk-settings */
  endpoint: string
  questionCount: number
}

const props = defineProps<Props>()
const emit = defineEmits<{
  close: []
  saved: [payload: { points?: number, pp_fine?: number }]
}>()

const api = useApi()
const swal = useSweetAlert()

const changePoints = ref(false)
const changeFine = ref(false)
const points = ref<number | null>(1)
const ppFine = ref<number | null>(0)
const saving = ref(false)

watch(() => props.show, (open) => {
  if (!open) return
  changePoints.value = false
  changeFine.value = false
  points.value = 1
  ppFine.value = 0
})

const save = async () => {
  if (!changePoints.value && !changeFine.value) {
    swal.toast('เลือกอย่างน้อยหนึ่งอย่างที่จะเปลี่ยน', 'warning')
    return
  }

  const payload: { points?: number, pp_fine?: number } = {}
  if (changePoints.value) {
    const p = Number(points.value)
    if (!Number.isFinite(p) || p < 0.5 || p > 1000 || Math.round(p * 2) !== p * 2) {
      swal.toast('คะแนนต้องอยู่ระหว่าง 0.5–1000 และเพิ่มทีละ 0.5', 'warning')
      return
    }
    payload.points = p
  }
  if (changeFine.value) {
    const f = Number(ppFine.value)
    if (!Number.isInteger(f) || f < 0) {
      swal.toast('แต้มที่หักต้องเป็นจำนวนเต็มตั้งแต่ 0 ขึ้นไป', 'warning')
      return
    }
    payload.pp_fine = f
  }

  const parts: string[] = []
  if (payload.points !== undefined) parts.push(`คะแนนข้อละ <b>${payload.points}</b>`)
  if (payload.pp_fine !== undefined) parts.push(`แต้มที่หักเมื่อแก้คำตอบ/ดูเฉลย <b>${payload.pp_fine}</b>`)
  const ok = await swal.confirm(
    `ตั้ง ${parts.join(' และ ')} ให้คำถามทั้ง <b>${props.questionCount}</b> ข้อ` +
      (payload.points !== undefined ? '<br><small>คะแนนของคำตอบที่ส่งไปแล้วจะถูกคำนวณใหม่ตามค่านี้</small>' : ''),
    'ยืนยันการตั้งค่าทั้งชุด',
    { confirmText: 'บันทึกทุกข้อ', icon: 'warning' }
  )
  if (!ok) return

  saving.value = true
  try {
    const res: any = await api.patch(props.endpoint, payload)
    swal.toast(res?.message || 'บันทึกแล้ว', 'success')
    emit('saved', payload)
    emit('close')
  } catch (err: any) {
    // useApi เก็บ body ของ error ไว้ที่ err.data
    swal.error(err?.data?.message || 'บันทึกไม่สำเร็จ')
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <TransitionRoot appear :show="show" as="template">
    <Dialog as="div" @close="emit('close')" class="relative z-50">
      <TransitionChild
        as="template"
        enter="duration-300 ease-out"
        enter-from="opacity-0"
        enter-to="opacity-100"
        leave="duration-200 ease-in"
        leave-from="opacity-100"
        leave-to="opacity-0"
      >
        <div class="fixed inset-0 bg-black/25 backdrop-blur-sm" />
      </TransitionChild>

      <div class="fixed inset-0 overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-2 sm:p-4 text-center">
          <TransitionChild
            as="template"
            enter="duration-300 ease-out"
            enter-from="opacity-0 scale-95"
            enter-to="opacity-100 scale-100"
            leave="duration-200 ease-in"
            leave-from="opacity-100 scale-100"
            leave-to="opacity-0 scale-95"
          >
            <DialogPanel class="w-full max-w-md transform overflow-y-auto max-h-[92vh] rounded-2xl bg-white dark:bg-gray-800 p-4 sm:p-6 text-left align-middle shadow-xl transition-all">
              <DialogTitle as="h3" class="text-base sm:text-lg font-bold leading-6 text-gray-900 dark:text-white mb-1 flex items-center justify-between gap-2">
                <span class="min-w-0 break-words">ตั้งค่าทั้งชุด</span>
                <button
                  type="button"
                  aria-label="ปิด"
                  @click="emit('close')"
                  class="min-h-[44px] sm:min-h-0 min-w-[44px] sm:min-w-0 inline-flex items-center justify-center text-gray-400 hover:text-gray-500 p-2 -mr-2 flex-shrink-0"
                >
                  <Icon icon="fluent:dismiss-24-regular" class="w-5 h-5" />
                </button>
              </DialogTitle>
              <p class="text-sm text-gray-500 dark:text-gray-400 mb-4 break-words">
                ค่าที่เลือกจะถูกใช้กับคำถามทั้ง {{ questionCount }} ข้อในชุดนี้
              </p>

              <!-- คะแนน -->
              <div class="rounded-xl border border-gray-200 dark:border-gray-700 p-3 sm:p-4 mb-3">
                <label class="flex items-center gap-3 min-h-[44px] cursor-pointer">
                  <input v-model="changePoints" type="checkbox" class="w-5 h-5 rounded text-orange-500 flex-shrink-0">
                  <span class="min-w-0 flex-1 font-medium text-gray-900 dark:text-white break-words">เปลี่ยนคะแนนข้อละ</span>
                </label>
                <input
                  v-if="changePoints"
                  v-model.number="points"
                  type="number"
                  min="0.5"
                  max="1000"
                  step="0.5"
                  inputmode="decimal"
                  aria-label="คะแนนข้อละ"
                  class="mt-2 w-full sm:w-40 min-h-[44px] sm:min-h-0 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-orange-500 focus:border-orange-500"
                >
                <p v-if="changePoints" class="text-xs text-gray-500 dark:text-gray-400 mt-1 break-words">ตั้งได้ทีละ 0.5 · คะแนนของคำตอบที่ส่งไปแล้วจะคำนวณใหม่</p>
              </div>

              <!-- แต้มที่หัก -->
              <div class="rounded-xl border border-gray-200 dark:border-gray-700 p-3 sm:p-4">
                <label class="flex items-center gap-3 min-h-[44px] cursor-pointer">
                  <input v-model="changeFine" type="checkbox" class="w-5 h-5 rounded text-orange-500 flex-shrink-0">
                  <span class="min-w-0 flex-1 font-medium text-gray-900 dark:text-white break-words">เปลี่ยนแต้มที่หักเมื่อแก้คำตอบ / ดูเฉลย</span>
                </label>
                <input
                  v-if="changeFine"
                  v-model.number="ppFine"
                  type="number"
                  min="0"
                  step="1"
                  inputmode="numeric"
                  aria-label="แต้มที่หัก"
                  class="mt-2 w-full sm:w-40 min-h-[44px] sm:min-h-0 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-orange-500 focus:border-orange-500"
                >
                <p v-if="changeFine" class="text-xs text-gray-500 dark:text-gray-400 mt-1 break-words">0 = แก้คำตอบและดูเฉลยได้ฟรี</p>
              </div>

              <div class="mt-6 flex flex-col-reverse sm:flex-row sm:justify-end gap-2 sm:gap-3">
                <button
                  type="button"
                  :disabled="saving"
                  @click="emit('close')"
                  class="w-full sm:w-auto min-h-[44px] px-4 py-2 rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white text-sm font-medium hover:bg-gray-200 dark:hover:bg-gray-600"
                >
                  ยกเลิก
                </button>
                <button
                  type="button"
                  :disabled="saving || (!changePoints && !changeFine)"
                  @click="save"
                  class="w-full sm:w-auto min-h-[44px] px-4 py-2 rounded-lg bg-orange-500 text-white text-sm font-medium hover:bg-orange-600 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
                >
                  <Icon v-if="saving" icon="svg-spinners:180-ring-with-bg" class="w-4 h-4" />
                  บันทึกทุกข้อ
                </button>
              </div>
            </DialogPanel>
          </TransitionChild>
        </div>
      </div>
    </Dialog>
  </TransitionRoot>
</template>
