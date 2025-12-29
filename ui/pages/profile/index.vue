<script setup lang="ts">
// Redirect to own profile page
definePageMeta({
  layout: 'main',
  middleware: 'auth'
})

const authStore = useAuthStore()

// Redirect to user profile with own reference code or 'me'
onMounted(() => {
  if (authStore.user?.reference_code) {
    navigateTo(`/profile/${authStore.user.reference_code}`)
  } else {
    navigateTo('/profile/me')
  }
})
</script>

<template>
  <div class="flex items-center justify-center min-h-[400px]">
    <Icon icon="fluent:spinner-ios-20-regular" class="w-12 h-12 animate-spin text-vikinger-purple" />
  </div>
</template>
