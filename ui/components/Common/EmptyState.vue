<script setup lang="ts">
import { Icon } from '@iconify/vue'

interface Props {
  icon?: string
  title: string
  description?: string
  /** Primary CTA */
  ctaLabel?: string
  ctaIcon?: string
  /** Compact = smaller padding */
  compact?: boolean
}
withDefaults(defineProps<Props>(), {
  icon: 'heroicons:inbox',
  compact: false,
})
const emit = defineEmits<{ action: [] }>()
</script>

<template>
  <div
    :class="[
      'flex flex-col items-center justify-center text-center bg-white dark:bg-vikinger-dark-200 rounded-xl',
      compact ? 'py-6 px-4' : 'py-12 px-6',
    ]"
    role="status"
  >
    <div
      :class="[
        'rounded-full bg-gradient-to-br from-vikinger-purple/10 to-vikinger-cyan/10 flex items-center justify-center mb-4',
        compact ? 'w-12 h-12' : 'w-20 h-20',
      ]"
    >
      <Icon
        :icon="icon"
        :class="['text-vikinger-purple', compact ? 'w-6 h-6' : 'w-10 h-10']"
      />
    </div>
    <h3 :class="['font-bold text-gray-900 dark:text-white', compact ? 'text-sm' : 'text-base']">
      {{ title }}
    </h3>
    <p
      v-if="description"
      :class="['text-gray-500 dark:text-gray-400 mt-1', compact ? 'text-xs' : 'text-sm max-w-md']"
    >
      {{ description }}
    </p>
    <button
      v-if="ctaLabel"
      type="button"
      class="mt-4 px-4 py-2 bg-vikinger-purple text-white rounded-lg font-medium hover:bg-vikinger-purple/90 inline-flex items-center gap-2"
      @click="emit('action')"
    >
      <Icon v-if="ctaIcon" :icon="ctaIcon" class="w-4 h-4" />
      {{ ctaLabel }}
    </button>
  </div>
</template>
