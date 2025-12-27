<script setup lang="ts">
import BaseIcon from '../atoms/BaseIcon.vue'

defineProps({
  tag: {
    type: String,
    required: true
  },
  count: {
    type: String,
    required: true
  }
})
</script>

<template>
  <div class="flex items-center justify-between py-3 border-b border-gray-100 dark:border-gray-700 last:border-0 hover:bg-gray-50 dark:hover:bg-gray-800 px-2 rounded transition-colors cursor-pointer">
    <div class="flex items-center gap-3">
      <div class="w-8 h-8 rounded-full bg-blue-100 dark:bg-blue-900 flex items-center justify-center text-blue-600 dark:text-blue-300">
        <BaseIcon name="lni:thunder" size="sm" />
      </div>
      <div>
        <h4 class="text-sm font-semibold text-gray-800 dark:text-gray-200">{{ tag }}</h4>
        <span class="text-xs text-gray-500">{{ count }}</span>
      </div>
    </div>
  </div>
</template>
