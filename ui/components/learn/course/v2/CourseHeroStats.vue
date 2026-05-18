<script setup lang="ts">
import { Icon } from '@iconify/vue'

const props = defineProps({
  lessonsCount: { type: Number, default: 0 },
  enrolledStudents: { type: Number, default: 0 },
  rating: { type: [Number, String], default: 0 },
  groupsCount: { type: Number, default: 0 },
  variant: { type: String, default: 'grid' }, // 'grid' (horizontal) or 'stack' (vertical)
})

const stats = computed(() => [
  {
    label: 'บทเรียน',
    value: props.lessonsCount,
    icon: 'heroicons:book-open-solid',
    colorClass: 'text-blue-600 dark:text-blue-300',
    bgClass: 'bg-blue-50 dark:bg-blue-900/40 border-blue-100 dark:border-blue-900/50'
  },
  {
    label: 'ผู้เรียน',
    value: props.enrolledStudents,
    icon: 'heroicons:users-solid',
    colorClass: 'text-purple-600 dark:text-purple-300',
    bgClass: 'bg-purple-50 dark:bg-purple-900/40 border-purple-100 dark:border-purple-900/50'
  },
  {
    label: props.rating ? 'คะแนน' : 'กลุ่ม',
    value: props.rating ? (typeof props.rating === 'number' ? props.rating.toFixed(1) : props.rating) : props.groupsCount,
    icon: props.rating ? 'fluent:star-24-filled' : 'heroicons:user-group-solid',
    colorClass: props.rating ? 'text-amber-500' : 'text-emerald-600 dark:text-emerald-300',
    bgClass: props.rating ? 'bg-amber-50 dark:bg-amber-900/40 border-amber-100 dark:border-amber-900/50' : 'bg-emerald-50 dark:bg-emerald-900/40 border-emerald-100 dark:border-emerald-900/50'
  }
])
</script>

<template>
  <div :class="variant === 'grid' ? 'grid grid-cols-3 gap-2 sm:gap-4' : 'flex flex-col gap-3'">
    <div 
      v-for="stat in stats" 
      :key="stat.label"
      class="flex min-w-0 items-center gap-3 rounded-xl border p-3 transition-all hover:scale-[1.02]"
      :class="[stat.bgClass, variant === 'grid' ? 'flex-col justify-center text-center py-3' : 'px-4 py-3']"
    >
      <Icon :icon="stat.icon" class="h-5 w-5 shrink-0" :class="stat.colorClass" />
      <div :class="variant === 'grid' ? 'flex flex-col items-center' : 'flex flex-col'">
        <span class="text-base font-black leading-none text-gray-900 dark:text-white sm:text-lg">
          {{ stat.value }}
        </span>
        <span class="text-[10px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 sm:text-xs">
          {{ stat.label }}
        </span>
      </div>
    </div>
  </div>
</template>
