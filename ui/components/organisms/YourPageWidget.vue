<script setup lang="ts">
import BaseCard from '../atoms/BaseCard.vue'
import BaseAvatar from '../atoms/BaseAvatar.vue'
import BaseIcon from '../atoms/BaseIcon.vue'
import { useAuthStore } from '~/stores/auth'

const authStore = useAuthStore()
</script>

<template>
  <BaseCard class="mb-6">
    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 border-b border-gray-100 dark:border-gray-700 pb-2">
      Your Page
    </h3>
    
    <div class="flex items-center gap-4 mb-6">
      <BaseAvatar :src="authStore.user?.avatar || '/images/resources/user1.jpg'" size="lg" />
      <div>
        <h4 class="font-bold text-gray-900 dark:text-white">My Creative Page</h4>
        <div class="flex items-center gap-1 text-sm text-gray-500">
          <BaseIcon name="lni:envelope" />
          <span>Messages</span>
        </div>
        <div class="flex items-center gap-1 text-sm text-gray-500">
          <BaseIcon name="lni:alarm" />
          <span>Notifications</span>
        </div>
      </div>
    </div>
    
    <div class="flex justify-between mb-6 border-b border-gray-100 dark:border-gray-700 pb-4">
      <div class="flex flex-col items-center cursor-pointer hover:text-blue-500">
        <BaseIcon name="lni:pencil" size="lg" class="mb-1" />
        <span class="text-xs">Publish</span>
      </div>
      <div class="flex flex-col items-center cursor-pointer hover:text-blue-500">
        <BaseIcon name="lni:image" size="lg" class="mb-1" />
        <span class="text-xs">Photo</span>
      </div>
      <div class="flex flex-col items-center cursor-pointer hover:text-blue-500">
        <BaseIcon name="lni:video" size="lg" class="mb-1" />
        <span class="text-xs">Live</span>
      </div>
      <div class="flex flex-col items-center cursor-pointer hover:text-blue-500">
        <BaseIcon name="lni:users" size="lg" class="mb-1" />
        <span class="text-xs">Invite</span>
      </div>
    </div>
    
    <div class="flex gap-2 mb-4">
      <button class="flex-1 bg-orange-500 text-white py-1 px-3 rounded-full text-sm font-medium hover:bg-orange-600 transition-colors">
        Likes
      </button>
      <button class="flex-1 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 py-1 px-3 rounded-full text-sm font-medium hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
        Views
      </button>
    </div>
    
    <div class="text-center mb-4">
      <div class="text-2xl font-bold text-gray-800 dark:text-white flex items-center justify-center gap-2">
        <BaseIcon name="lni:heart" class="text-red-500" />
        884
      </div>
      <div class="text-sm text-orange-500">35 New Likes This Week</div>
    </div>
    
    <div class="flex justify-center -space-x-2">
      <BaseAvatar src="/images/resources/user1.jpg" size="sm" class="border-2 border-white dark:border-gray-800" />
      <BaseAvatar src="/images/resources/user2.jpg" size="sm" class="border-2 border-white dark:border-gray-800" />
      <BaseAvatar src="/images/resources/user3.jpg" size="sm" class="border-2 border-white dark:border-gray-800" />
      <BaseAvatar src="/images/resources/user4.jpg" size="sm" class="border-2 border-white dark:border-gray-800" />
      <BaseAvatar src="/images/resources/user5.jpg" size="sm" class="border-2 border-white dark:border-gray-800" />
    </div>
  </BaseCard>
</template>
