<script setup lang="ts">
import BaseAvatar from '../atoms/BaseAvatar.vue'

defineProps({
  friend: {
    type: Object,
    required: true
  }
})
</script>

<template>
  <div class="flex items-center gap-3 py-2 hover:bg-gray-50 dark:hover:bg-gray-800 px-2 rounded cursor-pointer transition-colors">
    <BaseAvatar :src="friend.avatar" size="sm" :status="friend.status" />
    <div class="flex-1 min-w-0">
      <h4 class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ friend.name }}</h4>
      <p class="text-xs text-gray-500 truncate">{{ friend.status === 'online' ? 'Online' : 'Offline' }}</p>
    </div>
  </div>
</template>
