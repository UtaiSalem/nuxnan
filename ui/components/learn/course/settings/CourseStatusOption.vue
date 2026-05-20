<script setup lang="ts">
import { computed } from 'vue'
import { Icon } from '@iconify/vue'

type CourseStatus = 'published' | 'draft' | 'archived'
type StatusColor = 'green' | 'gray' | 'orange'

const model = defineModel<CourseStatus>()

interface Props {
  value: CourseStatus
  label: string
  description: string
  icon: string
  color: StatusColor
}

const props = defineProps<Props>()

const isActive = computed(() => model.value === props.value)

const activeClasses = computed(() => {
  if (props.color === 'green') {
    return 'border-green-500 bg-green-50/30 dark:bg-green-900/10'
  }
  if (props.color === 'orange') {
    return 'border-orange-500 bg-orange-50/30 dark:bg-orange-900/10'
  }
  return 'border-gray-400 bg-gray-50 dark:bg-gray-700/30'
})

const dotColorClass = computed(() => {
  if (props.color === 'green') return 'bg-green-500'
  if (props.color === 'orange') return 'bg-orange-500'
  return 'bg-gray-400'
})

const onSelect = () => {
  model.value = props.value
}
</script>

<template>
  <label
    class="flex items-center gap-3 p-4 rounded-xl border-2 border-gray-100 dark:border-gray-800 cursor-pointer transition-all hover:bg-gray-50 dark:hover:bg-gray-700/50 min-h-[72px]"
    :class="isActive ? activeClasses : ''"
    @click.prevent="onSelect"
  >
    <div class="flex items-center justify-center w-6 h-6 rounded-full border-2 border-gray-300 dark:border-gray-600">
      <div
        v-if="isActive"
        class="w-3.5 h-3.5 rounded-full"
        :class="dotColorClass"
      />
    </div>

    <input type="radio" class="hidden" :checked="isActive" :value="value" @change="onSelect" />

    <div class="flex-1 min-w-0">
      <div class="font-bold text-gray-900 dark:text-white text-base truncate">
        {{ label }}
      </div>
      <div class="text-[11px] text-gray-500 dark:text-gray-400 leading-snug">
        {{ description }}
      </div>
    </div>
  </label>
</template>
