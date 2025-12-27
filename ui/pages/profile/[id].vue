<script setup lang="ts">
import BaseCard from '~/components/atoms/BaseCard.vue'
import BaseAvatar from '~/components/atoms/BaseAvatar.vue'
import FeedPostCard from '~/components/organisms/FeedPostCard.vue'
import { useFeedStore } from '~/stores/feed'

definePageMeta({
  layout: 'default'
})

const route = useRoute()
const feedStore = useFeedStore()

// Mock user data based on ID
const user = ref({
  id: route.params.id,
  username: 'Sarah Jane',
  avatar: '/images/resources/user2.jpg',
  cover: '/images/resources/timeline-1.jpg',
  bio: 'Love traveling and photography 📸',
  stats: {
    posts: 120,
    followers: '1.5k',
    following: 450
  }
})
</script>

<template>
  <div>
    <!-- Cover & Profile Info -->
    <BaseCard no-padding class="mb-6 relative">
      <div class="h-48 md:h-64 w-full bg-gray-200 dark:bg-gray-700">
        <img :src="user.cover" alt="Cover" class="w-full h-full object-cover" />
      </div>
      
      <div class="px-6 pb-6">
        <div class="relative flex justify-between items-end -mt-12 mb-4">
          <div class="flex items-end gap-4">
            <BaseAvatar :src="user.avatar" size="xl" class="ring-4 ring-white dark:ring-gray-800" />
            <div class="mb-1">
              <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ user.username }}</h1>
              <p class="text-gray-500 dark:text-gray-400">{{ user.bio }}</p>
            </div>
          </div>
          <div class="mb-1 hidden sm:block">
            <div class="flex gap-4 text-center">
              <div>
                <span class="block font-bold text-gray-900 dark:text-white">{{ user.stats.posts }}</span>
                <span class="text-xs text-gray-500">Posts</span>
              </div>
              <div>
                <span class="block font-bold text-gray-900 dark:text-white">{{ user.stats.followers }}</span>
                <span class="text-xs text-gray-500">Followers</span>
              </div>
              <div>
                <span class="block font-bold text-gray-900 dark:text-white">{{ user.stats.following }}</span>
                <span class="text-xs text-gray-500">Following</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </BaseCard>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <div class="lg:col-span-2 space-y-6">
        <FeedPostCard 
          v-for="post in feedStore.posts" 
          :key="post.id" 
          :post="post" 
        />
      </div>
      <div class="lg:col-span-1">
        <BaseCard>
          <h3 class="font-bold mb-4">About</h3>
          <p class="text-gray-600 dark:text-gray-300">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.</p>
        </BaseCard>
      </div>
    </div>
  </div>
</template>
