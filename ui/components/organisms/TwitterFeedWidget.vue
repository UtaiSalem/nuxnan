<script setup lang="ts">
import BaseCard from '../atoms/BaseCard.vue'
import BaseIcon from '../atoms/BaseIcon.vue'
import BaseAvatar from '../atoms/BaseAvatar.vue'

const tweets = [
  {
    id: 1,
    user: 'Jhon William',
    handle: '@jhonwilliam',
    content: 'tomorrow with the company we were working and 5 childs.',
    time: '2 hours ago'
  },
  {
    id: 2,
    user: 'Jhon William',
    handle: '@jhonwilliam',
    content: 'tomorrow with the company we were working and 5 childs.',
    time: '2 hours ago'
  }
]
</script>

<template>
  <BaseCard class="mb-6">
    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 border-b border-gray-100 dark:border-gray-700 pb-2">
      Twitter Feed
    </h3>
    
    <div class="space-y-4">
      <div v-for="tweet in tweets" :key="tweet.id" class="flex gap-3">
        <div class="flex-shrink-0">
           <BaseIcon name="lni:twitter" class="text-blue-400 text-2xl" />
        </div>
        <div>
          <div class="flex items-center gap-2 mb-1">
            <span class="font-bold text-sm text-gray-800 dark:text-white">{{ tweet.user }}</span>
            <span class="text-xs text-gray-500">{{ tweet.handle }}</span>
          </div>
          <p class="text-sm text-gray-600 dark:text-gray-300 mb-1">{{ tweet.content }}</p>
          <span class="text-xs text-gray-400">{{ tweet.time }}</span>
        </div>
      </div>
    </div>
  </BaseCard>
</template>
