<script setup>
import { Icon } from '@iconify/vue'
import { ref } from 'vue'

const stories = ref([
  { id: 1, name: 'Tamana Bhatia', image: '/images/story-1.jpg', avatar: 'https://i.pravatar.cc/150?img=1' },
  { id: 2, name: 'Emily Caros', image: '/images/story-2.jpg', avatar: 'https://i.pravatar.cc/150?img=2' },
  { id: 3, name: 'Daniel Cardos', image: '/images/story-3.jpg', avatar: 'https://i.pravatar.cc/150?img=3' },
  { id: 4, name: 'Emma Watson', image: '/images/story-4.jpg', avatar: 'https://i.pravatar.cc/150?img=4' },
])

const addStory = () => {
  // Add story functionality
}

const viewStory = (story) => {
  // View story functionality
}
</script>

<template>
  <div class="vikinger-card">
    <div class="flex items-center justify-between mb-4">
      <h3 class="text-lg font-bold text-gray-800 dark:text-white">Recent Stories</h3>
      <a href="#" class="text-sm text-vikinger-purple hover:underline">See all</a>
    </div>
    
    <div class="flex gap-3 overflow-x-auto scrollbar-hide pb-2">
      
      <!-- Add Your Story -->
      <div 
        @click="addStory"
        class="relative flex-shrink-0 w-32 h-48 rounded-xl overflow-hidden cursor-pointer group"
      >
        <div class="absolute inset-0 bg-gradient-to-br from-vikinger-purple to-vikinger-cyan"></div>
        <div class="absolute inset-0 bg-black/20 group-hover:bg-black/30 transition-colors"></div>
        
        <div class="relative h-full flex flex-col items-center justify-center text-white">
          <div class="w-12 h-12 bg-white/20 backdrop-blur-sm rounded-full flex items-center justify-center mb-2 group-hover:scale-110 transition-transform">
            <Icon icon="fluent:add-24-filled" class="w-6 h-6" />
          </div>
          <span class="text-sm font-semibold">Add Story</span>
        </div>
      </div>

      <!-- Friend Stories -->
      <div 
        v-for="story in stories" 
        :key="story.id"
        @click="viewStory(story)"
        class="relative flex-shrink-0 w-32 h-48 rounded-xl overflow-hidden cursor-pointer group"
      >
        <!-- Story Image -->
        <img :src="story.image" :alt="story.name" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" />
        
        <!-- Gradient Overlay -->
        <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent"></div>
        
        <!-- User Avatar -->
        <div class="absolute top-3 left-3">
          <div class="w-10 h-10 rounded-full border-3 border-vikinger-purple overflow-hidden">
            <img :src="story.avatar" :alt="story.name" class="w-full h-full object-cover" />
          </div>
        </div>
        
        <!-- User Name -->
        <div class="absolute bottom-2 left-2 right-2">
          <span class="text-white text-sm font-semibold line-clamp-2">{{ story.name }}</span>
        </div>
      </div>

    </div>
  </div>
</template>

<style scoped>
.scrollbar-hide::-webkit-scrollbar {
  display: none;
}
.scrollbar-hide {
  -ms-overflow-style: none;
  scrollbar-width: none;
}
</style>
