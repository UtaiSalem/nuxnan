<script setup lang="ts">
import { computed } from 'vue'
import { 
  DEFAULT_AVATAR, 
  AVATAR_SIZE_CLASSES, 
  UI_AVATARS_CONFIG,
  getInitialsFromName,
  type AvatarSize 
} from '~/utils/avatarConstants'

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
    type: String as () => AvatarSize,
    default: 'md',
    validator: (value: string) => ['xs', 'sm', 'md', 'lg', 'xl', '2xl', '3xl', '4xl'].includes(value)
  },
  status: {
    type: String,
    default: null,
    validator: (value: string | null) => [null, 'online', 'offline', 'busy'].includes(value)
  },
  lazy: {
    type: Boolean,
    default: true
  }
})

const emit = defineEmits<{
  (e: 'error', event: Event): void
}>()

const sizeClasses = computed(() => {
  return AVATAR_SIZE_CLASSES[props.size as keyof typeof AVATAR_SIZE_CLASSES] || AVATAR_SIZE_CLASSES.md
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
    const initials = getInitialsFromName(props.name)
    
    // Deterministic color based on name
    const bgColors = [
      '94a3b8', '64748b', '78716c', '6b7280', '71717a',
      '737373', 'a3a3a3', '9ca3af', 'a1a1aa', 'a8a29e'
    ]
    let hash = 0
    for (let i = 0; i < props.name.length; i++) {
      hash = props.name.charCodeAt(i) + ((hash << 5) - hash)
    }
    const index = Math.abs(hash) % bgColors.length
    const bgColor = bgColors[index]
    
    return `${UI_AVATARS_CONFIG.baseUrl}?name=${encodeURIComponent(initials)}&background=${bgColor}&color=fff&size=128`
  }

  return DEFAULT_AVATAR
})

const altText = computed(() => {
  if (props.alt !== 'User Avatar') return props.alt
  if (props.name) return `Avatar of ${props.name}`
  return 'User Avatar'
})

function handleError(e: Event) {
  const img = e.target as HTMLImageElement
  if (img.src !== DEFAULT_AVATAR) {
    img.src = DEFAULT_AVATAR
  }
  emit('error', e)
}
</script>

<template>
  <div class="relative inline-block">
    <img 
      :src="avatarSrc" 
      :alt="altText"
      :loading="lazy ? 'lazy' : 'eager'"
      class="rounded-full object-cover border-2 border-white dark:border-gray-800"
      :class="sizeClasses"
      role="img"
      :aria-label="altText"
      @error="handleError"
    >
    <span 
      v-if="status" 
      class="absolute bottom-0 right-0 block w-3 h-3 rounded-full ring-2 ring-white dark:ring-gray-800"
      :class="statusColor"
      :aria-label="`Status: ${status}`"
    ></span>
  </div>
</template>
