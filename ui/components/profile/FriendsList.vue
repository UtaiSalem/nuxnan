<script setup lang="ts">
import { Icon } from '@iconify/vue'
import BaseCard from '~/components/atoms/BaseCard.vue'
import CircleAvatar from '~/components/CircleAvatar.vue'
import type { Friend } from '~/composables/useFriends'

const props = defineProps<{
  userId?: string | number
  isOwnProfile?: boolean
}>()

const emit = defineEmits<{
  (e: 'friend-action', data: { action: string; userId: number }): void
}>()

const { 
  friends, 
  isLoading, 
  hasMore, 
  fetchFriends, 
  unfriend,
  loadMore,
  searchFriends 
} = useFriends()

const toast = useToast()

// State
const searchQuery = ref('')
const searchResults = ref<Friend[]>([])
const isSearching = ref(false)
const selectedFilter = ref<'all' | 'online' | 'recent'>('all')
const showConfirmModal = ref(false)
const friendToRemove = ref<Friend | null>(null)

// Computed
const displayedFriends = computed(() => {
  if (searchQuery.value) {
    return searchResults.value
  }
  
  let filtered = [...friends.value]
  
  if (selectedFilter.value === 'online') {
    filtered = filtered.filter(f => f.is_online)
  } else if (selectedFilter.value === 'recent') {
    filtered = filtered.slice(0, 12)
  }
  
  return filtered
})

const onlineFriendsCount = computed(() => friends.value.filter(f => f.is_online).length)

// Methods
const handleSearch = async () => {
  if (!searchQuery.value.trim()) {
    searchResults.value = []
    return
  }
  
  isSearching.value = true
  try {
    searchResults.value = await searchFriends(searchQuery.value)
  } finally {
    isSearching.value = false
  }
}

const debouncedSearch = useDebounceFn(handleSearch, 300)

const confirmUnfriend = (friend: Friend) => {
  friendToRemove.value = friend
  showConfirmModal.value = true
}

const handleUnfriend = async () => {
  if (!friendToRemove.value) return
  
  const success = await unfriend(friendToRemove.value.id)
  if (success) {
    toast.success(`เลิกเป็นเพื่อนกับ ${friendToRemove.value.name} แล้ว`)
    emit('friend-action', { action: 'unfriend', userId: friendToRemove.value.id })
  } else {
    toast.error('ไม่สามารถดำเนินการได้')
  }
  
  showConfirmModal.value = false
  friendToRemove.value = null
}

const goToProfile = (friend: Friend) => {
  navigateTo(`/profile/${friend.reference_code || friend.id}`)
}

const startChat = (friend: Friend) => {
  // TODO: Implement chat functionality
  navigateTo(`/messages/${friend.id}`)
}

// Initialize
onMounted(async () => {
  await fetchFriends(props.userId)
})

// Watch for search input
watch(searchQuery, () => {
  debouncedSearch()
})
</script>

<template>
  <div class="space-y-6">
    <!-- Header & Search -->
    <BaseCard class="bg-gray-800 border-gray-700 p-5">
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-4">
        <div>
          <h3 class="text-xl font-bold text-white flex items-center gap-2">
            <Icon icon="fluent:people-24-filled" class="w-6 h-6 text-vikinger-cyan" />
            เพื่อน
            <span class="text-vikinger-cyan font-normal ml-1">({{ friends.length }})</span>
          </h3>
          <p class="text-sm text-gray-400 mt-1">
            <span class="text-green-400">{{ onlineFriendsCount }}</span> คนกำลังออนไลน์
          </p>
        </div>
        
        <!-- Search Input -->
        <div class="relative w-full md:w-80">
          <Icon 
            icon="fluent:search-24-regular" 
            class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" 
          />
          <input
            v-model="searchQuery"
            type="text"
            placeholder="ค้นหาเพื่อน..."
            class="w-full pl-10 pr-4 py-2.5 bg-gray-700 border border-gray-600 rounded-lg text-white placeholder-gray-400 focus:ring-2 focus:ring-vikinger-purple focus:border-transparent"
          />
          <Icon 
            v-if="isSearching" 
            icon="fluent:spinner-ios-20-regular" 
            class="absolute right-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400 animate-spin" 
          />
        </div>
      </div>

      <!-- Filter Tabs -->
      <div class="flex gap-2 border-b border-gray-700 pb-3">
        <button
          v-for="filter in [
            { key: 'all', label: 'ทั้งหมด', icon: 'fluent:people-24-regular' },
            { key: 'online', label: 'ออนไลน์', icon: 'fluent:presence-available-24-filled' },
            { key: 'recent', label: 'เพิ่งเพิ่ม', icon: 'fluent:clock-24-regular' },
          ]"
          :key="filter.key"
          @click="selectedFilter = filter.key as typeof selectedFilter"
          :class="[
            'flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-all',
            selectedFilter === filter.key
              ? 'bg-vikinger-purple/20 text-vikinger-purple border border-vikinger-purple/30'
              : 'text-gray-400 hover:text-white hover:bg-gray-700'
          ]"
        >
          <Icon :icon="filter.icon" class="w-4 h-4" />
          {{ filter.label }}
        </button>
      </div>
    </BaseCard>

    <!-- Loading State -->
    <div v-if="isLoading" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
      <div v-for="i in 6" :key="i" class="animate-pulse">
        <BaseCard class="bg-gray-800 border-gray-700 p-4">
          <div class="flex items-center gap-4">
            <div class="w-16 h-16 rounded-full bg-gray-700"></div>
            <div class="flex-1 space-y-2">
              <div class="h-4 bg-gray-700 rounded w-3/4"></div>
              <div class="h-3 bg-gray-700 rounded w-1/2"></div>
            </div>
          </div>
        </BaseCard>
      </div>
    </div>

    <!-- Friends Grid -->
    <div v-else-if="displayedFriends.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
      <BaseCard 
        v-for="friend in displayedFriends" 
        :key="friend.id"
        class="bg-gray-800 border-gray-700 overflow-hidden hover:border-vikinger-purple/50 transition-all group"
      >
        <!-- Card Content -->
        <div class="p-4">
          <div class="flex items-start gap-4">
            <!-- Avatar -->
            <div class="relative cursor-pointer" @click="goToProfile(friend)">
              <CircleAvatar
                :src="friend.avatar || '/images/default-avatar.png'"
                :alt="friend.name"
                size="lg"
                :border-width="2"
                :border-color="friend.is_online ? '#22c55e' : '#6b7280'"
                :show-online-status="true"
                :is-online="friend.is_online"
              />
              <!-- Level Badge -->
              <div class="absolute -bottom-1 -right-1 w-6 h-6 rounded-full bg-gradient-to-r from-vikinger-purple to-vikinger-cyan flex items-center justify-center text-xs font-bold text-white shadow-lg">
                {{ friend.level || 1 }}
              </div>
            </div>
            
            <!-- Info -->
            <div class="flex-1 min-w-0">
              <h4 
                class="font-semibold text-white truncate hover:text-vikinger-cyan cursor-pointer transition-colors"
                @click="goToProfile(friend)"
              >
                {{ friend.name || friend.full_name }}
              </h4>
              <p class="text-sm text-gray-400 truncate">@{{ friend.username }}</p>
              
              <!-- Mutual Friends -->
              <p v-if="friend.mutual_friends_count" class="text-xs text-gray-500 mt-1">
                <Icon icon="fluent:people-16-regular" class="w-3 h-3 inline mr-1" />
                {{ friend.mutual_friends_count }} เพื่อนร่วม
              </p>
              
              <!-- Online Status -->
              <div class="flex items-center gap-1 mt-2">
                <span 
                  :class="[
                    'w-2 h-2 rounded-full',
                    friend.is_online ? 'bg-green-500' : 'bg-gray-500'
                  ]"
                ></span>
                <span class="text-xs" :class="friend.is_online ? 'text-green-400' : 'text-gray-500'">
                  {{ friend.is_online ? 'ออนไลน์' : 'ออฟไลน์' }}
                </span>
              </div>
            </div>

            <!-- Actions Menu -->
            <div class="relative">
              <div class="dropdown dropdown-end">
                <label tabindex="0" class="btn btn-ghost btn-sm btn-circle">
                  <Icon icon="fluent:more-vertical-24-regular" class="w-5 h-5 text-gray-400" />
                </label>
                <ul tabindex="0" class="dropdown-content z-20 menu p-2 shadow-lg bg-gray-900 border border-gray-700 rounded-xl w-48">
                  <li>
                    <a @click="goToProfile(friend)" class="flex items-center gap-2 text-gray-300 hover:text-white hover:bg-gray-700">
                      <Icon icon="fluent:person-24-regular" class="w-4 h-4" />
                      ดูโปรไฟล์
                    </a>
                  </li>
                  <li>
                    <a @click="startChat(friend)" class="flex items-center gap-2 text-gray-300 hover:text-white hover:bg-gray-700">
                      <Icon icon="fluent:chat-24-regular" class="w-4 h-4" />
                      ส่งข้อความ
                    </a>
                  </li>
                  <li v-if="isOwnProfile">
                    <a @click="confirmUnfriend(friend)" class="flex items-center gap-2 text-red-400 hover:text-red-300 hover:bg-red-900/30">
                      <Icon icon="fluent:person-delete-24-regular" class="w-4 h-4" />
                      เลิกเป็นเพื่อน
                    </a>
                  </li>
                </ul>
              </div>
            </div>
          </div>
        </div>

        <!-- Quick Actions -->
        <div class="flex border-t border-gray-700">
          <button 
            @click="startChat(friend)"
            class="flex-1 py-3 flex items-center justify-center gap-2 text-gray-400 hover:text-vikinger-cyan hover:bg-gray-700/50 transition-colors"
          >
            <Icon icon="fluent:chat-24-regular" class="w-4 h-4" />
            <span class="text-sm">ข้อความ</span>
          </button>
          <button 
            @click="goToProfile(friend)"
            class="flex-1 py-3 flex items-center justify-center gap-2 text-gray-400 hover:text-vikinger-purple hover:bg-gray-700/50 transition-colors border-l border-gray-700"
          >
            <Icon icon="fluent:arrow-right-24-regular" class="w-4 h-4" />
            <span class="text-sm">โปรไฟล์</span>
          </button>
        </div>
      </BaseCard>
    </div>

    <!-- Empty State -->
    <BaseCard v-else class="bg-gray-800 border-gray-700 text-center py-12">
      <Icon 
        :icon="searchQuery ? 'fluent:search-24-regular' : 'fluent:people-24-regular'" 
        class="w-16 h-16 text-gray-600 mx-auto mb-4" 
      />
      <p class="text-gray-400">
        {{ searchQuery ? 'ไม่พบเพื่อนที่ค้นหา' : 'ยังไม่มีเพื่อน' }}
      </p>
      <p v-if="!searchQuery && isOwnProfile" class="text-sm text-gray-500 mt-2">
        เริ่มเพิ่มเพื่อนเพื่อเชื่อมต่อกับคนอื่นๆ!
      </p>
      <NuxtLink 
        v-if="!searchQuery && isOwnProfile" 
        to="/members"
        class="inline-flex items-center gap-2 mt-4 px-4 py-2 bg-vikinger-purple text-white rounded-lg hover:bg-vikinger-purple/80 transition-colors"
      >
        <Icon icon="fluent:person-add-24-regular" class="w-5 h-5" />
        ค้นหาเพื่อน
      </NuxtLink>
    </BaseCard>

    <!-- Load More Button -->
    <div v-if="hasMore && !searchQuery" class="text-center">
      <button
        @click="loadMore(props.userId)"
        :disabled="isLoading"
        class="px-6 py-3 bg-gray-700 text-white rounded-lg hover:bg-gray-600 transition-colors disabled:opacity-50 flex items-center gap-2 mx-auto"
      >
        <Icon v-if="isLoading" icon="fluent:spinner-ios-20-regular" class="w-5 h-5 animate-spin" />
        <span>{{ isLoading ? 'กำลังโหลด...' : 'โหลดเพิ่มเติม' }}</span>
      </button>
    </div>

    <!-- Confirm Unfriend Modal -->
    <Teleport to="body">
      <Transition name="modal">
        <div 
          v-if="showConfirmModal"
          class="fixed inset-0 z-50 flex items-center justify-center p-4"
        >
          <div class="absolute inset-0 bg-black/70" @click="showConfirmModal = false" />
          <div class="relative bg-gray-800 rounded-xl shadow-2xl w-full max-w-md border border-gray-700">
            <div class="p-6 text-center">
              <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-red-500/20 flex items-center justify-center">
                <Icon icon="fluent:person-delete-24-filled" class="w-8 h-8 text-red-500" />
              </div>
              <h3 class="text-xl font-bold text-white mb-2">เลิกเป็นเพื่อน?</h3>
              <p class="text-gray-400 mb-6">
                คุณแน่ใจหรือไม่ว่าต้องการเลิกเป็นเพื่อนกับ 
                <span class="text-white font-medium">{{ friendToRemove?.name }}</span>?
              </p>
              <div class="flex gap-3 justify-center">
                <button
                  @click="showConfirmModal = false"
                  class="px-6 py-2.5 text-gray-400 hover:text-white bg-gray-700 hover:bg-gray-600 rounded-lg transition-colors"
                >
                  ยกเลิก
                </button>
                <button
                  @click="handleUnfriend"
                  class="px-6 py-2.5 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors"
                >
                  เลิกเป็นเพื่อน
                </button>
              </div>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<style scoped>
.modal-enter-active,
.modal-leave-active {
  transition: all 0.3s ease;
}

.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}
</style>
