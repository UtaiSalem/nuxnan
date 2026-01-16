<script setup lang="ts">
import { Icon } from '@iconify/vue'

// Redirect to new profile settings route
definePageMeta({
  layout: 'main',
  middleware: 'auth',
})

useHead({
  title: 'ตั้งค่า'
})

const router = useRouter()
const authStore = useAuthStore()

// Redirect to /profile/{reference_code}/settings
onMounted(() => {
  if (authStore.user?.reference_code) {
    router.replace(`/profile/${authStore.user.reference_code}/settings`)
  }
})
</script>

<template>
  <div class="flex items-center justify-center min-h-[400px]">
    <div class="text-center">
      <Icon icon="fluent:spinner-ios-20-regular" class="w-12 h-12 animate-spin text-blue-500 mx-auto mb-4" />
      <p class="text-gray-600 dark:text-gray-400">กำลังเปลี่ยนเส้นทาง...</p>
    </div>
  </div>
</template>
