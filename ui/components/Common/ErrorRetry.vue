<script setup lang="ts">
import { Icon } from '@iconify/vue'

interface Props {
  message?: string
  /** Show as inline (compact) or block */
  variant?: 'inline' | 'block'
}
withDefaults(defineProps<Props>(), {
  message: 'โหลดข้อมูลไม่สำเร็จ',
  variant: 'block',
})
const emit = defineEmits<{ retry: [] }>()
</script>

<template>
  <div
    :class="[
      variant === 'block'
        ? 'bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800/50 rounded-xl py-6 px-4 text-center'
        : 'text-xs text-red-600 dark:text-red-400 py-2 text-center'
    ]"
    role="alert"
  >
    <Icon
      icon="heroicons:exclamation-triangle"
      :class="variant === 'block' ? 'w-8 h-8 text-red-500 mx-auto mb-2' : 'w-4.5 h-4.5 inline mr-1 align-text-bottom'"
    />
    <p :class="variant === 'block' ? 'text-sm text-red-700 dark:text-red-300 mb-3' : 'inline'">
      {{ message }}
    </p>
    <button class="min-h-[44px] sm:min-h-0"
      type="button"
      :class="[
        'inline-flex items-center gap-1 font-semibold focus:outline-none focus:ring-2 focus:ring-red-500 rounded',
        variant === 'block'
          ? 'px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 text-sm'
          : 'underline text-red-600 dark:text-red-400 ml-2 text-xs'
      ]"
      @click="emit('retry')"
    >
      <Icon icon="heroicons:arrow-path" class="w-3.5 h-3.5" />
      ลองอีกครั้ง
    </button>
  </div>
</template>
