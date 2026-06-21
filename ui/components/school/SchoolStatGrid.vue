<script setup lang="ts">
import { computed } from 'vue'
import { Icon } from '@iconify/vue'

interface Props {
  academy: {
    total_students?: number | null
    total_teachers?: number | null
    courses_offered?: number | null
    total_classrooms?: number | null
  }
}

const props = defineProps<Props>()

const fmt = (n?: number | null) => {
  if (n == null) return '—'
  return new Intl.NumberFormat('th-TH').format(n)
}

const stats = computed(() => [
  {
    label: 'นักเรียน',
    value: fmt(props.academy.total_students),
    icon: 'heroicons:academic-cap',
    iconBg: 'bg-vikinger-cyan/15 text-cyan-700 dark:text-cyan-300',
  },
  {
    label: 'ครูและบุคลากร',
    value: fmt(props.academy.total_teachers),
    icon: 'heroicons:user-group',
    iconBg: 'bg-vikinger-purple/15 text-vikinger-purple',
  },
  {
    label: 'รายวิชา',
    value: fmt(props.academy.courses_offered),
    icon: 'heroicons:book-open',
    iconBg: 'bg-green-500/15 text-green-700 dark:text-green-300',
  },
  {
    label: 'ห้องเรียน',
    value: fmt(props.academy.total_classrooms),
    icon: 'heroicons:building-library',
    iconBg: 'bg-orange-500/15 text-orange-700 dark:text-orange-400',
  },
])
</script>

<template>
  <div class="grid grid-cols-2 gap-3">
    <div
      v-for="stat in stats"
      :key="stat.label"
      class="bg-white dark:bg-vikinger-dark-200 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-3 flex flex-col gap-2"
    >
      <span :class="['w-9 h-9 rounded-lg flex items-center justify-center', stat.iconBg]">
        <Icon :icon="stat.icon" class="w-5 h-5" />
      </span>
      <div>
        <div class="text-lg font-bold text-gray-900 dark:text-white leading-tight">{{ stat.value }}</div>
        <div class="text-xs text-gray-500 dark:text-gray-400 leading-tight">{{ stat.label }}</div>
      </div>
    </div>
  </div>
</template>
