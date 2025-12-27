<script setup lang="ts">
const props = defineProps({
  src: {
    type: String,
    default: ''
  },
  alt: {
    type: String,
    default: 'User Avatar'
  },
  size: {
    type: String,
    default: 'md',
    validator: (value: string) => ['sm', 'md', 'lg', 'xl'].includes(value)
  },
  status: {
    type: String,
    default: null,
    validator: (value: string | null) => [null, 'online', 'offline', 'busy'].includes(value)
  }
})

const sizeClasses = computed(() => {
  const sizes = {
    sm: 'w-8 h-8',
    md: 'w-10 h-10',
    lg: 'w-12 h-12',
    xl: 'w-16 h-16'
  }
  return sizes[props.size as keyof typeof sizes]
})

const statusColor = computed(() => {
  const colors = {
    online: 'bg-green-500',
    offline: 'bg-gray-400',
    busy: 'bg-red-500'
  }
  return props.status ? colors[props.status as keyof typeof colors] : ''
})
</script>

<template>
  <div class="relative inline-block">
    <img 
      :src="src || '/images/default-avatar.png'" 
      :alt="alt" 
      class="rounded-full object-cover border-2 border-white dark:border-gray-800"
      :class="sizeClasses"
    >
    <span 
      v-if="status" 
      class="absolute bottom-0 right-0 block w-3 h-3 rounded-full ring-2 ring-white dark:ring-gray-800"
      :class="statusColor"
    ></span>
  </div>
</template>
