<script setup lang="ts">
import { Icon } from '@iconify/vue'
import { useFraudReports, type FraudCategory } from '~/composables/useFraudReports'

interface Props {
  show: boolean
  reportedUserId: number | null
  reportedUserName?: string
  /** Optional transaction this report is tied to. */
  transactionType?: 'points' | 'wallet' | null
  transactionId?: number | null
}

const props = withDefaults(defineProps<Props>(), {
  reportedUserName: '',
  transactionType: null,
  transactionId: null,
})

const emit = defineEmits<{ (e: 'close'): void; (e: 'submitted'): void }>()

const { submitReport, FRAUD_CATEGORY_OPTIONS } = useFraudReports()
const { parseApiError } = useApiError()
const toast = useToast()

const category = ref<FraudCategory>('money_fraud')
const description = ref('')
const evidenceNote = ref('')
const isSubmitting = ref(false)
const errorMessage = ref('')

const resetForm = () => {
  category.value = props.transactionType === 'points' ? 'point_fraud' : 'money_fraud'
  description.value = ''
  evidenceNote.value = ''
  errorMessage.value = ''
}

// Reset whenever the modal opens so a reused modal never shows stale input.
watch(() => props.show, (open) => {
  if (open) resetForm()
})

const close = () => {
  if (isSubmitting.value) return
  emit('close')
}

const submit = async () => {
  errorMessage.value = ''

  if (!props.reportedUserId) {
    errorMessage.value = 'ไม่พบบัญชีที่ต้องการร้องเรียน'
    return
  }
  if (description.value.trim().length < 10) {
    errorMessage.value = 'กรุณาอธิบายรายละเอียดอย่างน้อย 10 ตัวอักษร'
    return
  }

  isSubmitting.value = true
  try {
    await submitReport({
      reported_user_id: props.reportedUserId,
      category: category.value,
      description: description.value.trim(),
      related_transaction_type: props.transactionType,
      related_transaction_id: props.transactionId,
      evidence_note: evidenceNote.value.trim() || null,
    })
    toast.success('ส่งคำร้องเรียนเรียบร้อยแล้ว ทีมงานจะตรวจสอบโดยเร็วที่สุด')
    emit('submitted')
    emit('close')
  } catch (err: any) {
    errorMessage.value = parseApiError(err, 'ไม่สามารถส่งคำร้องเรียนได้ กรุณาลองใหม่').message
  } finally {
    isSubmitting.value = false
  }
}
</script>

<template>
  <Modal :show="show" max-width="lg" :closeable="!isSubmitting" @close="close">
    <div class="p-4 sm:p-6">
      <!-- Header -->
      <div class="flex items-start gap-3">
        <div class="flex-shrink-0 w-11 h-11 rounded-xl bg-red-100 dark:bg-red-900/30 flex items-center justify-center">
          <Icon icon="fluent:shield-error-24-filled" class="w-6 h-6 text-red-600 dark:text-red-400" />
        </div>
        <div class="min-w-0 flex-1">
          <h3 class="text-base sm:text-lg font-bold text-gray-900 dark:text-white break-words">
            ร้องเรียนบัญชีทุจริต
          </h3>
          <p v-if="reportedUserName" class="text-sm text-gray-500 dark:text-gray-400 break-words">
            บัญชี: <span class="font-semibold text-gray-700 dark:text-gray-200">{{ reportedUserName }}</span>
          </p>
        </div>
        <button
          type="button"
          class="flex-shrink-0 min-h-[44px] min-w-[44px] sm:min-h-0 sm:min-w-0 inline-flex items-center justify-center p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 rounded-lg transition-colors"
          :disabled="isSubmitting"
          @click="close"
        >
          <Icon icon="fluent:dismiss-24-regular" class="w-5 h-5" />
        </button>
      </div>

      <!-- Transaction context (when reporting from a transaction) -->
      <div
        v-if="transactionType && transactionId"
        class="mt-4 p-3 rounded-xl bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 text-sm text-amber-800 dark:text-amber-300 break-words"
      >
        <Icon icon="fluent:receipt-24-regular" class="inline w-4 h-4 mr-1 align-text-bottom" />
        แนบรายการธุรกรรม {{ transactionType === 'wallet' ? 'Wallet' : 'แต้ม' }} #{{ transactionId }}
      </div>

      <!-- Form -->
      <form class="mt-4 space-y-4" @submit.prevent="submit">
        <!-- Category -->
        <div>
          <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200 mb-1.5">
            ประเภทการทุจริต
          </label>
          <select
            v-model="category"
            class="w-full min-h-[44px] px-3 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-red-500 focus:border-red-500"
          >
            <option v-for="opt in FRAUD_CATEGORY_OPTIONS" :key="opt.value" :value="opt.value">
              {{ opt.label }}
            </option>
          </select>
        </div>

        <!-- Description -->
        <div>
          <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200 mb-1.5">
            รายละเอียด <span class="text-red-500">*</span>
          </label>
          <textarea
            v-model="description"
            rows="4"
            maxlength="2000"
            placeholder="อธิบายสิ่งที่เกิดขึ้น เช่น หลอกโอนเงิน ไม่ส่งของ โกงแต้ม ฯลฯ"
            class="w-full px-3 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-red-500 focus:border-red-500 resize-y break-words"
          ></textarea>
          <p class="mt-1 text-xs text-gray-400 text-right">{{ description.length }}/2000</p>
        </div>

        <!-- Evidence note -->
        <div>
          <label class="block text-sm font-semibold text-gray-700 dark:text-gray-200 mb-1.5">
            หลักฐานเพิ่มเติม (ไม่บังคับ)
          </label>
          <textarea
            v-model="evidenceNote"
            rows="2"
            maxlength="2000"
            placeholder="ลิงก์สลิป โพสต์ หรือข้อมูลอ้างอิงอื่น ๆ"
            class="w-full px-3 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-red-500 focus:border-red-500 resize-y break-words"
          ></textarea>
        </div>

        <!-- Error -->
        <p v-if="errorMessage" class="text-sm text-red-600 dark:text-red-400 break-words">
          {{ errorMessage }}
        </p>

        <!-- Actions -->
        <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2 pt-2">
          <button
            type="button"
            class="min-h-[44px] px-4 py-2.5 rounded-xl text-sm font-semibold text-gray-700 dark:text-gray-200 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
            :disabled="isSubmitting"
            @click="close"
          >
            ยกเลิก
          </button>
          <button
            type="submit"
            class="min-h-[44px] px-5 py-2.5 rounded-xl text-sm font-bold text-white bg-red-600 hover:bg-red-700 disabled:opacity-60 transition-colors flex items-center justify-center gap-2"
            :disabled="isSubmitting"
          >
            <Icon v-if="isSubmitting" icon="fluent:spinner-ios-20-regular" class="w-5 h-5 animate-spin" />
            <Icon v-else icon="fluent:send-24-filled" class="w-5 h-5" />
            ส่งคำร้องเรียน
          </button>
        </div>
      </form>
    </div>
  </Modal>
</template>
