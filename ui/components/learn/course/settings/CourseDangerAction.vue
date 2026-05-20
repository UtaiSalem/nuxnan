<script setup lang="ts">
import { computed } from 'vue'
import { Icon } from '@iconify/vue'
import ResponsiveCard from '@/components/Common/ResponsiveCard.vue'

type Variant = 'danger' | 'primary'

interface Props {
  label: string
  description: string
  icon: string
  buttonLabel: string
  variant: Variant
  loading?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  loading: false,
})

const emit = defineEmits<{
  action: []
}>()

const responsiveCardVariant = computed(() => {
  return props.variant === 'danger' ? 'danger' : 'highlight'
})

const buttonClass = computed(() => {
  if (props.variant === 'danger') {
    return 'w-full flex items-center justify-center gap-2 px-4 py-3 bg-red-500 hover:bg-red-600 text-white font-bold rounded-xl transition-all shadow-lg shadow-red-500/20 active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed'
  }
  return 'w-full flex items-center justify-center gap-2 px-4 py-3 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl transition-all shadow-lg shadow-blue-500/20 active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed'
})

const buttonIcon = computed(() => {
  // Keep icon deterministic for the current UI (danger action = delete).
  return props.variant === 'danger' ? 'fluent:delete-24-filled' : 'fluent:trash-24-filled'
})

const onClick = () => {
  emit('action')
}
</script>

<template>
  <ResponsiveCard
    :title="label"
    :icon="icon"
    :variant="responsiveCardVariant"
    :iconColor="variant === 'danger' ? 'text-red-500' : 'text-blue-500'"
  >
    <div class="text-center py-2">
      <p
        v-if="description"
        class="text-xs text-red-600/80 dark:text-red-400/80 mb-5 leading-relaxed"
      >
        {{ description }}
      </p>

      <button :type="'button'" :class="buttonClass" :disabled="loading" @click="onClick">
        <Icon v-if="loading" icon="svg-spinners:ring-resize" class="w-5 h-5" />
        <Icon v-else :icon="buttonIcon" class="w-5 h-5" />
        {{ buttonLabel }}
      </button>
    </div>
  </ResponsiveCard>
</template>
