<script setup lang="ts">
defineProps({
  content: {
    type: [String, Number],
    default: ''
  },
  variant: {
    type: String,
    default: 'primary',
    validator: (value: string) => ['primary', 'secondary', 'success', 'warning', 'danger'].includes(value)
  },
  rounded: {
    type: Boolean,
    default: false
  }
})

const classes = computed(() => {
  const base = 'inline-flex items-center justify-center px-2 py-0.5 text-xs font-bold leading-none'
  
  const variants = {
    primary: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
    secondary: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200',
    success: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
    warning: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
    danger: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'
  }

  return [
    base,
    variants[props.variant as keyof typeof variants],
    props.rounded ? 'rounded-full' : 'rounded'
  ].join(' ')
})
</script>

<template>
  <span :class="classes">
    <slot>{{ content }}</slot>
  </span>
</template>
