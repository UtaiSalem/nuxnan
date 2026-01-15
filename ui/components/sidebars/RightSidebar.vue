<script setup>
import { ref, computed, onMounted } from 'vue'
import { Icon } from '@iconify/vue'
import { useAuthStore } from '~/stores/auth'
import { useGamification } from '~/composables/useGamification'

const authStore = useAuthStore()
const { getPointsLeaderboard, isLoading: isGamificationLoading } = useGamification()

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

// Leaderboard data
const leaderboard = ref([])
const fetchLeaderboard = async () => {
  try {
    const data = await getPointsLeaderboard({ limit: 5 })
    leaderboard.value = data.leaderboard
  } catch (error) {
    console.error('Failed to fetch leaderboard:', error)
  }
}

onMounted(() => {
  fetchLeaderboard()
})

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
      <NuxtLink to="/play/friends" class="block text-center text-xs text-vikinger-purple hover:text-vikinger-cyan mt-3 transition-colors">
        View All Friends →
      </NuxtLink>
    </div>

    <!-- Top Learners (Leaderboard) -->
    <div class="vikinger-card">
      <div class="flex items-center justify-between mb-4">
        <h4 class="font-bold text-vikinger-purple text-sm">
          <Icon icon="fluent:trophy-24-filled" class="w-4 h-4 inline mr-1 text-vikinger-yellow" />
          Top Learners
        </h4>
        <span class="badge badge-purple">Rankings</span>
      </div>
      
      <!-- Loading State -->
      <div v-if="isGamificationLoading && !leaderboard.length" class="space-y-3 animate-pulse">
        <div v-for="i in 5" :key="i" class="flex items-center gap-3 p-2">
          <div class="w-9 h-9 bg-gray-200 dark:bg-vikinger-dark-200 rounded-full"></div>
          <div class="flex-1 space-y-2">
            <div class="h-3 bg-gray-200 dark:bg-vikinger-dark-200 rounded w-2/3"></div>
            <div class="h-2 bg-gray-200 dark:bg-vikinger-dark-200 rounded w-1/2"></div>
          </div>
        </div>
      </div>

      <div v-else class="space-y-3">
        <div 
          v-for="(user, index) in leaderboard" 
          :key="user.user_id" 
          class="flex items-center gap-3 p-2 rounded-vikinger hover:bg-vikinger-light-300 dark:hover:bg-vikinger-dark-200 cursor-pointer transition-colors group"
        >
          <div class="relative shrink-0">
            <img :src="user.profile_photo_url || '/images/default-avatar.png'" class="w-9 h-9 rounded-full border-2 border-transparent group-hover:border-vikinger-purple transition-colors" :alt="user.user_name" />
            <span 
              class="absolute -top-1 -left-1 w-5 h-5 rounded-full border-2 border-white dark:border-vikinger-dark-100 flex items-center justify-center text-[10px] font-bold text-white shadow-sm"
              :class="index === 0 ? 'bg-vikinger-yellow' : index === 1 ? 'bg-gray-300' : index === 2 ? 'bg-orange-400' : 'bg-vikinger-purple'"
            >
              {{ index + 1 }}
            </span>
          </div>
          <div class="flex-1 min-w-0">
            <div class="font-medium text-gray-800 dark:text-white text-sm truncate">{{ user.user_name }}</div>
            <div class="flex items-center gap-2 mt-0.5">
              <div class="flex items-center gap-1 text-[10px] font-bold text-vikinger-purple" title="แต้มสะสม (PP)">
                <img src="/storage/images/badge/completedq.png" class="w-3 h-3" alt="pp" />
                {{ Number(user.pp).toLocaleString() }}
              </div>
              <div class="flex items-center gap-1 text-[10px] font-bold text-vikinger-yellow" title="เงินในวอลเล็ต (Gold)">
                <img src="/storage/images/badge/goldc.png" class="w-3 h-3" alt="gold" />
                {{ Number(user.wallet).toLocaleString() }}
              </div>
            </div>
          </div>
          <div class="shrink-0 group-hover:scale-110 transition-transform">
            <Icon icon="fluent:star-24-filled" class="w-4 h-4 text-vikinger-yellow" />
          </div>
        </div>

        <!-- No data state -->
        <div v-if="!leaderboard.length" class="text-center py-4 text-gray-500 dark:text-gray-400 text-xs">
          ยังไม่มีข้อมูลลำดับ
        </div>
      </div>

      <div class="mt-3 relative">
        <input 
          type="text" 
          placeholder="ค้นหาสมาชิก..." 
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
