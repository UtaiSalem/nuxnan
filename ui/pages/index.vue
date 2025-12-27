<script setup lang="ts">
import NewsfeedTemplate from '~/components/templates/NewsfeedTemplate.vue'
import { useAuthStore } from '~/stores/auth'

definePageMeta({
  layout: 'default'
})

const authStore = useAuthStore()

if (!authStore.isAuthenticated) {
  await navigateTo('/welcome')
}
</script>

<template>
  <NewsfeedTemplate />
</template>

