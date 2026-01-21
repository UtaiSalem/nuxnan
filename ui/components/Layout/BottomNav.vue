<template>
  <div
    class="fixed bottom-0 left-0 right-0 z-40 bg-white dark:bg-gray-900 border-t border-secondary-200 dark:border-gray-700 shadow-lg md:hidden transition-colors duration-300 safe-area-inset-bottom"
  >
    <div class="flex items-center justify-around px-2 py-2 pb-safe">
      <!-- News (ข่าว) -->
      <NuxtLink
        to="/play/newsfeed"
        class="flex flex-col items-center justify-center gap-1 min-w-[60px] p-3 hover:bg-secondary-100 rounded-xl transition-colors duration-200 active:scale-95"
        :class="{ 'text-primary-600 bg-primary-50': isActive('/play/newsfeed') }"
        aria-label="ข่าว"
      >
        <Icon icon="mdi:newspaper" class="w-6 h-6" />
        <span class="text-xs font-medium mt-0.5">ข่าว</span>
      </NuxtLink>

      <!-- Schools/Academies (โรงเรียน) -->
      <NuxtLink
        to="/academies"
        class="flex flex-col items-center justify-center gap-1 min-w-[60px] p-3 hover:bg-secondary-100 rounded-xl transition-colors duration-200 active:scale-95"
        :class="{ 'text-primary-600 bg-primary-50': isActive('/academies') }"
        aria-label="โรงเรียน"
      >
        <Icon icon="mdi:school" class="w-6 h-6" />
        <span class="text-xs font-medium mt-0.5">โรงเรียน</span>
      </NuxtLink>

      <!-- Courses (รายวิชา) -->
      <NuxtLink
        to="/courses"
        class="flex flex-col items-center justify-center gap-1 min-w-[60px] p-3 hover:bg-secondary-100 rounded-xl transition-colors duration-200 active:scale-95"
        :class="{ 'text-primary-600 bg-primary-50': isActive('/courses') }"
        aria-label="รายวิชา"
      >
        <Icon icon="mdi:book-open-variant" class="w-6 h-6" />
        <span class="text-xs font-medium mt-0.5">รายวิชา</span>
      </NuxtLink>

      <!-- Groups (สะสมแต้ม) -->
      <NuxtLink
        to="/play/groups"
        class="flex flex-col items-center justify-center gap-1 min-w-[60px] p-3 hover:bg-secondary-100 rounded-xl transition-colors duration-200 active:scale-95"
        :class="{ 'text-primary-600 bg-primary-50': isActive('/play/groups') }"
        aria-label="สะสมแต้ม"
      >
        <Icon icon="mdi:account-group" class="w-6 h-6" />
        <span class="text-xs font-medium mt-0.5">สะสมแต้ม</span>
      </NuxtLink>

      <!-- Live/Streams (ดูโฆษณา) -->
      <NuxtLink
        to="/play/streams"
        class="flex flex-col items-center justify-center gap-1 min-w-[60px] p-3 hover:bg-secondary-100 rounded-xl transition-colors duration-200 active:scale-95"
        :class="{ 'text-primary-600 bg-primary-50': isActive('/play/streams') }"
        aria-label="ดูโฆษณา"
      >
        <Icon icon="mdi:video" class="w-6 h-6" />
        <span class="text-xs font-medium mt-0.5">ดูโฆษณา</span>
      </NuxtLink>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Icon } from '@iconify/vue'

const route = useRoute()

const isActive = (path: string) => {
  return route.path === path || route.path.startsWith(path + '/')
}
</script>

<style scoped>
/* Safe area for mobile devices with notches/home indicators */
@supports (padding-bottom: env(safe-area-inset-bottom)) {
  .pb-safe {
    padding-bottom: env(safe-area-inset-bottom);
  }
}

/* Active state animation */
.active\:scale-95 {
  transform: scale(0.95);
}

/* Touch-friendly tap targets */
.min-w-\[60px\] {
  min-width: 60px;
  min-height: 60px;
}
</style>
