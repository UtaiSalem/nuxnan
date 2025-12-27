<template>
  <div
    class="fixed bottom-0 left-0 right-0 z-40 bg-white dark:bg-gray-900 border-t border-secondary-200 dark:border-gray-700 shadow-lg md:hidden transition-colors duration-300"
  >
    <div class="flex items-center justify-between px-4 py-2">
      <!-- Left Menu -->
      <div class="flex items-center space-x-6">
        <NuxtLink
          to="/newsfeed"
          class="relative p-2 hover:bg-secondary-100 rounded-full transition-colors duration-200"
          :class="{ 'text-primary-600': isActive('/newsfeed') }"
        >
          <Icon icon="mdi:home" class="w-6 h-6" />
        </NuxtLink>

        <NuxtLink
          to="/notifications"
          class="relative p-2 hover:bg-secondary-100 rounded-full transition-colors duration-200"
          :class="{ 'text-primary-600': isActive('/notifications') }"
        >
          <Icon icon="mdi:bell" class="w-6 h-6" />
          <span
            v-if="notificationCount > 0"
            class="absolute top-0 right-0 w-5 h-5 bg-primary-500 text-white text-xs rounded-full flex items-center justify-center font-bold"
          >
            {{ notificationCount }}
          </span>
        </NuxtLink>
      </div>

      <!-- Menu Button (Hamburger) -->
      <button @click="toggleMenu" class="relative p-2" :class="{ active: menuOpen }">
        <div class="hamburger-menu">
          <span class="hamburger-line"></span>
          <span class="hamburger-line"></span>
          <span class="hamburger-line"></span>
          <span class="hamburger-line"></span>
          <span class="hamburger-line"></span>
          <span class="hamburger-line"></span>
        </div>
      </button>

      <!-- Right Menu -->
      <div class="flex items-center space-x-6">
        <NuxtLink
          to="/messages"
          class="relative p-2 hover:bg-secondary-100 rounded-full transition-colors duration-200"
          :class="{ 'text-primary-600': isActive('/messages') }"
        >
          <Icon icon="mdi:message" class="w-6 h-6" />
        </NuxtLink>

        <NuxtLink
          to="/timeline"
          class="relative p-2 hover:bg-secondary-100 rounded-full transition-colors duration-200"
          :class="{ 'text-primary-600': isActive('/timeline') }"
        >
          <Icon icon="mdi:account" class="w-6 h-6" />
        </NuxtLink>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Icon } from '@iconify/vue'

const route = useRoute()
const menuOpen = ref(false)
const notificationCount = ref(3)

const isActive = (path: string) => {
  return route.path === path
}

const toggleMenu = () => {
  menuOpen.value = !menuOpen.value
  // TODO: Emit event to parent to toggle sidebar
}
</script>

<style scoped>
.hamburger-menu {
  @apply w-6 h-6 relative flex flex-col justify-between;
}

.hamburger-line {
  @apply w-full h-0.5 bg-secondary-700 transition-all duration-300;
}

.active .hamburger-line:nth-child(1) {
  @apply transform rotate-45 translate-y-2;
}

.active .hamburger-line:nth-child(2) {
  @apply opacity-0;
}

.active .hamburger-line:nth-child(3) {
  @apply transform -rotate-45 -translate-y-2;
}

.active .hamburger-line:nth-child(4),
.active .hamburger-line:nth-child(5),
.active .hamburger-line:nth-child(6) {
  @apply hidden;
}
</style>
