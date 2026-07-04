<template>
  <div v-if="errors?.length || warnings?.length" class="space-y-1">
    <div v-for="(err, idx) in errors" :key="'e'+idx" class="inline-flex items-center gap-1 text-xs font-medium text-red-700 bg-red-100 dark:bg-red-900/30 dark:text-red-400 px-2 py-0.5 rounded mr-1 mb-1">
      <Icon icon="fluent:dismiss-circle-24-regular" class="w-3 h-3" />
      {{ err.message }}
    </div>
    <div v-for="(warn, idx) in warnings" :key="'w'+idx" class="inline-flex items-center gap-1 text-xs font-medium text-amber-700 bg-amber-100 dark:bg-amber-900/30 dark:text-amber-400 px-2 py-0.5 rounded mr-1 mb-1">
      <Icon icon="fluent:warning-24-regular" class="w-3 h-3" />
      {{ warn.message }}
    </div>
  </div>
  <div v-else-if="status === 'valid' || status === 'imported'" class="inline-flex items-center gap-1 text-xs font-medium text-green-700 bg-green-100 dark:bg-green-900/30 dark:text-green-400 px-2 py-0.5 rounded">
    <Icon icon="fluent:checkmark-circle-24-regular" class="w-3 h-3" />
    พร้อมนำเข้า
  </div>
</template>

<script setup lang="ts">
import type { ImportRowError, ImportRowStatus } from '../../../types/studentImport'

defineProps<{
  errors?: ImportRowError[] | null
  warnings?: ImportRowError[] | null
  status: ImportRowStatus
}>()
</script>
