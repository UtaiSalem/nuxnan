<script setup lang="ts">
import { computed } from 'vue'

const props = defineProps({
  src: {
    type: String,
    default: ''
  },
  name: {
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

const avatarSrc = computed(() => {
  if (props.src) return props.src
  
  if (props.name) {
    const bgColors = [
      '94a3b8', '64748b', '78716c', '6b7280', '71717a',
      '737373', 'a3a3a3', '9ca3af', 'a1a1aa', 'a8a29e'
    ]
    
    // Deterministic color based on name
    let hash = 0
    for (let i = 0; i < props.name.length; i++) {
        hash = props.name.charCodeAt(i) + ((hash << 5) - hash)
    }
    const index = Math.abs(hash) % bgColors.length
    const bgColor = bgColors[index]
    
    return `https://ui-avatars.com/api/?name=${encodeURIComponent(props.name)}&background=${bgColor}&color=fff&size=128`
  }

  return '/images/default-avatar.png'
})
</script>

<template>
  <div class="relative inline-block">
    <img 
      :src="avatarSrc" 
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
