<script setup lang="ts">
defineProps({
  noPadding: {
    type: Boolean,
    default: false
  }
})
</script>

<template>
  <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
    <div :class="{ 'p-4': !noPadding }">
      <slot />
    </div>
  </div>
</template>
