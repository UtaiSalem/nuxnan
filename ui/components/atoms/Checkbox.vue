<template>
  <div class="flex items-center cursor-pointer" @click="toggle">
    <div
      class="w-5 h-5 rounded border flex items-center justify-center transition-colors duration-200 mr-3"
      :class="modelValue ? 'bg-vikinger-blue border-vikinger-blue' : 'border-gray-300 bg-white'"
    >
      <svg v-if="modelValue" xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 text-white fill-current" viewBox="0 0 20 20" fill="currentColor">
        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
      </svg>
    </div>
    <span class="text-xs font-bold text-gray-800 select-none">{{ label }}</span>
  </div>
</template>

<script setup lang="ts">
const props = defineProps({
  label: {
    type: String,
    default: ''
  },
  modelValue: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['update:modelValue'])

const toggle = () => {
  emit('update:modelValue', !props.modelValue)
}
</script>
