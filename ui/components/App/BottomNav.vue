<template>
  <nav class="fixed bottom-0 left-0 right-0 bg-white border-t border-secondary-200 z-40">
    <div class="flex items-center justify-around px-4 py-3">
      <!-- Home -->
      <NuxtLink
        to="/newsfeed"
        class="flex flex-col items-center gap-1 p-2 hover:bg-gray-50 rounded-lg transition-colors"
        :class="{ 'text-primary-600': isActive('/newsfeed') }"
      >
        <Icon icon="mdi:home" class="w-6 h-6" />
      </NuxtLink>

      <!-- Notifications -->
      <NuxtLink
        to="/notifications"
        class="relative flex flex-col items-center gap-1 p-2 hover:bg-gray-50 rounded-lg transition-colors"
        :class="{ 'text-primary-600': isActive('/notifications') }"
      >
        <Icon icon="mdi:bell" class="w-6 h-6" />
        <span
          v-if="notificationCount > 0"
          class="absolute top-1 right-1 bg-primary-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center"
        >
          {{ notificationCount }}
        </span>
      </NuxtLink>

      <!-- Menu Toggle -->
      <button
        @click="toggleMenu"
        class="flex flex-col items-center gap-1 p-2 hover:bg-gray-50 rounded-lg transition-colors"
      >
        <div class="flex flex-col gap-1">
          <span
            class="w-6 h-0.5 bg-current transition-all"
            :class="menuOpen ? 'rotate-45 translate-y-1.5' : ''"
          ></span>
          <span
            class="w-6 h-0.5 bg-current transition-all"
            :class="menuOpen ? 'opacity-0' : ''"
          ></span>
          <span
            class="w-6 h-0.5 bg-current transition-all"
            :class="menuOpen ? '-rotate-45 -translate-y-1.5' : ''"
          ></span>
        </div>
      </button>

      <!-- Messages -->
      <NuxtLink
        to="/messages"
        class="flex flex-col items-center gap-1 p-2 hover:bg-gray-50 rounded-lg transition-colors"
        :class="{ 'text-primary-600': isActive('/messages') }"
      >
        <Icon icon="mdi:message" class="w-6 h-6" />
      </NuxtLink>

      <!-- Profile -->
      <NuxtLink
        to="/timeline"
        class="flex flex-col items-center gap-1 p-2 hover:bg-gray-50 rounded-lg transition-colors"
        :class="{ 'text-primary-600': isActive('/timeline') }"
      >
        <Icon icon="mdi:account" class="w-6 h-6" />
      </NuxtLink>
    </div>
  </nav>
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
}

// Watch route changes to close menu
watch(
  () => route.path,
  () => {
    menuOpen.value = false
  }
)
</script>
