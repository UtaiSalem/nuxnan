<template>
  <button
    :type="type"
    class="w-full h-12 rounded-xl font-bold shadow-lg transform transition-transform duration-200 active:scale-95 flex items-center justify-center"
    :class="[
      // Solid variants
      variant === 'primary' ? 'bg-vikinger-purple hover:bg-opacity-90 text-white' : '',
      variant === 'secondary' ? 'bg-vikinger-blue hover:bg-opacity-90 text-white' : '',
      variant === 'success' ? 'bg-green-600 hover:bg-green-700 text-white' : '',
      variant === 'danger' ? 'bg-red-600 hover:bg-red-700 text-white' : '',
      variant === 'warning' ? 'bg-yellow-500 hover:bg-yellow-600 text-white' : '',
      variant === 'info' ? 'bg-blue-500 hover:bg-blue-600 text-white' : '',
      
      // Outline variants
      variant === 'outline' ? 'bg-white border-[3px] border-gray-600 text-gray-900 font-bold shadow-md hover:border-gray-700 hover:bg-gray-50 hover:shadow-lg' : '',
      variant === 'outline-primary' ? 'bg-white border-[3px] border-vikinger-purple text-vikinger-purple shadow-md hover:bg-vikinger-purple hover:text-white hover:shadow-lg' : '',
      variant === 'outline-secondary' ? 'bg-white border-[3px] border-vikinger-blue text-vikinger-blue shadow-md hover:bg-vikinger-blue hover:text-white hover:shadow-lg' : '',
      
      // Special variants
      variant === 'ghost' ? 'bg-transparent text-gray-500 shadow-none hover:text-gray-700' : '',
      variant === 'link' ? 'bg-transparent text-vikinger-purple shadow-none hover:text-opacity-80 underline' : '',
      variant === 'google' ? 'bg-[#DB4437] hover:bg-opacity-90 text-white' : '',
      variant === 'gradient' ? 'bg-gradient-to-r from-vikinger-purple via-vikinger-blue to-vikinger-cyan text-white shadow-vikinger hover:shadow-vikinger-lg hover:scale-[1.02]' : '',
      
      disabled ? 'opacity-50 cursor-not-allowed pointer-events-none' : ''
    ]"
    :disabled="disabled"
  >
    <slot>{{ text }}</slot>
  </button>
</template>

<script setup lang="ts">
import type { PropType } from 'vue'

type ButtonVariant = 
  | 'primary' 
  | 'secondary' 
  | 'success' 
  | 'danger' 
  | 'warning' 
  | 'info'
  | 'outline' 
  | 'outline-primary' 
  | 'outline-secondary'
  | 'ghost' 
  | 'link'
  | 'google'
  | 'gradient'

defineProps({
  text: {
    type: String,
    default: ''
  },
  type: {
    type: String as PropType<'button' | 'submit' | 'reset'>,
    default: 'button'
  },
  variant: {
    type: String as PropType<ButtonVariant>,
    default: 'primary'
  },
  disabled: {
    type: Boolean,
    default: false
  }
})
</script>
