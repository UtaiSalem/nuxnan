<script setup lang="ts">
import { Icon } from '@iconify/vue'

defineProps<{
  state: 'loading' | 'empty' | 'error'
  title?: string
  message?: string
}>()

const emit = defineEmits<{
  retry: []
}>()
</script>

<template>
  <div
    :role="state === 'error' ? 'alert' : 'status'"
    :aria-busy="state === 'loading'"
    class="flex flex-col items-center gap-3 px-3 py-10 text-center sm:py-16"
  >
    <template v-if="state === 'loading'">
      <div aria-hidden="true" class="animate-spin rounded-full h-12 w-12 border-4 border-primary-500 border-t-transparent"></div>
      <p class="break-words text-sm text-gray-600 dark:text-gray-300">{{ title || 'กำลังโหลด…' }}</p>
    </template>
    <template v-else>
      <Icon
        :icon="state === 'empty' ? 'fluent:document-search-24-regular' : 'fluent:error-circle-24-regular'"
        aria-hidden="true"
        :class="['h-12 w-12', state === 'empty' ? 'text-gray-400 dark:text-gray-500' : 'text-red-500 dark:text-red-400']"
      />
      <h2 class="break-words text-lg font-semibold text-gray-900 dark:text-white">
        {{ title || (state === 'empty' ? 'ยังไม่มีข้อมูล' : 'โหลดข้อมูลไม่สำเร็จ') }}
      </h2>
    </template>
    <p v-if="message" class="max-w-md break-words text-sm text-gray-500 dark:text-gray-400">{{ message }}</p>
    <button
      v-if="state === 'error'"
      type="button"
      class="min-h-[44px] rounded-lg bg-primary-500 px-4 py-2 text-sm font-medium text-white hover:bg-primary-600 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 focus-visible:ring-offset-2 dark:hover:bg-primary-400 dark:focus-visible:ring-offset-gray-900 sm:min-h-0"
      @click="emit('retry')"
    >
      ลองใหม่
    </button>
    <slot name="action" />
  </div>
</template>
