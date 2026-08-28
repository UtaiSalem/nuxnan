<script setup lang="ts">
import { Icon } from '@iconify/vue'

// Redirect to own profile page
definePageMeta({
  layout: 'main',
  middleware: 'auth'
})

const authStore = useAuthStore()

// Redirect to user profile with own reference code or 'me'
onMounted(() => {
  const targetId = authStore.user?.personal_code || authStore.user?.reference_code
  if (targetId) {
    navigateTo(`/profile/${targetId}`)
  } else {
    navigateTo('/profile/me')
  }
})
</script>

<template>
  <div class="flex items-center justify-center min-h-[400px] px-4 sm:px-0">
    <Icon icon="fluent:spinner-ios-20-regular" class="w-12 h-12 animate-spin text-vikinger-purple" aria-hidden="true" />
  </div>
</template>
