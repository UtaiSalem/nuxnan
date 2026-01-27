<script setup>
import { Icon } from '@iconify/vue'
const route = useRoute()

const navigation = [
  { 
    name: 'ฟีดข่าว', 
    href: '/play/newsfeed', 
    icon: 'fluent:chat-bubbles-question-24-regular', 
    activeIcon: 'fluent:chat-bubbles-question-24-filled' 
  },
  { 
    name: 'โรงเรียน', 
    href: '/academies', 
    icon: 'mdi:school-outline', 
    activeIcon: 'mdi:school' 
  },
  { 
    name: 'คอร์สเรียน', 
    href: '/learn/courses', 
    icon: 'fluent:book-24-regular', 
    activeIcon: 'fluent:book-24-filled' 
  },
  { 
    name: 'รายได้', 
    href: '/earn/points', 
    icon: 'fluent:wallet-24-regular', 
    activeIcon: 'fluent:wallet-24-filled' 
  },
  { 
    name: 'แดชบอร์ด', 
    href: '/dashboard', 
    icon: 'fluent:grid-24-regular', 
    activeIcon: 'fluent:grid-24-filled' 
  },
]
</script>

<template>
  <div class="fixed bottom-0 left-0 right-0 z-50 bg-white dark:bg-vikinger-dark-100 border-t border-gray-200 dark:border-vikinger-dark-50/30 lg:hidden pb-safe">
    <div class="flex items-center justify-around h-16 px-1">
      <NuxtLink
        v-for="item in navigation"
        :key="item.href"
        :to="item.href"
        class="flex flex-col items-center justify-center w-full h-full gap-0.5 transition-colors relative group py-1"
        :class="route.path.startsWith(item.href) ? 'text-vikinger-purple dark:text-vikinger-cyan' : 'text-gray-500 dark:text-gray-400 hover:text-vikinger-purple dark:hover:text-vikinger-cyan'"
      >
        <div class="relative">
           <Icon :icon="route.path.startsWith(item.href) ? item.activeIcon : item.icon" class="w-6 h-6" />
        </div>
        <span class="text-[10px] font-medium truncate max-w-[64px] leading-tight">{{ item.name }}</span>
      </NuxtLink>
    </div>
  </div>
</template>

<style scoped>
.pb-safe {
  padding-bottom: env(safe-area-inset-bottom);
}
</style>
