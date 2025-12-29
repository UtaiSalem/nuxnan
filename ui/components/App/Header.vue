<template>
  <header class="fixed top-0 left-0 right-0 bg-white shadow-sm z-40">
    <div class="flex items-center justify-between px-4 py-3">
      <!-- Back Button -->
      <button
        @click="goBack"
        class="p-2 hover:bg-gray-100 rounded-full transition-colors"
        title="Back"
      >
        <Icon icon="mdi:chevron-double-left" class="w-6 h-6 text-secondary-700" />
      </button>

      <!-- Search Button -->
      <button
        @click="openSearch"
        class="p-2 hover:bg-gray-100 rounded-full transition-colors"
        title="Search"
      >
        <Icon icon="mdi:magnify" class="w-6 h-6 text-secondary-700" />
      </button>

      <!-- Logo -->
      <NuxtLink to="/newsfeed" class="flex-1 flex justify-center">
        <div class="flex items-center gap-2">
          <Icon icon="mdi:account-group" class="w-8 h-8 text-primary-600" />
          <span class="text-xl font-bold text-secondary-900">Pitnik</span>
        </div>
      </NuxtLink>

      <!-- Settings Button -->
      <button
        @click="openSettings"
        class="p-2 hover:bg-gray-100 rounded-full transition-colors"
        title="Settings"
      >
        <Icon icon="mdi:dots-vertical" class="w-6 h-6 text-secondary-700" />
      </button>

      <!-- New Post Button -->
      <button
        @click="openNewPost"
        class="p-2 hover:bg-primary-100 rounded-full transition-colors"
        title="Create New Post"
      >
        <Icon icon="mdi:plus" class="w-6 h-6 text-primary-600" />
      </button>
    </div>
  </header>
</template>

<script setup lang="ts">
import { Icon } from '@iconify/vue'

const router = useRouter()

const goBack = () => {
  router.back()
}

const openSearch = () => {
  navigateTo('/search')
}

const openSettings = () => {
  navigateTo('/settings')
}

const openNewPost = () => {
  // Open new post modal or navigate to create post page
}
</script>
