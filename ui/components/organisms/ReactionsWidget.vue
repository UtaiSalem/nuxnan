<script setup>
import { Icon } from '@iconify/vue'

defineProps({
  likes: {
    type: Number,
    default: 12642
  },
  loves: {
    type: Number,
    default: 8913
  },
  dislikes: {
    type: Number,
    default: 945
  },
  happy: {
    type: Number,
    default: 7034
  }
})
</script>

<template>
  <div class="vikinger-card">
    <div class="flex items-center justify-between mb-6">
      <h3 class="text-lg font-bold text-gray-800 dark:text-white">Reactions Received</h3>
      <Icon icon="fluent:more-horizontal-24-regular" class="w-5 h-5 text-gray-500" />
    </div>

    <div class="grid grid-cols-2 gap-4">
      <!-- Likes -->
      <div class="text-center">
        <div class="w-12 h-12 mx-auto mb-2 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
          <Icon icon="fluent:thumb-like-24-filled" class="w-6 h-6 text-blue-500" />
        </div>
        <div class="text-2xl font-bold text-gray-800 dark:text-white">{{ likes.toLocaleString() }}</div>
        <div class="text-xs font-semibold text-gray-600 dark:text-gray-400 mt-1">LIKES</div>
      </div>

      <!-- Loves -->
      <div class="text-center">
        <div class="w-12 h-12 mx-auto mb-2 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center">
          <Icon icon="fluent:heart-24-filled" class="w-6 h-6 text-red-500" />
        </div>
        <div class="text-2xl font-bold text-gray-800 dark:text-white">{{ loves.toLocaleString() }}</div>
        <div class="text-xs font-semibold text-gray-600 dark:text-gray-400 mt-1">LOVES</div>
      </div>

      <!-- Dislikes -->
      <div class="text-center">
        <div class="w-12 h-12 mx-auto mb-2 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
          <Icon icon="fluent:thumb-dislike-24-filled" class="w-6 h-6 text-gray-500" />
        </div>
        <div class="text-2xl font-bold text-gray-800 dark:text-white">{{ dislikes.toLocaleString() }}</div>
        <div class="text-xs font-semibold text-gray-600 dark:text-gray-400 mt-1">DISLIKES</div>
      </div>

      <!-- Happy -->
      <div class="text-center">
        <div class="w-12 h-12 mx-auto mb-2 rounded-full bg-yellow-100 dark:bg-yellow-900/30 flex items-center justify-center">
          <Icon icon="fluent:emoji-laugh-24-filled" class="w-6 h-6 text-yellow-500" />
        </div>
        <div class="text-2xl font-bold text-gray-800 dark:text-white">{{ happy.toLocaleString() }}</div>
        <div class="text-xs font-semibold text-gray-600 dark:text-gray-400 mt-1">HAPPY</div>
      </div>
    </div>
  </div>
</template>
