<script setup lang="ts">
import BaseCard from '../atoms/BaseCard.vue'
import BaseAvatar from '../atoms/BaseAvatar.vue'
import BaseIcon from '../atoms/BaseIcon.vue'
import PostAction from '../molecules/PostAction.vue'

defineProps({
  post: {
    type: Object,
    required: true,
  },
})
</script>

<template>
  <BaseCard class="mb-6">
    <!-- Header -->
    <div class="flex items-center justify-between mb-4">
      <div class="flex items-center gap-3">
        <BaseAvatar :src="post.author.avatar" size="md" />
        <div>
          <h4 class="font-bold text-gray-900 dark:text-white">{{ post.author.name }}</h4>
          <span class="text-xs text-gray-500">{{ post.time }}</span>
        </div>
      </div>
      <button class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
        <i class="pi pi-ellipsis-v text-xl"></i>
      </button>
    </div>

    <!-- Content -->
    <div class="mb-4">
      <p class="text-gray-800 dark:text-gray-200 mb-3">{{ post.content }}</p>
      <img
        v-if="post.image"
        :src="post.image"
        alt="Post image"
        class="w-full rounded-lg object-cover max-h-96"
      />
    </div>

    <!-- Actions -->
    <PostAction :likes="post.likes" :comments="post.comments" :shares="post.shares" />
  </BaseCard>
</template>
