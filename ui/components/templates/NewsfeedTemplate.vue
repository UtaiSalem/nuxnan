<script setup lang="ts">
import CreatePostBox from '../feed/CreatePostBox.vue'
import FeedPostCard from '../organisms/FeedPostCard.vue'
import TrendingWidget from '../organisms/TrendingWidget.vue'
import ProfileCard from '../molecules/ProfileCard.vue'
import { useAuthStore } from '~/stores/auth'
import { useFeedStore } from '~/stores/feed'
import { usePosts } from '~/composables/usePosts'

const authStore = useAuthStore()
const feedStore = useFeedStore()
const { fetchPosts } = usePosts()

onMounted(() => {
  fetchPosts()
})
</script>

<template>
  <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
    <!-- Left Column: Profile & Shortcuts -->
    <div class="hidden lg:block lg:col-span-1">
      <div class="sticky top-20 space-y-6">
        <ProfileCard v-if="authStore.user" :user="authStore.user" />
      </div>
    </div>

    <!-- Middle Column: Feed -->
    <div class="lg:col-span-2">
      <CreatePostBox mode="compact" />
      
      <div class="space-y-6">
        <FeedPostCard 
          v-for="post in feedStore.posts" 
          :key="post.id" 
          :post="post" 
        />
      </div>
    </div>

    <!-- Right Column: Widgets -->
    <div class="hidden xl:block xl:col-span-1">
      <div class="sticky top-20 space-y-6">
        <TrendingWidget />
      </div>
    </div>
  </div>
</template>
