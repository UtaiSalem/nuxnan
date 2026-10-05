<script setup lang="ts">
import { ref, computed } from 'vue'
import { Icon } from '@iconify/vue'

type AlertType = 'success' | 'error' | 'warning' | 'info'

interface Props {
  type?: AlertType
  message: string
  /** Optional technical detail, shown behind a "ดูรายละเอียด" toggle. */
  detail?: string
  dismissible?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  type: 'info',
  detail: '',
  dismissible: false
})

const emit = defineEmits<{ (e: 'close'): void }>()

const showDetail = ref(false)

const STYLES: Record<AlertType, { wrap: string; icon: string; iconName: string }> = {
  success: {
    wrap: 'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800 text-green-800 dark:text-green-300',
    icon: 'text-green-500 dark:text-green-400',
    iconName: 'fluent:checkmark-circle-24-filled'
  },
  error: {
    wrap: 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800 text-red-800 dark:text-red-300',
    icon: 'text-red-500 dark:text-red-400',
    iconName: 'fluent:error-circle-24-filled'
  },
  warning: {
    wrap: 'bg-amber-50 dark:bg-amber-900/20 border-amber-200 dark:border-amber-800 text-amber-800 dark:text-amber-300',
    icon: 'text-amber-500 dark:text-amber-400',
    iconName: 'fluent:warning-24-filled'
  },
  info: {
    wrap: 'bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-800 text-blue-800 dark:text-blue-300',
    icon: 'text-blue-500 dark:text-blue-400',
    iconName: 'fluent:info-24-filled'
  }
}

const style = computed(() => STYLES[props.type])
</script>

<template>
  <div class="rounded-xl border p-3 sm:p-4 flex items-start gap-3" :class="style.wrap" role="alert">
    <Icon :icon="style.iconName" class="w-5 h-5 flex-shrink-0 mt-0.5" :class="style.icon" />
    <div class="min-w-0 flex-1">
      <p class="text-sm font-medium break-words">{{ message }}</p>

      <template v-if="detail">
        <button
          type="button"
          class="mt-1 text-xs underline underline-offset-2 opacity-80 hover:opacity-100"
          @click="showDetail = !showDetail"
        >
          {{ showDetail ? 'ซ่อนรายละเอียด' : 'ดูรายละเอียดทางเทคนิค' }}
        </button>
        <pre
          v-if="showDetail"
          class="mt-2 p-2 rounded-lg bg-black/5 dark:bg-black/30 text-xs whitespace-pre-wrap break-words overflow-x-auto max-h-48 overflow-y-auto"
        >{{ detail }}</pre>
      </template>
    </div>

    <button
      v-if="dismissible"
      type="button"
      class="flex-shrink-0 min-h-[32px] min-w-[32px] inline-flex items-center justify-center opacity-60 hover:opacity-100"
      aria-label="ปิด"
      @click="emit('close')"
    >
      <Icon icon="fluent:dismiss-24-regular" class="w-4 h-4" />
    </button>
  </div>
</template>
