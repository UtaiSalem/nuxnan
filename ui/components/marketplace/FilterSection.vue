<script setup lang="ts">
import { Icon } from '@iconify/vue'

defineProps<{
  title: string
  icon: string
  open: boolean
}>()

defineEmits(['toggle'])
</script>

<template>
  <div class="border-b border-gray-100 dark:border-vikinger-dark-100 last:border-b-0">
    <button @click="$emit('toggle')" 
      class="w-full px-4 py-3 flex items-center justify-between hover:bg-gray-50 dark:hover:bg-vikinger-dark-100/30 transition-colors">
      <div class="flex items-center gap-2">
        <Icon :icon="icon" class="w-4 h-4 text-vikinger-purple" />
        <span class="text-xs font-bold text-gray-700 dark:text-gray-200 uppercase tracking-wider">{{ title }}</span>
      </div>
      <Icon icon="fluent:chevron-down-24-regular" 
        class="w-4 h-4 text-gray-400 transition-transform"
        :class="open ? 'rotate-180' : ''" />
    </button>
    <Transition name="slide">
      <div v-show="open" class="px-4 pb-4">
        <slot />
      </div>
    </Transition>
  </div>
</template>

<style scoped>
.slide-enter-active, .slide-leave-active { transition: all 0.2s ease; max-height: 500px; }
.slide-enter-from, .slide-leave-to { max-height: 0; opacity: 0; overflow: hidden; }
</style>
