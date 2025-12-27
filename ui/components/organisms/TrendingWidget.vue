<script setup lang="ts">
import BaseCard from '../atoms/BaseCard.vue'
import TrendingItem from '../molecules/TrendingItem.vue'
import { useTrending } from '~/composables/useTrending'
import { useFeedStore } from '~/stores/feed'

const { fetchTrending } = useTrending()
const feedStore = useFeedStore()

onMounted(() => {
  fetchTrending()
})
</script>

<template>
  <BaseCard>
    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 border-b border-gray-100 dark:border-gray-700 pb-2">
      Trending Topics
    </h3>
    <div class="space-y-1">
      <TrendingItem 
        v-for="(item, index) in feedStore.trendingTopics" 
        :key="index" 
        :tag="item.tag" 
        :count="item.count" 
      />
    </div>
  </BaseCard>
</template>
