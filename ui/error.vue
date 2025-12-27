<template>
  <div class="min-h-screen bg-gray-50 flex items-center justify-center p-4">
    <div class="text-center">
      <div class="mb-6">
        <Icon icon="mdi:alert-circle" class="w-24 h-24 text-primary-500 mx-auto mb-4" />
      </div>

      <h1 class="text-6xl font-bold text-secondary-900 mb-4">404</h1>
      <h2 class="text-2xl font-semibold text-secondary-700 mb-4">Page Not Found</h2>
      <p class="text-secondary-600 mb-8">
        Sorry, the page you are looking for doesn't exist or has been moved.
      </p>

      <NuxtLink
        to="/newsfeed"
        class="inline-flex items-center gap-2 px-6 py-3 bg-primary-600 text-white rounded-lg font-semibold hover:bg-primary-700 transition-colors"
      >
        <Icon icon="mdi:home" class="w-5 h-5" />
        Back to Home
      </NuxtLink>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Icon } from '@iconify/vue'

definePageMeta({
  layout: false,
})

useHead({
  title: '404 - Page Not Found',
})
</script>
