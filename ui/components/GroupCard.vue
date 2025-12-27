<template>
  <div class="bg-white rounded-xl shadow-sm overflow-hidden hover:shadow-md transition-shadow">
    <!-- Cover Image -->
    <div class="relative h-40">
      <img :src="group.cover" :alt="group.name" class="w-full h-full object-cover" />
      <div class="absolute inset-0 bg-gradient-to-b from-transparent to-black/50"></div>
    </div>

    <!-- Group Info -->
    <div class="p-4">
      <h3 class="text-lg font-bold text-secondary-900 mb-2">{{ group.name }}</h3>
      <p class="text-sm text-secondary-600 mb-3">{{ group.description }}</p>

      <!-- Stats -->
      <div class="flex items-center gap-4 text-xs text-secondary-500 mb-4">
        <span class="flex items-center gap-1">
          <Icon icon="mdi:account-group" class="w-4 h-4" />
          {{ formatNumber(group.members) }} members
        </span>
        <span class="flex items-center gap-1">
          <Icon icon="mdi:post" class="w-4 h-4" />
          {{ formatNumber(group.posts) }} posts
        </span>
      </div>

      <!-- Action Button -->
      <button
        v-if="group.isJoined"
        class="w-full px-4 py-2 bg-secondary-200 text-secondary-700 rounded-lg font-medium hover:bg-secondary-300 transition-colors"
      >
        Joined
      </button>
      <button
        v-else
        class="w-full px-4 py-2 bg-primary-600 text-white rounded-lg font-medium hover:bg-primary-700 transition-colors"
      >
        Join Group
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Icon } from '@iconify/vue'

interface Props {
  group: {
    id: number
    name: string
    description: string
    cover: string
    members: number
    posts: number
    isJoined: boolean
  }
}

defineProps<Props>()

const formatNumber = (num: number) => {
  if (num >= 1000) {
    return (num / 1000).toFixed(1) + 'K'
  }
  return num.toString()
}
</script>
