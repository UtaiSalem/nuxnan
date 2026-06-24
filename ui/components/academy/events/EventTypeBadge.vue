<script setup lang="ts">
/**
 * EventTypeBadge — แสดง badge ของประเภทกิจกรรมโรงเรียน (event_type)
 * ใช้ taxonomy จาก ui/constants/schoolEventTypes.ts
 */
import { Icon } from '@iconify/vue'
import { computed } from 'vue'
import { EVENT_TYPE_COLOR_CLASSES, getSchoolEventTypeMeta } from '~/constants/schoolEventTypes'

const props = defineProps<{
  event_type?: string | null
  size?: 'sm' | 'md'
}>()

const meta = computed(() => getSchoolEventTypeMeta(props.event_type))
const colorClasses = computed(() => EVENT_TYPE_COLOR_CLASSES[meta.value.color])

const sizeClasses = computed(() =>
  props.size === 'md'
    ? 'px-3 py-1.5 text-sm gap-2'
    : 'px-2.5 py-1 text-xs gap-1.5',
)

const iconSize = computed(() => (props.size === 'md' ? 'w-4 h-4' : 'w-3.5 h-3.5'))
</script>

<template>
  <span
    class="inline-flex items-center rounded-full font-medium whitespace-nowrap"
    :class="[colorClasses.badge, sizeClasses]"
  >
    <Icon :icon="meta.icon" :class="iconSize" />
    {{ meta.label }}
  </span>
</template>
