<script setup lang="ts">
import { computed } from 'vue'
import { Icon } from '@iconify/vue'

const props = defineProps<{
  system: 'points' | 'wallet'
}>()

const title = computed(() =>
  props.system === 'points'
    ? 'ระบบสะสมแต้มถูกระงับชั่วคราว'
    : 'ระบบกระเป๋าเงิน (Wallet) ถูกระงับชั่วคราว'
)

const detail = computed(() =>
  props.system === 'points'
    ? 'บัญชีของคุณอยู่ระหว่างการตรวจสอบ จึงถูกระงับการสะสม โอน และแปลงแต้มไว้ชั่วคราว คุณยังสามารถเข้าเรียนได้ตามปกติ หากมีข้อสงสัยกรุณาติดต่อผู้ดูแลระบบ'
    : 'บัญชีของคุณอยู่ระหว่างการตรวจสอบ จึงถูกระงับการถอน โอน เติม และแปลงเงินใน Wallet ไว้ชั่วคราว คุณยังสามารถเข้าเรียนได้ตามปกติ หากมีข้อสงสัยกรุณาติดต่อผู้ดูแลระบบ'
)
</script>

<template>
  <div
    class="flex items-start gap-3 rounded-xl border border-amber-300 bg-amber-50 p-3 sm:p-4 dark:border-amber-500/40 dark:bg-amber-900/20"
    role="alert"
  >
    <Icon
      icon="fluent:shield-error-24-filled"
      class="mt-0.5 h-6 w-6 flex-shrink-0 text-amber-600 dark:text-amber-400"
    />
    <div class="min-w-0 flex-1">
      <p class="font-semibold text-amber-800 break-words dark:text-amber-200">{{ title }}</p>
      <p class="mt-1 text-sm text-amber-700 break-words dark:text-amber-300/90">{{ detail }}</p>
    </div>
  </div>
</template>
