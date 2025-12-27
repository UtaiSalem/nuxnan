<template>
  <article class="bg-white rounded-xl shadow-sm overflow-hidden">
    <!-- Post Header -->
    <div class="p-4 flex items-center justify-between">
      <div class="flex items-center gap-3">
        <NuxtLink :to="`/profile/${post.author.username}`">
          <img
            :src="post.author.avatar"
            :alt="post.author.name"
            class="w-12 h-12 rounded-full object-cover hover:ring-2 hover:ring-primary-500 transition-all"
          />
        </NuxtLink>

        <div>
          <NuxtLink
            :to="`/profile/${post.author.username}`"
            class="font-semibold text-secondary-900 hover:text-primary-600 transition-colors"
          >
            {{ post.author.name }}
          </NuxtLink>
          <p class="text-xs text-secondary-500">{{ post.timestamp }}</p>
        </div>
      </div>

      <button class="p-2 hover:bg-gray-100 rounded-full transition-colors">
        <Icon icon="mdi:dots-horizontal" class="w-5 h-5 text-secondary-600" />
      </button>
    </div>

    <!-- Post Content -->
    <div class="px-4 pb-3">
      <p class="text-secondary-700 leading-relaxed">{{ post.content }}</p>
    </div>

    <!-- Post Images -->
    <div v-if="post.images && post.images.length > 0" class="relative">
      <div v-if="post.images.length === 1">
        <img :src="post.images[0]" alt="Post image" class="w-full max-h-[500px] object-cover" />
      </div>

      <div v-else class="grid grid-cols-2 gap-1">
        <img
          v-for="(image, index) in post.images.slice(0, 4)"
          :key="index"
          :src="image"
          alt="Post image"
          class="w-full aspect-square object-cover"
          :class="{ 'col-span-2': index === 0 && post.images.length === 3 }"
        />
      </div>
    </div>

    <!-- Post Stats -->
    <div
      class="px-4 py-3 flex items-center justify-between text-sm text-secondary-600 border-t border-secondary-100"
    >
      <button class="hover:text-primary-600 transition-colors">{{ post.likes }} likes</button>
      <div class="flex items-center gap-4">
        <button class="hover:text-primary-600 transition-colors">
          {{ post.comments }} comments
        </button>
        <button class="hover:text-primary-600 transition-colors">{{ post.shares }} shares</button>
      </div>
    </div>

    <!-- Post Actions -->
    <div class="px-4 py-3 flex items-center justify-around border-t border-secondary-100">
      <button
        @click="toggleLike"
        class="flex items-center gap-2 px-4 py-2 hover:bg-gray-50 rounded-lg transition-all"
        :class="{ 'text-primary-600': post.isLiked }"
      >
        <Icon :name="post.isLiked ? 'mdi:heart' : 'mdi:heart-outline'" class="w-5 h-5" />
        <span class="text-sm font-medium">Like</span>
      </button>

      <button
        class="flex items-center gap-2 px-4 py-2 hover:bg-gray-50 rounded-lg transition-colors"
      >
        <Icon icon="mdi:comment-outline" class="w-5 h-5" />
        <span class="text-sm font-medium">Comment</span>
      </button>

      <button
        class="flex items-center gap-2 px-4 py-2 hover:bg-gray-50 rounded-lg transition-colors"
      >
        <Icon icon="mdi:share-outline" class="w-5 h-5" />
        <span class="text-sm font-medium">Share</span>
      </button>
    </div>
  </article>
</template>

<script setup lang="ts">
import { Icon } from '@iconify/vue'

interface Props {
  post: any
}

const props = defineProps<Props>()

const toggleLike = () => {
  props.post.isLiked = !props.post.isLiked
  if (props.post.isLiked) {
    props.post.likes++
  } else {
    props.post.likes--
  }
}
</script>
