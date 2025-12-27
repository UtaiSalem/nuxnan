<template>
  <span v-if="isClient" :class="iconClass" v-bind="$attrs">
    <Icon :icon="icon" />
  </span>
  <span v-else :class="iconClass" v-bind="$attrs">
    <!-- Server-side fallback - will be replaced by client -->
  </span>
</template>

<script setup>
import { ref, onMounted } from 'vue'

const props = defineProps({
  icon: {
    type: String,
    required: true,
  },
  iconClass: {
    type: String,
    default: '',
  },
})

const isClient = ref(false)
let Icon = null

onMounted(async () => {
  isClient.value = true
  // Dynamically import Icon component only on client side
  try {
    const iconifyModule = await import('@iconify/vue')
    Icon = iconifyModule.Icon
  } catch (error) {
    console.warn('Iconify not available, using fallback:', error)
  }
})
</script>
