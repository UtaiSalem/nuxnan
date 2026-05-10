<script setup lang="ts">
import { Icon } from '@iconify/vue'

interface Props {
  title?: string
  icon?: string
  iconColor?: string
  variant?: 'default' | 'danger' | 'highlight'
  noPadding?: boolean
}

withDefaults(defineProps<Props>(), {
  variant: 'default',
  iconColor: 'text-blue-500',
  noPadding: false
})
</script>

<template>
  <section 
    class="rounded-2xl border overflow-hidden transition-all duration-300"
    :class="{
      'bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 shadow-sm': variant === 'default',
      'bg-red-50 dark:bg-red-900/10 border-red-200 dark:border-red-900/30': variant === 'danger',
      'bg-blue-50 dark:bg-blue-900/10 border-blue-200 dark:border-blue-900/30': variant === 'highlight'
    }"
  >
    <!-- Header -->
    <div 
      v-if="title" 
      class="px-4 sm:px-6 py-3 sm:py-4 border-b"
      :class="{
        'border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/50': variant === 'default',
        'border-red-100 dark:border-red-900/30 bg-red-100/50 dark:bg-red-900/20': variant === 'danger',
        'border-blue-100 dark:border-blue-900/30 bg-blue-100/50 dark:bg-blue-900/20': variant === 'highlight'
      }"
    >
      <h2 class="text-base sm:text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
        <Icon v-if="icon" :icon="icon" class="w-5 h-5" :class="iconColor" />
        {{ title }}
      </h2>
    </div>
    
    <!-- Body -->
    <div :class="noPadding ? '' : 'p-4 sm:p-6'">
      <slot />
    </div>
    
    <!-- Footer -->
    <div v-if="$slots.footer" class="px-4 sm:px-6 py-3 sm:py-4 border-t border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/50">
      <slot name="footer" />
    </div>
  </section>
</template>
