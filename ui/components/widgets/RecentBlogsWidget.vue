<script setup>
import { Icon } from '@iconify/vue'

const blogs = ref([
  {
    id: 1,
    title: "Moira's fade reach much farther...",
    image: '/images/blog-1.jpg',
    time: '2 weeks ago',
    link: '/blog/1'
  },
  {
    id: 2,
    title: "Daniel asks The voice of doomfist...",
    image: '/images/blog-2.jpg',
    time: '3 months ago',
    link: '/blog/2'
  },
  {
    id: 3,
    title: "The socimo over watch scandals.",
    image: '/images/blog-3.jpg',
    time: '1 day before',
    link: '/blog/3'
  }
])
</script>

<template>
  <div class="vikinger-card">
    <div class="flex items-center justify-between mb-4">
      <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Recent Blogs</h4>
      <a href="/blog" class="text-xs text-vikinger-purple hover:underline">See All</a>
    </div>
    <ul class="space-y-3">
      <li v-for="blog in blogs" :key="blog.id" class="flex gap-3">
        <figure class="w-16 h-16 flex-shrink-0 rounded-lg overflow-hidden">
          <img :src="blog.image" :alt="blog.title" class="w-full h-full object-cover" />
        </figure>
        <div class="flex-1 min-w-0">
          <h6 class="text-sm font-semibold text-gray-800 dark:text-white mb-1 line-clamp-2">
            <NuxtLink :to="blog.link" class="hover:text-vikinger-purple transition-colors">
              {{ blog.title }}
            </NuxtLink>
          </h6>
          <span class="text-xs text-gray-500 dark:text-gray-400">{{ blog.time }}</span>
        </div>
      </li>
    </ul>
  </div>
</template>
