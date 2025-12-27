<template>
  <nav
    class="fixed left-0 top-0 h-full w-64 bg-white dark:bg-gray-900 shadow-2xl transform transition-transform duration-300 z-50 overflow-y-auto custom-scrollbar"
    :class="{ '-translate-x-full': !isOpen, 'translate-x-0': isOpen }"
  >
    <!-- Close Button -->
    <button
      @click="closeSidebar"
      class="absolute top-4 right-4 p-2 hover:bg-secondary-100 dark:hover:bg-gray-800 rounded-full transition-colors duration-200"
    >
      <Icon icon="mdi:close-circle" class="w-6 h-6 text-secondary-600 dark:text-gray-400" />
    </button>

    <!-- User Profile Section -->
    <div class="p-6 border-b border-secondary-200 dark:border-gray-700">
      <div class="flex items-center space-x-3">
        <div class="relative">
          <img
            src="/images/resources/user3.jpg"
            alt="Profile"
            class="w-16 h-16 rounded-full object-cover"
          />
          <span
            class="absolute bottom-0 right-0 w-4 h-4 bg-green-500 border-2 border-white rounded-full"
          ></span>
        </div>
        <div class="flex-1">
          <h5 class="font-semibold text-secondary-900 dark:text-gray-100">Monica Gill</h5>
          <NuxtLink to="/timeline" class="text-sm text-primary-600 hover:text-primary-700">
            view profile
          </NuxtLink>
        </div>
      </div>
    </div>

    <!-- Menu Items -->
    <ul class="py-4">
      <li v-for="item in menuItems" :key="item.path">
        <NuxtLink
          :to="item.path"
          class="flex items-center space-x-3 px-6 py-3 hover:bg-secondary-50 dark:hover:bg-gray-800 transition-colors duration-200"
          :class="{ 'bg-primary-50 text-primary-600 dark:bg-gray-800 dark:text-primary-400': isActivePath(item.path) }"
          @click="closeSidebar"
        >
          <div class="w-8 h-8 flex items-center justify-center">
            <img :src="item.icon" :alt="item.label" class="w-6 h-6" />
          </div>
          <span class="font-medium dark:text-gray-200">{{ item.label }}</span>
        </NuxtLink>
      </li>
    </ul>

    <!-- Night Mode Toggle -->
    <div class="px-6 py-4 border-t border-secondary-200 dark:border-gray-700">
      <div class="flex items-center justify-between">
        <div class="flex items-center space-x-3">
          <Icon icon="mdi:weather-night" class="w-5 h-5 text-secondary-600" />
          <span class="font-medium text-secondary-700 dark:text-gray-300">Night Mode</span>
        </div>
        <label class="relative inline-flex items-center cursor-pointer">
          <input type="checkbox" v-model="isDarkMode" @change="toggleTheme" class="sr-only peer" />
          <div
            class="w-11 h-6 bg-secondary-300 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-secondary-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary-600"
          ></div>
        </label>
      </div>
    </div>

    <!-- Additional Options -->
    <ul class="px-6 py-4 space-y-2 border-t border-secondary-200 dark:border-gray-700">
      <li>
        <button
          class="flex items-center space-x-3 text-secondary-700 dark:text-gray-300 hover:text-primary-600 dark:hover:text-primary-400 transition-colors duration-200"
        >
          <Icon icon="mdi:inbox" class="w-5 h-5" />
          <span>Inbox</span>
        </button>
      </li>
      <li>
        <button
          class="flex items-center space-x-3 text-secondary-700 hover:text-primary-600 transition-colors duration-200"
        >
          <Icon icon="mdi:lock" class="w-5 h-5" />
          <span>Lock screen</span>
        </button>
      </li>
      <li>
        <button
          class="flex items-center space-x-3 text-secondary-700 hover:text-primary-600 transition-colors duration-200"
        >
          <Icon icon="mdi:cog" class="w-5 h-5" />
          <span>Setting</span>
        </button>
      </li>
      <li>
        <button
          class="flex items-center space-x-3 text-secondary-700 hover:text-primary-600 transition-colors duration-200"
        >
          <Icon icon="mdi:shield-check" class="w-5 h-5" />
          <span>Privacy</span>
        </button>
      </li>
      <li>
        <button
          class="flex items-center space-x-3 text-secondary-700 hover:text-primary-600 transition-colors duration-200"
        >
          <Icon icon="mdi:help-circle" class="w-5 h-5" />
          <span>Help</span>
        </button>
      </li>
      <li>
        <button
          class="flex items-center space-x-3 text-secondary-700 hover:text-primary-600 transition-colors duration-200"
        >
          <Icon icon="mdi:bookmark" class="w-5 h-5" />
          <span>Saved Posts</span>
        </button>
      </li>
      <li>
        <button
          class="flex items-center space-x-3 text-secondary-700 hover:text-primary-600 transition-colors duration-200"
        >
          <Icon icon="mdi:power" class="w-5 h-5" />
          <span>Logout</span>
        </button>
      </li>
    </ul>
  </nav>

  <!-- Overlay -->
  <div
    v-if="isOpen"
    @click="closeSidebar"
    class="fixed inset-0 bg-black/50 z-40 transition-opacity duration-300"
  ></div>
</template>

<script setup lang="ts">
import { Icon } from '@iconify/vue'
import { useUIStore } from '~/stores/ui'

const route = useRoute()
const uiStore = useUIStore()

// Use UI store for dark mode
const isDarkMode = computed(() => uiStore.isDarkMode)

const toggleTheme = () => {
  uiStore.toggleTheme()
}

const props = defineProps<{
  isOpen: boolean
}>()

const emit = defineEmits<{
  close: []
}>()

const menuItems = [
  { path: '/newsfeed', icon: '/images/svg/home.png', label: 'Home' },
  { path: '/timeline', icon: '/images/svg/user.png', label: 'My Profile' },
  { path: '/photos', icon: '/images/svg/photos.png', label: 'Photos' },
  { path: '/videos', icon: '/images/svg/video.png', label: 'Videos' },
  { path: '/groups', icon: '/images/svg/groups.png', label: 'Groups' },
  { path: '/pages', icon: '/images/svg/pages.png', label: 'Pages' },
  { path: '/notifications', icon: '/images/svg/about.png', label: 'Notifications' },
  { path: '/messages', icon: '/images/svg/inbox.png', label: 'Messages' },
  { path: '/followers', icon: '/images/svg/users.png', label: 'Followers' },
  { path: '/blogs', icon: '/images/svg/news.png', label: 'Blog Posts' },
  { path: '/marketplace', icon: '/images/svg/market.png', label: 'Market Place' },
  { path: '/events', icon: '/images/svg/event.png', label: 'Events' },
  { path: '/nearby', icon: '/images/svg/event.png', label: 'Nearby' },
  { path: '/settings', icon: '/images/svg/setting.png', label: 'Settings' },
]

const isActivePath = (path: string) => {
<template>
  <nav
    class="fixed left-0 top-0 h-full w-64 bg-white dark:bg-gray-900 shadow-2xl transform transition-transform duration-300 z-50 overflow-y-auto custom-scrollbar"
    :class="{ '-translate-x-full': !isOpen, 'translate-x-0': isOpen }"
  >
    <!-- Close Button -->
    <button
      @click="closeSidebar"
      class="absolute top-4 right-4 p-2 hover:bg-secondary-100 dark:hover:bg-gray-800 rounded-full transition-colors duration-200"
    >
      <Icon icon="mdi:close-circle" class="w-6 h-6 text-secondary-600 dark:text-gray-400" />
    </button>

    <!-- User Profile Section -->
    <div class="p-6 border-b border-secondary-200 dark:border-gray-700">
      <div class="flex items-center space-x-3">
        <div class="relative">
          <img
            src="/images/resources/user3.jpg"
            alt="Profile"
            class="w-16 h-16 rounded-full object-cover"
          />
          <span
            class="absolute bottom-0 right-0 w-4 h-4 bg-green-500 border-2 border-white rounded-full"
          ></span>
        </div>
        <div class="flex-1">
          <h5 class="font-semibold text-secondary-900 dark:text-gray-100">Monica Gill</h5>
          <NuxtLink to="/timeline" class="text-sm text-primary-600 hover:text-primary-700">
            view profile
          </NuxtLink>
        </div>
      </div>
    </div>

    <!-- Menu Items -->
    <ul class="py-4">
      <li v-for="item in menuItems" :key="item.path">
        <NuxtLink
          :to="item.path"
          class="flex items-center space-x-3 px-6 py-3 hover:bg-secondary-50 dark:hover:bg-gray-800 transition-colors duration-200"
          :class="{ 'bg-primary-50 text-primary-600 dark:bg-gray-800 dark:text-primary-400': isActivePath(item.path) }"
          @click="closeSidebar"
        >
          <div class="w-8 h-8 flex items-center justify-center">
            <img :src="item.icon" :alt="item.label" class="w-6 h-6" />
          </div>
          <span class="font-medium dark:text-gray-200">{{ item.label }}</span>
        </NuxtLink>
      </li>
    </ul>

    <!-- Night Mode Toggle -->
    <div class="px-6 py-4 border-t border-secondary-200 dark:border-gray-700">
      <div class="flex items-center justify-between">
        <div class="flex items-center space-x-3">
          <Icon icon="mdi:weather-night" class="w-5 h-5 text-secondary-600" />
          <span class="font-medium text-secondary-700 dark:text-gray-300">Night Mode</span>
        </div>
        <label class="relative inline-flex items-center cursor-pointer">
          <input type="checkbox" v-model="isDarkMode" @change="toggleTheme" class="sr-only peer" />
          <div
            class="w-11 h-6 bg-secondary-300 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-secondary-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary-600"
          ></div>
        </label>
      </div>
    </div>

    <!-- Additional Options -->
    <ul class="px-6 py-4 space-y-2 border-t border-secondary-200 dark:border-gray-700">
      <li>
        <button
          class="flex items-center space-x-3 text-secondary-700 dark:text-gray-300 hover:text-primary-600 dark:hover:text-primary-400 transition-colors duration-200"
        >
          <Icon icon="mdi:inbox" class="w-5 h-5" />
          <span>Inbox</span>
        </button>
      </li>
      <li>
        <button
          class="flex items-center space-x-3 text-secondary-700 hover:text-primary-600 transition-colors duration-200"
        >
          <Icon icon="mdi:lock" class="w-5 h-5" />
          <span>Lock screen</span>
        </button>
      </li>
      <li>
        <button
          class="flex items-center space-x-3 text-secondary-700 hover:text-primary-600 transition-colors duration-200"
        >
          <Icon icon="mdi:cog" class="w-5 h-5" />
          <span>Setting</span>
        </button>
      </li>
      <li>
        <button
          class="flex items-center space-x-3 text-secondary-700 hover:text-primary-600 transition-colors duration-200"
        >
          <Icon icon="mdi:shield-check" class="w-5 h-5" />
          <span>Privacy</span>
        </button>
      </li>
      <li>
        <button
          class="flex items-center space-x-3 text-secondary-700 hover:text-primary-600 transition-colors duration-200"
        >
          <Icon icon="mdi:help-circle" class="w-5 h-5" />
          <span>Help</span>
        </button>
      </li>
      <li>
        <button
          class="flex items-center space-x-3 text-secondary-700 hover:text-primary-600 transition-colors duration-200"
        >
          <Icon icon="mdi:bookmark" class="w-5 h-5" />
          <span>Saved Posts</span>
        </button>
      </li>
      <li>
        <button
          class="flex items-center space-x-3 text-secondary-700 hover:text-primary-600 transition-colors duration-200"
        >
          <Icon icon="mdi:power" class="w-5 h-5" />
          <span>Logout</span>
        </button>
      </li>
    </ul>
  </nav>

  <!-- Overlay -->
  <div
    v-if="isOpen"
    @click="closeSidebar"
    class="fixed inset-0 bg-black/50 z-40 transition-opacity duration-300"
  ></div>
</template>

<script setup lang="ts">
import { Icon } from '@iconify/vue'
import { useUIStore } from '~/stores/ui'

const route = useRoute()
const uiStore = useUIStore()

// Use UI store for dark mode
const isDarkMode = computed(() => uiStore.isDarkMode)

const toggleTheme = () => {
  uiStore.toggleTheme()
}

const props = defineProps<{
  isOpen: boolean
}>()

const emit = defineEmits<{
  close: []
}>()

const menuItems = [
  { path: '/newsfeed', icon: '/images/svg/home.png', label: 'Home' },
  { path: '/timeline', icon: '/images/svg/user.png', label: 'My Profile' },
  { path: '/photos', icon: '/images/svg/photos.png', label: 'Photos' },
  { path: '/videos', icon: '/images/svg/video.png', label: 'Videos' },
  { path: '/groups', icon: '/images/svg/groups.png', label: 'Groups' },
  { path: '/pages', icon: '/images/svg/pages.png', label: 'Pages' },
  { path: '/notifications', icon: '/images/svg/about.png', label: 'Notifications' },
  { path: '/messages', icon: '/images/svg/inbox.png', label: 'Messages' },
  { path: '/followers', icon: '/images/svg/users.png', label: 'Followers' },
  { path: '/blogs', icon: '/images/svg/news.png', label: 'Blog Posts' },
  { path: '/marketplace', icon: '/images/svg/market.png', label: 'Market Place' },
  { path: '/events', icon: '/images/svg/event.png', label: 'Events' },
  { path: '/nearby', icon: '/images/svg/event.png', label: 'Nearby' },
  { path: '/settings', icon: '/images/svg/setting.png', label: 'Settings' },
]

const isActivePath = (path: string) => {
  return route.path === path
}

const closeSidebar = () => {
  emit('close')
}
</script>
