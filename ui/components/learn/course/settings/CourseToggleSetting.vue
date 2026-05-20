<script setup lang="ts">
import { computed } from 'vue'

type ToggleVariant = 'neutral' | 'orange' | 'cyan' | 'amber' | 'green'

const model = defineModel<boolean>()

interface Props {
  label: string
  description?: string
  icon?: string
  disabled?: boolean
  variant?: ToggleVariant
}

const props = withDefaults(defineProps<Props>(), {
  description: '',
  disabled: false,
  variant: 'neutral',
})

const onToggle = (value: boolean) => {
  if (props.disabled) return
  model.value = value
}

const ringColorClass = computed(() => {
  // Keep visuals similar to existing page: gray background, colored knob when on.
  if (props.variant === 'orange') return 'peer-checked:bg-orange-500'
  if (props.variant === 'cyan') return 'peer-checked:bg-cyan-500'
  if (props.variant === 'amber') return 'peer-checked:bg-amber-500'
  if (props.variant === 'green') return 'peer-checked:bg-emerald-500'
  return 'peer-checked:bg-orange-500'
})

const wrapperBorderClass = computed(() => {
  // default uses existing gray card styling
  return 'bg-gray-50 dark:bg-gray-900/50 border border-gray-100 dark:border-gray-800'
})
</script>

<template>
  <label
    class="flex items-center justify-between cursor-pointer min-h-[64px] gap-4 p-4 rounded-xl"
    :class="[wrapperBorderClass, disabled ? 'opacity-60 cursor-not-allowed' : '']"
  >
    <div class="min-w-0">
      <div class="font-bold text-gray-900 dark:text-white text-base">
        {{ label }}
      </div>
      <div v-if="description" class="text-[11px] text-gray-500 dark:text-gray-400">
        {{ description }}
      </div>
    </div>

    <div class="relative inline-flex items-center flex-shrink-0">
      <input
        :checked="model"
        :disabled="disabled"
        type="checkbox"
        class="sr-only peer"
        @change="onToggle(($event.target as HTMLInputElement).checked)"
      />
      <div
        class="w-12 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-orange-300 dark:peer-focus:ring-orange-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600"
        :class="ringColorClass"
      />
    </div>
  </label>
</template>
