<script setup lang="ts">
import { computed } from 'vue'

interface Props {
  width?: string
  height?: string
  rounded?: 'sm' | 'md' | 'lg' | 'xl' | 'full'
  shimmer?: boolean
}
const props = withDefaults(defineProps<Props>(), { rounded: 'md', shimmer: true })

const roundedClass = computed(() => {
  const map = {
    sm: 'rounded-sm',
    md: 'rounded-md',
    lg: 'rounded-lg',
    xl: 'rounded-xl',
    full: 'rounded-full'
  }
  return map[props.rounded] || 'rounded-md'
})
</script>

<template>
  <div
    :class="[
      'bg-gray-200 dark:bg-gray-700 inline-block',
      roundedClass,
      shimmer && 'animate-pulse motion-reduce:animate-none',
    ]"
    :style="{ width, height }"
    aria-hidden="true"
  />
</template>
