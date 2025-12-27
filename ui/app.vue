<script setup lang="ts">
const colorMode = useColorMode()

// Initialize color mode on mount
onMounted(() => {
  // The color mode will be automatically applied by the @nuxtjs/color-mode module
  // This ensures proper SSR and client-side hydration
})
</script>

<template>
  <NuxtLayout>
    <NuxtPage />
    
    <!-- Toast Notifications -->
    <ToastNotification />
  </NuxtLayout>
</template>
