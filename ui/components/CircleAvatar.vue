<template>
  <div class="circle-avatar-wrapper" :style="wrapperStyle" role="img" :aria-label="altText">
    <!-- Avatar Image -->
    <div 
      class="circle-avatar"
      :class="[sizeClass, { 'has-border': showBorder }]"
      :style="avatarStyle"
    >
      <img 
        v-if="imageSrc"
        :src="imageSrc" 
        :alt="altText"
        :loading="lazy ? 'lazy' : 'eager'"
        class="avatar-image"
        @error="handleImageError"
      />
      <div v-else class="avatar-fallback">
        <Icon :name="fallbackIcon" class="fallback-icon" aria-hidden="true" />
      </div>
    </div>

    <!-- Online Status -->
    <div 
      v-if="showOnlineStatus"
      class="online-status"
      :class="{ online: isOnline }"
      :aria-label="isOnline ? 'Online' : 'Offline'"
    />
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { DEFAULT_AVATAR } from '~/utils/avatarConstants'

const props = withDefaults(defineProps<{
  src?: string | null
  alt?: string
  name?: string
  fallbackIcon?: string
  size?: 'xs' | 'sm' | 'md' | 'lg' | 'xl' | '2xl' | '3xl' | number
  showBorder?: boolean
  borderColor?: string
  borderWidth?: number
  showOnlineStatus?: boolean
  isOnline?: boolean
  lazy?: boolean
}>(), {
  alt: 'Avatar',
  fallbackIcon: 'mdi:account',
  size: 'md',
  showBorder: true,
  borderColor: '#23d2e2',
  borderWidth: 3,
  showOnlineStatus: false,
  isOnline: false,
  lazy: true,
})

const emit = defineEmits<{
  (e: 'error', event: Event): void
}>()

// Track if image failed to load
const imageLoadFailed = ref(false)

// Size presets
const sizePresets: Record<string, number> = {
  'xs': 32,
  'sm': 48,
  'md': 80,
  'lg': 120,
  'xl': 160,
  '2xl': 200,
  '3xl': 260,
}

const computedSize = computed(() => {
  if (typeof props.size === 'number') return props.size
  return sizePresets[props.size] || sizePresets['md']
})

const sizeClass = computed(() => {
  if (typeof props.size === 'string') return `size-${props.size}`
  return ''
})

const wrapperStyle = computed(() => ({
  width: `${computedSize.value}px`,
  height: `${computedSize.value}px`,
}))

const avatarStyle = computed(() => ({
  width: `${computedSize.value}px`,
  height: `${computedSize.value}px`,
  borderColor: props.showBorder ? props.borderColor : 'transparent',
  borderWidth: props.showBorder ? `${props.borderWidth}px` : '0',
}))

// Computed image source with fallback handling
const imageSrc = computed(() => {
  if (imageLoadFailed.value) return null // Show fallback icon
  return props.src || null
})

// Computed alt text for accessibility
const altText = computed(() => {
  if (props.alt !== 'Avatar') return props.alt
  if (props.name) return `Avatar of ${props.name}`
  return 'User avatar'
})

function handleImageError(event: Event) {
  const img = event.target as HTMLImageElement
  // Try fallback to default avatar first
  if (img.src !== DEFAULT_AVATAR) {
    img.src = DEFAULT_AVATAR
  } else {
    // If default also fails, show fallback icon
    imageLoadFailed.value = true
  }
  emit('error', event)
}
</script>

<style scoped>
.circle-avatar-wrapper {
  position: relative;
  display: inline-block;
}

.circle-avatar {
  border-radius: 50%;
  overflow: hidden;
  border-style: solid;
  background: #1d2333;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
}

.avatar-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.3s ease;
}

.circle-avatar:hover .avatar-image {
  transform: scale(1.05);
}

.avatar-fallback {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: rgba(255, 255, 255, 0.6);
}

.fallback-icon {
  width: 40%;
  height: 40%;
}

/* Online Status */
.online-status {
  position: absolute;
  bottom: 8%;
  right: 8%;
  width: 16px;
  height: 16px;
  border-radius: 50%;
  border: 3px solid #1d2333;
  background: #6b7280;
}

.online-status.online {
  background: #40d04f;
  box-shadow: 0 0 8px rgba(64, 208, 79, 0.6);
}

/* Size adjustments for online status */
.size-xs .online-status { width: 8px; height: 8px; border-width: 2px; }
.size-sm .online-status { width: 10px; height: 10px; border-width: 2px; }
.size-md .online-status { width: 14px; height: 14px; }
.size-lg .online-status { width: 16px; height: 16px; }
.size-xl .online-status { width: 18px; height: 18px; }
.size-2xl .online-status { width: 20px; height: 20px; }
.size-3xl .online-status { width: 24px; height: 24px; }
</style>
