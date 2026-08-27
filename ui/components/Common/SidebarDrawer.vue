<script setup lang="ts">
import { watch, onMounted, onBeforeUnmount } from 'vue'
import { Icon } from '@iconify/vue'

interface Props {
  open: boolean
  side?: 'left' | 'right'
  title?: string
}
const props = withDefaults(defineProps<Props>(), { side: 'left' })
const emit = defineEmits<{ 'update:open': [v: boolean]; close: [] }>()

const close = () => {
  emit('update:open', false)
  emit('close')
}

// Lock body scroll (with iOS compatibility)
watch(() => props.open, (open) => {
  if (import.meta.client) {
    if (open) {
      document.body.style.overflow = 'hidden'
      // iOS specific scroll locking wrapper
      document.body.style.position = 'relative'
    } else {
      document.body.style.overflow = ''
      document.body.style.position = ''
    }
  }
})

// Escape key
const onEsc = (e: KeyboardEvent) => {
  if (e.key === 'Escape' && props.open) close()
}
onMounted(() => document.addEventListener('keydown', onEsc))
onBeforeUnmount(() => {
  document.removeEventListener('keydown', onEsc)
  if (import.meta.client) {
    document.body.style.overflow = ''
    document.body.style.position = ''
  }
})
</script>

<template>
  <Teleport to="body">
    <Transition
      enter-active-class="transition-opacity duration-200"
      enter-from-class="opacity-0"
      leave-active-class="transition-opacity duration-150"
      leave-to-class="opacity-0"
    >
      <div
        v-if="open"
        class="fixed inset-0 z-40 bg-black/50 backdrop-blur-sm"
        @click="close"
        aria-hidden="true"
      />
    </Transition>

    <Transition
      :enter-active-class="`transition-transform duration-300 motion-reduce:duration-0`"
      :enter-from-class="side === 'left' ? '-translate-x-full' : 'translate-x-full'"
      :leave-active-class="`transition-transform duration-200 motion-reduce:duration-0`"
      :leave-to-class="side === 'left' ? '-translate-x-full' : 'translate-x-full'"
    >
      <aside
        v-if="open"
        :class="[
          'fixed top-0 bottom-0 z-50 w-[85vw] max-w-sm bg-white dark:bg-vikinger-dark-100 shadow-2xl overflow-y-auto',
          side === 'left' ? 'left-0' : 'right-0',
        ]"
        role="dialog"
        :aria-label="title || 'sidebar'"
        aria-modal="true"
      >
        <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100 dark:border-gray-700 sticky top-0 bg-white dark:bg-vikinger-dark-100">
          <span class="font-bold text-gray-900 dark:text-white">{{ title }}</span>
          <button
            type="button"
            class="min-h-[44px] sm:min-h-0 min-w-[44px] sm:min-w-0 inline-flex items-center justify-center p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-vikinger-purple"
            aria-label="ปิด"
            @click="close"
          >
            <Icon icon="fluent:dismiss-24-regular" class="w-5 h-5" />
          </button>
        </div>
        <div class="p-4">
          <slot />
        </div>
      </aside>
    </Transition>
  </Teleport>
</template>
