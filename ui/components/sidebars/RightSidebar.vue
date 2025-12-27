<script setup>
import { ref, computed } from 'vue'
import { Icon } from '@iconify/vue'
import { useAuthStore } from '~/stores/auth'

const authStore = useAuthStore()

// Stats data
const userStats = computed(() => ({
  posts: 294,
  postsGrowth: 0.4,
  views: 87365,
  viewsGrowth: 3.2,
  likes: 12642,
  loves: 8913,
  dislikes: 945,
  happy: 7034,
}))

// Online friends
const onlineFriends = ref([
  { id: 1, name: 'Nick Grissom', avatar: 'https://i.pravatar.cc/150?img=11', status: 'online' },
  { id: 2, name: 'Sarah Chen', avatar: 'https://i.pravatar.cc/150?img=12', status: 'online' },
  { id: 3, name: 'Mike Johnson', avatar: 'https://i.pravatar.cc/150?img=13', status: 'away' },
  { id: 4, name: 'Emily Davis', avatar: 'https://i.pravatar.cc/150?img=14', status: 'online' },
  { id: 5, name: 'Alex Turner', avatar: 'https://i.pravatar.cc/150?img=15', status: 'busy' },
])

// Messages
const messages = ref([
  { id: 1, user: 'Nick Grissom', avatar: 'https://i.pravatar.cc/150?img=11', text: 'Can you stream the new game?', time: '2hrs' },
  { id: 2, user: 'Sarah Chen', avatar: 'https://i.pravatar.cc/150?img=12', text: 'Hey! Check out my latest post', time: '4hrs' },
  { id: 3, user: 'Mike Johnson', avatar: 'https://i.pravatar.cc/150?img=13', text: 'Thanks for the help!', time: '1d' },
])

// Events
const upcomingEvents = ref([
  { id: 1, title: 'Gaming Tournament', date: 'Dec 15', icon: 'fluent:games-24-regular' },
  { id: 2, title: 'Online Workshop', date: 'Dec 18', icon: 'fluent:book-24-regular' },
  { id: 3, title: 'Community Meetup', date: 'Dec 22', icon: 'fluent:people-24-regular' },
])

const getStatusColor = (status) => {
  switch (status) {
    case 'online': return 'bg-vikinger-green'
    case 'away': return 'bg-vikinger-yellow'
    case 'busy': return 'bg-vikinger-pink'
    default: return 'bg-gray-400'
  }
}
</script>

<template>
  <div class="space-y-4 sticky top-20">
    <!-- Stats Box -->
    <div class="stats-box">
      <div class="flex items-center justify-between mb-2">
        <h4 class="font-semibold text-sm">Stats Box</h4>
        <div class="flex gap-1">
          <button class="p-1 rounded hover:bg-white/20 transition-colors">
            <Icon icon="fluent:chevron-left-24-regular" class="w-4 h-4" />
          </button>
          <button class="p-1 rounded hover:bg-white/20 transition-colors">
            <Icon icon="fluent:chevron-right-24-regular" class="w-4 h-4" />
          </button>
        </div>
      </div>
      <div class="stats-number">{{ userStats.posts }}</div>
      <div class="flex items-center gap-2 mt-1">
        <span class="stats-trend">
          <Icon icon="fluent:arrow-up-24-filled" class="w-3 h-3 mr-1" />
          {{ userStats.postsGrowth }}%
        </span>
      </div>
      <div class="stats-label mt-2">Posts Created</div>
      <div class="text-xs opacity-60">In the last month</div>
    </div>

    <!-- Reactions Received -->
    <div class="vikinger-card">
      <div class="flex items-center justify-between mb-4">
        <h4 class="font-bold text-gray-800 dark:text-white text-sm">Reactions Received</h4>
      </div>
      <div class="grid grid-cols-2 gap-3">
        <div class="text-center p-2 rounded-vikinger bg-vikinger-light-200 dark:bg-vikinger-dark-200">
          <div class="w-8 h-8 rounded-full bg-vikinger-purple flex items-center justify-center mx-auto mb-1">
            <Icon icon="fluent:thumb-like-24-filled" class="w-4 h-4 text-white" />
          </div>
          <div class="text-lg font-bold text-gray-800 dark:text-white">{{ userStats.likes.toLocaleString() }}</div>
          <div class="text-xs text-gray-500 dark:text-gray-400">LIKES</div>
        </div>
        <div class="text-center p-2 rounded-vikinger bg-vikinger-light-200 dark:bg-vikinger-dark-200">
          <div class="w-8 h-8 rounded-full bg-vikinger-pink flex items-center justify-center mx-auto mb-1">
            <Icon icon="fluent:heart-24-filled" class="w-4 h-4 text-white" />
          </div>
          <div class="text-lg font-bold text-gray-800 dark:text-white">{{ userStats.loves.toLocaleString() }}</div>
          <div class="text-xs text-gray-500 dark:text-gray-400">LOVES</div>
        </div>
        <div class="text-center p-2 rounded-vikinger bg-vikinger-light-200 dark:bg-vikinger-dark-200">
          <div class="w-8 h-8 rounded-full bg-gray-400 flex items-center justify-center mx-auto mb-1">
            <Icon icon="fluent:thumb-dislike-24-filled" class="w-4 h-4 text-white" />
          </div>
          <div class="text-lg font-bold text-gray-800 dark:text-white">{{ userStats.dislikes.toLocaleString() }}</div>
          <div class="text-xs text-gray-500 dark:text-gray-400">DISLIKES</div>
        </div>
        <div class="text-center p-2 rounded-vikinger bg-vikinger-light-200 dark:bg-vikinger-dark-200">
          <div class="w-8 h-8 rounded-full bg-vikinger-yellow flex items-center justify-center mx-auto mb-1">
            <Icon icon="fluent:emoji-24-filled" class="w-4 h-4 text-vikinger-dark" />
          </div>
          <div class="text-lg font-bold text-gray-800 dark:text-white">{{ userStats.happy.toLocaleString() }}</div>
          <div class="text-xs text-gray-500 dark:text-gray-400">HAPPY</div>
        </div>
      </div>
    </div>

    <!-- Online Friends -->
    <div class="vikinger-card">
      <div class="flex items-center justify-between mb-4">
        <h4 class="font-bold text-gray-800 dark:text-white text-sm">
          <Icon icon="fluent:people-24-regular" class="w-4 h-4 inline mr-1 text-vikinger-cyan" />
          Friends Online
        </h4>
        <span class="badge badge-cyan">{{ onlineFriends.filter(f => f.status === 'online').length }}</span>
      </div>
      <div class="flex flex-wrap gap-2">
        <div 
          v-for="friend in onlineFriends" 
          :key="friend.id"
          class="relative group cursor-pointer"
          :title="friend.name"
        >
          <img 
            :src="friend.avatar" 
            :alt="friend.name"
            class="w-10 h-10 rounded-full border-2 border-white dark:border-vikinger-dark-100 hover:border-vikinger-cyan transition-colors"
          />
          <span 
            class="absolute bottom-0 right-0 w-3 h-3 rounded-full border-2 border-white dark:border-vikinger-dark-100"
            :class="getStatusColor(friend.status)"
          ></span>
        </div>
      </div>
      <NuxtLink to="/friends" class="block text-center text-xs text-vikinger-purple hover:text-vikinger-cyan mt-3 transition-colors">
        View All Friends →
      </NuxtLink>
    </div>

    <!-- Messages / Chat -->
    <div class="vikinger-card">
      <div class="flex items-center justify-between mb-4">
        <h4 class="font-bold text-vikinger-cyan text-sm">
          <Icon icon="fluent:chat-24-regular" class="w-4 h-4 inline mr-1" />
          Messages
        </h4>
        <span class="badge badge-purple">3</span>
      </div>
      <div class="space-y-3">
        <div 
          v-for="msg in messages" 
          :key="msg.id" 
          class="flex items-center gap-3 p-2 rounded-vikinger hover:bg-vikinger-light-300 dark:hover:bg-vikinger-dark-200 cursor-pointer transition-colors"
        >
          <div class="relative shrink-0">
            <img :src="msg.avatar" class="w-9 h-9 rounded-full" :alt="msg.user" />
            <span class="absolute bottom-0 right-0 w-2.5 h-2.5 bg-vikinger-green rounded-full border-2 border-white dark:border-vikinger-dark-100"></span>
          </div>
          <div class="flex-1 min-w-0">
            <div class="font-medium text-gray-800 dark:text-white text-sm truncate">{{ msg.user }}</div>
            <div class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ msg.text }}</div>
          </div>
          <div class="text-xs text-gray-400 shrink-0">{{ msg.time }}</div>
        </div>
      </div>
      <div class="mt-3 relative">
        <input 
          type="text" 
          placeholder="Search Messages..." 
          class="input-vikinger text-sm pr-10"
        />
        <Icon icon="fluent:search-24-regular" class="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
      </div>
    </div>

    <!-- Upcoming Events -->
    <div class="vikinger-card">
      <div class="flex items-center justify-between mb-4">
        <h4 class="font-bold text-gray-800 dark:text-white text-sm">
          <Icon icon="fluent:calendar-24-regular" class="w-4 h-4 inline mr-1 text-vikinger-purple" />
          Upcoming Events
        </h4>
      </div>
      <div class="space-y-3">
        <div 
          v-for="event in upcomingEvents" 
          :key="event.id"
          class="flex items-center gap-3 p-2 rounded-vikinger bg-vikinger-light-200 dark:bg-vikinger-dark-200"
        >
          <div class="w-10 h-10 rounded-vikinger bg-gradient-vikinger flex items-center justify-center shrink-0">
            <Icon :icon="event.icon" class="w-5 h-5 text-white" />
          </div>
          <div class="flex-1 min-w-0">
            <div class="font-medium text-gray-800 dark:text-white text-sm truncate">{{ event.title }}</div>
            <div class="text-xs text-vikinger-cyan">{{ event.date }}</div>
          </div>
        </div>
      </div>
      <NuxtLink to="/events" class="block text-center text-xs text-vikinger-purple hover:text-vikinger-cyan mt-3 transition-colors">
        View All Events →
      </NuxtLink>
    </div>

    <!-- Ad Banner -->
    <div class="vikinger-card bg-gradient-vikinger text-white text-center overflow-hidden relative">
      <div class="absolute top-0 right-0 w-20 h-20 bg-white/10 rounded-full -translate-y-1/2 translate-x-1/2"></div>
      <div class="absolute bottom-0 left-0 w-16 h-16 bg-white/10 rounded-full translate-y-1/2 -translate-x-1/2"></div>
      <div class="relative z-10">
        <Icon icon="fluent:rocket-24-filled" class="w-10 h-10 mx-auto mb-2" />
        <h4 class="font-bold">Upgrade to Pro!</h4>
        <p class="text-xs opacity-80 mt-1 mb-3">Unlock exclusive features and badges</p>
        <button class="px-4 py-2 bg-white text-vikinger-purple rounded-vikinger font-semibold text-sm hover:bg-vikinger-light-200 transition-colors">
          Learn More
        </button>
      </div>
    </div>
  </div>
</template>
