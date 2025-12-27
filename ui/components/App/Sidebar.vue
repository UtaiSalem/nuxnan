<template>
  <Transition name="slide">
    <aside v-if="isOpen" class="fixed inset-0 z-50" @click.self="closeSidebar">
      <!-- Overlay -->
      <div class="absolute inset-0 bg-black/50" @click="closeSidebar"></div>

      <!-- Sidebar Content -->
      <nav
        class="absolute left-0 top-0 bottom-0 w-80 max-w-[85vw] bg-white shadow-xl overflow-y-auto"
      >
        <div class="p-6">
          <!-- Close Button -->
          <button
            @click="closeSidebar"
            class="absolute top-4 right-4 p-2 hover:bg-gray-100 rounded-full transition-colors"
          >
            <Icon icon="mdi:close" class="w-6 h-6 text-secondary-700" />
          </button>

          <!-- Menu Items -->
          <ul class="space-y-2 mt-8">
            <li v-for="item in menuItems" :key="item.path">
              <NuxtLink
                :to="item.path"
                class="flex items-center gap-4 p-3 rounded-lg hover:bg-gray-50 transition-colors"
                :class="{ 'bg-primary-50 text-primary-600': isActive(item.path) }"
                @click="closeSidebar"
              >
                <Icon :name="item.icon" class="w-6 h-6" />
                <span class="font-medium">{{ item.label }}</span>
              </NuxtLink>
            </li>
          </ul>
        </div>
      </nav>
    </aside>
  </Transition>
</template>

<script setup lang="ts">
import { Icon } from '@iconify/vue'

const route = useRoute()
const isOpen = ref(false)

const menuItems = [
  { path: '/newsfeed', icon: 'mdi:home', label: 'Home' },
  { path: '/about', icon: 'mdi:account', label: 'My Profile' },
  { path: '/photos', icon: 'mdi:image-multiple', label: 'Photos' },
  { path: '/videos', icon: 'mdi:video', label: 'Videos' },
  { path: '/groups', icon: 'mdi:account-group', label: 'Groups' },
  { path: '/fav-page', icon: 'mdi:file-document', label: 'Pages' },
  { path: '/notifications', icon: 'mdi:bell', label: 'Notifications' },
  { path: '/messages', icon: 'mdi:message', label: 'Messages' },
  { path: '/followers', icon: 'mdi:account-multiple', label: 'Followers' },
  { path: '/blogs', icon: 'mdi:post', label: 'Blog Posts' },
  { path: '/marketplace', icon: 'mdi:store', label: 'Market Place' },
  { path: '/events', icon: 'mdi:calendar', label: 'Events' },
  { path: '/nearby', icon: 'mdi:map-marker', label: 'Nearby' },
  { path: '/settings', icon: 'mdi:cog', label: 'Settings' },
]

const isActive = (path: string) => {
  return route.path === path
}

const closeSidebar = () => {
  isOpen.value = false
}

const openSidebar = () => {
  isOpen.value = true
}

// Expose methods to parent
defineExpose({
  openSidebar,
  closeSidebar,
})
</script>

<style scoped>
.slide-enter-active,
.slide-leave-active {
  transition: all 0.3s ease;
}

.slide-enter-from .absolute.left-0,
.slide-leave-to .absolute.left-0 {
  transform: translateX(-100%);
}

.slide-enter-from .absolute.inset-0.bg-black\/50,
.slide-leave-to .absolute.inset-0.bg-black\/50 {
  opacity: 0;
}
</style>
