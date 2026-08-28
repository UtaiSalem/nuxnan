<template>
  <TransitionRoot :show="visible" as="template">
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
          <DialogPanel class="w-full max-w-md rounded-xl bg-white p-4 sm:p-6 shadow-xl dark:bg-gray-900">
            <DialogTitle class="text-lg font-bold mb-4 text-gray-900 dark:text-white">
              เปิดบัญชีนักเรียน
            </DialogTitle>

            <div class="space-y-4">
              <div v-if="student" class="flex items-center gap-4 bg-gray-50 dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700">
                <div class="w-12 h-12 rounded-full bg-blue-100 dark:bg-blue-900/40 flex items-center justify-center text-blue-600 dark:text-blue-400">
                  <Icon icon="fluent:person-24-regular" class="w-6 h-6" />
                </div>
                <div>
                  <h4 class="font-bold text-gray-900 dark:text-white">{{ student.first_name_th }} {{ student.last_name_th }}</h4>
                  <p class="text-sm text-gray-500 dark:text-gray-400">รหัส: {{ student.student_id }}</p>
                </div>
              </div>

              <div v-if="!invitationLink" class="text-center py-6">
                <Icon icon="fluent:mail-alert-24-regular" class="w-16 h-16 mx-auto text-gray-400 mb-4" />
                <p class="text-gray-600 dark:text-gray-300 mb-6">
                  นักเรียนคนนี้ยังไม่มีบัญชีเข้าใช้งานระบบ คุณสามารถสร้างลิงก์เชิญเพื่อให้นักเรียน (หรือผู้ปกครอง) นำไปเปิดบัญชีได้
                </p>
                <button
                  @click="generateLink"
                  :disabled="isGenerating"
                  class="btn-primary w-full flex justify-center items-center gap-2"
                >
                  <Icon v-if="isGenerating" icon="fluent:spinner-ios-20-regular" class="w-5 h-5 animate-spin" />
                  <Icon v-else icon="fluent:link-24-regular" class="w-5 h-5" />
                  สร้างลิงก์เปิดบัญชี
                </button>
              </div>

              <div v-else class="space-y-4">
                <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 p-4 rounded-xl">
                  <h4 class="font-bold text-green-800 dark:text-green-400 flex items-center gap-2 mb-2">
                    <Icon icon="fluent:checkmark-circle-24-filled" class="w-5 h-5" />
                    สร้างลิงก์สำเร็จ
                  </h4>
                  <p class="text-sm text-green-700 dark:text-green-500 mb-4">
                    คัดลอกลิงก์ด้านล่างแล้วส่งให้นักเรียนเพื่อทำการตั้งรหัสผ่านและเปิดบัญชี (ลิงก์หมดอายุใน 7 วัน)
                  </p>

                  <div class="flex gap-2">
                    <input
                      type="text"
                      readonly
                      :value="invitationLink"
                      class="min-w-0 form-input flex-1 bg-white dark:bg-gray-900 text-sm"
                    />
                    <button @click="copyLink" class="btn-secondary whitespace-nowrap flex flex-shrink-0 items-center gap-2">
                      <Icon :icon="copied ? 'fluent:checkmark-24-regular' : 'fluent:copy-24-regular'" class="w-4 h-4" />
                      {{ copied ? 'คัดลอกแล้ว' : 'คัดลอก' }}
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <div class="mt-6 flex justify-end">
              <button @click="close" class="btn-ghost">ปิด</button>
            </div>
          </DialogPanel>
        </TransitionChild>
      </div>
    </Dialog>
  </TransitionRoot>
</template>

<script setup lang="ts">
import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from '@headlessui/vue'
import { Icon } from '@iconify/vue'
import { ref, computed } from 'vue'
import { useStudentAccountService } from '~/services/studentAccountService'

const props = defineProps<{
  modelValue: boolean
  academyId: number | null
  student: any | null
}>()

const emit = defineEmits(['update:modelValue', 'invited'])

const visible = computed({
  get: () => props.modelValue,
  set: (val) => emit('update:modelValue', val)
})

const { generateInvitation } = useStudentAccountService()

const isGenerating = ref(false)
const invitationLink = ref('')
const copied = ref(false)

const generateLink = async () => {
  if (!props.student || !props.academyId) return

  isGenerating.value = true
  try {
    const res = await generateInvitation(String(props.academyId), props.student.id)
    if (res?.data?.token) {
      const baseUrl = window.location.origin
      invitationLink.value = `${baseUrl}/activate-student/${res.data.token}`
      emit('invited')
    }
  } catch (error) {
    console.error(error)
  } finally {
    isGenerating.value = false
  }
}

const copyLink = () => {
  navigator.clipboard.writeText(invitationLink.value)
  copied.value = true
  setTimeout(() => copied.value = false, 2000)
}

const close = () => {
  visible.value = false
  // Reset for next time
  setTimeout(() => {
    invitationLink.value = ''
    copied.value = false
  }, 300)
}
</script>
