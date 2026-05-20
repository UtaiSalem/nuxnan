<script setup lang="ts">
import { Icon } from '@iconify/vue'

interface Props {
  label: string
  required?: boolean
  icon?: string
  hint?: string
}

const props = withDefaults(defineProps<Props>(), {
  required: false,
  icon: '',
  hint: '',
})
</script>

<template>
  <div class="space-y-2">
    <div class="flex items-center gap-2">
      <Icon v-if="icon" :icon="icon" class="w-4 h-4 text-gray-400 dark:text-gray-500" />
      <label class="text-sm font-bold text-gray-700 dark:text-gray-300">
        {{ label }}
      </label>
      <span v-if="required" class="text-red-500 font-bold">*</span>
    </div>

    <div class="relative group">
      <slot />
    </div>

    <p v-if="hint" class="text-[11px] text-gray-500 dark:text-gray-400 leading-tight">
      {{ hint }}
    </p>
  </div>
</template>
