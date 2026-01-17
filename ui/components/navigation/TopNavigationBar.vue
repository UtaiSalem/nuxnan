<script setup>
import { Icon } from '@iconify/vue'

const route = useRoute()

const menuItems = [
  { 
    id: 'newsfeed', 
    label: 'Newsfeed', 
    href: '/play/newsfeed', 
    icon: 'fluent:news-24-regular' 
  },
  { 
    id: 'videos', 
    label: 'Videos', 
    href: '/videos', 
    icon: 'fluent:video-24-regular' 
  },
  { 
    id: 'courses', 
    label: 'Courses', 
    href: '/learn/courses', 
    icon: 'fluent:book-24-regular' 
  },
  { 
    id: 'books', 
    label: 'Books', 
    href: '/books', 
    icon: 'fluent:library-24-regular' 
  },
  { 
    id: 'blog', 
    label: 'Blog', 
    href: '/blog', 
    icon: 'fluent:document-24-regular' 
  },
  { 
    id: 'groups', 
    label: 'Groups', 
    href: '/play/groups', 
    icon: 'fluent:people-community-24-regular' 
  },
  { 
    id: 'homevisit', 
    label: 'Home Visit', 
    href: '/home-visit', 
    icon: 'fluent:home-24-regular' 
  },
]

const isActive = (href) => {
  return route.path.startsWith(href)
}
</script>

<template>
  <div class="bg-white dark:bg-vikinger-dark-100 border-b border-vikinger-light-400 dark:border-vikinger-dark-50/30 shadow-sm">
    <div class="container mx-auto px-4">
      <div class="flex items-center justify-between py-3">
        
        <!-- Left: Menu Toggle (Mobile) -->
        <button class="lg:hidden p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-vikinger-dark-200">
          <Icon icon="fluent:navigation-24-regular" class="w-6 h-6 text-gray-600 dark:text-gray-300" />
        </button>

        <!-- Center: Navigation Menu -->
        <div class="flex-1 flex items-center justify-center">
          <div class="flex items-center gap-1 overflow-x-auto scrollbar-hide">
            <NuxtLink
              v-for="item in menuItems"
              :key="item.id"
              :to="item.href"
              class="flex flex-col items-center gap-1 px-4 py-2 rounded-lg transition-all duration-300 whitespace-nowrap"
              :class="isActive(item.href) 
                ? 'bg-gradient-vikinger text-white shadow-vikinger' 
                : 'text-gray-600 dark:text-gray-300 hover:bg-vikinger-purple/10 dark:hover:bg-vikinger-purple/20'"
            >
              <Icon :icon="item.icon" class="w-6 h-6" />
              <span class="text-xs font-semibold">{{ item.label }}</span>
            </NuxtLink>
          </div>
        </div>

        <!-- Right: User Info (Optional) -->
        <div class="hidden md:flex items-center gap-4 text-sm text-gray-600 dark:text-gray-300">
          <div class="flex items-center gap-2">
            <Icon icon="fluent:people-24-regular" class="w-4 h-4" />
            <span>Followers: <strong>204</strong></span>
          </div>
          <div class="flex items-center gap-1">
            <Icon v-for="i in 5" :key="i" icon="fluent:star-24-filled" class="w-3 h-3 text-yellow-500" />
          </div>
        </div>

      </div>
    </div>
  </div>
</template>

<style scoped>
.scrollbar-hide::-webkit-scrollbar {
  display: none;
}
.scrollbar-hide {
  -ms-overflow-style: none;
  scrollbar-width: none;
}
</style>
