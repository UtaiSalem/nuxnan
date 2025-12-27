<script setup lang="ts">
const props = defineProps({
  name: {
    type: String,
    required: true,
  },
  size: {
    type: String,
    default: 'md',
  },
  color: {
    type: String,
    default: 'currentColor',
  },
})

const sizeClass = computed(() => {
  const sizes: Record<string, string> = {
    sm: 'text-sm',
    md: 'text-base',
    lg: 'text-xl',
    xl: 'text-2xl',
  }
  return sizes[props.size] || props.size
})

// แปลงชื่อไอคอนเป็น PrimeIcons class
const iconClass = computed(() => {
  // ถ้าเป็น PrimeIcon อยู่แล้ว (pi pi-xxx)
  if (props.name.startsWith('pi ')) {
    return props.name
  }

  // แปลง iconify names เป็น PrimeIcons
  const iconMap: Record<string, string> = {
    'lni:plus': 'pi pi-plus',
    'lni:more-alt': 'pi pi-ellipsis-v',
    'mdi:cart-plus': 'pi pi-shopping-cart',
    'mdi:dots-horizontal': 'pi pi-ellipsis-h',
    'mdi:delete': 'pi pi-trash',
    'mdi:cancel': 'pi pi-times',
    'mdi:pencil': 'pi pi-pencil',
    'mdi:comment': 'pi pi-comment',
    'mdi:share-variant': 'pi pi-share-alt',
    'mdi:heart': 'pi pi-heart',
    'mdi:send': 'pi pi-send',
    'mdi:reply': 'pi pi-reply',
  }

  return iconMap[props.name] || 'pi pi-circle'
})
</script>

<template>
  <i :class="[iconClass, sizeClass, color]" />
</template>
