<script setup lang="ts">
interface Props {
  label: string
  error?: string
  required?: boolean
  hint?: string
}
defineProps<Props>()
const id = useId()
</script>

<template>
  <div class="flex flex-col">
    <label :for="id" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1.5">
      {{ label }}
      <span v-if="required" class="text-red-500" aria-hidden="true">*</span>
      <span v-if="required" class="sr-only">(จำเป็น)</span>
    </label>
    <slot :id="id" :describedBy="error ? `${id}-error` : (hint ? `${id}-hint` : undefined)" />
    <p v-if="error" :id="`${id}-error`" class="mt-1.5 text-xs font-medium text-red-500 flex items-center gap-1" role="alert">
      <span class="inline-block w-1.5 h-1.5 rounded-full bg-red-500"></span>
      {{ error }}
    </p>
    <p v-else-if="hint" :id="`${id}-hint`" class="mt-1 text-xs text-gray-500 dark:text-gray-400">
      {{ hint }}
    </p>
  </div>
</template>
