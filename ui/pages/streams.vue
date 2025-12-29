<template>
  <div class="min-h-screen py-8 px-4 sm:px-6 lg:px-8 bg-gray-50 dark:bg-gray-900">
    <div class="max-w-4xl mx-auto">
      <!-- Page Header -->
      <div class="text-center mb-8">
        <div class="w-20 h-20 mx-auto mb-4 bg-gradient-to-br from-red-400 to-red-600 rounded-full flex items-center justify-center shadow-lg">
          <Icon name="mdi:video-wireless-outline" class="w-10 h-10 text-white" />
        </div>
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
          สตรีม
        </h1>
        <p class="text-gray-600 dark:text-gray-400 max-w-2xl mx-auto">
          ชมการถ่ายทอดสดและวิดีโอจากผู้สอน
        </p>
      </div>

      <!-- Coming Soon Card -->
      <BaseCard class="text-center py-12">
        <Icon name="mdi:clock-outline" class="w-16 h-16 mx-auto text-red-500 mb-4" />
        <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">
          เร็วๆ นี้
        </h2>
        <p class="text-gray-600 dark:text-gray-400">
          ระบบสตรีมกำลังอยู่ระหว่างการพัฒนา กรุณารอติดตามการอัปเดต
        </p>
      </BaseCard>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Icon } from '@iconify/vue'

definePageMeta({
  layout: 'main'
})

useHead({
  title: 'สตรีม - Nuxni'
})
</script>
