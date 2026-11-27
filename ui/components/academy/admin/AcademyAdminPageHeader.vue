<script setup lang="ts">
defineProps<{
  title: string
  description?: string
}>()
</script>

<template>
  <div>
    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between mb-4 sm:mb-6">
      <div class="min-w-0 flex-1">
        <h1 class="break-words text-lg sm:text-2xl font-bold text-gray-900 dark:text-white">{{ title }}</h1>
        <p v-if="description" class="mt-1 text-sm text-gray-500 dark:text-gray-400 break-words">{{ description }}</p>
      </div>
      <div v-if="$slots.actions" class="flex flex-wrap gap-2 sm:flex-shrink-0">
        <slot name="actions" />
      </div>
    </div>
    <slot />
  </div>
</template>
