<script setup lang="ts">
import BaseCard from '../atoms/BaseCard.vue'
import BaseAvatar from '../atoms/BaseAvatar.vue'
import BaseButton from '../atoms/BaseButton.vue'

defineProps({
  user: {
    type: Object,
    required: true
  }
})
</script>

<template>
  <BaseCard class="text-center">
    <div class="flex flex-col items-center">
      <BaseAvatar :src="user.avatar" size="xl" :status="user.status" class="mb-3" />
      <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ user.username }}</h3>
      <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">{{ user.bio || 'No bio available' }}</p>
      
      <div class="flex justify-center gap-4 w-full border-t border-gray-100 dark:border-gray-700 pt-4">
        <div class="text-center">
          <span class="block font-bold text-gray-900 dark:text-white">{{ user.stats?.posts || 0 }}</span>
          <span class="text-xs text-gray-500">Posts</span>
        </div>
        <div class="text-center">
          <span class="block font-bold text-gray-900 dark:text-white">{{ user.stats?.followers || 0 }}</span>
          <span class="text-xs text-gray-500">Followers</span>
        </div>
        <div class="text-center">
          <span class="block font-bold text-gray-900 dark:text-white">{{ user.stats?.following || 0 }}</span>
          <span class="text-xs text-gray-500">Following</span>
        </div>
      </div>
    </div>
  </BaseCard>
</template>
