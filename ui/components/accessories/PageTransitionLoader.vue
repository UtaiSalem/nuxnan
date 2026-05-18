<script setup lang="ts">
import { Icon } from '@iconify/vue'

defineProps<{
  show: boolean
  text?: string
}>()
</script>

<template>
  <Transition
    enter-active-class="transition duration-300 ease-out"
    enter-from-class="opacity-0"
    enter-to-class="opacity-100"
    leave-active-class="transition duration-200 ease-in"
    leave-from-class="opacity-100"
    leave-to-class="opacity-0"
  >
    <div
      v-if="show"
      class="absolute inset-0 z-50 flex flex-col items-center justify-center bg-white/60 dark:bg-vikinger-dark/60 backdrop-blur-sm rounded-2xl"
    >
      <div class="relative">
        <!-- Outer Ring -->
        <div class="w-20 h-20 rounded-full border-4 border-vikinger-purple/20 border-t-vikinger-purple animate-spin"></div>
        
        <!-- Inner Icon -->
        <div class="absolute inset-0 flex items-center justify-center">
          <Icon 
            icon="svg-spinners:bars-rotate-fade" 
            class="w-10 h-10 text-vikinger-cyan" 
          />
        </div>
      </div>
      
      <p v-if="text" class="mt-4 font-black text-vikinger-purple dark:text-vikinger-cyan animate-pulse">
        {{ text }}
      </p>
    </div>
  </Transition>
</template>

<style scoped>
.backdrop-blur-sm {
  backdrop-filter: blur(4px);
}
</style>
