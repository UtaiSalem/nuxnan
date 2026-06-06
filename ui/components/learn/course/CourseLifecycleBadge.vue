<script setup lang="ts">
import { Icon } from '@iconify/vue'
import { computed } from 'vue'
import { useCourseLifecycle, type LifecycleCourseShape } from '~/composables/useCourseLifecycle'

const props = withDefaults(
  defineProps<{
    course: LifecycleCourseShape | null | undefined
    /** Hide the badge entirely when the course is still in the default Active state. */
    hideWhenActive?: boolean
    size?: 'sm' | 'md'
  }>(),
  { hideWhenActive: false, size: 'sm' }
)

const { badge, state } = useCourseLifecycle(computed(() => props.course))

const sizeClasses = computed(() =>
  props.size === 'md'
    ? 'px-2.5 py-1 text-xs gap-1.5'
    : 'px-2 py-0.5 text-[10px] gap-1'
)

const iconSize = computed(() => (props.size === 'md' ? 'w-3.5 h-3.5' : 'w-3 h-3'))

const visible = computed(() => !(props.hideWhenActive && state.value === 'active'))
</script>

<template>
  <span
    v-if="visible"
    :class="['inline-flex items-center font-medium rounded-full border', sizeClasses, badge.classes]"
    :title="badge.label"
  >
    <Icon :icon="badge.icon" :class="iconSize" />
    <span>{{ badge.label }}</span>
  </span>
</template>
