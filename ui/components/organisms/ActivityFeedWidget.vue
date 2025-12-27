<script setup>
import { Icon } from '@iconify/vue'

defineProps({
  activities: {
    type: Array,
    default: () => [
      {
        id: 1,
        user: { name: 'Nick Grissom', avatar: 'https://i.pravatar.cc/150?img=12' },
        action: 'unlocked a video',
        time: '8 minutes ago'
      },
      {
        id: 2,
        user: { name: 'Matt Parker', avatar: 'https://i.pravatar.cc/150?img=13' },
        action: 'Awesome! It was not easy!',
        time: '26 mins'
      },
      {
        id: 3,
        user: { name: 'Neko Bebop', avatar: 'https://i.pravatar.cc/150?img=14' },
        action: 'Awesome! It was not easy!',
        time: '31 mins'
      },
      {
        id: 4,
        user: { name: 'Bearded Wonder', avatar: 'https://i.pravatar.cc/150?img=15' },
        action: 'Boom! You got it and won!',
        time: '7hrs'
      },
      {
        id: 5,
        user: { name: 'James Murdock', avatar: 'https://i.pravatar.cc/150?img=16' },
        action: 'Awesome! It was not easy!',
        time: '7hrs'
      },
      {
        id: 6,
        user: { name: 'The Green Goo', avatar: 'https://i.pravatar.cc/150?img=17' },
        action: 'Get go stream! You won game!',
        time: '9hrs'
      },
      {
        id: 7,
        user: { name: 'Sarah Diamonds', avatar: 'https://i.pravatar.cc/150?img=18' },
        action: 'Hi! sending you new game!',
        time: '16hrs'
      },
      {
        id: 8,
        user: { name: 'Destroy Dex', avatar: 'https://i.pravatar.cc/150?img=19' },
        action: 'Get go stream! You won game!',
        time: '23hrs'
      },
      {
        id: 9,
        user: { name: 'Damian Dragoons', avatar: 'https://i.pravatar.cc/150?img=20' },
        action: 'Awesome! It was not easy!',
        time: '1day'
      }
    ]
  }
})
</script>

<template>
  <div class="vikinger-card">
    <div class="flex items-center justify-between mb-4 pb-4 border-b border-vikinger-light-400 dark:border-vikinger-dark-200">
      <h3 class="text-sm font-bold text-gray-800 dark:text-white">Activity Feed</h3>
      <button class="text-xs text-vikinger-purple hover:text-vikinger-cyan font-semibold">View All</button>
    </div>

    <div class="space-y-3 max-h-[600px] overflow-y-auto scrollbar-thin scrollbar-thumb-vikinger-purple/30 scrollbar-track-transparent">
      <div 
        v-for="activity in activities" 
        :key="activity.id"
        class="flex items-start gap-3 p-2 rounded-lg hover:bg-vikinger-light-300 dark:hover:bg-vikinger-dark-200 transition-colors cursor-pointer"
      >
        <div class="relative flex-shrink-0">
          <img 
            :src="activity.user.avatar" 
            :alt="activity.user.name"
            class="w-10 h-10 rounded-full object-cover"
          />
          <div class="absolute -bottom-1 -right-1 w-4 h-4 bg-green-500 rounded-full border-2 border-white dark:border-vikinger-dark-100"></div>
        </div>
        
        <div class="flex-1 min-w-0">
          <div class="flex items-start justify-between gap-2">
            <p class="text-sm font-semibold text-gray-800 dark:text-white truncate">
              {{ activity.user.name }}
            </p>
            <span class="text-xs text-gray-500 dark:text-gray-400 whitespace-nowrap">{{ activity.time }}</span>
          </div>
          <p class="text-xs text-gray-600 dark:text-gray-400 mt-0.5 line-clamp-2">
            {{ activity.action }}
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.scrollbar-thin::-webkit-scrollbar {
  width: 6px;
}

.scrollbar-thin::-webkit-scrollbar-track {
  background: transparent;
}

.scrollbar-thin::-webkit-scrollbar-thumb {
  background: rgba(97, 93, 250, 0.3);
  border-radius: 3px;
}

.scrollbar-thin::-webkit-scrollbar-thumb:hover {
  background: rgba(97, 93, 250, 0.5);
}
</style>
