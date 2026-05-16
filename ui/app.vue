<script setup lang="ts">
const colorMode = useColorMode()
const authStore = useAuthStore()

// Initialize color mode on mount
onMounted(() => {
  // The color mode will be automatically applied by the @nuxtjs/color-mode module
  // This ensures proper SSR and client-side hydration
})

const isLoginTransitioning = computed(() => authStore.isLoginTransitioning)
</script>

<template>
  <div class="min-h-screen">
    <!-- Global Login Transition Overlay -->
    <Transition name="overlay-fade">
      <div
        v-if="isLoginTransitioning"
        class="fixed inset-0 bg-black/60 backdrop-blur-md z-[9999] flex items-center justify-center"
      >
        <div class="bg-white/95 dark:bg-vikinger-dark-100 rounded-3xl p-10 shadow-2xl flex flex-col items-center space-y-6">
          <div class="relative w-20 h-20">
            <div class="absolute inset-0 border-4 border-vikinger-purple/30 rounded-full"></div>
            <div class="absolute inset-0 border-4 border-transparent border-t-vikinger-purple border-r-vikinger-cyan rounded-full animate-spin"></div>
            <div class="absolute inset-2 border-4 border-transparent border-b-vikinger-cyan rounded-full animate-spin" style="animation-direction: reverse; animation-duration: 1s;"></div>
          </div>
          <div class="text-center">
            <p class="text-gray-800 dark:text-white font-bold text-xl">กำลังเข้าสู่ระบบ...</p>
            <p class="text-gray-500 dark:text-gray-400 text-sm mt-1">โปรดรอสักครู่</p>
          </div>
        </div>
      </div>
    </Transition>

    <NuxtLayout>
      <div class="app-container">
        <NuxtPage />
        
        <!-- Toast Notifications -->
        <NotificationsToastNotification />
      </div>
    </NuxtLayout>
  </div>
</template>

<style>
/* Global Page Transitions */
.page-enter-active,
.page-leave-active {
  transition: opacity 0.2s ease;
}
.page-enter-from,
.page-leave-to {
  opacity: 0;
}

/* Overlay Transition */
.overlay-fade-enter-active,
.overlay-fade-leave-active {
  transition: opacity 0.4s ease;
}
.overlay-fade-enter-from,
.overlay-fade-leave-to {
  opacity: 0;
}
</style>
