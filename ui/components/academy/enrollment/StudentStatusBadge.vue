<script setup lang="ts">
import { Icon } from '@iconify/vue'
import { computed } from 'vue'
import {
  getEnrollmentStatusLabel,
  getEnrollmentStatusStyle,
} from '~/types/enrollment'
import type { EnrollmentStatus } from '~/types/enrollment'

interface Props {
  status: EnrollmentStatus | string | null | undefined
  statusText?: string | null
  size?: 'sm' | 'md'
}

const props = withDefaults(defineProps<Props>(), {
  statusText: null,
  size: 'sm',
})

const style = computed(() => getEnrollmentStatusStyle(props.status))
const label = computed(() => getEnrollmentStatusLabel(props.status, props.statusText))
const sizeClass = computed(() => {
  return props.size === 'md'
    ? 'px-3 py-1.5 text-sm gap-1.5'
    : 'px-2.5 py-1 text-xs gap-1'
})
const iconClass = computed(() => {
  return props.size === 'md' ? 'w-4 h-4' : 'w-3.5 h-3.5'
})
</script>

<template>
  <span
    :class="[
      'inline-flex items-center rounded-full border font-medium whitespace-nowrap',
      style.bgClass,
      style.textClass,
      style.borderClass,
      sizeClass,
    ]"
  >
    <Icon :icon="style.icon" :class="iconClass" />
    <span>{{ label }}</span>
  </span>
</template>
