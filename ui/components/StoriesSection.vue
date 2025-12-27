<template>
  <div class="mb-6">
    <!-- Header -->
    <div
      class="flex items-center justify-between mb-4 border-b border-gray-100 dark:border-gray-700 pb-2"
    >
      <h3 class="font-bold text-gray-900 dark:text-white">Recent Stories</h3>
      <button class="text-orange-500 text-sm font-medium hover:text-orange-600">See All</button>
    </div>

    <!-- Stories Grid -->
    <div class="grid grid-cols-4 gap-4">
      <!-- Add Story -->
      <button
        class="group relative aspect-[1/2] rounded-xl overflow-hidden bg-gray-100 dark:bg-gray-700 flex flex-col items-center justify-center hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
      >
        <div
          class="w-12 h-12 rounded-full bg-white dark:bg-gray-800 flex items-center justify-center mb-2 shadow-sm group-hover:scale-110 transition-transform"
        >
          <i class="pi pi-plus text-2xl text-blue-500"></i>
        </div>
        <span class="text-xs font-bold text-gray-500 dark:text-gray-400">Add Story</span>
      </button>

      <!-- Story Items -->
      <button
        v-for="story in stories"
        :key="story.id"
        class="group relative aspect-[1/2] rounded-xl overflow-hidden"
      >
        <img
          :src="story.image"
          :alt="story.user"
          class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
        />
        <div
          class="absolute inset-0 bg-gradient-to-b from-transparent via-transparent to-black/70"
        ></div>

        <!-- Avatar -->
        <div class="absolute top-3 left-3">
          <div class="w-10 h-10 rounded-full border-2 border-blue-500 overflow-hidden">
            <img :src="story.avatar" :alt="story.user" class="w-full h-full object-cover" />
          </div>
        </div>

        <!-- Username -->
        <p class="absolute bottom-4 left-3 right-3 text-sm font-bold text-white truncate text-left">
          {{ story.user }}
        </p>
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
const stories = ref([
  {
    id: 1,
    user: 'Jack Carter',
    avatar: '/images/resources/user1.jpg',
    image: '/images/resources/story-1.jpg',
  },
  {
    id: 2,
    user: 'Karlie Puse',
    avatar: '/images/resources/user2.jpg',
    image: '/images/resources/story-2.jpg',
  },
  {
    id: 3,
    user: 'Michael Doe',
    avatar: '/images/resources/user3.jpg',
    image: '/images/resources/story-3.jpg',
  },
])
</script>
